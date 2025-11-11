<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';

// ---------------------------
// Cargar variables del .env (raíz del proyecto)
// ---------------------------
function load_dotenv(string $path)
{
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        // Quitar comillas si las hay
        if ((substr($value,0,1) === '"' && substr($value,-1) === '"') || (substr($value,0,1) === "'" && substr($value,-1) === "'")) {
            $value = substr($value,1,-1);
        }
        if (getenv($name) === false) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
        }
    }
}

load_dotenv(__DIR__ . '/../../.env');

// ---------------------------
// Helper de logging simple
// ---------------------------
function notif_log(string $message)
{
    $logPath = getenv('MAIL_LOG_PATH') ?: __DIR__ . '/../../logs/mail.log';
    $line = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message);
    @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
}

// ---------------------------
// Función genérica para enviar correo
// ---------------------------
function enviarCorreo($destinatario, $nombreDestinatario, $asunto, $cuerpoHtml, $cuerpoTextoPlano = '')
{
    // Validación básica de email
    if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
        notif_log("Dirección inválida: {$destinatario}");
        return false;
    }

    // Evitar enviar si las credenciales no están configuradas (valores por defecto)
    $username = getenv('MAIL_USERNAME') ?: '';
    $password = getenv('MAIL_PASSWORD') ?: '';
    if ($username === '' || stripos($username, 'tu_correo') !== false || $password === '' || stripos($password, 'tu_contraseña') !== false) {
        notif_log('Credenciales SMTP no configuradas. Envío cancelado.');
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $secure = getenv('MAIL_SMTP_SECURE') ?: 'STARTTLS';
        if (strtoupper($secure) === 'SSL') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->Port = (int)(getenv('MAIL_PORT') ?: 587);

        // Debug opcional: redirigir al log si se habilita
        $smtpDebug = (int)(getenv('MAIL_SMTP_DEBUG') ?: 0);
        $mail->SMTPDebug = $smtpDebug;
        if ($smtpDebug > 0) {
            $mail->Debugoutput = function($str, $level) {
                notif_log("SMTP debug (level {$level}): {$str}");
            };
        }

        $from = getenv('MAIL_FROM') ?: $username;
        $fromName = getenv('MAIL_FROM_NAME') ?: 'NIS2';
        $mail->setFrom($from, $fromName);
        $mail->addAddress($destinatario, $nombreDestinatario);

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHtml;
        $mail->AltBody = $cuerpoTextoPlano ?: strip_tags($cuerpoHtml);

        $mail->send();
        notif_log("Correo enviado a {$destinatario} asunto: {$asunto}");
        return true;
    } catch (Exception $e) {
        $err = $mail->ErrorInfo ?: $e->getMessage();
        notif_log("Error al enviar correo a {$destinatario}: {$err}");
        return false;
    }
}

/**
 * Enviar a múltiples destinatarios.
 * $recipients puede ser:
 * - string email
 * - array de emails ['a@b.com','c@d.com']
 * - array asociativo [['email'=>'a@b.com','name'=>'A'], ...]
 * $logInfo: texto adicional que se escribirá en el log y opcionalmente se incorporará al cuerpo del correo
 */
function enviarCorreos(array|string $recipients, string $asunto, string $cuerpoHtml, string $cuerpoTextoPlano = '', ?string $logInfo = null, bool $includeLog = false): array
{
    // Normalizar lista
    $list = [];
    if (is_string($recipients)) {
        $list[] = ['email' => $recipients, 'name' => ''];
    } else {
        foreach ($recipients as $r) {
            if (is_string($r)) {
                $list[] = ['email' => $r, 'name' => ''];
            } elseif (is_array($r)) {
                $list[] = ['email' => $r['email'] ?? '', 'name' => $r['name'] ?? ''];
            }
        }
    }

    $results = [];
    // Si se solicita incluir el contenido del log, leerlo
    $logPath = getenv('MAIL_LOG_PATH') ?: __DIR__ . '/../../logs/mail.log';
    $logContent = '';
    if ($includeLog && file_exists($logPath)) {
        $logContent = "\n\n-- Logs recientes --\n" . file_get_contents($logPath);
    }

    foreach ($list as $r) {
        $email = $r['email'];
        $name = $r['name'];
        $body = $cuerpoHtml;
        if ($logInfo) {
            $body .= "\n\n" . nl2br(htmlspecialchars($logInfo));
        }
        if ($includeLog && $logContent) {
            $body .= "\n\n<pre>" . htmlspecialchars($logContent) . "</pre>";
        }

        $ok = enviarCorreo($email, $name, $asunto, $body, $cuerpoTextoPlano);
        $results[$email] = $ok ? 'ok' : 'error';
        notif_log(sprintf('Resultado envio a %s: %s (subject: %s)', $email, $ok ? 'OK' : 'ERROR', $asunto));
    }

    return $results;
}

/**
 * Helper para obtener input desde CLI o HTTP.
 * Si se pasa JSON (CLI arg 1 or POST field json) lo decodifica.
 */
function read_input(): array
{
    // CLI: primer argumento JSON
    if (php_sapi_name() === 'cli') {
        global $argv;
        if (isset($argv[1])) {
            $raw = $argv[1];
            $data = json_decode($raw, true);
            if (is_array($data)) return $data;
        }
        // Fallback: empty
        return [];
    }

    // Web: prefer POST, luego GET, luego raw json body
    if (!empty($_POST)) return $_POST;
    if (!empty($_GET)) return $_GET;
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
