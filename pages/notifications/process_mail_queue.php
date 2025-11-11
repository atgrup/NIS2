<?php
// Worker to process mail_queue entries. Run via CLI or cron.
set_time_limit(0);
require_once __DIR__ . '/mail_notification.php';
// include DB connexion
$dbPath = __DIR__ . '/../../api/includes/conexion.php';
if (!file_exists($dbPath)) {
    echo "conexion.php no encontrado en {$dbPath}\n";
    exit(1);
}
require_once $dbPath; // provides $conexion

$limit = 10;
// Fetch pending items
$sql = "SELECT id, recipient_email, recipient_name, subject, body_html, body_text, log_info, include_log, attempts, max_attempts FROM mail_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $limit);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($row = $res->fetch_assoc()) $rows[] = $row;
$stmt->close();

if (empty($rows)) {
    echo "No hay correos en cola.\n";
    exit(0);
}

foreach ($rows as $r) {
    $id = (int)$r['id'];
    // mark as sending
    $u = $conexion->prepare("UPDATE mail_queue SET status = 'sending', updated_at = NOW() WHERE id = ? AND status = 'pending'");
    $u->bind_param('i', $id);
    $u->execute();
    $u->close();

    // re-fetch row to ensure we have current data
    $q = $conexion->prepare("SELECT id, recipient_email, recipient_name, subject, body_html, body_text, log_info, include_log, attempts, max_attempts FROM mail_queue WHERE id = ?");
    $q->bind_param('i', $id);
    $q->execute();
    $res2 = $q->get_result();
    if (!$res2 || $res2->num_rows === 0) { $q->close(); continue; }
    $row = $res2->fetch_assoc();
    $q->close();

    $to = $row['recipient_email'];
    $name = $row['recipient_name'];
    $subject = $row['subject'];
    $html = $row['body_html'];
    $text = $row['body_text'];
    $logInfo = $row['log_info'];
    $includeLog = (int)$row['include_log'] === 1;
    $attachments = [];
    if (!empty($row['attachments'])) {
        $decoded = json_decode($row['attachments'], true);
        if (is_array($decoded)) $attachments = $decoded;
    }
    $attempts = (int)$row['attempts'];
    $maxAttempts = (int)$row['max_attempts'];

    echo "Procesando queue id={$id} -> {$to}\n";
    $ok = enviarCorreo($to, $name, $subject, $html, $text, $attachments);
    // If there are attachments configured for this queue row, pass them to the mail sender
    // (enviarCorreo doesn't support attachments yet here; we'll add attachment handling below)
    if ($ok) {
        // update status to sent
        $up = $conexion->prepare("UPDATE mail_queue SET status = 'sent', attempts = attempts + 1, updated_at = NOW() WHERE id = ?");
        $up->bind_param('i', $id);
        $up->execute();
        $up->close();
        recordMailLog($id, $to, $subject, 'ok', null);
        echo "Enviado ok id={$id}\n";
    } else {
        $attempts++;
        // Collect helpful recent log lines to store as error detail
        $logPath = __DIR__ . '/../../logs/mail.log';
        $recentErr = null;
        if (file_exists($logPath)) {
            $lines = @file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines && count($lines) > 0) {
                $slice = array_slice($lines, -8);
                $recentErr = implode(" | ", $slice);
            }
        }
        $errDetail = $recentErr ? substr($recentErr, 0, 2000) : "Error al enviar (intentos={$attempts})";
        $newStatus = $attempts >= $maxAttempts ? 'failed' : 'pending';
        $up = $conexion->prepare("UPDATE mail_queue SET status = ?, attempts = ?, error = ?, updated_at = NOW() WHERE id = ?");
        $up->bind_param('sisi', $newStatus, $attempts, $errDetail, $id);
        $up->execute();
        $up->close();
        recordMailLog($id, $to, $subject, 'error', $errDetail);
        echo "Fallo envio id={$id}, nuevo estado={$newStatus}\n";
    }
}

echo "Procesamiento finalizado.\n";
