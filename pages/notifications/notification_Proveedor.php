<?php
/**
 * notification_Proveedor.php
 * Espera recibir: destinatario, nombre, estado, (opcional) comentarios, logInfo, includeLog
 * Se puede invocar por CLI con JSON o por POST/GET.
 */
require_once __DIR__ . '/mail_notification.php';

$input = read_input();
$destinatario = $input['destinatario'] ?? null;
$nombre = $input['nombre'] ?? null;
$estado = $input['estado'] ?? null;
$comentarios = $input['comentarios'] ?? null;
$logInfo = $input['logInfo'] ?? null;
$includeLog = !empty($input['includeLog']);

if (!$destinatario || !$nombre || !$estado) {
    $msg = "Faltan parámetros. Se requieren: destinatario, nombre, estado.";
    echo $msg . "\n";
    notif_log($msg);
    exit(1);
}

$asunto = "Estado de tu documentación: {$estado}";
$html = "<p>Estimado {$nombre},</p>\n<p>Tu documentación ha sido <b>{$estado}</b>.</p>";
if ($comentarios) $html .= "\n<p>Comentarios: {$comentarios}</p>";

$res = enviarCorreos($destinatario, $asunto, $html, '', $logInfo, $includeLog);
echo json_encode($res) . "\n";

