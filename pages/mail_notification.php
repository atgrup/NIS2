<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Carga automática de Composer
require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';         
    $mail->SMTPAuth = true;
    $mail->Username = 'tu_correo@gmail.com'; 
    $mail->Password = 'tu_contraseña_o_app_password'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Configuración del correo
    $mail->setFrom('tu_correo@gmail.com', 'Notificador NIS2');
    $mail->addAddress('destinatario@empresa.com', 'Destinatario');
    $mail->isHTML(true);
    $mail->Subject = 'Estado de la documentación';
    $mail->Body    = 'La documentación se encuentra en estado <b>APROBADA</b>.';
    $mail->AltBody = 'La documentación se encuentra en estado APROBADA.';

    $mail->send();
    echo 'Correo enviado correctamente.';
} catch (Exception $e) {
    echo "Error al enviar el correo: {$mail->ErrorInfo}";
}
