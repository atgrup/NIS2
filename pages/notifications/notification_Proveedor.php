<?php
require_once 'mail_notification.php';

$destinatario = 'proveedor@example.com';
$nombre = 'Proveedor XYZ';
$estado = 'APROBADO';

$asunto = "Estado de tu documentación";
$html = "
    <p>Estimado $nombre,</p>
    <p>Tu documentación ha sido <b>$estado</b>.</p>
";

enviarCorreo($destinatario, $nombre, $asunto, $html);
