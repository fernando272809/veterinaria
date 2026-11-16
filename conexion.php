<?php
$host='localhost'; $bd='veterinariadb'; $usuario='root'; $contrasena='';
try { $conexion=new PDO("mysql:host=$host;dbname=$bd;charset=utf8mb4",$usuario,$contrasena,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }
catch(PDOException $e){ die('Error de conexión. Revisa conexion.php y que MySQL esté encendido.'); }
