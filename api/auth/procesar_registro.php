
<?php
require '../includes/conexion.php'; // conexión a la base de datos

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
    $stmt = $conexion->prepare("SELECT id_usuarios FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        header("Location: ../../registro.php?error=email");
        exit;
    }

    // Generar hash de la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Generar token de verificación
    $verification_code = bin2hex(random_bytes(16));

    // Insertar el usuario con correo no verificado
   $stmt = $conexion->prepare("INSERT INTO usuarios (correo, password, verificado, token_verificacion, tipo_usuario_id) VALUES (?, ?, 0, ?, 2)");
$stmt->bind_param("sss", $email, $password_hash, $verification_code);

    if ($stmt->execute()) {
        // Enlace de verificación (ajústalo a tu entorno: localhost o dominio real)
        $verification_link = "http://localhost/NIS2/api/auth/verify.php?code=$verification_code";

        // Preparar email
        $subject = "Verifica tu correo - NIS2";
        $message = "
            <p>Hola, <strong>$nombre_empresa</strong>!</p>
            <p>Por favor verifica tu correo haciendo clic en el siguiente enlace:</p>
            <p><a href='$verification_link'>$verification_link</a></p>
            <p>Gracias.</p>
        ";
        $headers = "From: no-reply@tusitio.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        // Enviar correo
        mail($email, $subject, $message, $headers);

        // Redirigir con mensaje de éxito
header("Location: ../../pages/registro.php?success=1");
        exit;
    } else {
        header("Location: ../../pages/registro.php?error=unknown");
        exit;
    }
} else {
    // Acceso directo no permitido
header("Location: ../../pages/registro.php?success=1");
    exit;
}
?>

