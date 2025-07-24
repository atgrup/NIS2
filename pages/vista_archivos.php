<?php
// Verificar si la sesión no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ajustar la ruta de conexión según tu estructura
$ruta_conexion = __DIR__ . '/../api/includes/conexion.php';
if (!file_exists($ruta_conexion)) {
    die("Error: No se encontró el archivo de conexión en $ruta_conexion");
}
require $ruta_conexion;

// Mostrar mensajes
$mensaje_exito = $_SESSION['mensaje_exito'] ?? null;
$mensaje_error = $_SESSION['mensaje_error'] ?? null;
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);

$rol = strtolower($_SESSION['rol']);
$usuario_id = $_SESSION['id_usuario'];
$prov_id = $_SESSION['proveedor_id'] ?? null;

// Consulta para archivos según el rol
if ($rol === 'administrador') {
    $sql_total = "SELECT COUNT(*) as total FROM archivos_subidos";
    $result_total = $conexion->query($sql_total);
    $total_filas = $result_total->fetch_assoc()['total'];

    $sql = "SELECT a.*, p.nombre AS nombre_plantilla FROM archivos_subidos a
            LEFT JOIN plantillas p ON a.plantilla_id = p.id
            ORDER BY a.fecha_subida DESC
            LIMIT $inicio, $filas_por_pagina";
    $archivosRes = $conexion->query($sql);

} elseif ($rol === 'proveedor') {
    $sql_total = "SELECT COUNT(*) as total FROM archivos_subidos WHERE proveedor_id = ?";
    $stmt_total = $conexion->prepare($sql_total);
    $stmt_total->bind_param("i", $prov_id);
    $stmt_total->execute();
    $total_filas = $stmt_total->get_result()->fetch_assoc()['total'];

    $sql = "SELECT a.*, p.nombre AS nombre_plantilla FROM archivos_subidos a
            LEFT JOIN plantillas p ON a.plantilla_id = p.id
            WHERE a.proveedor_id = ?
            ORDER BY a.fecha_subida DESC
            LIMIT $inicio, $filas_por_pagina";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $prov_id);
    $stmt->execute();
    $archivosRes = $stmt->get_result();
}
$total_paginas = ceil($total_filas / $filas_por_pagina);

// Consulta para plantillas
$plantillasRes = $conexion->query("SELECT id, nombre FROM plantillas");
if (!$plantillasRes) {
    die("Error en consulta de plantillas: " . $conexion->error);
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Archivos Subidos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    .form-label { color: grey; }
   
    .table-scroll table { width: 100%; margin-bottom: 0; }
    .pagination-container { margin-top: 15px; }
    .modal-header { background-color: #0d6efd; color: white; }
    .btn-close-white { filter: invert(1); }
    
  /* Estilo para que los botones de acciones estén en línea horizontal */
  td[data-no-sort] {
    white-space: nowrap;
    display: flex;
    gap: 0.25rem; /* espacio entre botones */
    align-items: center;
    justify-content: start;
  }
</style>

</head>
<body class="p-4">

<!-- Mensajes de operaciones -->
<?php if ($mensaje_exito): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?= $mensaje_exito ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if ($mensaje_error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?= $mensaje_error ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
<!-- Modal para subir archivos -->
<?php if ($rol !== 'consultor'): ?>
<div class="modal fade" id="modalSubirArchivo" tabindex="-1" aria-labelledby="modalSubirArchivoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formSubirArchivo" action="subir_archivo_rellenado.php" method="post" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header bg-mi-color text-white">
          <h5 class="modal-title" id="modalSubirArchivoLabel">Subir Nuevo Archivo</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="archivo" class="form-label fw-bold">Seleccionar Archivo</label>
            <input type="file" class="form-control" id="archivo" name="archivo" required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
            <div class="form-text">Formatos permitidos: PDF, Word, Excel, imágenes</div>
          </div>

          <div class="mb-3">
            <label for="plantilla_id" class="form-label fw-bold">Plantilla Asociada</label>
            <select name="plantilla_id" id="plantilla_id" class="form-select" required>
              <option value="">-- Seleccione una plantilla --</option>
              <?php if ($plantillasRes->num_rows > 0): ?>
                <?php while ($f = $plantillasRes->fetch_assoc()): ?>
                  <option value="<?= htmlspecialchars($f['id']) ?>">
                    <?= htmlspecialchars($f['nombre']) ?>
                  </option>
                <?php endwhile; ?>
              <?php else: ?>
                <option disabled>No hay plantillas disponibles</option>
              <?php endif; ?>
            </select>
          </div>

          <div id="mensajeRespuesta" class="alert d-none"></div>

          <input type="hidden" name="usuario_id" value="<?= htmlspecialchars($usuario_id) ?>">
          <input type="hidden" name="proveedor_id" value="<?= htmlspecialchars($prov_id) ?>">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle"></i> Cancelar
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-upload"></i> Subir Archivo
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Tabla principal de archivos -->
<div>
  <?php if (isset($archivosRes) && $archivosRes->num_rows > 0): ?>
    <table class="table table-bordered table-hover archivos-table">
      <thead class="table-light">
        <tr>
          <th>Nombre del Archivo</th>
          <th>Plantilla Asociada</th>
          <th>Fecha de Subida</th>
          <th>Empresa</th>
          <th>Usuario</th>
          <th>Estado de Revisión</th>
          <th data-no-sort>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $archivosRes->fetch_assoc()): ?>
          <tr id="fila-<?= $row['id'] ?>">
            <td><a href="../api/download.php?id=<?= $row['id'] ?>" title="Descargar Archivo"><?= htmlspecialchars($row['nombre_archivo']) ?></a></td>
            <td><?= htmlspecialchars($row['nombre_plantilla'] ?? 'Sin plantilla') ?></td>
            <td><?= htmlspecialchars($row['fecha_subida']) ?></td>
                <td><?= htmlspecialchars($row['nombre_empresa'] ?? '-') ?></td>
            <td><?= htmlspecialchars($row['correo_usuario'] ?? '-') ?></td>
            <td><?= ucfirst(htmlspecialchars($row['revision_estado'] ?? 'pendiente')) ?></td>
            <td>
              <!-- Botón para ver en nueva pestaña -->
              <!-- Poner bien la ruta de ver el documento, ya que depende del  -->
            <?php 
              $nombre = $row['nombre_archivo'];
              $ruta_url = './documentos_subidos/' . urlencode($nombre);
            ?>
            <a href="<?= $ruta_url ?>" target="_blank" class="btn btn-sm btn-info me-1" title="Ver documento">
              <i class="bi bi-eye"></i>
            </a>

              <!-- Botón para eliminar -->
            <button class="btn btn-sm btn-danger"
                    onclick="mostrarModalEliminarArchivo('<?= $row['id'] ?>', '<?= htmlspecialchars($row['nombre_archivo'], ENT_QUOTES) ?>')"
                    title="Eliminar Archivo">
              <i class="bi bi-trash"></i>
            </button>

            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="alert alert-info mt-3">No se encontraron archivos subidos.</div>
  <?php endif; ?>
</div>
<?php
$url_base = '?vista=archivos';
echo generar_paginacion($url_base, $pagina_actual, $total_paginas);
?>
 
<!-- Modal Confirmar Eliminación de Archivo -->
<div class="modal fade" id="modalEliminarArchivo" tabindex="-1" aria-labelledby="modalEliminarArchivoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-mi-color text-white">
        <h5 class="modal-title" id="modalEliminarArchivoLabel">Eliminar Archivo</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p id="eliminarArchivoTexto"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="btnConfirmarEliminarArchivo">Eliminar</button>
      </div>
       <!-- Aquí está el formulario oculto -->
      <form id="formEliminarArchivo" action="eliminar_archivos.php" method="post">
        <input type="hidden" name="id_archivo" id="id_archivo_a_eliminar">
      </form>
    </div>
  </div>
</div>


  <script>
function mostrarModalEliminarArchivo(id, nombreArchivo) {
  const modalElement = document.getElementById('modalEliminarArchivo');
  const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
  
  document.getElementById('eliminarArchivoTexto').textContent = 
    `¿Estás seguro de que deseas eliminar el archivo "${nombreArchivo}"?`;
  
  // Asigna el ID al campo oculto del formulario
  document.getElementById('id_archivo_a_eliminar').value = id;

  // Asigna la acción del botón "Eliminar"
  document.getElementById('btnConfirmarEliminarArchivo').onclick = function () {
    document.getElementById('formEliminarArchivo').submit();
  };

  modal.show();
}
</script>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Función para subir archivos
document.getElementById('formSubirArchivo')?.addEventListener('submit', function(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  const mensajeDiv = document.getElementById('mensajeRespuesta');
  const submitBtn = form.querySelector('button[type="submit"]');
  
  // Deshabilitar botón durante el envío
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';

  fetch(form.action, {
    method: 'POST',
    body: formData,
  })
  .then(response => response.text())
  .then(data => {
    mensajeDiv.classList.remove('d-none', 'alert-danger');
    mensajeDiv.classList.add('alert-success');
    mensajeDiv.innerHTML = data;
    setTimeout(() => location.reload(), 1500);
  })
  .catch(error => {
    mensajeDiv.classList.remove('d-none', 'alert-success');
    mensajeDiv.classList.add('alert-danger');
    mensajeDiv.innerHTML = 'Error al subir el archivo: ' + error.message;
  })
  .finally(() => {
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="bi bi-upload"></i> Subir Archivo';
  });
});


</script>
</body>
</html> 