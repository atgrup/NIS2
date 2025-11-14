<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    
}
header('Content-Type: text/plain');


// Obtener valores de forma segura (no lanza notices si no existen)
$usuario_id = $_SESSION['id_usuarios'] ?? ($_POST['id_usuarios'] ?? null);
$proveedor_id = $_SESSION['tipo_usuario_id'] ?? ($_POST['tipo_usuario_id'] ?? null);
// Intentamos leer token_verificacion primero desde la sesión y, si no está, desde POST (por si se envía así)
$token_verificacion = $_SESSION['token_verificacion'] 
    ?? ($_POST['token_verificacion'] ?? null);

// Si no viene por POST, mirar cabecera Authorization (útil para APIs)
if (empty($token_verificacion)) {
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    } else {
        $auth = null;
    }

    if ($auth && stripos($auth, 'Bearer ') === 0) {
        $token_verificacion = substr($auth, 7);
    }
}

// incluir conexión después de iniciar sesión y obtener variables
require '../api/includes/conexion.php';

// Validar y devolver cuál falta exactamente (útil para debug)
$missing = [];
if (empty($usuario_id))       $missing[] = 'usuario_id';
if (empty($proveedor_id))     $missing[] = 'proveedor_id';
if (empty($token_verificacion)) $missing[] = 'token_verificacion';

if (!empty($missing)) {
    echo json_encode([
        'success' => false,
        'error' => 'Usuario no autenticado: faltan variables',
        'faltan' => $missing,
        // opcional: mostrar en qué parte se estaban buscando (solo para debug)
        'debug' => [
            'session_id_usuarios' => isset($_SESSION['id_usuarios']),
            'session_tipo_usuario_id' => isset($_SESSION['tipo_usuario_id']),
            'session_token_verificacion' => isset($_SESSION['token_verificacion']),
            'post_token_verificacion' => isset($_POST['token_verificacion']),
            'post_id_usuarios' => isset($_POST['id_usuarios']),
            'post_tipo_usuario_id' => isset($_POST['tipo_usuario_id']),
            'http_authorization_present' => !empty($auth)
        ]
    ]);
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

// -----------------------------------------------------------
//  NUEVA LÓGICA SEGÚN tipo_usuario_id
// -----------------------------------------------------------
if ($proveedor_id == 2) {
    // Insertar en archivos_subidos
    $stmt = $conexion->prepare("
        INSERT INTO archivos_subidos 
        (id, proveedor_id, usuario_id, archivo_url, nombre_archivo, comentario, fecha_subida, revision_estado, plantilla_uuid, plantilla_id)
        VALUES (?, ?, ?, ?, ?, ?,  NOW(), 'pendiente', ?, ?)
    ");

} elseif ($proveedor_id == 3) {
    // Insertar en plantillas
    $stmt = $conexion->prepare("
        INSERT INTO plantillas
        (id, nombre, descripcion, consultor_id, archivo_url, fecha_subida)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
} else {
    echo json_encode(['success' => false, 'error' => 'tipo_usuario_id no válido']);
    exit;
}
// -----------------------------------------------------------

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conexion->error]);
    exit;
}

$prov_id_final = $proveedor_id ?? 0;
$user_id_final = $usuario_id;

if ($proveedor_id == 2) {
    // bind para archivos_subidos (5 campos)
    $stmt->bind_param("ssiii", $nombre_original, $ruta_destino, $prov_id_final, $user_id_final, $plantilla_id);

} elseif ($proveedor_id == 3) {
    // bind para plantillas (4 campos)
    $stmt->bind_param("ssii", $nombre_original, $ruta_destino, $prov_id_final, $user_id_final);
}

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
    exit;
}

// Retornar éxito
echo json_encode(['success' => true, 'archivo_id' => $stmt->insert_id]);
$stmt->close();
?>
