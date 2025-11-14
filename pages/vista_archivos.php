<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/../api/includes/conexion.php';

$rol = strtolower($_SESSION['rol'] ?? '');
$usuario_id = $_SESSION['id_usuarios'] ?? null;
$prov_id = $_SESSION['proveedor_id'] ?? null;

// Paginación
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$filas_por_pagina = 10;
$inicio = ($pagina_actual - 1) * $filas_por_pagina;

// Consulta archivos
if ($rol === 'administrador' || $rol === 'consultor') {
    $sql_total = "SELECT COUNT(*) as total FROM archivos_subidos";
    $total_filas = $conexion->query($sql_total)->fetch_assoc()['total'];

    $sql = "SELECT a.id, a.nombre_archivo, a.archivo_url, p.nombre AS nombre_plantilla, a.fecha_subida,
            pr.nombre_empresa, u.correo AS correo_usuario, a.revision_estado
            FROM archivos_subidos a
            LEFT JOIN plantillas p ON a.plantilla_id = p.id
            LEFT JOIN proveedores pr ON a.proveedor_id = pr.id
            LEFT JOIN usuarios u ON a.usuario_id = u.id_usuarios
            ORDER BY a.fecha_subida DESC
            LIMIT ?, ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $inicio, $filas_por_pagina);
    $stmt->execute();
    $archivosRes = $stmt->get_result();

} elseif ($rol === 'proveedor') {
    $prov_id_sesion = $prov_id ?? 0;
    $sql_total = "SELECT COUNT(*) AS total FROM archivos_subidos WHERE proveedor_id = ? OR usuario_id = ?";
    $stmt_total = $conexion->prepare($sql_total);
    $stmt_total->bind_param("ii", $prov_id_sesion, $usuario_id);
    $stmt_total->execute();
    $total_filas = $stmt_total->get_result()->fetch_assoc()['total'];

    $sql = "SELECT a.id, a.nombre_archivo, a.archivo_url, p.nombre AS nombre_plantilla, a.fecha_subida,
            pr.nombre_empresa, u.correo AS correo_usuario, a.revision_estado
            FROM archivos_subidos a
            LEFT JOIN plantillas p ON a.plantilla_id = p.id
            LEFT JOIN proveedores pr ON a.proveedor_id = pr.id
            LEFT JOIN usuarios u ON a.usuario_id = u.id_usuarios
            WHERE a.proveedor_id = ? OR a.usuario_id = ?
            ORDER BY a.fecha_subida DESC
            LIMIT ?, ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iiii", $prov_id_sesion, $usuario_id, $inicio, $filas_por_pagina);
    $stmt->execute();
    $archivosRes = $stmt->get_result();
} else {
    $archivosRes = null;
}

// Plantillas
$plantillasRes = $conexion->query("SELECT id, nombre FROM plantillas");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Archivos Subidos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
.modal-header { background-color: #0d6efd; color: black; }
.form-label { color: black; }
</style>
</head>
<body class="p-4">

<?php if ($rol === 'consultor' || $rol === 'proveedor'): ?>
<div class="modal fade" id="modalSubirArchivo" tabindex="-1">
  <div class="modal-dialog">
    <form id="formSubirArchivoModal" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Subir Nuevo Archivo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="archivo-modal" class="form-label">Seleccionar Archivo</label>
            
            <input type="file" name="archivo" id="archivo-modal" class="form-control" required accept=".pdf">
            <div class="form-text">Formatos permitidos: PDF</div>
          </div>
          <div class="mb-3">
            <label for="plantilla_id" class="form-label">Plantilla Asociada</label>
            <select name="plantilla_id" id="plantilla_id" class="form-select" required>
              <option value="">-- Seleccione una plantilla --</option>
              <?php while($p = $plantillasRes->fetch_assoc()): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Subir Archivo</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="table-responsive mt-3">
<?php if ($archivosRes && $archivosRes->num_rows > 0): ?>
<table class="table table-bordered table-hover">
  <thead class="table-light">
    <tr>
      <th>Nombre archivo</th>
      <th>Plantilla</th>
      <th>Fecha subida</th>
      <th>Empresa</th>
      <th>Usuario</th>
      <th>Estado revisión</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php while($row = $archivosRes->fetch_assoc()): ?>
    <tr id="fila-<?= $row['id'] ?>">
      <td><a href="<?= htmlspecialchars($row['archivo_url']) ?>" target="_blank"><?= htmlspecialchars($row['nombre_archivo']) ?></a></td>
      <td><?= htmlspecialchars($row['nombre_plantilla'] ?? 'Sin plantilla') ?></td>
      <td><?= htmlspecialchars($row['fecha_subida']) ?></td>
      <td><?= htmlspecialchars($row['nombre_empresa'] ?? '-') ?></td>
      <td><?= htmlspecialchars(explode('@',$row['correo_usuario']??'')[0] ?? '-') ?></td>
      <td><img src="documentos_subidos/<?= strtolower($row['revision_estado']??'pendiente') ?>.png" style="width:100px;"></td>
      <td>
        <a href="<?= htmlspecialchars($row['archivo_url']) ?>" target="_blank" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
        <a href="<?= htmlspecialchars($row['archivo_url']) ?>" download class="btn btn-sm btn-success"><i class="bi bi-download"></i></a>
      </td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>
<?php else: ?>
<div class="alert alert-info">No se encontraron archivos.</div>
<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const form = document.getElementById('formSubirArchivoModal');
if(form){
  form.addEventListener('submit', e => {
    e.preventDefault();
    const formData = new FormData(form);
    fetch('subir_archivo_rellenado.php', {method:'POST', body: formData})
      .then(res => res.json())
      .then(data => {
        if(data.success){
          bootstrap.Modal.getInstance(document.getElementById('modalSubirArchivo')).hide();
          form.reset();
          // He quitado la línea de 'data.htmlFila' porque tu PHP no la genera
          // En su lugar, simplemente recargamos la página para ver el nuevo archivo
          alert('¡Archivo subido con éxito!');
          window.location.reload(); 
        } else {
          alert('Error: '+(data.error||'desconocido'));
        }
      })
      .catch(err => { console.error(err); alert('Error al subir el archivo'); });
  });
}
</script>
</body>
</html>
