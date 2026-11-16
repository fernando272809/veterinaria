<?php
require_once "auth.php";
requiereSesion();
$stmt = $conexion->prepare("SELECT idVenta,fecha,total FROM ventas WHERE idUsuario=? ORDER BY idVenta DESC");
$stmt->execute([(int)$_SESSION["idUsuario"]]); $compras = $stmt->fetchAll();
$detalleCompra = null;
if (isset($_GET["compra"])) {
    $stmt = $conexion->prepare("SELECT v.idVenta,v.fecha,v.total FROM ventas v WHERE v.idVenta=? AND v.idUsuario=?");
    $stmt->execute([(int)$_GET["compra"],(int)$_SESSION["idUsuario"]]); $detalleCompra = $stmt->fetch();
    if ($detalleCompra) {
        $stmt = $conexion->prepare("SELECT p.nombre,d.cantidad,d.precio,(d.cantidad*d.precio) subtotal FROM detalle_venta d JOIN productos p ON p.idProducto=d.idProducto WHERE d.idVenta=?");
        $stmt->execute([$detalleCompra["idVenta"]]); $detalle = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Mis compras | Veterinaria</title><link rel="stylesheet" href="estilos.css"></head><body><div class="layout">
<aside class="sidebar"><div class="brand"><span>🐾</span><div><b>Veterinaria</b><small>Panel de gestión</small></div></div><nav><a href="panel.php">⌂ &nbsp; Inicio</a><a href="productos.php">🛍 &nbsp; Productos</a><a href="carrito.php">🛒 &nbsp; Mi carrito</a><a class="active" href="mis_compras.php">📦 &nbsp; Mis compras</a><a href="logout.php">↪ &nbsp; Cerrar sesión</a></nav></aside>
<main class="main"><header class="topbar"><div><span class="eyebrow">HISTORIAL</span><h1>Mis compras</h1></div><a class="btn secondary" href="productos.php">Ir a la tienda</a></header>
<?php if($detalleCompra): ?><section class="table-card"><h2>Compra #<?= (int)$detalleCompra["idVenta"] ?></h2><p class="muted">Fecha: <?= escapar($detalleCompra["fecha"]) ?></p><div class="table-wrap"><table><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr></thead><tbody><?php foreach($detalle as $d): ?><tr><td><?= escapar($d["nombre"]) ?></td><td><?= (int)$d["cantidad"] ?></td><td>$<?= number_format((float)$d["precio"],2) ?></td><td>$<?= number_format((float)$d["subtotal"],2) ?></td></tr><?php endforeach; ?></tbody></table></div><h2 class="history-total">Total: $<?= number_format((float)$detalleCompra["total"],2) ?></h2><a href="mis_compras.php">Volver al historial</a></section><?php else: ?>
<section class="table-card"><h2>Historial de pedidos</h2><div class="table-wrap"><table><thead><tr><th>Pedido</th><th>Fecha</th><th>Total</th><th>Detalle</th></tr></thead><tbody><?php foreach($compras as $c): ?><tr><td>#<?= (int)$c["idVenta"] ?></td><td><?= escapar($c["fecha"]) ?></td><td>$<?= number_format((float)$c["total"],2) ?></td><td><a class="table-link" href="mis_compras.php?compra=<?= (int)$c["idVenta"] ?>">Ver compra</a></td></tr><?php endforeach; ?><?php if(!$compras): ?><tr><td colspan="4">Todavía no tienes compras registradas.</td></tr><?php endif; ?></tbody></table></div></section><?php endif; ?>
<footer>© <?= date("Y") ?> Veterinaria</footer></main></div></body></html>
