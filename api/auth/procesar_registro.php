<?php
require '../includes/conexion.php'; // Conexión a la base de datos

// Solo procesa si la petición es POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoge datos del formulario
    $correo = $_POST['email'];
    $password = $_POST['password'];
    $repeat = $_POST['repeat_password'];
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

    // Helper: derive company name from email domain (take second-level domain, e.g. user@sub.domain.com -> domain)
    function companyFromEmail(string $email): string {
        $email = trim(strtolower($email));
        $at = strpos($email, '@');
        if ($at === false) return '';
        $host = substr($email, $at + 1);
        $host = preg_replace('/[^a-z0-9.\-]/', '', $host);
        $parts = explode('.', $host);
        $n = count($parts);
        if ($n === 0) return '';
        if ($n === 1) {
            $sld = $parts[0];
        } else {
            // take second-level domain (the label before the TLD)
            $sld = $parts[$n - 2];
        }
        // remove common tlds/labels like 'www'
        if (in_array($sld, ['www'])) {
            $sld = $parts[0] ?? $sld;
        }
        // sanitize: remove non alnum, replace hyphens/underscores with space
        $sld = preg_replace('/[_\-]+/', ' ', $sld);
        $sld = preg_replace('/[^a-z0-9 ]+/', '', $sld);
        $sld = trim($sld);
        // capitalize words
        $sld = mb_convert_case($sld, MB_CASE_TITLE, 'UTF-8');
        return $sld;
    }

    // If nombre_empresa not provided, derive it from the email domain
    if (empty(trim($nombre_empresa))) {
        $nombre_empresa = companyFromEmail($email);
    }

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
            // Sign verification code to bind it to recipient email if signing key available
            $signKey = getenv('TOKEN_SIGN_KEY') ?: getenv('MAIL_PASSWORD') ?: null;
            $signed_code = $verification_code;
            if ($signKey && !empty($email)) {
                // load mail helper functions (shim)
                require_once __DIR__ . '/../../pages/notifications/enviar_correo.php';
                if (function_exists('signTokenForEmail')) {
                    $signed_code = signTokenForEmail($verification_code, $email, $signKey);
                }
            }

            $verification_link = (getenv('APP_URL') ? rtrim(getenv('APP_URL'), '/') : 'http://localhost/NIS2/') . "/api/auth/verify.php?code=" . rawurlencode($signed_code);
            $subject = "Verifica tu correo - NIS2";
            // Renderizar plantilla y encolar con helper
            require_once __DIR__ . '/../../pages/notifications/enviar_correo.php';
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