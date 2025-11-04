<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

// Simple loader de .env (si existe .env en la raíz del proyecto) — no requiere dependencia externa.
function load_dotenv(string $path)
{
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        // Remove surrounding quotes
        if ((substr($value,0,1) === '"' && substr($value,-1) === '"') || (substr($value,0,1) === "'" && substr($value,-1) === "'")) {
            $value = substr($value,1,-1);
        }
        // Only set if not already in environment
        if (getenv($name) === false) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
        }
    }
}

// Cargar .env en la raíz del proyecto si existe
load_dotenv(__DIR__ . '/../.env');

// Helper para obtener variables con fallback
function env(string $key, $default = null)
{
    $val = getenv($key);
    if ($val === false) return $default;
    return $val;
}

$mail = new PHPMailer(true);

// Preparar logging
$logPath = env('MAIL_LOG_PATH', __DIR__ . '/../logs/mail.log');
// Asegurar carpeta
$logDir = dirname($logPath);
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

// Función para anexar al log con prefijo de fecha
function mail_log(string $message) {
    global $logPath;
    $line = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message);
    @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
}

try {
    // Validación temprana: si las credenciales no están configuradas o todavía son
    // los valores de ejemplo, no intentar enviar (evita el mensaje "Could not authenticate").
    $configuredUser = env('MAIL_USERNAME', '');
    $configuredPass = env('MAIL_PASSWORD', '');
    $isPlaceholderUser = $configuredUser === '' || stripos($configuredUser, 'tu_correo') !== false;
    $isPlaceholderPass = $configuredPass === '' || stripos($configuredPass, 'tu_contraseña') !== false || stripos($configuredPass, 'app_password') !== false;
    if ($isPlaceholderUser || $isPlaceholderPass) {
        $msg = "No se han configurado las credenciales SMTP en .env. Copia .env.example a .env y rellena MAIL_USERNAME y MAIL_PASSWORD. Envío cancelado.";
        mail_log($msg);
        if (PHP_SAPI === 'cli') {
            echo $msg . "\n";
        } else {
            echo htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        return;
    }
    // Configuración del servidor SMTP
    $mail->isSMTP();
    // Nivel de debug desde .env (0 por defecto)
    $smtpDebug = (int) env('MAIL_SMTP_DEBUG', 0);
    $mail->SMTPDebug = $smtpDebug;
    // Si se solicita debug, redirigir la salida de debug al archivo de log
    if ($smtpDebug > 0) {
        $mail->Debugoutput = function($str, $level) {
            // level puede usarse si se quiere filtrar
            mail_log("SMTP-debug (level {$level}): {$str}");
        };
    }

    $mail->Host = env('MAIL_HOST', 'smtp.gmail.com');
    $mail->SMTPAuth = true;
    $mail->Username = env('MAIL_USERNAME', 'tu_correo@gmail.com');
    $mail->Password = env('MAIL_PASSWORD', 'tu_contraseña_o_app_password');
    // Selección de encriptación
    $secure = env('MAIL_SMTP_SECURE', 'STARTTLS');
    if (strtoupper($secure) === 'SSL') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }
    $mail->Port = (int) env('MAIL_PORT', 587);

    // Configuración del correo
    $from = env('MAIL_FROM', 'tu_correo@gmail.com');
    $fromName = env('MAIL_FROM_NAME', 'Notificador NIS2');
    $mail->setFrom($from, $fromName);

    $to = env('MAIL_TO', 'destinatario@empresa.com');
    $toName = env('MAIL_TO_NAME', 'Destinatario');
    $mail->addAddress($to, $toName);

    $mail->isHTML(true);
    $mail->Subject = 'Estado de la documentación';
    $mail->Body    = 'La documentación se encuentra en estado <b>APROBADA</b>.';
    $mail->AltBody = 'La documentación se encuentra en estado APROBADA.';

    $mail->send();
    // Registrar en log
    mail_log('Correo enviado correctamente a ' . $to);
    // Mostrar salida limpia según contexto (CLI o web)
    if (PHP_SAPI === 'cli') {
        echo "Correo enviado correctamente.\n";
    } else {
        echo 'Correo enviado correctamente.';
    }
} catch (Exception $e) {
    // Mostrar errores de forma segura y legible y escribir en log
    $errorMsg = $e->getMessage() ?: $mail->ErrorInfo;
    mail_log('Error al enviar el correo: ' . $errorMsg);
    if (PHP_SAPI === 'cli') {
        // Mostrar el error en stdout para evitar que PowerShell lo pinte en rojo
        echo "Error al enviar el correo: {$errorMsg}\n";
    } else {
        echo 'Error al enviar el correo: ' . htmlspecialchars($errorMsg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
