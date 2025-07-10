<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'ArchivoModel.php';

header('Content-Type: application/json');

session_start();
$usuario_id = $_SESSION['usuario_id'] ?? null;

$modelo = new ArchivoModel();

if ($usuario_id) {
    $archivos = $modelo->obtenerArchivosPorUsuario($usuario_id);
    echo json_encode($archivos);
} else {
    echo json_encode(["error" => "No hay sesión de usuario"]);
}
