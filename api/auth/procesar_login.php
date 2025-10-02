<?php
// Incluye el archivo de conexión a la base de datos
require_once '../includes/conexion.php';

// Inicia sesión para poder guardar los datos del usuario autenticado
session_start();

// Obtiene correo y contraseña enviados por POST
$correo = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Si alguno está vacío, redirige al login con error
if (empty($correo) || empty($password)) {
    header("Location: ../../pages/login.php?error=credenciales");
    exit;
}

// Prepara la consulta para obtener usuario por correo
$stmt = $conexion->prepare("
    SELECT u.id_usuarios, u.password, t.nombre AS rol
    FROM usuarios u
    INNER JOIN tipo_usuario t ON u.tipo_usuario_id = t.id_tipo_usuario
    WHERE u.correo = ?
");

// Asocia el correo al parámetro y ejecuta
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

// Verifica si encontró exactamente 1 usuario
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Comprueba que la contraseña ingresada coincida con la hasheada en BD
    if (password_verify($password, $user['password'])) {
        // Si coincide, guarda datos del usuario en la sesión
        $_SESSION['id_usuario'] = $user['id_usuarios'];
        $_SESSION['correo'] = $correo;
        $_SESSION['rol'] = $user['rol']; // ejemplo: 'ADMINISTRADOR', 'CONSULTOR', etc.

        // Redirige a la página principal para usuarios autenticados
        header("Location: ../../pages/plantillaUsers.php");
        exit;
    } else {
        // Contraseña incorrecta → vuelve al login con error
        header("Location: ../../pages/login.php?error=credenciales");
        exit;
    }
} else {
    // Usuario no encontrado → vuelve al login con error
    header("Location: ../../pages/login.php?error=credenciales");
    exit;
}
?>


