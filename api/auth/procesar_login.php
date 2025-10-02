<?php
require_once '../includes/conexion.php';

session_start();

$correo = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($correo) || empty($password)) {
    header("Location: ../../pages/login.php?error=credenciales");
    exit;
}

// Obtener usuario y estado de verificación
$stmt = $conexion->prepare("
    SELECT u.id_usuarios, u.password, u.verificado, t.nombre AS rol
    FROM usuarios u
    INNER JOIN tipo_usuario t ON u.tipo_usuario_id = t.id_tipo_usuario
    WHERE u.correo = ?
");
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // <-- AQUÍ colocas la verificación -->
    if (!$user['verificado']) {
        header("Location: ../../pages/login.php?error=no_verificado");
        exit;
    }
    // <-- FIN de la verificación -->

    if (password_verify($password, $user['password'])) {
        $_SESSION['id_usuario'] = $user['id_usuarios'];
        $_SESSION['correo'] = $correo;
        $_SESSION['rol'] = $user['rol'];
        header("Location: ../../pages/plantillaUsers.php");
        exit;
    } else {
        header("Location: ../../pages/login.php?error=credenciales");
        exit;
    }
}
else {
    header("Location: ../../pages/login.php?error=credenciales");
    exit;
}

$stmt->close();
$conexion->close();
?>
