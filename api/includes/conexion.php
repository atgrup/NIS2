<?php
$host = 'jordio35.sg-host.com';
$usuario = 'u74bscuknwn9n';
$contrasena = 'ad123456-';
$base_de_datos = 'dbs1il8vaitgwc';

// Crear conexión
$conexion = new mysqli($host, $usuario, $contrasena, $base_de_datos);

// Verificar conexión
if ($conexion->connect_error) {
    die("❌ Error de conexión: " . $conexion->connect_error);
}
?>
