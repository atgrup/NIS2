<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conexion = new mysqli('jordio35.sg-host.com', 'u74bscuknwn9n', 'ad123456-', 'dbs1il8vaitgwc');

    $correo = $_POST['email'];
    $password = $_POST['password'];
    $repeat = $_POST['repeat-password'];
    $tipo_usuario_id = 2;

    if ($password !== $repeat) {
        header("Location: registro.php?error=contraseña");
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conexion->prepare("INSERT INTO usuarios (correo, password, tipo_usuario_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $correo, $hash, $tipo_usuario_id);

    if ($stmt->execute()) {
        header("Location: login.php?registro=ok");
    } else {
        header("Location: registro.php?error=bd");
    }

    $stmt->close();
    $conexion->close();
    exit;
}
