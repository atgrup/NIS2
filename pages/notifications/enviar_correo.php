<?php
/**
 * Central PHPMailer sender and compatibility shim.
 *
 * This file exposes two conveniences:
 *  - getMailer(): returns a pre-configured PHPMailer instance
 *  - enviarCorreoVerificacion($to, $token): sends a verification email (HTML)
 *
 * Other files should `require_once __DIR__ . '/enviar_correo.php'` and call
 * `getMailer()` or `enviarCorreoVerificacion()` instead of creating their own PHPMailer.
 *
 * Configuration is read from environment variables (preferred) or _safe_ defaults.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Composer autoload (project uses composer/vendor)
$vendor = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($vendor)) {
	// try project root vendor
	$vendor = __DIR__ . '/../../../vendor/autoload.php';
}
if (!file_exists($vendor)) {
	http_response_code(500);
	echo "Error: Composer autoload not found. Run 'composer install' in the project root.\n";
	exit;
}
require_once $vendor;

// Prefer a PHP config file over .env for simplicity in non-Laravel projects.
// If config_mail.php exists it should return an array with mail settings.
$mailConfig = [];
$configFile = __DIR__ . '/config_mail.php';
if (file_exists($configFile)) {
	$mailConfig = require $configFile;
} else {
	// Fallback: load .env if present for backward compatibility
	if (file_exists(__DIR__ . '/../../.env')) {
		$lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($lines as $line) {
			$line = trim($line);
			if ($line === '' || $line[0] === '#') continue;
			[$k, $v] = array_map('trim', explode('=', $line, 2) + [1 => '']);
			$v = trim($v, "\"'");
			if (getenv($k) === false) putenv("$k=$v");
		}
	}
}

/**
 * Create and return a configured PHPMailer instance.
 * Keep debug output in a log file to avoid red text in PowerShell.
 */
function getMailer(): PHPMailer
{
	$mail = new PHPMailer(true);

	// make sure $mailConfig from file-scope is visible here
	global $mailConfig;
	if (!isset($mailConfig) || !is_array($mailConfig)) {
		$configFile = __DIR__ . '/config_mail.php';
		if (file_exists($configFile)) {
			$mailConfig = require $configFile;
		} else {
			$mailConfig = [];
		}
	}

	// Read config from PHP config file if available, otherwise fall back to env
	$cfg = is_array($mailConfig) ? $mailConfig : [];
	$host = $cfg['host'] ?? getenv('MAIL_HOST') ?: 'webmail.atgroup.es';
	$port = (int)($cfg['port'] ?? getenv('MAIL_PORT') ?: 465);
	$username = $cfg['username'] ?? getenv('MAIL_USERNAME') ?: 'mandreo@atgroup.es';
	$password = $cfg['password'] ?? getenv('MAIL_PASSWORD') ?: '&togk6Fy^5se';
	$from = $cfg['from'] ?? getenv('MAIL_FROM') ?: $username;
	$fromName = $cfg['from_name'] ?? getenv('MAIL_FROM_NAME') ?: 'Notificador NIS2';
	$secure = $cfg['secure'] ?? getenv('MAIL_SMTP_SECURE') ?? getenv('MAIL_SECURE') ?? 'ssl';
	$smtpDebug = (int)($cfg['debug'] ?? getenv('MAIL_SMTP_DEBUG') ?? getenv('MAIL_DEBUG') ?? 0);

	// Configure SMTP
	$mail->isSMTP();
	$mail->SMTPDebug = $smtpDebug;
	$mail->Host = $host;
	$mail->SMTPAuth = true;
	$mail->Username = $username;
	$mail->Password = $password;
	if (strtolower($secure) === 'ssl') {
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
	} else {
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
	}
	$mail->Port = $port;

	// From address
	$mail->setFrom($from, $fromName);

	// Avoid printing debug to console; write PHPMailer debug to logs/mail.log
	$logDir = __DIR__ . '/../../logs';
	if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
	$debugFile = $logDir . '/mail.log';
	$mail->Debugoutput = function ($str, $level) use ($debugFile) {
		$ts = date('Y-m-d H:i:s');
		file_put_contents($debugFile, "[$ts] (level $level) $str\n", FILE_APPEND | LOCK_EX);
	};
	// set debug level 0 in normal usage; callers may set higher temporarily
	$mail->SMTPDebug = 0;

	// Recommended safety flags
	$mail->SMTPOptions = [
		'ssl' => [
			'verify_peer' => false,
			'verify_peer_name' => false,
			'allow_self_signed' => true,
		],
	];

	return $mail;
}

/**
 * Send a verification email to $emailDestino with a provided token.
 * Returns true on success, false on failure. Errors are logged to logs/mail.log.
 */
function enviarCorreoVerificacion(string $emailDestino, string $token): bool
{
	$mail = getMailer();
	try {
		$mail->clearAllRecipients();
		$mail->addAddress($emailDestino);
		$mail->isHTML(true);
		$subject = 'Verifica tu correo electrónico';
		$mail->Subject = $subject;

		$appUrl = getenv('APP_URL') ?: 'http://localhost/NIS2';
		$verifyUrl = rtrim($appUrl, '/') . '/api/auth/verify.php?token=' . urlencode($token);

		$body = "<h3>Verifica tu dirección de correo</h3>" .
			"<p>Por favor, haz clic en el siguiente enlace para verificar tu cuenta:</p>" .
			"<p><a href='{$verifyUrl}'>Verificar mi cuenta</a></p>" .
			"<p>Si tú no solicitaste este registro, ignora este correo.</p>";

		$mail->Body = $body;

		$mail->send();
		return true;
	} catch (Exception $e) {
		// Log error; avoid echoing to browser/cli
		$err = sprintf("Error al enviar el correo a %s: %s", $emailDestino, $mail->ErrorInfo ?: $e->getMessage());
		$logFile = __DIR__ . '/../../logs/mail.log';
		$ts = date('Y-m-d H:i:s');
		file_put_contents($logFile, "[$ts] $err\n", FILE_APPEND | LOCK_EX);
		return false;
	}
}

// Backwards-compatible alias used elsewhere in repo
if (!function_exists('enviarCorreo')) {
	function enviarCorreo(string $to, string $subject, string $htmlBody): bool
	{
		$mail = getMailer();
		try {
			$mail->clearAllRecipients();
			$mail->addAddress($to);
			$mail->isHTML(true);
			$mail->Subject = $subject;
			$mail->Body = $htmlBody;
			$mail->send();
			return true;
		} catch (Exception $e) {
			$err = sprintf("Error al enviar el correo a %s: %s", $to, $mail->ErrorInfo ?: $e->getMessage());
			$logFile = __DIR__ . '/../../logs/mail.log';
			$ts = date('Y-m-d H:i:s');
			file_put_contents($logFile, "[$ts] $err\n", FILE_APPEND | LOCK_EX);
			return false;
		}
	}
}

// If this file is accessed directly in CLI with a 'send_test' argument, perform a quick test.
if (php_sapi_name() === 'cli') {
	$args = $_SERVER['argv'] ?? [];
	foreach ($args as $arg) {
		if (strpos($arg, 'send_test:') === 0) {
			[$_, $to] = explode(':', $arg, 2) + [1 => ''];
			$token = bin2hex(random_bytes(16));
			$ok = enviarCorreoVerificacion($to, $token);
			$msg = $ok ? "Test email queued/sent to $to\n" : "Failed sending test to $to; see logs/mail.log\n";
			echo $msg;
			break;
		}
	}
}

return;
