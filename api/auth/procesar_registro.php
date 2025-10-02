<?php
require '../includes/conexion.php'; // Conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'] ?? '';
    $nombre_empresa = $_POST['nombre_empresa'] ?? '';
    $password = $_POST['password'] ?? '';
    $repeat_password = $_POST['repeat-password'] ?? '';

    // Validación básica
    if ($password !== $repeat_password) {
        header("Location: ../../pages/registro.php?error=pass");
        exit;
    }

    // Verificar si el correo ya existe
    $stmt = $conexion->prepare("SELECT id_usuarios FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        header("Location: ../../pages/registro.php?error=email");
        exit;
    }

    // Generar hash de la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Generar token de verificación
    $verification_code = bin2hex(random_bytes(16));
    $verificado = 0;
    $tipo_usuario_id = 2; // Por defecto proveedor

    // Insertar el usuario
    $stmt = $conexion->prepare("
        INSERT INTO usuarios (correo, nombre_empresa, password, verificado, token_verificacion, tipo_usuario_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        die("Error en la preparación del statement: " . $conexion->error);
    }
    $stmt->bind_param("sssiii", $email, $nombre_empresa, $password_hash, $verificado, $verification_code, $tipo_usuario_id);

    if ($stmt->execute()) {
        // Preparar correo de verificación
        $verification_link = "http://localhost/NIS2/api/auth/verify.php?code=$verification_code";
        $subject = "Verifica tu correo - NIS2";
        $message = "
            <p>Hola, <strong>$nombre_empresa</strong>!</p>
            <p>Por favor verifica tu correo haciendo clic en el siguiente enlace:</p>
            <p><a href='$verification_link'>$verification_link</a></p>
            <p>Gracias.</p>
        ";
        $headers = "From: no-reply@tusitio.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        // Enviar correo (puedes comentar esta línea mientras haces pruebas)
        mail($email, $subject, $message, $headers);

        // Redirigir con token para mostrar modal
        header("Location: ../../pages/registro.php?success=1&token=$verification_code");
        exit;
    } else {
        header("Location: ../../pages/registro.php?error=unknown");
        exit;
    }

} else {
    // Acceso directo
    header("Location: ../../pages/registro.php");
    exit;
}
?>
