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

/**
 * Enqueue a single email into mail_queue table.
 * Returns inserted queue id on success or false on failure.
 */
function enqueueEmail(string $recipient, string $name, string $subject, string $bodyHtml, string $bodyText = '', ?string $logInfo = null, bool $includeLog = false, int $maxAttempts = 3)
{
    // Basic validation
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        notif_log("enqueueEmail: dirección inválida {$recipient}");
        return false;
    }

    // include DB connection
    $dbPath = __DIR__ . '/../../api/includes/conexion.php';
    if (!file_exists($dbPath)) {
        notif_log('enqueueEmail: no se encontró conexion.php en expected path ' . $dbPath);
        return false;
    }
    require_once $dbPath; // defines $conexion

    $sql = "INSERT INTO mail_queue (recipient_email, recipient_name, subject, body_html, body_text, log_info, include_log, max_attempts, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    if (!isset($conexion) || !$conexion) {
        notif_log('enqueueEmail: conexion DB no definida');
        return false;
    }
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
    $err = (is_object($conexion) && isset($conexion->error)) ? $conexion->error : 'unknown';
        notif_log('enqueueEmail: fallo al preparar consulta: ' . $err);
        return false;
    }
    $inc = $includeLog ? 1 : 0;
    $stmt->bind_param('ssssssii', $recipient, $name, $subject, $bodyHtml, $bodyText, $logInfo, $inc, $maxAttempts);
    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        $stmt->close();
        notif_log("Encolado correo a {$recipient} (queue id: {$id}) asunto: {$subject}");
        return $id;
    } else {
        notif_log('enqueueEmail: fallo al ejecutar insertar: ' . $stmt->error);
        $stmt->close();
        return false;
    }
}

/**
 * Record a mail_logs row for an attempt/result.
 */
function recordMailLog(?int $queueId, string $recipient, string $subject, string $status = 'ok', ?string $error = null)
{
    $dbPath = __DIR__ . '/../../api/includes/conexion.php';
    if (!file_exists($dbPath)) return false;
    require_once $dbPath;
    // ensure $conexion is available in this function scope
    if (!isset($conexion) || !$conexion) {
        // try to bring global
        global $conexion;
    }
    if (!isset($conexion) || !$conexion) return false;
    $sql = "INSERT INTO mail_logs (queue_id, recipient, subject, status, error, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param('issss', $queueId, $recipient, $subject, $status, $error);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Genera un token aleatorio seguro.
 */
function generateToken(int $length = 48): string
{
    return bin2hex(random_bytes((int)ceil($length / 2)));
}

/**
 * Crea una entrada en email_actions vinculada a una cola (queue_id opcional).
 * $meta puede ser un array o string (si es array se json_encodea).
 */
function createEmailActionToken(?int $queueId, string $action, ?int $archivoId = null, $meta = null, int $expiresHours = 72)
{
    $dbPath = __DIR__ . '/../../api/includes/conexion.php';
    if (!file_exists($dbPath)) {
        notif_log('createEmailActionToken: conexion.php no encontrado');
        return false;
    }
    require_once $dbPath; // defines $conexion
    if (!isset($conexion) || !$conexion) {
        notif_log('createEmailActionToken: conexion DB no definida');
        return false;
    }

    $token = generateToken(64);
    $metaStr = null;
    if (is_array($meta)) $metaStr = json_encode($meta);
    elseif (is_string($meta)) $metaStr = $meta;

    $expires_at = null;
    if ($expiresHours > 0) {
        $expires_at = date('Y-m-d H:i:s', time() + ($expiresHours * 3600));
    }

    $sql = "INSERT INTO email_actions (token, queue_id, action, archivo_id, meta, expires_at, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        $err = (is_object($conexion) && property_exists($conexion, 'error')) ? $conexion->error : 'unknown';
        notif_log('createEmailActionToken: fallo al preparar stmt: ' . $err);
        return false;
    }
    // queue_id may be null
    $q = $queueId;
    $stmt->bind_param('sissss', $token, $q, $action, $archivoId, $metaStr, $expires_at);
    if ($stmt->execute()) {
        $stmt->close();
        notif_log("createEmailActionToken: token creado={$token} action={$action} archivo_id={$archivoId} queue_id={$queueId}");
        return $token;
    } else {
        notif_log('createEmailActionToken: fallo al ejecutar insert: ' . $stmt->error);
        $stmt->close();
        return false;
    }
}
