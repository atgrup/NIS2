<?php
require '../includes/conexion.php'; // Conexión a la base de datos
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'] ?? '';
    $password = trim($_POST['password'] ?? '');

    // Validación mínima
    if (empty($email) || empty($password)) {
        header("Location: ../../pages/login.php?error=credenciales");
        exit;
    }

    // Buscar usuario por correo
    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE correo = ?");
    if (!$stmt) {
        die("Error preparando statement: " . $conexion->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        // Usuario no encontrado
        header("Location: ../../pages/login.php?error=credenciales");
        exit;
    }

    // Verificar contraseña
    if (!password_verify($password, $user['password'])) {
        // Contraseña incorrecta
        header("Location: ../../pages/login.php?error=credenciales");
        exit;
    }

    // Verificar si está verificado
    if ($user['verificado'] == 0) {
        header("Location: ../../pages/login.php?error=no_verificado");
        exit;
    }

    // Usuario correcto y verificado → iniciar sesión
    $_SESSION['user_id'] = $user['id_usuarios'];
    $_SESSION['tipo_usuario'] = $user['tipo_usuario_id']; // opcional, útil si quieres diferenciar roles

    // Redirigir a vista de archivos o dashboard de proveedor
    header("Location: ../../pages/vista_archivos.php");
    exit;

} else {
    // Acceso directo al archivo
    header("Location: ../../pages/login.php");
    exit;
}
?>
