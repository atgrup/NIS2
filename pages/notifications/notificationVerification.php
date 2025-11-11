<?php
require_once 'mail_notification.php';

$destinatario = 'usuario@example.com';
$nombre = 'Usuario Ejemplo';
$token = '12345XYZ';
$link = "https://tu-sitio.com/verificar.php?token=$token";

$asunto = "Verifica tu correo electrónico";
$html = "
    <h2>Bienvenido, $nombre</h2>
    <p>Por favor verifica tu correo haciendo clic en el siguiente enlace:</p>
    <a href='$link'>Verificar mi correo</a>
";

if (enviarCorreo($destinatario, $nombre, $asunto, $html)) {
    echo "Correo de verificación enviado ✅";
} else {
    echo "Error al enviar el correo ❌";
}

