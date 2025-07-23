<?php
session_start();
include '../api/includes/conexion.php';

if (!isset($conexion)) {
    $_SESSION['error'] = "Error de conexión a la base de datos";
    header('Location: plantillaUsers.php?vista=consultores');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['error'] = "Método no permitido";
    header('Location: plantillaUsers.php?vista=consultores');
    exit;
}

// Recoger datos del formulario
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['contrasena'] ?? '';
$repeat_password = $_POST['contrasena2'] ?? '';

// Validaciones básicas
if (empty($correo) || empty($password) || empty($repeat_password)) {
    $_SESSION['error'] = "Todos los campos son obligatorios";
    header('Location: plantillaUsers.php?vista=consultores');
    exit;
}

if ($password !== $repeat_password) {
    $_SESSION['error'] = "Las contraseñas no coinciden";
    header('Location: plantillaUsers.php?vista=consultores');
    exit;
}

// Verificar si el correo ya existe
$stmt = $conexion->prepare("SELECT id_usuarios FROM usuarios WHERE correo = ?");
$stmt->bind_param('s', $correo);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $_SESSION['error'] = "El correo ya está registrado";
    $stmt->close();
    header('Location: plantillaUsers.php?vista=consultores');
    exit;
}
$stmt->close();

// Hash de la contraseña
$hash = password_hash($password, PASSWORD_DEFAULT);

// ID del tipo de usuario CONSULTOR (según tu BD es 3)
$tipo_usuario_id = 3;

// Iniciar transacción
$conexion->begin_transaction();

try {
    // Insertar usuario
    $stmt = $conexion->prepare("INSERT INTO usuarios (correo, password, tipo_usuario_id) VALUES (?, ?, ?)");
    $stmt->bind_param('ssi', $correo, $hash, $tipo_usuario_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al crear usuario: ".$conexion->error);
    }
    
    $usuario_id = $stmt->insert_id;
    $stmt->close();

    // Insertar consultor (tabla consultores requiere usuario_id)
    $stmt2 = $conexion->prepare("INSERT INTO consultores (usuario_id) VALUES (?)");
    $stmt2->bind_param('i', $usuario_id);
    
    if (!$stmt2->execute()) {
        throw new Exception("Error al crear consultor: ".$conexion->error);
    }
    
    $stmt2->close();
    
    // Confirmar transacción
    $conexion->commit();
    $_SESSION['success'] = "Consultor creado correctamente";
    
} catch (Exception $e) {
    // Revertir en caso de error
    $conexion->rollback();
    $_SESSION['error'] = $e->getMessage();
}

header('Location: plantillaUsers.php?vista=consultores');
exit;
?>