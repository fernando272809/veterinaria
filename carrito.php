<?php
require_once "auth.php";
requiereSesion();
if (!isset($_SESSION["carrito"])) $_SESSION["carrito"] = [];
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";
    if ($accion === "agregar") {
        $id = (int)($_POST["idProducto"] ?? 0);
        $cantidad = max(1, (int)($_POST["cantidad"] ?? 1));
        $stmt = $conexion->prepare("SELECT stock,nombre FROM productos WHERE idProducto=?");
        $stmt->execute([$id]); $prod = $stmt->fetch();
        if (!$prod) $mensaje = "El producto ya no está disponible.";
        elseif ((int)$prod["stock"] < $cantidad + (int)($_SESSION["carrito"][$id] ?? 0)) $mensaje = "No hay suficientes existencias de ".$prod["nombre"].".";
        else { $_SESSION["carrito"][$id] = $cantidad + (int)($_SESSION["carrito"][$id] ?? 0); $mensaje = "Producto agregado al carrito."; }
    } elseif ($accion === "actualizar") {
        foreach (($_POST["cantidades"] ?? []) as $id => $cantidad) {
            $id = (int)$id; $cantidad = (int)$cantidad;
            if ($cantidad <= 0) { unset($_SESSION["carrito"][$id]); continue; }
            $stmt = $conexion->prepare("SELECT stock FROM productos WHERE idProducto=?");
            $stmt->execute([$id]); $stock = $stmt->fetchColumn();
            if ($stock !== false) $_SESSION["carrito"][$id] = min($cantidad, (int)$stock);
        }
        $mensaje = "Carrito actualizado.";
    } elseif ($accion === "vaciar") {
        $_SESSION["carrito"] = []; $mensaje = "Se vació el carrito.";
    } elseif ($accion === "comprar") {
        if (empty($_SESSION["carrito"])) $mensaje = "Tu carrito está vacío.";
        else {
            try {
                $conexion->beginTransaction();
                $total = 0; $items = [];
                $consulta = $conexion->prepare("SELECT idProducto,nombre,precio,stock FROM productos WHERE idProducto=? FOR UPDATE");
                foreach ($_SESSION["carrito"] as $id => $cantidad) {
                    $consulta->execute([(int)$id]); $prod = $consulta->fetch();
                    if (!$prod || (int)$prod["stock"] < (int)$cantidad) throw new RuntimeException("No hay existencias suficientes para completar la compra. Actualiza tu carrito.");
                    $precio = (float)$prod["precio"];
                    $total += $precio * (int)$cantidad;
                    $items[] = ["id"=>(int)$id,"cantidad"=>(int)$cantidad,"precio"=>$precio];
                }
                $stmt = $conexion->prepare("INSERT INTO ventas (idUsuario,total) VALUES (?,?)");
                $stmt->execute([(int)$_SESSION["idUsuario"],$total]);
                $idVenta = (int)$conexion->lastInsertId();
                $detalle = $conexion->prepare("INSERT INTO detalle_venta (idVenta,idProducto,cantidad,precio) VALUES (?,?,?,?)");
                $descontar = $conexion->prepare("UPDATE productos SET stock=stock-? WHERE idProducto=?");
                foreach ($items as $item) {
                    $detalle->execute([$idVenta,$item["id"],$item["cantidad"],$item["precio"]]);
                    $descontar->execute([$item["cantidad"],$item["id"]]);
                }
                $conexion->commit();
                $_SESSION["carrito"] = [];
                $_SESSION["ultima_compra"] = $idVenta;
                header("Location: mis_compras.php?compra=".$idVenta);
                exit;
            } catch (Throwable $e) {
                if ($conexion->inTransaction()) $conexion->rollBack();
                $mensaje = $e instanceof RuntimeException ? $e->getMessage() : "No se pudo procesar la compra. Revisa que las tablas de ventas estén creadas.";
            }
        }
    }
}

$items = []; $total = 0;
if (!empty($_SESSION["carrito"])) {
    $ids = array_map("intval", array_keys($_SESSION["carrito"]));
    $placeholders = implode(",", array_fill(0, count($ids), "?"));
    $stmt = $conexion->prepare("SELECT * FROM productos WHERE idProducto IN ($placeholders)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $p) {
        $cantidad = (int)$_SESSION["carrito"][$p["idProducto"]];
        $subtotal = (float)$p["precio"] * $cantidad; $total += $subtotal;
        $items[] = ["producto"=>$p,"cantidad"=>$cantidad,"subtotal"=>$subtotal];
    }
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Mi carrito | Veterinaria</title><link rel="stylesheet" href="estilos.css"></head><body><div class="layout">
<aside class="sidebar"><div class="brand"><span>🐾</span><div><b>Veterinaria</b><small>Panel de gestión</small></div></div><nav><a href="panel.php">⌂ &nbsp; Inicio</a><a href="productos.php">🛍 &nbsp; Productos</a><a class="active" href="carrito.php">🛒 &nbsp; Mi carrito</a><a href="mis_compras.php">📦 &nbsp; Mis compras</a><a href="logout.php">↪ &nbsp; Cerrar sesión</a></nav></aside>
<main class="main"><header class="topbar"><div><span class="eyebrow">TU PEDIDO</span><h1>Mi carrito</h1></div><a class="btn secondary" href="productos.php">Seguir comprando</a></header>
<?php if($mensaje): ?><div class="alert"><?= escapar($mensaje) ?></div><?php endif; ?>
<section class="table-card"><h2>Productos seleccionados</h2>
<?php if($items): ?><form method="POST"><input type="hidden" name="accion" value="actualizar"><div class="table-wrap"><table><thead><tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th></tr></thead><tbody>
<?php foreach($items as $item): $pr=$item["producto"]; ?><tr><td><?= escapar($pr["nombre"]) ?></td><td>$<?= number_format((float)$pr["precio"],2) ?></td><td><input class="quantity-input" type="number" min="0" max="<?= (int)$pr["stock"] ?>" name="cantidades[<?= (int)$pr["idProducto"] ?>]" value="<?= $item["cantidad"] ?>"></td><td>$<?= number_format($item["subtotal"],2) ?></td></tr><?php endforeach; ?>
</tbody></table></div><button class="btn secondary">Actualizar carrito</button></form>
<div class="cart-summary"><form method="POST"><input type="hidden" name="accion" value="vaciar"><button class="link-danger">Vaciar carrito</button></form><div><span>Total:</span><strong>$<?= number_format($total,2) ?></strong><form method="POST" onsubmit="return confirm('¿Confirmas tu compra? Se descontará el stock disponible.')"><input type="hidden" name="accion" value="comprar"><button class="btn">Confirmar compra</button></form></div></div>
<?php else: ?><div class="empty-state"><span>🛒</span><h3>Tu carrito está vacío</h3><p>Explora nuestra tienda y agrega productos para tus mascotas.</p><a class="btn" href="productos.php">Ver productos</a></div><?php endif; ?>
</section><footer>© <?= date("Y") ?> Veterinaria</footer></main></div></body></html>
