<?php
// Inicia la sesión. Esto es crucial para almacenar mensajes de estado como errores o éxitos.
session_start();

// Incluye el archivo de conexión a la base de datos.
include '../api/includes/conexion.php';

// --- Verificaciones Iniciales ---
// Comprueba si la variable $conexion está definida y es válida. Si no, significa que la conexión falló.
if (!isset($conexion)) {
    $_SESSION['error'] = "Error de conexión a la base de datos";
    // Redirige al usuario de vuelta a la página de consultores.
    header('Location: plantillaUsers.php?vista=consultores');
    // Termina la ejecución del script.
    exit;
}

// Comprueba si el método de la solicitud no es POST. Si no es un formulario enviado, redirige.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['error'] = "Método no permitido";
    header('Location: plantillaUsers.php?vista=consultores');
    exit;
}

// --- Recopilación de Datos del Formulario ---
// Recoge el correo electrónico del formulario, eliminando espacios con `trim()` y usando el operador de fusión de null `??` para evitar errores si la variable no existe.
$correo = trim($_POST['correo'] ?? '');
// Recoge las contraseñas.
$password = $_POST['contrasena'] ?? '';
$repeat_password = $_POST['contrasena2'] ?? '';

// --- Validación de Datos ---
// Verifica si alguno de los campos obligatorios está vacío.
if (empty($correo) || empty($password) || empty($repeat_password)) {
    $_SESSION['error'] = "Todos los campos son obligatorios";
    header('Location: plantillaUsers.php?vista=consultores');
    exit;
}

// Verifica si las contraseñas no coinciden.
if ($password !== $repeat_password) {
    $_SESSION['error'] = "Las contraseñas no coinciden";
    header('Location: plantillaUsers.php?vista=consultores');
    exit;
}

// --- Verificación de Correo Existente en la Base de Datos ---
// Prepara una consulta SQL para buscar el correo en la tabla `usuarios`.
$stmt = $conexion->prepare("SELECT id_usuarios FROM usuarios WHERE correo = ?");
// Vincula el parámetro del correo. 's' indica que es un string.
$stmt->bind_param('s', $correo);
// Ejecuta la consulta.
$stmt->execute();
// Almacena el resultado para poder usar `num_rows`.
$stmt->store_result();

// Si el número de filas es mayor que cero, el correo ya existe.
if ($stmt->num_rows > 0) {
    $_SESSION['error'] = "El correo ya está registrado";
    $stmt->close();
    header('Location: plantillaUsers.php?vista=consultores');
    exit;
}
// Cierra la primera declaración preparada.
$stmt->close();

// --- Creación de Usuario y Consultor ---
// Genera un hash seguro de la contraseña.
$hash = password_hash($password, PASSWORD_DEFAULT);

// Define el ID para el tipo de usuario "Consultor".
$tipo_usuario_id = 3;

// Inicia una transacción de base de datos. Esto asegura que si una de las inserciones falla, ambas se revierten.
$conexion->begin_transaction();

// Utiliza un bloque `try-catch` para manejar errores de la transacción.
try {
    // --- Inserción en la tabla `usuarios` ---
    // Prepara la consulta para insertar el nuevo usuario.
    $stmt = $conexion->prepare("INSERT INTO usuarios (correo, password, tipo_usuario_id) VALUES (?, ?, ?)");
    // Vincula los parámetros: 's' para string, 's' para string, 'i' para entero.
    $stmt->bind_param('ssi', $correo, $hash, $tipo_usuario_id);
    
    // Si la ejecución falla, lanza una excepción.
    if (!$stmt->execute()) {
        throw new Exception("Error al crear usuario: " . $conexion->error);
    }
    
    // Obtiene el ID del usuario recién insertado, que se usará para la tabla `consultores`.
    $usuario_id = $stmt->insert_id;
    // Cierra la declaración.
    $stmt->close();

    // --- Inserción en la tabla `consultores` ---
    // Extrae la parte del correo antes del `@` para usarla como nombre del consultor.
    $nombre_consultor = explode('@', $correo)[0];

    // Prepara la consulta para insertar al consultor.
    $stmt2 = $conexion->prepare("INSERT INTO consultores (usuario_id, nombre) VALUES (?, ?)");
    // Vincula los parámetros: 'i' para entero, 's' para string.
    $stmt2->bind_param('is', $usuario_id, $nombre_consultor);

    // Si la ejecución falla, lanza una excepción.
    if (!$stmt2->execute()) {
        throw new Exception("Error al crear consultor: " . $conexion->error);
    }
    // Cierra la segunda declaración.
    $stmt2->close();
    
    // Si todo fue exitoso, confirma la transacción.
    $conexion->commit();
    // Almacena un mensaje de éxito.
    $_SESSION['success'] = "Consultor creado correctamente";
    
} catch (Exception $e) {
    // Si se capturó una excepción, revierte la transacción.
    $conexion->rollback();
    // Almacena el mensaje de error de la excepción en la sesión.
    $_SESSION['error'] = $e->getMessage();
}

// Redirige al usuario de vuelta a la página de consultores, independientemente del resultado.
header('Location: plantillaUsers.php?vista=consultores');
// Termina la ejecución.
exit;
?>