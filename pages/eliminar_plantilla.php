<?php
// Inicia la sesión si no está ya activa. Esto evita advertencias si la sesión ya fue iniciada en otro script.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluye el archivo de conexión a la base de datos.
require_once dirname(__DIR__) . '/api/includes/conexion.php';

// --- Verificación de Autenticación ---
// Comprueba si el usuario está logueado verificando la variable de sesión `id_usuario`.
if (!isset($_SESSION['id_usuario'])) {
    // Si no está logueado, responde con un JSON de error de no autorizado.
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    // Detiene la ejecución del script.
    exit;
}

// Almacena el ID del usuario logueado.
$usuario_id = $_SESSION['id_usuario'];

// --- Obtener el Tipo de Usuario ---
// Prepara una consulta para obtener el `tipo_usuario_id` del usuario actual.
$stmtTipo = $conexion->prepare("SELECT tipo_usuario_id FROM usuarios WHERE id_usuarios = ?");
// Vincula el ID del usuario (`i` de entero).
$stmtTipo->bind_param("i", $usuario_id);
// Ejecuta la consulta.
$stmtTipo->execute();
// Vincula el resultado a la variable `$tipo_usuario_id`.
$stmtTipo->bind_result($tipo_usuario_id);
// Obtiene el resultado.
$stmtTipo->fetch();
// Cierra la declaración.
$stmtTipo->close();

// --- Validación de Entrada ---
// Verifica si se ha recibido la ID de la plantilla a través de POST.
if (!isset($_POST['id'])) {
    // Si falta la ID, responde con un JSON de error.
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'missing_id']);
    exit;
}

// Convierte la ID a un entero para asegurar su tipo.
$id = intval($_POST['id']);

// 1. Obtener la Plantilla y el `consultor_id` asociado.
// Prepara una consulta para obtener el nombre del archivo de la plantilla y el ID del consultor que la subió.
$stmt = $conexion->prepare("SELECT nombre, consultor_id FROM plantillas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
// Obtiene el resultado como un objeto.
$result = $stmt->get_result();

// Si no se encuentra exactamente una fila, la plantilla no existe.
if ($result->num_rows !== 1) {
    $stmt->close();
    echo json_encode(['success' => false, 'error' => 'not_found']);
    exit;
}

// Almacena los datos de la plantilla en un array asociativo.
$plantilla = $result->fetch_assoc();
$nombre_plantilla = $plantilla['nombre'];
$consultor_id_plantilla = $plantilla['consultor_id'];
$stmt->close();

// 2. Obtener el `consultor_id` del usuario logueado (si aplica).
// Inicializa la variable.
$consultor_id_usuario = null;
// Si el usuario es de tipo 'Consultor' (ID 3)...
if ($tipo_usuario_id == 3) {
    // Prepara una consulta para obtener el `consultor_id` del usuario actual.
    $stmtConsultor = $conexion->prepare("SELECT id FROM consultores WHERE usuario_id = ?");
    $stmtConsultor->bind_param("i", $usuario_id);
    $stmtConsultor->execute();
    $stmtConsultor->bind_result($consultor_id_usuario);
    $stmtConsultor->fetch();
    $stmtConsultor->close();
}

// 3. Verificar Permisos de Eliminación.
// Comprueba si el usuario no es un Administrador (ID 1) Y si el ID del consultor logueado NO coincide con el ID del consultor de la plantilla.
if ($tipo_usuario_id != 1 && $consultor_id_usuario !== $consultor_id_plantilla) {
    // Si no tiene permisos, responde con un JSON de error de no autorizado.
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

// 4. Eliminar el Archivo Físico.
// Define la ruta completa del archivo en el servidor. `dirname(__DIR__)` sube dos niveles en la estructura de directorios.
$ruta_archivo = dirname(__DIR__) . '/plantillas_disponibles/' . $nombre_plantilla;
// Comprueba si el archivo existe.
if (file_exists($ruta_archivo)) {
    // Si existe, lo elimina.
    unlink($ruta_archivo);
}

// 5. Eliminar el Registro de la Base de Datos.
// Prepara una consulta para eliminar el registro de la plantilla de la tabla `plantillas`.
$stmtDelete = $conexion->prepare("DELETE FROM plantillas WHERE id = ?");
$stmtDelete->bind_param("i", $id);
// Si la ejecución de la consulta es exitosa...
if ($stmtDelete->execute()) {
    // Responde con un JSON de éxito.
    echo json_encode(['success' => true]);
} else {
    // Si hay un error en la base de datos, responde con un JSON de error.
    echo json_encode(['success' => false, 'error' => 'db_error']);
}
// Cierra la declaración.
$stmtDelete->close();

// Cierra la conexión a la base de datos.
$conexion->close();
// Termina la ejecución.
exit;
?>