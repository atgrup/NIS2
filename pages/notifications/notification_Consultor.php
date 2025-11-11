<?php
/**
 * notification_Consultor.php
 * Uso:
 * CLI: php notification_Consultor.php '{"destinatario":"c@d.com","nombre":"Cons","proveedor":"P","archivo":"f.pdf","logInfo":"texto opcional","includeLog":true}'
 * Web: POST/GET con los mismos campos.
 */
require_once __DIR__ . '/mail_notification.php';

$input = read_input();
$destinatario = $input['destinatario'] ?? null;
$nombre = $input['nombre'] ?? null;
$proveedor = $input['proveedor'] ?? null;
$archivo = $input['archivo'] ?? null;
$logInfo = $input['logInfo'] ?? null;
$includeLog = !empty($input['includeLog']);

if (!$destinatario || !$nombre || !$proveedor || !$archivo) {
    $msg = "Faltan parámetros. Se requieren: destinatario, nombre, proveedor, archivo.";
    echo $msg . "\n";
    notif_log($msg);
    exit(1);
}

$asunto = "Nuevo archivo subido por {$proveedor}";
$html = "<p>Hola {$nombre},</p>\n<p>El proveedor <b>{$proveedor}</b> ha subido un nuevo archivo: <b>{$archivo}</b>.</p>";

$res = enviarCorreos($destinatario, $asunto, $html, '', $logInfo, $includeLog);
echo json_encode($res) . "\n";
