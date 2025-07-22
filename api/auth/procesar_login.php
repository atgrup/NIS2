<?php
require_once '../includes/conexion.php';

session_start();

$correo = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($correo) || empty($password)) {
    header("Location: ../../pages/login.php?error=credenciales");
    exit;
}

$stmt = $conexion->prepare("
    SELECT u.id_usuarios, u.password, t.nombre AS rol
    FROM usuarios u
    INNER JOIN tipo_usuario t ON u.tipo_usuario_id = t.id_tipo_usuario
    WHERE u.correo = ?
");

$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        $_SESSION['id_usuario'] = $user['id_usuarios'];
        $_SESSION['correo'] = $correo;
        $_SESSION['rol'] = $user['rol']; // 'rol' es el nombre del tipo de usuario

        header("Location: ../../pages/plantillaUsers.php");
        exit;
    } else {
        header("Location: ../../pages/login.php?error=credenciales");
        exit;
    }
} else {
    header("Location: ../../pages/login.php?error=credenciales");
    exit;
}
?>


