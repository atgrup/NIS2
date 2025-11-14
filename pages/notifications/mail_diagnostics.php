<?php
// Mail diagnostics utility - can be run via CLI or web
// Usage (CLI): php mail_diagnostics.php status
// Usage (CLI) send test: php mail_diagnostics.php send_test:you@example.com
// Usage (Web): open this file in browser and use form to run a test send

require_once __DIR__ . '/enviar_correo.php';

// Include DB connection for queries
$dbPath = __DIR__ . '/../../api/includes/conexion.php';
if (!file_exists($dbPath)) {
    $dbError = "No se encontró conexion.php en: {$dbPath}";
} else {
    require_once $dbPath; // defines $conexion
}

function mask(string $v): string {
    if ($v === '') return '(vacío)';
    if (strpos($v, '@') !== false) return preg_replace('/(.).+(@.+)/', '$1***$2', $v);
    return substr($v,0,3) . str_repeat('*', max(3, strlen($v)-6)) . substr($v, -3);
}

function run_status($conexion=null) {
    $out = [];
    // .env / MAIL variables
    $vars = ['MAIL_HOST','MAIL_PORT','MAIL_USERNAME','MAIL_PASSWORD','MAIL_FROM','MAIL_SMTP_SECURE','MAIL_SMTP_DEBUG','APP_URL','MAIL_TEST_TO'];
    $env = [];
    foreach ($vars as $v) $env[$v] = getenv($v) ?: '';
    $out['env'] = $env;

    // DB checks
    if (!isset($conexion) || !$conexion) {
        $out['db_error'] = 'Conexión DB no disponible.';
        return $out;
    }

    // Mail queue recent
    $q = $conexion->prepare("SELECT id, recipient_email, subject, status, attempts, created_at, updated_at FROM mail_queue ORDER BY created_at DESC LIMIT 30");
    $q->execute();
    $res = $q->get_result();
    $out['mail_queue'] = $res->fetch_all(MYSQLI_ASSOC);

    // Mail logs recent
    $q2 = $conexion->prepare("SELECT id, queue_id, recipient, subject, status, error, created_at FROM mail_logs ORDER BY created_at DESC LIMIT 50");
    $q2->execute();
    $res2 = $q2->get_result();
    $out['mail_logs'] = $res2->fetch_all(MYSQLI_ASSOC);

    return $out;
}

function send_test_email(string $to, ?string $name = null) {
    $name = $name ?: 'Prueba';
    $subject = 'Prueba de envío desde NIS2';
    $html = '<p>Este es un correo de prueba desde la herramienta de diagnóstico de NIS2.</p>';
    $ok = enviarCorreo($to, $name, $subject, $html, 'Prueba de texto plano');
    return $ok;
}

// CLI handling
if (php_sapi_name() === 'cli') {
    global $argv;
    $cmd = $argv[1] ?? 'status';
    if ($cmd === 'status') {
        $out = run_status($conexion ?? null);
        echo "=== ENV VARS ===\n";
        foreach ($out['env'] as $k=>$v) echo "{$k}: " . mask($v) . "\n";
        echo "\n";
        if (isset($out['db_error'])) {
            echo "DB ERROR: " . $out['db_error'] . "\n";
            exit(1);
        }
        echo "=== mail_queue (últimas 30) ===\n";
        foreach ($out['mail_queue'] as $r) {
            echo sprintf("[%s] %s - %s (%s) attempts=%s updated=%s\n", $r['id'], $r['recipient_email'], $r['subject'], $r['status'], $r['attempts'], $r['updated_at']);
        }
        echo "\n=== mail_logs (últimas 50) ===\n";
        foreach ($out['mail_logs'] as $r) {
            echo sprintf("[%s] queue=%s to=%s status=%s err=%s at=%s\n", $r['id'], $r['queue_id'], $r['recipient'], $r['status'], substr($r['error'] ?: '',0,150), $r['created_at']);
        }
        exit(0);
    }

    if (strpos($cmd, 'send_test:') === 0) {
        $to = substr($cmd, strlen('send_test:'));
        echo "Enviando correo de prueba a: {$to}\n";
        $ok = send_test_email($to);
        echo $ok ? "Envío OK\n" : "Envío FALLIDO (revisa logs)\n";
        exit($ok ? 0 : 2);
    }

    echo "Unknown command. Usage:\n php mail_diagnostics.php status\n php mail_diagnostics.php send_test:you@example.com\n";
    exit(1);
}

// Web UI
$out = run_status($conexion ?? null);
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Mail diagnostics - NIS2</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <h3>Diagnóstico de correo</h3>
  <h5>Environment</h5>
  <table class="table table-sm">
    <tbody>
    <?php foreach ($out['env'] as $k=>$v): ?>
      <tr><th><?php echo htmlspecialchars($k); ?></th><td><?php echo htmlspecialchars(mask($v)); ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <?php if (isset($dbError)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($dbError); ?></div>
  <?php else: ?>
    <h5>Cola de correo (últimos 30)</h5>
    <table class="table table-striped table-sm">
      <thead><tr><th>ID</th><th>To</th><th>Asunto</th><th>Estado</th><th>Intentos</th><th>Actualizado</th></tr></thead>
      <tbody>
      <?php foreach ($out['mail_queue'] as $r): ?>
        <tr>
          <td><?php echo intval($r['id']); ?></td>
          <td><?php echo htmlspecialchars($r['recipient_email']); ?></td>
          <td><?php echo htmlspecialchars($r['subject']); ?></td>
          <td><?php echo htmlspecialchars($r['status']); ?></td>
          <td><?php echo intval($r['attempts']); ?></td>
          <td><?php echo htmlspecialchars($r['updated_at']); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <h5>Logs de correo (últimos 50)</h5>
    <table class="table table-sm table-striped">
      <thead><tr><th>ID</th><th>Queue</th><th>Destinatario</th><th>Estado</th><th>Error (parcial)</th><th>Fecha</th></tr></thead>
      <tbody>
      <?php foreach ($out['mail_logs'] as $r): ?>
        <tr>
          <td><?php echo intval($r['id']); ?></td>
          <td><?php echo intval($r['queue_id']); ?></td>
          <td><?php echo htmlspecialchars($r['recipient']); ?></td>
          <td><?php echo htmlspecialchars($r['status']); ?></td>
          <td><?php echo htmlspecialchars(substr($r['error'] ?? '', 0, 120)); ?></td>
          <td><?php echo htmlspecialchars($r['created_at']); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <h5>Enviar correo de prueba</h5>
    <form method="post" action="mail_diagnostics.php">
      <div class="mb-3">
        <label class="form-label">Enviar a (si vacío usa MAIL_TEST_TO env)</label>
        <input name="to" class="form-control" placeholder="destinatario@ejemplo.com">
      </div>
      <button type="submit" name="action" value="send_test" class="btn btn-primary">Enviar prueba</button>
    </form>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_test'):
      $to = trim($_POST['to'] ?? '');
      if ($to === '') $to = getenv('MAIL_TEST_TO') ?: '';
      if ($to === '') {
        echo '<div class="alert alert-warning mt-3">No hay destinatario de prueba configurado. Define MAIL_TEST_TO en .env o escribe un correo arriba.</div>';
      } else {
        echo '<div class="mt-3">Intentando envío a: ' . htmlspecialchars($to) . '</div>';
        $ok = send_test_email($to);
        if ($ok) echo '<div class="alert alert-success mt-2">Envío OK — revisa bandeja del destinatario</div>';
        else echo '<div class="alert alert-danger mt-2">Envío FALLIDO — revisa logs en logs/mail.log y la tabla mail_logs</div>';
      }
    endif; ?>
  <?php endif; ?>

</div>
</body>
</html>
