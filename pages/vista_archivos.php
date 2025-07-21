
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Archivos Subidos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="p-4">


<?php if (strtolower($rol) === 'consultor'): ?>
  <div class="alert alert-warning">Los consultores no pueden subir archivos.</div>
<?php endif; ?>

<?php if (strtolower($rol) !== 'consultor'): ?>
<!-- El buscador ya está junto al botón de subir archivo, no se crea ninguno nuevo -->
<div class="modal fade" id="modalSubirArchivo" tabindex="-1" aria-labelledby="modalSubirArchivoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formSubirArchivo" action="subir_Archivo_rellenado.php" method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalSubirArchivoLabel">Subir Archivo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="archivo" class="form-label">Archivo</label>
          <input type="file" class="form-control" id="archivo" name="archivo" required>
        </div>
        <div class="mb-3">
          <label for="plantilla_id" class="form-label">Plantillas Asociadas</label>
          <select name="plantilla_id" id="plantilla_id" class="form-select" required>
            <option value="">-- Seleccione --</option>
            <?php if ($plantillasRes): ?>
              <?php while ($f = $plantillasRes->fetch_assoc()): ?>
                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nombre']) ?></option>
              <?php endwhile; ?>
            <?php else: ?>
              <option disabled>No hay plantillas</option>
            <?php endif; ?>
          </select>
        </div>
        <div id="mensajeRespuesta" class="mt-2"></div>

        <input type="hidden" name="usuario_id" value="<?= $usuario_id ?>">
        <input type="hidden" name="proveedor_id" value="<?= $prov_id ?>">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Subir</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- TABLA DE ARCHIVOS SUBIDOS -->
<!-- Buscador ya está junto al botón de subir archivo, solo asegúrate de que el input tiene id="buscadorArchivos" -->
<?php
$conexion = new mysqli('jordio35.sg-host.com', 'u74bscuknwn9n', 'ad123456-', 'dbs1il8vaitgwc');
$archivos = [];
if ($conexion->connect_error) {
    echo '<div class="alert alert-danger">Error de conexión a la base de datos.</div>';
} else {
    $sql = "SELECT a.id, a.nombre_archivo, a.archivo_url, a.fecha_subida, a.revision_estado, p.nombre_empresa, u.correo as correo_usuario FROM archivos_subidos a LEFT JOIN proveedores p ON a.proveedor_id = p.id LEFT JOIN usuarios u ON p.usuario_id = u.id_usuarios ORDER BY a.fecha_subida DESC";
    $res = $conexion->query($sql);
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $archivos[] = $row;
        }
    }
    $res->free();
    $conexion->close();
}
?>
<div class="table-responsive mt-4" id="tablaArchivosContainer">
  <table class="table table-bordered table-hover" id="tablaArchivos">
    <thead class="table-light">
      <tr>
        <th>Nombre archivo</th>
        <th>Fecha subida</th>
        <th>Estado</th>
        <th>Empresa</th>
        <th>Usuario</th>
        <th data-no-sort>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($archivos) > 0): ?>
        <?php foreach ($archivos as $row): ?>
          <tr>
            <td><a href="../<?= htmlspecialchars($row['archivo_url']) ?>" target="_blank"><?= htmlspecialchars($row['nombre_archivo']) ?></a></td>
            <td><?= $row['fecha_subida'] ?></td>
            <td><?= htmlspecialchars($row['revision_estado']) ?></td>
            <td><?= htmlspecialchars($row['nombre_empresa'] ?? '-') ?></td>
            <td><?= htmlspecialchars($row['correo_usuario'] ?? '-') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="5" class="text-center">No hay archivos subidos.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Buscador funcional para la tabla de archivos usando el input que ya existe junto al botón de subir archivo
document.addEventListener('DOMContentLoaded', function() {
  // Busca el input de buscador que ya tienes junto al botón de subir archivo
  var buscador = document.getElementById('buscadorArchivos');
  if (buscador) {
    buscador.addEventListener('input', function() {
      const texto = this.value.trim().toLowerCase();
      const filas = document.querySelectorAll('#tablaArchivos tbody tr');
      filas.forEach(fila => {
        const celdas = fila.querySelectorAll('td');
        let visible = false;
        celdas.forEach(celda => {
          if (celda.textContent.toLowerCase().includes(texto)) {
            visible = true;
          }
        });
        fila.style.display = visible ? '' : 'none';
      });
    });
  }
});
</script>
</body>
<script>
document.getElementById('formSubirArchivo').addEventListener('submit', function(e) {
    e.preventDefault(); // evitar que recargue la página

    const form = e.target;
    const formData = new FormData(form);
    const mensajeDiv = document.getElementById('mensajeRespuesta');

    fetch(form.action, {
        method: 'POST',
        body: formData,
    })
    .then(response => response.text())
    .then(data => {
        mensajeDiv.innerHTML = `<div class="alert alert-info">${data}</div>`;
        // Opcional: limpiar el formulario o cerrar el modal si quieres:
        // form.reset();
        // bootstrap.Modal.getInstance(document.getElementById('modalSubirArchivo')).hide();
    })
    .catch(error => {
        mensajeDiv.innerHTML = `<div class="alert alert-danger">Error al subir el archivo.</div>`;
    });
});
</script>

</html>

