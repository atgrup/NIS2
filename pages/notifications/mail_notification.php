<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';

// Backwards-compat: if this file is required via enviar_correo.php shim,
// make sure functions are declared only once. (They already are declared below.)

// Reuse the central PHPMailer factory so all mail sending uses the same config
// and logging behavior. This file intentionally does NOT pull in Laravel or
// other frameworks; it uses PHPMailer via the project's composer autoload.
if (file_exists(__DIR__ . '/enviar_correo.php')) {
    require_once __DIR__ . '/enviar_correo.php';
} elseif (file_exists(__DIR__ . '/../notifications/enviar_correo.php')) {
    require_once __DIR__ . '/../notifications/enviar_correo.php';
}

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
function enviarCorreo($destinatario, $nombreDestinatario, $asunto, $cuerpoHtml, $cuerpoTextoPlano = '', array $attachments = [])
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

    // Pre-check: resolve host and attempt TCP connect to detect DNS/network issues early
    $host = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
    $port = (int)(getenv('MAIL_PORT') ?: 587);
    $secure = strtoupper(getenv('MAIL_SMTP_SECURE') ?: 'STARTTLS');
    $checkTimeout = 6; // seconds
    $dnsOk = false;
    try {
        // Try DNS resolution
        $resolved = gethostbynamel($host);
        if ($resolved && is_array($resolved) && count($resolved) > 0) {
            $dnsOk = true;
        }
    } catch (Throwable $t) {
        $dnsOk = false;
    }
    if (!$dnsOk) {
        notif_log("SMTP host no resolvible: {$host}. Comprueba MAIL_HOST en .env y la resolución DNS desde el servidor.");
        return false;
    }
    // Try opening TCP socket to host:port
    $addr = sprintf('%s:%d', $host, $port);
    $ctx = stream_context_create([]);
    $fp = @stream_socket_client($addr, $errno, $errstr, $checkTimeout, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        notif_log("No se puede conectar a SMTP {$addr}: ({$errno}) {$errstr}. Revisa firewall/puerto o usa el puerto correcto para MAIL_SMTP_SECURE.");
        return false;
    }
    // Close quick test socket
    fclose($fp);

    // Use the shared PHPMailer factory. getMailer() is defined in enviar_correo.php
    // which was required above. This ensures consistent SMTP options and logging.
    try {
        if (!function_exists('getMailer')) {
            // Fallback: instantiate directly if factory missing (shouldn't happen)
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            $secure = getenv('MAIL_SMTP_SECURE') ?: 'STARTTLS';
            if (strtoupper($secure) === 'SSL') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->Port = $port;
        } else {
            $mail = getMailer();
        }

        // Ensure no leftover recipients
        $mail->clearAllRecipients();
        $mail->addAddress($destinatario, $nombreDestinatario);

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHtml;
        $mail->AltBody = $cuerpoTextoPlano ?: strip_tags($cuerpoHtml);

        // Attach files if provided and exist
        foreach ($attachments as $att) {
            if (!$att) continue;
            // If relative path provided, try to resolve from project root
            $path = $att;
            if (!file_exists($path)) {
                $alt = __DIR__ . '/../../' . ltrim($att, '/\\');
                if (file_exists($alt)) $path = $alt;
            }
            if (file_exists($path)) {
                $mail->addAttachment($path);
            } else {
                notif_log("Adjunto no encontrado y se omitirá: {$att}");
            }
        }

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
function enqueueEmail(string $recipient, string $name, string $subject, string $bodyHtml, string $bodyText = '', ?string $logInfo = null, bool $includeLog = false, int $maxAttempts = 3, array $attachments = [])
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

    $sql = "INSERT INTO mail_queue (recipient_email, recipient_name, subject, body_html, body_text, log_info, include_log, max_attempts, attachments, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
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
    $attachmentsJson = !empty($attachments) ? json_encode(array_values($attachments)) : null;
    $stmt->bind_param('ssssssiss', $recipient, $name, $subject, $bodyHtml, $bodyText, $logInfo, $inc, $maxAttempts, $attachmentsJson);
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

        // Try to generate a signed token that ties to recipient email when possible.
        // Signing key taken from env TOKEN_SIGN_KEY. If not set, we fall back to MAIL_PASSWORD (less ideal).
        $signKey = getenv('TOKEN_SIGN_KEY') ?: getenv('MAIL_PASSWORD') ?: null;

        // Determine recipient email: prefer provided queueId -> mail_queue.recipient_email
        $recipientEmail = null;
        if ($queueId) {
            $q = $conexion->prepare("SELECT recipient_email FROM mail_queue WHERE id = ? LIMIT 1");
            if ($q) {
                $q->bind_param('i', $queueId);
                $q->execute();
                $r = $q->get_result();
                if ($r && $r->num_rows > 0) {
                    $rr = $r->fetch_assoc();
                    $recipientEmail = $rr['recipient_email'] ?? null;
                }
                $q->close();
            }
        }

        // If meta contains recipient email, try that
        if (!$recipientEmail && $meta) {
            if (is_string($meta)) {
                $decodedMeta = @json_decode($meta, true);
                if (is_array($decodedMeta) && !empty($decodedMeta['recipient_email'])) $recipientEmail = $decodedMeta['recipient_email'];
            } elseif (is_array($meta) && !empty($meta['recipient_email'])) {
                $recipientEmail = $meta['recipient_email'];
            }
        }

        // If still not found but caller provided 'bind_email' inside meta array, use it
        if (!$recipientEmail && is_array($meta) && !empty($meta['bind_email'])) {
            $recipientEmail = $meta['bind_email'];
        }

        // If sign key and recipient email available, return signed token
        if ($signKey && $recipientEmail) {
            $signed = signTokenForEmail($token, $recipientEmail, $signKey);
            return $signed;
        }

        // Fallback: return raw token
        return $token;
    } else {
        notif_log('createEmailActionToken: fallo al ejecutar insert: ' . $stmt->error);
        $stmt->close();
        return false;
    }
}


/**
 * Sign a token bound to an email address using HMAC-SHA256 and a secret key.
 * Returns a compact token: {rawtoken}.{base64url(hmac)}
 */
function signTokenForEmail(string $rawToken, string $email, string $secretKey): string
{
    $data = $rawToken . '|' . strtolower(trim($email));
    $hmac = hash_hmac('sha256', $data, $secretKey, true);
    $sig = rtrim(strtr(base64_encode($hmac), '+/', '-_'), '=');
    return $rawToken . '.' . $sig;
}

/**
 * Verify a signed token and return the raw token on success, or false on failure.
 * This function will attempt to find the expected recipient email from the DB when
 * given a signed token that corresponds to an email_actions row linked to a queue.
 */
function verifySignedToken(string $maybeSigned)
{
    global $conexion;
    if (strpos($maybeSigned, '.') === false) return $maybeSigned; // not signed
    list($raw, $sig) = explode('.', $maybeSigned, 2);
    // lookup action row
    $dbPath = __DIR__ . '/../../api/includes/conexion.php';
    if (!file_exists($dbPath)) {
        notif_log('verifySignedToken: conexion.php not found');
        return false;
    }
    require_once $dbPath;
    $stmt = $conexion->prepare("SELECT id, queue_id, meta FROM email_actions WHERE token = ? LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param('s', $raw);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) {
        $stmt->close();
        return false;
    }
    $row = $res->fetch_assoc();
    $stmt->close();

    $recipientEmail = null;
    if (!empty($row['queue_id'])) {
        $q = $conexion->prepare("SELECT recipient_email FROM mail_queue WHERE id = ? LIMIT 1");
        if ($q) {
            $q->bind_param('i', $row['queue_id']);
            $q->execute();
            $r = $q->get_result();
            if ($r && $r->num_rows > 0) {
                $rr = $r->fetch_assoc();
                $recipientEmail = $rr['recipient_email'] ?? null;
            }
            $q->close();
        }
    }
    if (!$recipientEmail && !empty($row['meta'])) {
        $m = @json_decode($row['meta'], true);
        if (is_array($m) && !empty($m['recipient_email'])) $recipientEmail = $m['recipient_email'];
    }

    $signKey = getenv('TOKEN_SIGN_KEY') ?: getenv('MAIL_PASSWORD') ?: null;
    if (!$signKey || !$recipientEmail) {
        notif_log('verifySignedToken: missing signKey or recipientEmail');
        return false;
    }

    $expected = signTokenForEmail($raw, $recipientEmail, $signKey);
    if (hash_equals($expected, $maybeSigned)) return $raw;
    return false;
}

/**
 * Verify a signed token when the recipient email is already known (e.g. registration verification).
 * Returns raw token on success, false on failure.
 */
function verifySignedTokenWithEmail(string $maybeSigned, string $email): bool|string
{
    if (strpos($maybeSigned, '.') === false) return $maybeSigned;
    list($raw, $sig) = explode('.', $maybeSigned, 2);
    $signKey = getenv('TOKEN_SIGN_KEY') ?: getenv('MAIL_PASSWORD') ?: null;
    if (!$signKey) return false;
    $expected = signTokenForEmail($raw, $email, $signKey);
    if (hash_equals($expected, $maybeSigned)) return $raw;
    return false;
}

/**
 * Render simple template from pages/notifications/templates/{name}.html
 * Replaces {{key}} placeholders with values from $vars array.
 */
function renderEmailTemplate(string $templateName, array $vars = []): string
{
    $tplPath = __DIR__ . '/templates/' . $templateName . '.html';
    if (!file_exists($tplPath)) {
        notif_log("renderEmailTemplate: plantilla no encontrada: {$tplPath}");
        return '';
    }
    $content = file_get_contents($tplPath);
    foreach ($vars as $k => $v) {
        $placeholder = '{{' . $k . '}}';
        $content = str_replace($placeholder, htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $content);
    }
    // Remove unreplaced placeholders
    $content = preg_replace('/{{[^}]+}}/', '', $content);
    return $content;
}

