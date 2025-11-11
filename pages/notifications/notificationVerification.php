<?php
/**
 * notificationVerification.php
 * Uso flexible: puede llamarse desde CLI o web con parámetros para enviar/enviar en cola
 * Parámetros aceptados: email, name, token, method (enqueue|direct)
 */
require_once __DIR__ . '/mail_notification.php';

$input = read_input(); // soporta CLI JSON, POST o GET

$destinatario = trim($input['email'] ?? $input['destinatario'] ?? '');
$nombre = trim($input['name'] ?? $input['nombre'] ?? ($destinatario ? explode('@', $destinatario)[0] : 'Usuario'));
$token = trim($input['token'] ?? $input['codigo'] ?? bin2hex(random_bytes(8)));
$method = strtolower(trim($input['method'] ?? $input['metodo'] ?? 'enqueue'));

// Construir link de verificación usando APP_URL si está disponible
$appUrl = getenv('APP_URL') ? rtrim(getenv('APP_URL'), '/') : '';
if ($appUrl) {
    $link = $appUrl . "/api/auth/verify.php?code=" . rawurlencode($token);
} else {
    // fallback a localhost
    $link = "http://localhost/NIS2/api/auth/verify.php?code=" . rawurlencode($token);
}

$asunto = "Verifica tu correo - NIS2";
$html = "<h2>Bienvenido, " . htmlspecialchars($nombre) . "</h2>\n" .
        "<p>Por favor verifica tu correo haciendo clic en el siguiente enlace:</p>\n" .
        "<p><a href='" . htmlspecialchars($link) . "'>Verificar mi correo</a></p>";

if (!$destinatario || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
    echo "Dirección de correo inválida o no proporcionada. Pasa 'email' como parámetro.\n";
    exit(1);
}

if ($method === 'direct') {
    // intentamos enviar directamente (sin encolar)
    $ok = enviarCorreo($destinatario, $nombre, $asunto, $html, 'Por favor verifica tu cuenta.');
    if ($ok) {
        echo "Correo de verificación enviado directamente a {$destinatario} ✅\n";
        exit(0);
    } else {
        echo "Error al enviar el correo directamente. Revisa logs.\n";
        exit(2);
    }
} else {
    // por defecto, encolamos usando enqueueEmail para procesarlo asíncronamente
    $queueId = enqueueEmail($destinatario, $nombre, $asunto, $html, 'Por favor verifica tu cuenta.', 'Verificación de cuenta', false);
    if ($queueId) {
        echo "Correo de verificación encolado (queue id: {$queueId}) ✅\n";
        echo "Token: {$token}\n";
        exit(0);
    } else {
        echo "Error al encolar el correo. Revisa logs y DB.\n";
        exit(3);
    }
}

