<?php
require 'conexion.php'; // tu conexión a la base de datos

// Comprobar que se envió el formulario por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $nombre_empresa = $_POST['nombre_empresa'];
    $password = $_POST['password'];
    $repeat_password = $_POST['repeat-password'];

    // Verificar que las contraseñas coincidan
    if ($password !== $repeat_password) {
        header("Location: ../../registro.php?error=pass");
        exit;
    }

    // Verificar si el correo ya existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        header("Location: ../../registro.php?error=email");
        exit;
    }

    // Generar hash de la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Generar código de verificación
    $verification_code = bin2hex(random_bytes(16));

    // Insertar el usuario con email no verificado
    $stmt = $conn->prepare("INSERT INTO usuarios (email, nombre_empresa, password, email_verified, verification_code) VALUES (?, ?, ?, 0, ?)");
    $stmt->bind_param("ssss", $email, $nombre_empresa, $password_hash, $verification_code);
    if ($stmt->execute()) {
        // Enviar correo de verificación
        $verification_link = "https://tusitio.com/verify.php?code=$verification_code";
        $subject = "Verifica tu correo - NIS2";
        $message = "Hola, $nombre_empresa!<br><br>Por favor verifica tu correo haciendo clic en el siguiente enlace:<br><a href='$verification_link'>$verification_link</a><br><br>Gracias.";
        $headers = "From: no-reply@tusitio.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        mail($email, $subject, $message, $headers);

        // Redirigir con mensaje de éxito
        header("Location: ../../registro.php?success=1");
        exit;
    } else {
        header("Location: ../../registro.php?error=unknown");
        exit;
    }
} else {
    // Acceso directo no permitido
    header("Location: ../../registro.php");
    exit;
}
?>
