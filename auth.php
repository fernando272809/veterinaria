<?php
if(session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__.'/conexion.php';
function requiereSesion(){if(empty($_SESSION['idUsuario'])){header('Location: login.php');exit;}}
function requiereRol(...$roles){requiereSesion();if(!in_array($_SESSION['rol']??'', $roles,true)){http_response_code(403);exit('No tienes permiso para acceder a esta sección.');}}
function requiereAdmin(){requiereRol('administrador');}
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
if (!function_exists('escapar')) {
    function escapar($valor) {
        return e($valor);
    }
}
function ir($url){header('Location: '.$url);exit;}
function encabezado($titulo){$rol=$_SESSION['rol']??'cliente';$links=['administrador'=>[['admin_panel.php','Inicio'],['usuarios.php','Usuarios y empleados'],['clientes.php','Clientes'],['mascotas.php','Mascotas'],['citas_admin.php','Todas las citas'],['productos.php','Inventario'],['compras_admin.php','Compras']], 'empleado'=>[['empleado_panel.php','Inicio'],['citas_empleado.php','Solicitudes de citas'],['mascotas.php','Mascotas']], 'cliente'=>[['cliente.php','Inicio'],['mis_mascotas.php','Mis mascotas'],['solicitar_cita.php','Solicitar cita'],['citas_cliente.php','Mis citas'],['productos.php','Tienda'],['carrito.php','Carrito'],['mis_compras.php','Mis compras']]]; echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($titulo).'</title><link rel="stylesheet" href="estilos.css"><link rel="stylesheet" href="sistema.css"></head><body><div class="layout"><aside class="sidebar"><div class="brand"><span>🐾</span><div><b>VetCare</b><small>Sistema veterinario</small></div></div><nav>';
foreach($links[$rol]??[] as $l) echo '<a href="'.e($l[0]).'">✦ &nbsp;'.e($l[1]).'</a>';echo '<a href="logout.php">↪ &nbsp;Cerrar sesión</a></nav><div class="sidebar-bottom">Sesión: '.e($_SESSION['nombre']??'').'<br>'.e(ucfirst($rol)).'</div></aside><main class="main"><header class="topbar"><div><span class="eyebrow">VETCARE · '.e(strtoupper($rol)).'</span><h1>'.e($titulo).'</h1></div><div class="profile"><span class="avatar">'.e(strtoupper(substr($_SESSION['nombre']??'U',0,1))).'</span><div><b>'.e($_SESSION['nombre']??'').'</b><small>'.e($rol).'</small></div></div></header>';
}
function pie(){echo '<footer>© '.date('Y').' VetCare · Sistema de gestión veterinaria</footer></main></div></body></html>';}
function alerta($m){if($m)echo '<div class="notice">'.e($m).'</div>';}
