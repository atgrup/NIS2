<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Archivos Subidos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    .form-label {
      color: grey;
    }
  </style>
</head>
<body class="p-4">

<?php
include '../api/includes/conexion.php';

if (strtolower($rol) === 'administrador') {
    $sql = "SELECT a.*, p.nombre AS nombre_plantilla FROM archivos_subidos a
            LEFT JOIN plantillas p ON a.plantilla_id = p.id
            ORDER BY a.fecha_subida DESC";
    $archivosRes = $conexion->query($sql);
} else if (strtolower($rol) !== 'consultor') {
    $sql = "SELECT a.*, p.nombre AS nombre_plantilla FROM archivos_subidos a
            LEFT JOIN plantillas p ON a.plantilla_id = p.id
            WHERE a.proveedor_id = ?
            ORDER BY a.fecha_subida DESC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $prov_id);
    $stmt->execute();
    $archivosRes = $stmt->get_result();
}
?>


<?php if (strtolower($rol) === 'consultor'): ?>
  <div class="alert alert-warning">Los consultores no pueden subir archivos.</div>
<?php endif; ?>

<?php if (strtolower($rol) !== 'consultor'): ?>
 

  <div class="modal fade" id="modalSubirArchivo" tabindex="-1" aria-labelledby="modalSubirArchivoLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form id="formSubirArchivo" action="subir_Archivo_rellenado.php" method="post" enctype="multipart/form-data" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalSubirArchivoLabel">Subir Archivo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="archivo" class="form-label">Archivo</label>
            <input type="file" class="form-control" id="archivo" name="archivo" required />
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

<?php if (isset($archivosRes) && $archivosRes && $archivosRes->num_rows > 0): ?>
  <div style="max-height: 70vh; overflow-y: auto;">
    <table class="table table-striped table-bordered mt-3 align-middle archivos-table">
      <thead>
        <tr>
          <th>Nombre del Archivo</th>
          <th>Plantilla Asociada</th>
          <th>Fecha de Subida</th>
          <th>Estado de Revisión</th>
          <th>Proveedor ID</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $archivosRes->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['nombre_archivo']) ?></td>
            <td><?= htmlspecialchars($row['nombre_plantilla'] ?? 'Sin plantilla') ?></td>
            <td><?= htmlspecialchars($row['fecha_subida']) ?></td>
            <td><?= ucfirst(htmlspecialchars($row['revision_estado'] ?? 'pendiente')) ?></td>
            <td><?= $row['proveedor_id'] ?? '—' ?></td>
            <td>
              <a href="<?= htmlspecialchars($row['archivo_url']) ?>" target="_blank" class="btn btn-sm btn-primary" title="Ver Archivo">
                <i class="bi bi-eye"></i>
              </a>
              <a href="<?= htmlspecialchars($row['archivo_url']) ?>" download class="btn btn-sm btn-success" title="Descargar Archivo">
                <i class="bi bi-download"></i>
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <div id="paginacion" class="mt-3 d-flex justify-content-center gap-2"></div>
<?php else: ?>
  <div class="alert alert-info mt-3">No se encontraron archivos subidos.</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('formSubirArchivo')?.addEventListener('submit', function(e) {
  e.preventDefault();

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
  })
  .catch(() => {
    mensajeDiv.innerHTML = `<div class="alert alert-danger">Error al subir el archivo.</div>`;
  });
});
 document.addEventListener('DOMContentLoaded', () => {
  const filasPorPagina = 10;
  let paginaActual = 1;

  const filas = [...document.querySelectorAll('table.archivos-table tbody tr')];
  const pagDiv = document.getElementById('paginacion');

  function mostrarPagina(pagina) {
    const inicio = (pagina - 1) * filasPorPagina;
    const fin = inicio + filasPorPagina;
    filas.forEach((fila, i) => fila.style.display = (i >= inicio && i < fin) ? '' : 'none');
  }

  function crearPaginacion() {
    pagDiv.innerHTML = '';
    const totalPaginas = Math.ceil(filas.length / filasPorPagina);

    const btnPrimera = document.createElement('button');
    btnPrimera.textContent = '⏮️';
    btnPrimera.className = 'btn btn-outline-primary';
    btnPrimera.disabled = paginaActual === 1;
    btnPrimera.onclick = () => { paginaActual = 1; mostrarPagina(paginaActual); crearPaginacion(); };
    pagDiv.appendChild(btnPrimera);

    for (let i = 1; i <= totalPaginas; i++) {
      const btn = document.createElement('button');
      btn.textContent = i;
      btn.className = 'btn ' + (i === paginaActual ? 'btn-primary' : 'btn-outline-primary');
      btn.onclick = () => { paginaActual = i; mostrarPagina(paginaActual); crearPaginacion(); };
      pagDiv.appendChild(btn);
    }

    const btnUltima = document.createElement('button');
    btnUltima.textContent = '⏭️';
    btnUltima.className = 'btn btn-outline-primary';
    btnUltima.disabled = paginaActual === totalPaginas;
    btnUltima.onclick = () => { paginaActual = totalPaginas; mostrarPagina(paginaActual); crearPaginacion(); };
    pagDiv.appendChild(btnUltima);
  }

  mostrarPagina(paginaActual);
  crearPaginacion();
});
</script>

</body>
</html>
