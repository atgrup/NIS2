<?php
// Vista simple de notificaciones / historial para usuario logueado
session_start();
require __DIR__ . '/../api/includes/conexion.php';
if (!isset($_SESSION['id_usuarios'])) {
    echo "Debes iniciar sesión para ver tus notificaciones.";
    exit;
}
$userId = $_SESSION['id_usuarios'];
$role = strtolower($_SESSION['rol'] ?? '');

// Si es proveedor, filtramos por proveedor_id; si es consultor, mostrar historial global o relacionado
$items = [];
if ($role === 'proveedor') {
    // Obtener proveedor_id
    $stmt = $conexion->prepare("SELECT id FROM proveedores WHERE usuario_id = ? LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $prov = $res->fetch_assoc();
    $provId = $prov['id'] ?? null;
    if ($provId) {
        $q = $conexion->prepare("SELECT h.id, h.archivo_id, h.previous_state, h.new_state, h.comentario, h.changed_by, h.created_at, a.nombre_archivo FROM archivo_estado_historial h LEFT JOIN archivos_subidos a ON h.archivo_id = a.id WHERE h.proveedor_id = ? ORDER BY h.created_at DESC LIMIT 100");
        $q->bind_param('i', $provId);
        $q->execute();
        $r = $q->get_result();
        while ($row = $r->fetch_assoc()) $items[] = $row;
    }
} elseif ($role === 'consultor') {
    // mostrar últimos cambios en los que hay actividad (global)
    $q = $conexion->prepare("SELECT h.id, h.archivo_id, h.previous_state, h.new_state, h.comentario, h.changed_by, h.created_at, a.nombre_archivo FROM archivo_estado_historial h LEFT JOIN archivos_subidos a ON h.archivo_id = a.id ORDER BY h.created_at DESC LIMIT 200");
    $q->execute();
    $r = $q->get_result();
    while ($row = $r->fetch_assoc()) $items[] = $row;
} else {
    // admin or others: show recent history
    $q = $conexion->prepare("SELECT h.id, h.archivo_id, h.previous_state, h.new_state, h.comentario, h.changed_by, h.created_at, a.nombre_archivo FROM archivo_estado_historial h LEFT JOIN archivos_subidos a ON h.archivo_id = a.id ORDER BY h.created_at DESC LIMIT 200");
    $q->execute();
    $r = $q->get_result();
    while ($row = $r->fetch_assoc()) $items[] = $row;
}

// Helper to get user email/name by id
function getUserById($conexion, $id) {
    $stmt = $conexion->prepare("SELECT id_usuarios, correo FROM usuarios WHERE id_usuarios = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res && $res->num_rows ? $res->fetch_assoc() : null;
}

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Notificaciones / Historial</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
    <h3>Notificaciones y historial</h3>
    <p>Usuario: <?php echo htmlspecialchars($_SESSION['correo'] ?? ''); ?> (<?php echo htmlspecialchars($role); ?>)</p>

    <?php if (empty($items)): ?>
        <div class="alert alert-info">No hay notificaciones recientes.</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead><tr><th>Fecha</th><th>Archivo</th><th>Anterior</th><th>Nuevo</th><th>Comentario</th><th>Por</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it):
                $user = $it['changed_by'] ? getUserById($conexion, $it['changed_by']) : null;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($it['created_at']); ?></td>
                    <td><a href="pages/visualizar_archivo_split.php?id=<?php echo intval($it['archivo_id']); ?>" target="_blank"><?php echo htmlspecialchars($it['nombre_archivo'] ?? ('#' . intval($it['archivo_id']))); ?></a></td>
                    <td><?php echo htmlspecialchars($it['previous_state']); ?></td>
                    <td><?php echo htmlspecialchars($it['new_state']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($it['comentario'] ?? '')); ?></td>
                    <td><?php echo $user ? htmlspecialchars($user['correo']) : 'Sistema/Token'; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
