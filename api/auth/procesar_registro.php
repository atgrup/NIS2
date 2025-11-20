<?php
require '../includes/conexion.php'; // Conexión a la base de datos

// Solo procesa si la petición es POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Recolección y limpieza de variables ---
    $email = trim($_POST['email'] ?? '');
    $nombre_empresa = trim($_POST['nombre_empresa'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $repeat_password = trim($_POST['repeat_password'] ?? '');
    $pais_origen = trim($_POST['pais_origen'] ?? '');
    $tipo_usuario_id = 2; // ID fijo para "PROVEEDOR"

    // --- 1. Validación: Contraseñas coinciden ---
    if ($password !== $repeat_password) {
        header("Location: ../../pages/revisa-correo.php?error=pass");
        exit;
    }

    // --- 2. Validación: Email ya existe ---
    $stmt = $conexion->prepare("SELECT id_usuarios FROM usuarios WHERE correo = ?");
    if (!$stmt) {
        header("Location: ../../pages/revisa-correo.php?error=db1"); // Error DB 1
        exit;
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        header("Location: ../../pages/revisa-correo.php?error=email");
        exit;
    }

    // --- 3. Preparación de datos ---
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $verification_code = bin2hex(random_bytes(16));
    $verificado = 0;

    // Helper: derive company name from email domain
    function companyFromEmail(string $email): string {
        $email = trim(strtolower($email)); $at = strpos($email, '@'); if ($at === false) return '';
        $host = substr($email, $at + 1); $host = preg_replace('/[^a-z0-9.\-]/', '', $host);
        $parts = explode('.', $host); $n = count($parts); if ($n === 0) return '';
        if ($n === 1) { $sld = $parts[0]; } else { $sld = $parts[$n - 2]; }
        if (in_array($sld, ['www'])) { $sld = $parts[0] ?? $sld; }
        $sld = preg_replace('/[_\-]+/', ' ', $sld); $sld = preg_replace('/[^a-z0-9 ]+/', '', $sld);
        $sld = trim($sld); $sld = mb_convert_case($sld, MB_CASE_TITLE, 'UTF-8');
        return $sld;
    }

    if (empty(trim($nombre_empresa))) {
        $nombre_empresa = companyFromEmail($email);
    }

    // --- 4. Inserción en 'usuarios' ---
    $stmt = $conexion->prepare("
        INSERT INTO usuarios (correo, password, tipo_usuario_id, verificado, token_verificacion)
        VALUES (?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        header("Location: ../../pages/revisa-correo.php?error=db2"); // Error DB 2
        exit;
    }
    $stmt->bind_param("ssiss", $email, $password_hash, $tipo_usuario_id, $verificado, $verification_code);

    if ($stmt->execute()) {
        $usuario_id = $conexion->insert_id;

        // --- 5. Inserción en 'proveedores' ---
        $estado = 'pendiente';
        $stmt2 = $conexion->prepare("
            INSERT INTO proveedores (usuario_id, nombre_empresa, estado)
            VALUES (?, ?, ?)
        ");
        if (!$stmt2) {
            header("Location: ../../pages/revisa-correo.php?error=db3"); // Error DB 3
            exit;
        }
        $stmt2->bind_param("iss", $usuario_id, $nombre_empresa, $estado);

        if ($stmt2->execute()) {
            
            // --- 6. Envío de correo ---
            $signKey = getenv('TOKEN_SIGN_KEY') ?: getenv('MAIL_PASSWORD') ?: null;
            $signed_code = $verification_code;
            if ($signKey && !empty($email)) {
                require_once __DIR__ . '/../../pages/notifications/enviar_correo.php';
                if (function_exists('signTokenForEmail')) {
                    $signed_code = signTokenForEmail($verification_code, $email, $signKey);
                }
            }

            $verification_link = (getenv('APP_URL') ? rtrim(getenv('APP_URL'), '/') : 'http://localhost/NIS2/') . "/api/auth/verify.php?code=" . rawurlencode($signed_code);
            $subject = "Verifica tu correo - NIS2";
            
            require_once __DIR__ . '/../../pages/notifications/enviar_correo.php';
            $body = renderEmailTemplate('verification', ['nombre' => $nombre_empresa, 'link' => $verification_link]);
            
            // --- ¡¡AQUÍ ESTÁ LA PRUEBA!! ---
            // He desactivado (comentado) la línea que envía el correo.
            // enqueueEmail($email, $nombre_empresa, $subject, $body, '', 'Verificación de cuenta', false);

            // --- 7. ÉXITO (Redirige a la nueva página) ---
            header("Location: ../../pages/revisa-correo.php?success=1&token=$verification_code");
            exit;

        } else {
            header("Location: ../../pages/revisa-correo.php?error=db4"); // Error DB 4
            exit;
        }
    } else {
        header("Location: ../../pages/revisa-correo.php?error=db5"); // Error DB 5
        exit;
    }

} else {
    // Si no es POST, redirige fuera.
    header("Location: ../../pages/login.php");
    exit;
}
?>