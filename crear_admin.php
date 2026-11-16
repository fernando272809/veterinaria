<?php
require_once 'conexion.php';
$n=(int)$conexion->query("SELECT COUNT(*) FROM usuario WHERE rol='administrador'")->fetchColumn(); $msg='';
if(!$n && $_SERVER['REQUEST_METHOD']==='POST'){
 $nombre=trim($_POST['nombre']??''); $usuario=trim($_POST['usuario']??''); $pass=$_POST['password']??'';
 if(!$nombre||!$usuario||strlen($pass)<8) $msg='Completa los campos. La contraseña debe tener al menos 8 caracteres.';
 else {try{$q=$conexion->prepare("INSERT INTO usuario(nombre,usuario,password,rol) VALUES(?,?,?,'administrador')");$q->execute([$nombre,$usuario,password_hash($pass,PASSWORD_DEFAULT)]);$msg='Administrador creado. Ya puedes iniciar sesión. Elimina crear_admin.php por seguridad.';$n=1;}catch(PDOException $e){$msg='No se pudo crear. Verifica que el usuario no esté repetido.';}}
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Crear administrador</title><link rel="stylesheet" href="estilos.css"></head><body class="login-body"><main class="login-card"><div class="logo-circle">🔐</div><h1>Primer administrador</h1><?php if($msg):?><div class="alert"><?=htmlspecialchars($msg)?></div><?php endif;?><?php if($n):?><p>Ya existe un administrador. <a href="login.php">Ir a iniciar sesión</a>.</p><?php else:?><form method="post"><label>Nombre completo</label><input name="nombre" required><label>Usuario</label><input name="usuario" required><label>Contraseña (mínimo 8 caracteres)</label><input type="password" name="password" minlength="8" required><button class="btn full">Crear administrador</button></form><?php endif;?></main></body></html>
