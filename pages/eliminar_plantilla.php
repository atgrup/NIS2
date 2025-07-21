<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/api/includes/conexion.php';

// Verificar si el usuario es administrador
$is_admin = false;
if (isset($_SESSION['id_usuario'])) {
    $stmtAdmin = $conexion->prepare("SELECT tipo_usuario_id FROM usuarios WHERE id_usuarios = ?");
    $stmtAdmin->bind_param("i", $_SESSION['id_usuario']);
    $stmtAdmin->execute();
    $stmtAdmin->bind_result($tipo_usuario_id);
    if ($stmtAdmin->fetch() && $tipo_usuario_id == 1) {
        $is_admin = true;
    }
    $stmtAdmin->close();
}

if (!$is_admin) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

if (!isset($_POST['uuid'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'missing_uuid']);
    exit;
}

$uuid = trim($_POST['uuid']);

// 1. Obtener el nombre del archivo de la plantilla desde la base de datos
$stmt = $conexion->prepare("SELECT nombre FROM plantillas WHERE uuid = ?");
$stmt->bind_param("s", $uuid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $plantilla = $result->fetch_assoc();
    $nombre_plantilla = $plantilla['nombre'];
    $stmt->close();

    // 2. Eliminar el archivo físico
    $ruta_archivo = dirname(__DIR__) . '/plantillas_disponibles/' . $nombre_plantilla;
    if (file_exists($ruta_archivo)) {
        unlink($ruta_archivo);
    }

    // 3. Eliminar el registro de la base de datos
    $stmtDelete = $conexion->prepare("DELETE FROM plantillas WHERE uuid = ?");
    $stmtDelete->bind_param("s", $uuid);
    if ($stmtDelete->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'db_error']);
    }
    $stmtDelete->close();
} else {
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'not_found']);
}

$conexion->close();
exit;