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

// Consulta para plantillas (usada en el modal)
$plantillasRes = $conexion->query("SELECT id, nombre FROM plantillas");
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
  </style>
</head>
<body class="p-4">



<!-- Modal para subir archivos -->
<?php if ($rol !== 'consultor'): ?>
<div class="modal fade" id="modalSubirArchivo" tabindex="-1" aria-labelledby="modalSubirArchivoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formSubirArchivo" action="subir_archivo_rellenado.php" method="post" enctype="multipart/form-data" class="modal-content">
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


  <div id="paginacion" class="mt-3 d-flex justify-content-center gap-2"></div>
<?php else: ?>
  <div class="alert alert-info mt-3">No se encontraron archivos subidos.</div>
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

  fetch(form.action, {
    method: 'POST',
    body: formData,
  })
  .then(response => response.text())
  .then(data => {
    mensajeDiv.innerHTML = `<div class="alert alert-info">${data}</div>`;
    setTimeout(() => location.reload(), 1500);
  })
  .catch(() => {
    mensajeDiv.innerHTML = `<div class="alert alert-danger">Error al subir el archivo.</div>`;
  });
});


// Función para eliminar archivos - Versión mejorada
document.querySelectorAll('.eliminar-archivo').forEach(btn => {
  btn.addEventListener('click', async function() {
    const id = this.getAttribute('data-id');
    const fila = document.getElementById(`fila-${id}`);
    
    if (!confirm('¿Estás seguro de eliminar este archivo?')) return;
    
    try {
      const response = await fetch(`../api/eliminar_archivo.php?id=${id}`);
      
      if (!response.ok) {
        throw new Error('Error en la respuesta del servidor');
      }
      
      const data = await response.json();
      
      if (data.success) {
        fila.remove();
        // Opcional: Mostrar notificación de éxito
        console.log('Archivo eliminado correctamente');
      } else {
        throw new Error(data.message || 'Error al eliminar el archivo');
      }
    } catch (error) {
      console.error('Error:', error);
      alert(`Error al eliminar: ${error.message}`);
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