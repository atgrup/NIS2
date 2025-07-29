<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/api/includes/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

$usuario_id = $_SESSION['id_usuario'];

// Verificar tipo de usuario (admin o consultor)
$stmtTipo = $conexion->prepare("SELECT tipo_usuario_id FROM usuarios WHERE id_usuarios = ?");
$stmtTipo->bind_param("i", $usuario_id);
$stmtTipo->execute();
$stmtTipo->bind_result($tipo_usuario_id);
$stmtTipo->fetch();
$stmtTipo->close();

if (!isset($_POST['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'missing_id']);
    exit;
}

$id = intval($_POST['id']);

// 1. Obtener plantilla con consultor_id y nombre
$stmt = $conexion->prepare("SELECT nombre, consultor_id FROM plantillas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    echo json_encode(['success' => false, 'error' => 'not_found']);
    exit;
}

$plantilla = $result->fetch_assoc();
$nombre_plantilla = $plantilla['nombre'];
$consultor_id_plantilla = $plantilla['consultor_id'];
$stmt->close();

// 2. Obtener consultor_id del usuario logueado (si es consultor)
$consultor_id_usuario = null;
if ($tipo_usuario_id == 3) { // Consultor
    $stmtConsultor = $conexion->prepare("SELECT id FROM consultores WHERE usuario_id = ?");
    $stmtConsultor->bind_param("i", $usuario_id);
    $stmtConsultor->execute();
    $stmtConsultor->bind_result($consultor_id_usuario);
    $stmtConsultor->fetch();
    $stmtConsultor->close();
}

// 3. Verificar permisos: admin (tipo_usuario_id==1) o consultor dueño de la plantilla
if ($tipo_usuario_id != 1 && $consultor_id_usuario !== $consultor_id_plantilla) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

// 4. Eliminar archivo físico
$ruta_archivo = dirname(__DIR__) . '/plantillas_disponibles/' . $nombre_plantilla;
if (file_exists($ruta_archivo)) {
    unlink($ruta_archivo);
}

// 5. Eliminar de la base de datos
$stmtDelete = $conexion->prepare("DELETE FROM plantillas WHERE id = ?");
$stmtDelete->bind_param("i", $id);
if ($stmtDelete->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'db_error']);
}
$stmtDelete->close();

$conexion->close();
exit;
