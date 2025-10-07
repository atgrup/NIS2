```php
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

    // Verificar si el correo ya existe en usuarios
    $stmt = $conexion->prepare("SELECT id_usuarios FROM usuarios WHERE correo = ?");
    if (!$stmt) {
        die("Error preparando la consulta de usuarios: " . $conexion->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        header("Location: ../../pages/registro.php?error=email");
        exit;
    }

    // Generar hash de la contraseña y token de verificación
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $verification_code = bin2hex(random_bytes(16));
    $verificado = 0;
    $tipo_usuario_id = 2; // Proveedor

    // Insertar en usuarios
    $stmt = $conexion->prepare("
        INSERT INTO usuarios (correo, password, tipo_usuario_id, verificado, token_verificacion)
        VALUES (?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        die("Error preparando inserción en usuarios: " . $conexion->error);
    }
    $stmt->bind_param("ssiss", $email, $password_hash, $tipo_usuario_id, $verificado, $verification_code);

    if ($stmt->execute()) {
        // Obtener el id_usuarios recién insertado
        $usuario_id = $conexion->insert_id;

        // Insertar en proveedores
        $estado = 'pendiente';
        $stmt2 = $conexion->prepare("
            INSERT INTO proveedores (usuario_id, nombre_empresa, estado)
            VALUES (?, ?, ?)
        ");
        if (!$stmt2) {
            die("Error preparando inserción en proveedores: " . $conexion->error);
        }
        $stmt2->bind_param("iss", $usuario_id, $nombre_empresa, $estado);

        if ($stmt2->execute()) {
            // Enviar correo de verificación (opcional durante pruebas)
            $verification_link = "http://localhost/NIS2/api/auth/verify.php?code=$verification_code";
            $subject = "Verifica tu correo - NIS2";
            $message = "
                <p>Hola, <strong>$nombre_empresa</strong>!</p>
                <p>Por favor verifica tu cuenta haciendo clic en el siguiente enlace:</p>
                <p><a href='$verification_link'>$verification_link</a></p>
                <p>Gracias.</p>
            ";
            $headers = "From: no-reply@tusitio.com\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            // mail($email, $subject, $message, $headers);

            // Redirigir con token para mostrar modal
            header("Location: ../../pages/registro.php?success=1&token=$verification_code");
            exit;
        } else {
            die("Error al insertar en proveedores: " . $stmt2->error);
        }
    } else {
        die("Error al insertar en usuarios: " . $stmt->error);
    }

} else {
    header("Location: ../../pages/registro.php");
    exit;
}
?>
