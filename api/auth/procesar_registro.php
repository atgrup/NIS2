<?php
require '../includes/conexion.php'; // Conexión a la base de datos

// Solo procesa si la petición es POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoge datos del formulario
    $correo = $_POST['email'];
    $password = $_POST['password'];
    $repeat = $_POST['repeat-password'];
    $nombre_empresa = $_POST['nombre_empresa'];
    $pais_origen = $_POST['pais_origen'] ?? '';

    $tipo_usuario_id = 2; // ID fijo para "PROVEEDOR"


    $email = $_POST['email'] ?? '';
    $nombre_empresa = $_POST['nombre_empresa'] ?? '';
    $password = trim($_POST['password'] ?? '');
    $repeat_password = trim($_POST['repeat_password'] ?? '');
        $pais_origen = trim($_POST['pais_origen'] ?? '');

var_dump($password, $repeat_password);
if ($password === $repeat_password) {
    echo "Las contraseñas coinciden ✅";
} else {
    echo "Las contraseñas NO coinciden ❌";
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
            // Enviar correo de verificación usando el sistema de notificaciones (enqueue)
            $verification_link = (getenv('APP_URL') ? rtrim(getenv('APP_URL'), '/') : 'http://localhost') . "/api/auth/verify.php?code=$verification_code";
            $subject = "Verifica tu correo - NIS2";
            // Renderizar plantilla y encolar con helper
            require_once __DIR__ . '/../../pages/notifications/mail_notification.php';
            $body = renderEmailTemplate('verification', ['nombre' => $nombre_empresa, 'link' => $verification_link]);
            enqueueEmail($email, $nombre_empresa, $subject, $body, '', 'Verificación de cuenta', false);

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