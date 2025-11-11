<?php
// Endpoint que procesa tokens incluidos en emails para permitir acciones (p.ej. cambiar estado desde el correo).
// Uso: pages/notifications/action.php?t=<token>

require_once __DIR__ . '/../../api/includes/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$token = $_GET['t'] ?? $_GET['token'] ?? $_POST['t'] ?? $_POST['token'] ?? null;
if (!$token) {
    http_response_code(400);
    echo "Token no especificado.";
    exit;
}

// Buscar token
$stmt = $conexion->prepare("SELECT id, token, queue_id, action, archivo_id, meta, expires_at, created_at FROM email_actions WHERE token = ? LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    http_response_code(404);
    echo "Token inválido o no encontrado.";
    exit;
}
$row = $res->fetch_assoc();
$stmt->close();

// Check expiry
if (!empty($row['expires_at']) && strtotime($row['expires_at']) <= time()) {
    echo "El token ha expirado.";
    exit;
}

$action = $row['action'];
$archivoId = $row['archivo_id'] ? intval($row['archivo_id']) : null;
$meta = $row['meta'] ? json_decode($row['meta'], true) : null;

// Soportar GET -> mostrar formulario de confirmación; POST -> aplicar la acción
try {
    if ($action === 'change_state') {
        if (!$archivoId) throw new Exception('Archivo no especificado en la acción.');

        // Obtener estado actual y datos básicos
        $q = $conexion->prepare("SELECT id, nombre_archivo, revision_estado, proveedor_id FROM archivos_subidos WHERE id = ? LIMIT 1");
        $q->bind_param('i', $archivoId);
        $q->execute();
        $r = $q->get_result();
        if (!$r || $r->num_rows === 0) throw new Exception('Archivo no encontrado.');
        $a = $r->fetch_assoc();
        $prev = $a['revision_estado'];
        $proveedor_id = $a['proveedor_id'];
        $archivo_nombre = $a['nombre_archivo'];
        $q->close();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Aplicar cambio: leer valores del formulario
            $new_state = $_POST['new_state'] ?? null;
            $comentario = $_POST['comentario'] ?? null;
            // Prefer session user id if available, otherwise use posted changed_by
            $changed_by = $_SESSION['id_usuarios'] ?? (isset($_POST['changed_by']) ? intval($_POST['changed_by']) : null);
            if (!$new_state) throw new Exception('Nuevo estado no especificado.');

            // Actualizar el estado
            $u = $conexion->prepare("UPDATE archivos_subidos SET revision_estado = ?, comentario = ? WHERE id = ?");
            $u->bind_param('ssi', $new_state, $comentario, $archivoId);
            if (!$u->execute()) {
                $u->close();
                throw new Exception('No se pudo actualizar el estado: ' . $conexion->error);
            }
            $u->close();

            // Insertar en historial
            $ins = $conexion->prepare("INSERT INTO archivo_estado_historial (archivo_id, proveedor_id, previous_state, new_state, comentario, changed_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $ins->bind_param('iisssi', $archivoId, $proveedor_id, $prev, $new_state, $comentario, $changed_by);
            $ins->execute();
            $ins->close();

                // Consumir token (marcar expirado) y registrar auditoría (used_by, used_at, used_ip, user agent)
                $used_by = $_SESSION['id_usuarios'] ?? null;
                $used_ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $used_ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
                $c = $conexion->prepare("UPDATE email_actions SET expires_at = NOW(), used_by = ?, used_at = NOW(), used_ip = ?, used_user_agent = ? WHERE id = ?");
                $c->bind_param('isss', $used_by, $used_ip, $used_ua, $row['id']);
                $c->execute();
                $c->close();

            // Notificar al proveedor (enqueue)
            require_once __DIR__ . '/mail_notification.php';
            $asuntoProv = "Estado de su archivo: {$new_state}";
            $htmlProv = "<p>Hola,</p><p>El archivo <b>{$archivo_nombre}</b> cambió su estado a <b>{$new_state}</b>.</p>";
            // obtener email del proveedor
            $q2 = $conexion->prepare("SELECT u.correo, pr.nombre_empresa FROM proveedores pr JOIN usuarios u ON pr.usuario_id = u.id_usuarios WHERE pr.id = ? LIMIT 1");
            $q2->bind_param('i', $proveedor_id);
            $q2->execute();
            $r2 = $q2->get_result();
            if ($r2 && $r2->num_rows > 0) {
                $rowp = $r2->fetch_assoc();
                $correoProv = $rowp['correo'] ?? null;
                $nombreProv = $rowp['nombre_empresa'] ?? '';
                if ($correoProv) {
                    $queueIdProv = enqueueEmail($correoProv, $nombreProv, $asuntoProv, $htmlProv, '', "Cambio de estado: {$new_state}", false);
                    // opcional: crear token para acciones del proveedor si se desea
                }
            }

            // Redirect to file view after applying
            $redirect = __DIR__ . '/../visualizar_archivo_split.php?id=' . $archivoId;
            if (!headers_sent()) {
                header('Location: ' . $redirect);
                exit;
            }
            echo "Acción aplicada. <a href=\"{$redirect}\">Ver archivo</a>";
            exit;
        }

        // Si GET: mostrar formulario de confirmación / edición antes de aplicar
        $appUrl = getenv('APP_URL') ?: '';
        $viewLink = ($appUrl ? rtrim($appUrl, '/') : '') . '/pages/visualizar_archivo_split.php?id=' . $archivoId;
        ?>
        <!doctype html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Confirmar acción sobre archivo</title>
        </head>
        <body>
            <h2>Revisar archivo: <?php echo htmlspecialchars($archivo_nombre); ?></h2>
            <p>Estado actual: <strong><?php echo htmlspecialchars($prev); ?></strong></p>
            <p><a href="<?php echo htmlspecialchars($viewLink); ?>" target="_blank">Ver archivo</a></p>
            <form method="post">
                <input type="hidden" name="t" value="<?php echo htmlspecialchars($token); ?>">
                <label for="new_state">Nuevo estado:</label>
                <select name="new_state" id="new_state">
                    <option value="pendiente">pendiente</option>
                    <option value="aprobado">aprobado</option>
                    <option value="rechazado">rechazado</option>
                </select>
                <br/>
                <label for="comentario">Comentario (opcional):</label><br/>
                <textarea name="comentario" id="comentario" rows="4" cols="60"></textarea>
                <br/>
                <input type="submit" value="Aplicar cambio">
            </form>
        </body>
        </html>
        <?php
        exit;
    } else {
        echo "Acción no soportada: {$action}";
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "Error al procesar la acción: " . htmlspecialchars($e->getMessage());
    exit;
}
