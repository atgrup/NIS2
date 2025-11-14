<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------ CAMBIO #1: Header JSON y sin var_dump ------
// Se eliminó var_dump($_SESSION);
// Se cambió text/plain por application/json
header('Content-Type: application/json');

// Incluir conexión
require '../api/includes/conexion.php';

// -----------------------------------------------------------
// PRIMERO asignar variables desde sesión / POST / headers
// -----------------------------------------------------------
$usuario_id = $_SESSION['id_usuarios'] ?? ($_POST['id_usuarios'] ?? null);

// --- CAMBIO #2: 'tipo_usuario_id' no existe en tu sesión ---
// En plantillaUsers.php usas $_SESSION['rol']. Usemos esa variable.
// Y en la BBDD, 1=admin, 2=proveedor, 3=consultor
$rol_sesion = $_SESSION['rol'] ?? null;
$proveedor_id = null; // Lo definiremos ahora

if ($rol_sesion === 'administrador') {
    $proveedor_id = 1;
} elseif ($rol_sesion === 'proveedor') {
    $proveedor_id = 2;
} elseif ($rol_sesion === 'consultor') {
    $proveedor_id = 3;
}
// -----------------------------------------------------------


// -----------------------------------------------------------
// FUNCIÓN PARA VERIFICAR QUE EL USUARIO ESTÁ VERIFICADO (token es NULL)
// -----------------------------------------------------------
function usuarioEstaVerificado($conexion, $usuario_id) {
    $sql = "SELECT token_verificacion FROM usuarios WHERE id_usuarios = ?";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) return false;

    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();

    $tokenDB = null;
    $stmt->bind_result($tokenDB);

    if ($stmt->fetch()) {
        $stmt->close();
        // ------ CAMBIO #3: Lógica de verificación invertida ------
        // El usuario está verificado SI el token ESTÁ vacío (empty).
        return empty($tokenDB);
    } else {
        $stmt->close();
        return false; // Usuario no encontrado
    }
}

// -----------------------------------------------------------
// VERIFICAR ESTADO EN BD
// -----------------------------------------------------------
if (!empty($usuario_id)) {
    // Usamos la función con el nombre corregido
    if (!usuarioEstaVerificado($conexion, $usuario_id)) {
        echo json_encode([
            'success' => false,
            'error' => 'Usuario no verificado. Por favor, revisa tu correo para activar tu cuenta.'
        ]);
        exit;
    }
}

// -----------------------------------------------------------
// VALIDAR VARIABLES FALTANTES
// -----------------------------------------------------------
$missing = [];
if (empty($usuario_id)) $missing[] = 'usuario_id';
if (empty($proveedor_id)) $missing[] = 'proveedor_id (rol)'; // El rol no se pudo determinar

// ------ CAMBIO #4: Eliminada la comprobación de token ------
// if (empty($token_verificacion)) $missing[] = 'token_verificacion';

if (!empty($missing)) {
    echo json_encode([
        'success' => false,
        'error' => 'Usuario no autenticado: faltan variables de sesión',
        'faltan' => $missing,
        'debug' => [
            'session_id_usuarios_set' => isset($_SESSION['id_usuarios']),
            'session_rol_set' => isset($_SESSION['rol']),
        ]
    ]);
    exit;
}

// -----------------------------------------------------------
// PROCESAR ARCHIVO (igual que tu código original)
// -----------------------------------------------------------
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
// LÓGICA DE INSERCIÓN (MODIFICADA PARA USAR ROL)
// -----------------------------------------------------------

// $proveedor_id (el ID de rol) ya lo definimos arriba.
// 2 = proveedor, 3 = consultor

if ($proveedor_id == 2) { // Si es Proveedor
    $stmt = $conexion->prepare("
        INSERT INTO archivos_subidos 
        (proveedor_id, usuario_id, archivo_url, nombre_archivo, fecha_subida, revision_estado, plantilla_id)
        VALUES (?, ?, ?, ?, NOW(), 'pendiente', ?)
    ");

    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => $conexion->error]);
        exit;
    }
    
    // Necesitamos el ID del proveedor (de la tabla 'proveedores'), no el ID de rol
    $stmt_prov = $conexion->prepare("SELECT id FROM proveedores WHERE usuario_id = ?");
    $stmt_prov->bind_param("i", $usuario_id);
    $stmt_prov->execute();
    $id_proveedor_tabla = null;
    $stmt_prov->bind_result($id_proveedor_tabla);
    $stmt_prov->fetch();
    $stmt_prov->close();

    $stmt->bind_param("iisss", $id_proveedor_tabla, $usuario_id, $ruta_destino, $nombre_original, $plantilla_id);

} elseif ($proveedor_id == 3) { // Si es Consultor
    $stmt = $conexion->prepare("
        INSERT INTO plantillas
        (nombre, consultor_id, archivo_url, fecha_subida)
        VALUES (?, ?, ?, NOW())
    ");

     if (!$stmt) {
        echo json_encode(['success' => false, 'error' => $conexion->error]);
        exit;
    }

    // Necesitamos el ID del consultor (de la tabla 'consultores')
    $stmt_cons = $conexion->prepare("SELECT id FROM consultores WHERE usuario_id = ?");
    $stmt_cons->bind_param("i", $usuario_id);
    $stmt_cons->execute();
    $id_consultor_tabla = null;
    $stmt_cons->bind_result($id_consultor_tabla);
    $stmt_cons->fetch();
    $stmt_cons->close();

    $stmt->bind_param("sis", $nombre_original, $id_consultor_tabla, $ruta_destino);

} else { // Si es Admin (rol 1) o desconocido
    echo json_encode(['success' => false, 'error' => 'Tu rol no tiene permisos para subir este tipo de archivo.']);
    exit;
}

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
    exit;
}

echo json_encode(['success' => true, 'archivo_id' => $stmt->insert_id]);
$stmt->close();
?>