<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array(strtolower($_SESSION['rol']), ['administrador', 'consultor'])) {
    http_response_code(403);
    exit('No autorizado');
}

require __DIR__ . '/../api/includes/conexion.php';

$id = intval($_POST['id'] ?? 0);
$estado = $_POST['estado'] ?? '';

$estados_permitidos = ['pendiente', 'aprobado', 'rechazado'];
if (!$id || !in_array($estado, $estados_permitidos)) {
    http_response_code(400);
    exit('Datos inválidos');
}

$stmt = $conexion->prepare("UPDATE archivos_subidos SET revision_estado = ? WHERE id = ?");
$stmt->bind_param("si", $estado, $id);
if ($stmt->execute()) {
    echo "Estado actualizado";
} else {
    http_response_code(500);
    echo "Error al actualizar";
}
