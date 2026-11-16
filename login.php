<?php
session_start(); require_once 'conexion.php'; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $u=trim($_POST['usuario']??'');$p=$_POST['password']??'';
 $q=$conexion->prepare('SELECT idUsuario,nombre,usuario,password,rol FROM usuario WHERE usuario=?');$q->execute([$u]);$r=$q->fetch();
 if($r && password_verify($p,$r['password'])){session_regenerate_id(true);$_SESSION['idUsuario']=$r['idUsuario'];$_SESSION['nombre']=$r['nombre'];$_SESSION['usuario']=$r['usuario'];$_SESSION['rol']=$r['rol'];header('Location: panel.php');exit;} $error='Usuario o contraseña incorrectos.';
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Iniciar sesión | Veterinaria</title><link rel="stylesheet" href="estilos.css"></head><body class="login-body"><main class="login-card"><div class="logo-circle">🐾</div><h1>Veterinaria</h1><p class="muted">Inicia sesión para continuar</p><?php if(isset($_GET['registro'])): ?><div class="alert">Cuenta creada. Ya puedes iniciar sesión.</div><?php endif; ?><?php if($error):?><div class="alert error"><?=htmlspecialchars($error)?></div><?php endif;?><form method="post"><label>Usuario</label><input name="usuario" required><label>Contraseña</label><input type="password" name="password" required><button class="btn full">Entrar</button></form><a class="small-link" href="registro_cliente.php">Crear cuenta de cliente</a><br><a class="small-link" href="crear_admin.php">Configurar primer administrador</a></main></body></html>
