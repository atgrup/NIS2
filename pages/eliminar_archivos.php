<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../api/includes/conexion.php';

header('Content-Type: application/json');

$id = $_POST['id'] ?? null;

if (!$id || !is_numeric($id)) {
  echo json_encode(['success' => false, 'error' => 'ID inválido']);
  exit;
}

$rol = $_SESSION['rol'] ?? '';
$usuario_id = $_SESSION['id_usuario'] ?? null;

if (!$usuario_id) {
  echo json_encode(['success' => false, 'error' => 'No autenticado']);
  exit;
}

if ($rol === 'administrador') {
  $stmt = $conexion->prepare("DELETE FROM archivos_subidos WHERE id = ?");
  $stmt->bind_param("i", $id);
} elseif ($rol === 'proveedor') {
  $prov_id = $_SESSION['proveedor_id'] ?? 0;
  $stmt = $conexion->prepare("DELETE FROM archivos_subidos WHERE id = ? AND proveedor_id = ?");
  $stmt->bind_param("ii", $id, $prov_id);
} else {
  echo json_encode(['success' => false, 'error' => 'Sin permisos']);
  exit;
}

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'error' => 'Error al eliminar']);
}
$stmt->close();
$conexion->close(); 