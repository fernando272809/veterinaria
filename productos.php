<?php
require_once __DIR__ . '/auth.php';
requiereSesion();

$esAdmin = (($_SESSION['rol'] ?? '') === 'administrador');
$categorias = ['Medicamentos', 'Alimento', 'Juguetes', 'Limpieza', 'Accesorios'];
$mensaje = $_SESSION['mensaje_productos'] ?? '';
unset($_SESSION['mensaje_productos']);

function iconoCategoriaProducto($categoria) {
    switch ($categoria) {
        case 'Medicamentos': return '💊';
        case 'Alimento': return '🥣';
        case 'Juguetes': return '🧸';
        case 'Limpieza': return '🧴';
        case 'Accesorios': return '🦴';
        default: return '🐾';
    }
}

function guardarImagenProducto($archivo, $imagenAnterior = '') {
    if (!isset($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $imagenAnterior;
    }
    if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir la fotografía. Intenta nuevamente.');
    }
    if (($archivo['size'] ?? 0) > 3 * 1024 * 1024) {
        throw new RuntimeException('La fotografía no debe superar 3 MB.');
    }
    $tmp = $archivo['tmp_name'] ?? '';
    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp);
        finfo_close($finfo);
    }
    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($permitidos[$mime])) {
        throw new RuntimeException('Formato no válido. Usa JPG, PNG, WEBP o GIF.');
    }
    $nombre = bin2hex(random_bytes(12)) . '.' . $permitidos[$mime];
    $directorio = __DIR__ . '/uploads/productos/';
    if (!is_dir($directorio) && !mkdir($directorio, 0755, true)) {
        throw new RuntimeException('No se pudo crear la carpeta para fotografías.');
    }
    if (!move_uploaded_file($tmp, $directorio . $nombre)) {
        throw new RuntimeException('No se pudo guardar la fotografía en el servidor.');
    }
    if ($imagenAnterior !== '' && strpos($imagenAnterior, 'uploads/productos/') === 0) {
        $vieja = __DIR__ . '/' . $imagenAnterior;
        if (is_file($vieja)) { @unlink($vieja); }
    }
    return 'uploads/productos/' . $nombre;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $esAdmin) {
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'guardar') {
            $id = (int)($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $precio = (float)($_POST['precio'] ?? 0);
            $stock = (int)($_POST['stock'] ?? -1);
            $descripcion = trim($_POST['descripcion'] ?? '');
            $categoria = $_POST['categoria'] ?? '';
            if ($nombre === '' || mb_strlen($nombre) > 50 || $precio <= 0 || $stock < 0 ||
                !in_array($categoria, $categorias, true) || $descripcion === '' || mb_strlen($descripcion) > 250) {
                throw new RuntimeException('Revisa los datos. El nombre admite 50 caracteres, la descripción 250, el precio debe ser mayor a cero y las existencias no pueden ser negativas.');
            }

            $imagenAnterior = '';
            if ($id > 0) {
                $q = $conexion->prepare('SELECT imagen FROM productos WHERE idProducto = ?');
                $q->execute([$id]);
                $registro = $q->fetch();
                if (!$registro) { throw new RuntimeException('El producto que intentas editar ya no existe.'); }
                $imagenAnterior = $registro['imagen'] ?? '';
            }
            $imagen = guardarImagenProducto($_FILES['imagen'] ?? null, $imagenAnterior);

            if ($id > 0) {
                $q = $conexion->prepare('UPDATE productos SET nombre=?, precio=?, stock=?, descripcion=?, imagen=?, categoria=? WHERE idProducto=?');
                $q->execute([$nombre, $precio, $stock, $descripcion, $imagen, $categoria, $id]);
                $_SESSION['mensaje_productos'] = 'Producto actualizado correctamente.';
            } else {
                $q = $conexion->prepare('INSERT INTO productos (nombre, precio, stock, descripcion, imagen, categoria) VALUES (?,?,?,?,?,?)');
                $q->execute([$nombre, $precio, $stock, $descripcion, $imagen, $categoria]);
                $_SESSION['mensaje_productos'] = 'Producto agregado a la tienda.';
            }
            ir('productos.php');
        } elseif ($accion === 'eliminar') {
            $id = (int)($_POST['id'] ?? 0);
            $q = $conexion->prepare('SELECT imagen FROM productos WHERE idProducto=?');
            $q->execute([$id]);
            $registro = $q->fetch();
            if (!$registro) { throw new RuntimeException('El producto ya no existe.'); }
            try {
                $del = $conexion->prepare('DELETE FROM productos WHERE idProducto=?');
                $del->execute([$id]);
            } catch (PDOException $ex) {
                throw new RuntimeException('No se puede eliminar porque el producto ya aparece en una compra. Puedes dejar sus existencias en cero.');
            }
            if (!empty($registro['imagen']) && strpos($registro['imagen'], 'uploads/productos/') === 0) {
                $ruta = __DIR__ . '/' . $registro['imagen'];
                if (is_file($ruta)) { @unlink($ruta); }
            }
            $_SESSION['mensaje_productos'] = 'Producto eliminado.';
            ir('productos.php');
        }
    } catch (Throwable $ex) {
        $mensaje = $ex->getMessage();
    }
}

$editar = null;
if ($esAdmin && isset($_GET['editar'])) {
    $q = $conexion->prepare('SELECT * FROM productos WHERE idProducto=?');
    $q->execute([(int)$_GET['editar']]);
    $editar = $q->fetch() ?: null;
}

$categoriaFiltro = trim($_GET['categoria'] ?? '');
$busqueda = trim($_GET['buscar'] ?? '');
$sql = 'SELECT * FROM productos WHERE 1=1';
$params = [];
if ($categoriaFiltro !== '' && in_array($categoriaFiltro, $categorias, true)) {
    $sql .= ' AND categoria=?'; $params[] = $categoriaFiltro;
}
if ($busqueda !== '') {
    $sql .= ' AND (nombre LIKE ? OR descripcion LIKE ?)';
    $params[] = '%' . $busqueda . '%'; $params[] = '%' . $busqueda . '%';
}
$sql .= ' ORDER BY nombre';
$q = $conexion->prepare($sql);
$q->execute($params);
$productos = $q->fetchAll();

encabezado('Tienda y productos');
?>
<section class="store-hero">
  <div><span class="eyebrow light">VETCARE · TIENDA</span><h2>Todo para su bienestar 🐾</h2><p>Alimentos, accesorios, higiene y productos para tus mascotas.</p></div>
</section>
<?php alerta($mensaje); ?>

<?php if ($esAdmin): ?>
<section class="form-card product-admin-form" id="formulario-producto">
  <div class="product-form-heading">
    <div><span class="eyebrow">INVENTARIO</span><h2><?= $editar ? 'Editar producto' : 'Agregar nuevo producto' ?></h2>
    <p>Registra el producto y aparecerá automáticamente en la tienda de los clientes.</p></div>
    <?php if ($editar): ?><a class="btn secondary" href="productos.php">Cancelar edición</a><?php endif; ?>
  </div>
  <form method="POST" enctype="multipart/form-data" class="product-form">
    <input type="hidden" name="accion" value="guardar">
    <input type="hidden" name="id" value="<?= e($editar['idProducto'] ?? 0) ?>">
    <div><label for="nombre">Nombre del producto</label><input id="nombre" name="nombre" maxlength="50" required value="<?= e($editar['nombre'] ?? '') ?>" placeholder="Ej. Croquetas para cachorro"></div>
    <div><label for="precio">Precio (MXN)</label><input id="precio" type="number" name="precio" min="0.01" step="0.01" required value="<?= e($editar['precio'] ?? '') ?>" placeholder="250.00"></div>
    <div><label for="stock">Existencias</label><input id="stock" type="number" name="stock" min="0" step="1" required value="<?= e($editar['stock'] ?? 0) ?>"></div>
    <div><label for="categoria">Categoría</label><select id="categoria" name="categoria" required><?php foreach ($categorias as $cat): ?><option value="<?= e($cat) ?>" <?= (($editar['categoria'] ?? 'Alimento') === $cat) ? 'selected' : '' ?>><?= e($cat) ?></option><?php endforeach; ?></select></div>
    <div class="wide"><label for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" maxlength="250" required placeholder="Describe brevemente el producto"><?= e($editar['descripcion'] ?? '') ?></textarea></div>
    <div class="wide"><label for="imagen">Fotografía del producto (JPG, PNG, WEBP o GIF; máximo 3 MB)</label><input id="imagen" type="file" name="imagen" accept="image/jpeg,image/png,image/webp,image/gif"><small>Si estás editando y no eliges otra fotografía, se conservará la actual.</small>
    <?php if (!empty($editar['imagen'])): ?><p class="current-product-image">Fotografía actual: <img src="<?= e($editar['imagen']) ?>" alt="Imagen actual"></p><?php endif; ?></div>
    <div class="wide form-actions"><button class="btn" type="submit"><?= $editar ? 'Guardar cambios' : '＋ Agregar producto' ?></button></div>
  </form>
</section>
<?php endif; ?>

<form class="filter-bar" method="GET">
  <input name="buscar" placeholder="Buscar producto..." value="<?= e($busqueda) ?>">
  <select name="categoria"><option value="">Todas las categorías</option><?php foreach ($categorias as $cat): ?><option value="<?= e($cat) ?>" <?= $categoriaFiltro === $cat ? 'selected' : '' ?>><?= e($cat) ?></option><?php endforeach; ?></select>
  <button class="btn" type="submit">Buscar</button><a class="btn secondary" href="productos.php">Limpiar</a>
</form>
<div class="section-head"><div><span class="eyebrow">CATÁLOGO</span><h2><?= count($productos) ?> productos encontrados</h2></div></div>
<div class="product-grid">
<?php foreach ($productos as $prod): ?>
  <article class="product-card">
    <div class="product-picture">
      <?php if (!empty($prod['imagen'])): ?><img src="<?= e($prod['imagen']) ?>" alt="<?= e($prod['nombre']) ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='grid'"><span class="product-emoji" style="display:none"><?= iconoCategoriaProducto($prod['categoria']) ?></span>
      <?php else: ?><span class="product-emoji"><?= iconoCategoriaProducto($prod['categoria']) ?></span><?php endif; ?>
      <span class="category-tag"><?= e($prod['categoria']) ?></span>
    </div>
    <div class="product-info"><h3><?= e($prod['nombre']) ?></h3><p><?= e($prod['descripcion']) ?></p>
      <div class="product-bottom"><strong>$<?= number_format((float)$prod['precio'], 2) ?></strong><small><?= (int)$prod['stock'] > 0 ? 'Existencias: ' . (int)$prod['stock'] : 'Agotado' ?></small></div>
      <?php if ((int)$prod['stock'] > 0): ?><form method="POST" action="carrito.php" class="add-cart"><input type="hidden" name="accion" value="agregar"><input type="hidden" name="idProducto" value="<?= (int)$prod['idProducto'] ?>"><input type="number" name="cantidad" min="1" max="<?= (int)$prod['stock'] ?>" value="1"><button class="btn" type="submit">Agregar al carrito</button></form><?php else: ?><button class="btn" disabled>Sin existencias</button><?php endif; ?>
      <?php if ($esAdmin): ?><div class="admin-product-actions"><a href="productos.php?editar=<?= (int)$prod['idProducto'] ?>#formulario-producto">Editar</a><form method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este producto?')"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$prod['idProducto'] ?>"><button class="link-danger" type="submit">Eliminar</button></form></div><?php endif; ?>
    </div>
  </article>
<?php endforeach; ?>
<?php if (!$productos): ?><p class="empty-state">No hay productos que coincidan con tu búsqueda.</p><?php endif; ?>
</div>
<?php pie(); ?>
