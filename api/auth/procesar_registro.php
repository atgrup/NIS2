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

    // Validar que no exista ya la empresa
    $stmt = $conexion->prepare("SELECT id FROM proveedores WHERE nombre_empresa = ?");
    if (!$stmt) {
        die("Error al preparar la consulta: " . $conexion->error);
    }
    $stmt->bind_param("s", $nombre_empresa);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        header("Location: ../../pages/registro.php?error=empresa");
        exit;
    }

    // Generar token de verificación
    $verification_code = bin2hex(random_bytes(16));

    // Usuario fijo (2 = proveedor) y estado por defecto
    $usuario_id = 2;
    $estado = 'pendiente';

    // Insertar en la tabla proveedores
    $stmt = $conexion->prepare("
        INSERT INTO proveedores (usuario_id, nombre_empresa, estado)
        VALUES (?, ?, ?)
    ");
    if (!$stmt) {
        die("Error al preparar la inserción en proveedores: " . $conexion->error);
    }
    $stmt->bind_param("iss", $usuario_id, $nombre_empresa, $estado);

    if ($stmt->execute()) {
        // Enviar correo de verificación simulado
        $verification_link = "http://localhost/NIS2/api/auth/verify.php?code=$verification_code";
        $subject = "Verifica tu empresa - NIS2";
        $message = "
            <p>Hola, <strong>$nombre_empresa</strong>!</p>
            <p>Por favor verifica tu empresa haciendo clic en el siguiente enlace:</p>
            <p><a href='$verification_link'>$verification_link</a></p>
            <p>Gracias.</p>
        ";
        $headers = "From: no-reply@tusitio.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        // Desactiva esta línea en pruebas si no tienes servidor SMTP
        // mail($email, $subject, $message, $headers);

        // Redirigir para mostrar el modal con el token
        header("Location: ../../pages/registro.php?success=1&token=$verification_code");
        exit;
    } else {
        die("Error al ejecutar la inserción en proveedores: " . $stmt->error);
    }

} else {
    // Si accede directamente sin POST
    header("Location: ../../pages/registro.php");
    exit;
}
?>
```
