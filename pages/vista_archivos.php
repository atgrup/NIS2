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

// Manejo de eliminación directa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_archivo'])) {
    $id_eliminar = (int)$_POST['id_archivo'];
    
    try {
        if ($_SESSION['rol'] === 'administrador') {
            $sql = "DELETE FROM archivos_subidos WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $id_eliminar);
        } elseif ($_SESSION['rol'] === 'proveedor') {
            $sql = "DELETE FROM archivos_subidos WHERE id = ? AND proveedor_id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ii", $id_eliminar, $_SESSION['proveedor_id']);
        }
        
        if (isset($stmt) && $stmt->execute()) {
            $_SESSION['mensaje_exito'] = "Archivo eliminado correctamente";
        } else {
            $_SESSION['mensaje_error'] = "No se pudo eliminar el archivo";
        }
    } catch (Exception $e) {
        $_SESSION['mensaje_error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Mostrar mensajes
$mensaje_exito = $_SESSION['mensaje_exito'] ?? null;
$mensaje_error = $_SESSION['mensaje_error'] ?? null;
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);

$rol = strtolower($_SESSION['rol']);
$usuario_id = $_SESSION['id_usuario'];
$prov_id = $_SESSION['proveedor_id'] ?? null;

// Consulta para archivos según el rol
if ($rol === 'administrador') {
    $sql = "SELECT a.*, p.nombre AS nombre_plantilla FROM archivos_subidos a
            LEFT JOIN plantillas p ON a.plantilla_id = p.id
            ORDER BY a.fecha_subida DESC";
    $archivosRes = $conexion->query($sql);
} elseif ($rol === 'proveedor') {
    $sql = "SELECT a.*, p.nombre AS nombre_plantilla FROM archivos_subidos a
            LEFT JOIN plantillas p ON a.plantilla_id = p.id
            WHERE a.proveedor_id = ?
            ORDER BY a.fecha_subida DESC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $prov_id);
    $stmt->execute();
    $archivosRes = $stmt->get_result();
}

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
    .table-scroll {
      max-height: 70vh;
      overflow-y: auto;
      border: 1px solid #dee2e6;
      border-radius: 0.25rem;
      margin-top: 20px;
    }
    .table-scroll table { width: 100%; margin-bottom: 0; }
    .pagination-container { margin-top: 15px; }
    .modal-header { background-color: #0d6efd; color: white; }
    .btn-close-white { filter: invert(1); }
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

<!-- Botón para abrir el modal -->


<!-- Formulario oculto para eliminación -->
<form id="formEliminarArchivo" method="post" style="display: none;">
    <input type="hidden" name="eliminar_archivo" value="1">
    <input type="hidden" name="id_archivo" id="id_archivo_a_eliminar">
</form>

<!-- Modal para subir archivos -->
<?php if ($rol !== 'consultor'): ?>
<div class="modal fade" id="modalSubirArchivo" tabindex="-1" aria-labelledby="modalSubirArchivoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formSubirArchivo" action="subir_archivo_rellenado.php" method="post" enctype="multipart/form-data">
        <div class="modal-header">
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
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Mensaje para consultores -->
<?php if ($rol === 'consultor'): ?>
  <div class="alert alert-warning">Los consultores no pueden subir archivos.</div>
<?php endif; ?>

<!-- Tabla principal de archivos -->
<div class="table-scroll">
  <?php if (isset($archivosRes) && $archivosRes->num_rows > 0): ?>
    <table class="table table-striped table-bordered align-middle archivos-table">
      <thead>
        <tr>
          <th>Nombre del Archivo</th>
          <th>Plantilla Asociada</th>
          <th>Fecha de Subida</th>
          <th>Estado de Revisión</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $archivosRes->fetch_assoc()): ?>
          <tr id="fila-<?= $row['id'] ?>">
            <td><?= htmlspecialchars($row['nombre_archivo']) ?></td>
            <td><?= htmlspecialchars($row['nombre_plantilla'] ?? 'Sin plantilla') ?></td>
            <td><?= htmlspecialchars($row['fecha_subida']) ?></td>
            <td><?= ucfirst(htmlspecialchars($row['revision_estado'] ?? 'pendiente')) ?></td>
            <td>
              <a href="<?= htmlspecialchars($row['archivo_url']) ?>" 
                 target="_blank" 
                 class="btn btn-sm btn-primary"
                 title="Ver Archivo">
                <i class="bi bi-eye"></i> 
              </a>
              
              <a href="../api/download.php?id=<?= $row['id'] ?>" 
                 class="btn btn-sm btn-success"
                 title="Descargar Archivo">
                <i class="bi bi-download"></i> 
              </a>
              
              <button class="btn btn-sm btn-danger eliminar-archivo" 
                      data-id="<?= $row['id'] ?>"
                      title="Eliminar Archivo">
                <i class="bi bi-trash"></i> 
              </button>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="alert alert-info">No se encontraron archivos subidos.</div>
  <?php endif; ?>
</div>

<!-- Paginación -->
<div id="paginacion" class="pagination-container d-flex justify-content-center gap-2"></div>

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

// Función para eliminar archivos
document.querySelectorAll('.eliminar-archivo').forEach(btn => {
  btn.addEventListener('click', function() {
    const id = this.getAttribute('data-id');
    
    if (confirm('¿Estás seguro de eliminar este archivo?')) {
      document.getElementById('id_archivo_a_eliminar').value = id;
      document.getElementById('formEliminarArchivo').submit();
    }
  });
});

// Paginación
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

    if (totalPaginas <= 1) return;

    const btnPrimera = document.createElement('button');
    btnPrimera.innerHTML = '&laquo;';
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
    btnUltima.innerHTML = '&raquo;';
    btnUltima.className = 'btn btn-outline-primary';
    btnUltima.disabled = paginaActual === totalPaginas;
    btnUltima.onclick = () => { paginaActual = totalPaginas; mostrarPagina(paginaActual); crearPaginacion(); };
    pagDiv.appendChild(btnUltima);
  }

  if (filas.length > 0) {
    mostrarPagina(paginaActual);
    crearPaginacion();
  }
});
</script>
</body>
</html>