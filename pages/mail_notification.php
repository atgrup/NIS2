<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

// ---------------------------
// Cargar variables del .env
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
        // Solo si no existe
        if (getenv($name) === false) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

// Cargar el .env en la raíz del proyecto
load_dotenv(__DIR__ . '/../.env');

// ---------------------------
// Enviar correo con PHPMailer
// ---------------------------
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = getenv('MAIL_HOST');
    $mail->SMTPAuth = true;
    $mail->Username = getenv('MAIL_USERNAME');
    $mail->Password = getenv('MAIL_PASSWORD');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = getenv('MAIL_PORT');

    $mail->setFrom(getenv('MAIL_FROM'), getenv('MAIL_FROM_NAME'));
    $mail->addAddress(getenv('MAIL_TO'), getenv('MAIL_TO_NAME'));

    $mail->isHTML(true);
    $mail->Subject = 'Estado de la documentación';
    $mail->Body    = 'La documentación se encuentra en estado <b>APROBADA</b>.';
    $mail->AltBody = 'La documentación se encuentra en estado APROBADA.';

    $mail->send();
    echo "Correo enviado ✅\n";
} catch (Exception $e) {
    echo "Error al enviar el correo: {$mail->ErrorInfo}\n";
}
