<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    
}
header('Content-Type: text/plain');

// Verificar que estén definidas
$usuario_id = $_SESSION['id_usuarios'] ?? null;
$rol = strtolower($_SESSION['rol'] ?? '');
$proveedor_id = $_SESSION['proveedor_id'] ?? null;
$correo = $_SESSION['correo'] ?? null;

if (!$usuario_id || !$rol || !$correo) {
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit;
}
require '../api/includes/conexion.php';

// Obtener datos de sesión
$usuario_id = $_SESSION['id_usuarios'] ?? null;
$rol = strtolower($_SESSION['rol'] ?? '');
$proveedor_id = $_SESSION['proveedor_id'] ?? null;

// Validar sesión
if (!$usuario_id || !$rol) {
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit;
}

// Procesar archivo subido
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No se seleccionó archivo o hubo un error al subirlo']);
    exit;
}

$archivo = $_FILES['archivo'];
$nombre_original = basename($archivo['name']);
$ruta_usuario = 'documentos_subidos/' . $_SESSION['correo'] . '/';

if (!is_dir('../' . $ruta_usuario)) {
    mkdir('../' . $ruta_usuario, 0777, true);
}

$ruta_destino = $ruta_usuario . $nombre_original;

if (!move_uploaded_file($archivo['tmp_name'], '../' . $ruta_destino)) {
    echo json_encode(['success' => false, 'error' => 'Error al mover el archivo']);
    exit;
}

$plantilla_id = !empty($_POST['plantilla_id']) ? (int)$_POST['plantilla_id'] : null;

// Guardar en base de datos
$stmt = $conexion->prepare("
    INSERT INTO archivos_subidos 
    (nombre_archivo, archivo_url, proveedor_id, usuario_id, plantilla_id, fecha_subida, revision_estado)
    VALUES (?, ?, ?, ?, ?, NOW(), 'pendiente')
");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conexion->error]);
    exit;
}

$prov_id_final = $proveedor_id ?? 0;
$user_id_final = $usuario_id;

$stmt->bind_param("ssiii", $nombre_original, $ruta_destino, $prov_id_final, $user_id_final, $plantilla_id);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
    exit;
}

// Retornar éxito
echo json_encode(['success' => true, 'archivo_id' => $stmt->insert_id]);
$stmt->close();
?>
