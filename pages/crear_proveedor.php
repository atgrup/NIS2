<?php
// Inicia la sesión. Esto permite almacenar información del usuario y mensajes de estado (éxito o error) para usarlos en otras páginas.
session_start();

// Incluye el archivo de conexión a la base de datos.
include '../api/includes/conexion.php';

// --- Verificaciones de Conexión y Método ---
// Comprueba si la variable de conexión a la base de datos existe. Si no, significa que la conexión falló.
if (!isset($conexion)) {
    // Almacena un mensaje de error en la sesión.
    $_SESSION['error'] = "Error de conexión a la base de datos";
    // Redirige al usuario a la página de proveedores.
    header('Location: plantillaUsers.php?vista=proveedores');
    // Termina la ejecución del script para evitar que se procese más código.
    exit;
}

// Verifica si la solicitud HTTP no es de tipo POST. Si no es un formulario enviado, se considera un acceso no válido.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Almacena un mensaje de error.
    $_SESSION['error'] = "Método no permitido";
    // Redirige al usuario.
    header('Location: plantillaUsers.php?vista=proveedores');
    // Termina la ejecución.
    exit;
}

// --- Recopilación de Datos del Formulario ---
// Recoge los datos del formulario. `trim()` elimina espacios en blanco y el operador `?? ''` asegura que la variable siempre tenga un valor (cadena vacía) para evitar errores si no se encuentra.
$correo = trim($_POST['email'] ?? '');
$nombre_empresa = trim($_POST['nombre_empresa'] ?? '');
$password = $_POST['password'] ?? '';
$repeat_password = $_POST['repeat-password'] ?? '';

// --- Validaciones de Datos ---
// Comprueba si alguno de los campos obligatorios está vacío.
if (empty($correo) || empty($nombre_empresa) || empty($password) || empty($repeat_password)) {
    // Si falta algún dato, se almacena un error.
    $_SESSION['error'] = "Todos los campos son obligatorios";
    // Redirige al usuario.
    header('Location: plantillaUsers.php?vista=proveedores');
    exit;
}

// Comprueba si las contraseñas introducidas no coinciden.
if ($password !== $repeat_password) {
    // Si no coinciden, se almacena un error.
    $_SESSION['error'] = "Las contraseñas no coinciden";
    // Redirige al usuario.
    header('Location: plantillaUsers.php?vista=proveedores');
    exit;
}

// --- Verificación de Correo Existente ---
// Prepara una consulta SQL para buscar el correo en la tabla `usuarios`.
$stmt = $conexion->prepare("SELECT id_usuarios FROM usuarios WHERE correo = ?");
// Vincula el valor de la variable `$correo` a la consulta. La 's' indica que es un string.
$stmt->bind_param('s', $correo);
// Ejecuta la consulta preparada.
$stmt->execute();
// Almacena el resultado de la consulta, lo que permite usar `num_rows`.
$stmt->store_result();

// Si el número de filas encontradas es mayor que 0, el correo ya está en uso.
if ($stmt->num_rows > 0) {
    // Almacena un error.
    $_SESSION['error'] = "El correo ya está registrado";
    // Cierra la declaración.
    $stmt->close();
    // Redirige al usuario.
    header('Location: plantillaUsers.php?vista=proveedores');
    exit;
}
// Cierra la declaración.
$stmt->close();

// --- Creación de Usuario y Proveedor ---
// Crea un hash seguro de la contraseña. `PASSWORD_DEFAULT` utiliza el algoritmo de hash más actual y recomendado.
$hash = password_hash($password, PASSWORD_DEFAULT);

// Define el ID para el tipo de usuario "Proveedor", que es 2 en este caso.
$tipo_usuario_id = 2;

// Inicia una transacción de la base de datos. Esto es fundamental para asegurar que ambas inserciones (usuario y proveedor) se completen con éxito o ninguna lo haga.
$conexion->begin_transaction();

// Se utiliza un bloque `try...catch` para manejar posibles errores durante la transacción.
try {
    // --- Insertar en la tabla `usuarios` ---
    // Prepara la consulta para insertar un nuevo registro de usuario.
    $stmt = $conexion->prepare("INSERT INTO usuarios (correo, password, tipo_usuario_id) VALUES (?, ?, ?)");
    // Vincula los valores a la consulta. 's' para strings, 'i' para entero.
    $stmt->bind_param('ssi', $correo, $hash, $tipo_usuario_id);
    
    // Si la ejecución de la consulta falla, lanza una excepción.
    if (!$stmt->execute()) {
        throw new Exception("Error al crear usuario: " . $conexion->error);
    }
    
    // Obtiene el ID del usuario recién insertado, que es necesario para la tabla de proveedores.
    $usuario_id = $stmt->insert_id;
    // Cierra la declaración.
    $stmt->close();

    // --- Insertar en la tabla `proveedores` ---
    // Prepara la consulta para insertar un nuevo registro de proveedor.
    $stmt2 = $conexion->prepare("INSERT INTO proveedores (nombre_empresa, usuario_id) VALUES (?, ?)");
    // Vincula los valores.
    $stmt2->bind_param('si', $nombre_empresa, $usuario_id);
    
    // Si la ejecución de esta segunda consulta falla, lanza una excepción.
    if (!$stmt2->execute()) {
        throw new Exception("Error al crear proveedor: " . $conexion->error);
    }
    
    // Cierra la segunda declaración.
    $stmt2->close();
    
    // Si ambas operaciones se realizaron sin errores, confirma los cambios en la base de datos.
    $conexion->commit();
    // Almacena un mensaje de éxito.
    $_SESSION['success'] = "Proveedor creado correctamente";
    
} catch (Exception $e) {
    // Si se captura una excepción, revierte todos los cambios de la transacción para mantener la integridad de la base de datos.
    $conexion->rollback();
    // Almacena el mensaje de error de la excepción en la sesión.
    $_SESSION['error'] = $e->getMessage();
}

// Redirige al usuario de vuelta a la página de proveedores, sin importar si el proceso fue exitoso o no.
header('Location: plantillaUsers.php?vista=proveedores');
// Termina la ejecución.
exit;
?>