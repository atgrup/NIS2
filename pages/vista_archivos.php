<?php
// ==========================
// SESIÓN Y CONEXIÓN A BD
// ==========================

// La función session_status() devuelve el estado de la sesión.
// Con PHP_SESSION_NONE comprobamos que aún no se haya iniciado una sesión antes de llamar a session_start().
// Esto evita el error "session already started".
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Calculamos la ruta absoluta del archivo de conexión, evitando problemas de rutas relativas
// cuando este archivo se incluya desde diferentes carpetas.
$ruta_conexion = __DIR__ . '/../api/includes/conexion.php';

// Validamos que el archivo de conexión realmente exista para evitar "include fatal error".
if (!file_exists($ruta_conexion)) {
    die("Error: No se encontró el archivo de conexión en $ruta_conexion");
}

// Importamos el archivo que contiene la conexión a la base de datos MySQL.
require $ruta_conexion;

// ==========================
// VARIABLES DE PAGINACIÓN
// ==========================

// El parámetro GET "pagina" indica qué página mostrar en la tabla de archivos.
// Si no existe, asumimos la primera página (1).
// max(1, ...) garantiza que nunca sea menor que 1 (ej: si alguien pone ?pagina=-2).
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;

// Cantidad de registros que se mostrarán por página en la tabla.
$filas_por_pagina = 10;

// Calculamos el desplazamiento (offset) que usará el LIMIT en la consulta SQL.
// Ejemplo: si estamos en la página 3 con 10 filas por página → inicio = 20.
$inicio = ($pagina_actual - 1) * $filas_por_pagina;

// ==========================
// MENSAJES DE OPERACIONES
// ==========================

// Recuperamos de la sesión los mensajes de éxito o error que se enviaron desde otras páginas.
// Ejemplo: después de subir o eliminar un archivo.
$mensaje_exito = $_SESSION['mensaje_exito'] ?? null;
$mensaje_error = $_SESSION['mensaje_error'] ?? null;

// Eliminamos los mensajes de la sesión para que no se repitan si recargamos la página.
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);

// ==========================
// DATOS DE SESIÓN DEL USUARIO
// ==========================

// Rol del usuario (se fuerza a minúsculas para evitar problemas de comparación).
$rol = strtolower($_SESSION['rol']);

// Guardamos el ID del usuario que inició sesión (clave primaria en la tabla usuarios).
$usuario_id = $_SESSION['id_usuario'];

// ID del proveedor al que pertenece el usuario (si aplica). 
// Puede ser null si el usuario no está asociado a ningún proveedor.
$prov_id = $_SESSION['proveedor_id'] ?? null;

// ==========================
// CONSULTAS SEGÚN EL ROL
// ==========================

// Diferenciamos las consultas según el tipo de usuario:
// - ADMINISTRADOR: puede ver todos los archivos.
// - CONSULTOR: también ve todos los archivos (solo lectura).
// - PROVEEDOR: únicamente ve sus propios archivos (los que él subió).
if ($rol === 'administrador' || $rol === 'consultor') {
    // --------------------------
    // CASO: ADMIN/CONSULTOR
    // --------------------------

    // Obtenemos el total de archivos en la base de datos (para la paginación).
    $sql_total = "SELECT COUNT(*) as total FROM archivos_subidos";
    $result_total = $conexion->query($sql_total);
    if (!$result_total) {
        die("Error en consulta total archivos: " . $conexion->error);
    }
    $total_filas = $result_total->fetch_assoc()['total'];

    // Consulta con JOINs para traer información detallada de cada archivo:
    // - nombre de la plantilla asociada
    // - empresa a la que pertenece
    // - usuario que lo subió
    // - estado de revisión
    $sql = "SELECT 
                a.id,
                a.nombre_archivo, 
                p.nombre AS nombre_plantilla, 
                a.fecha_subida, 
                pr.nombre_empresa, 
                u.correo AS correo_usuario,
                a.revision_estado
            FROM archivos_subidos a
            LEFT JOIN plantillas p ON a.plantilla_id = p.id
            LEFT JOIN proveedores pr ON a.proveedor_id = pr.id
            LEFT JOIN usuarios u ON a.usuario_id = u.id_usuarios
            ORDER BY a.fecha_subida DESC
            LIMIT ?, ?";

    // Usamos consultas preparadas para prevenir inyección SQL.
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conexion->error);
    }

    // Enlazamos los parámetros de LIMIT (inicio, cantidad de filas).
    $stmt->bind_param("ii", $inicio, $filas_por_pagina);
    $stmt->execute();
    $archivosRes = $stmt->get_result();

} elseif ($rol === 'proveedor') {
    // --------------------------
    // CASO: PROVEEDOR
    // --------------------------

    // Contamos únicamente los archivos subidos por este usuario.
    $sql_total = "SELECT COUNT(*) as total FROM archivos_subidos WHERE usuario_id = ?";
    $stmt_total = $conexion->prepare($sql_total);
    if (!$stmt_total) {
        die("Error en la preparación de la consulta total: " . $conexion->error);
    }
    $stmt_total->bind_param("i", $usuario_id);
    $stmt_total->execute();
    $total_filas = $stmt_total->get_result()->fetch_assoc()['total'];

    // Consulta de archivos, pero filtrada solo a los que pertenecen a este usuario.
    $sql = "SELECT 
                a.id,
                a.nombre_archivo, 
                p.nombre AS nombre_plantilla, 
                a.fecha_subida, 
                pr.nombre_empresa, 
                u.correo AS correo_usuario,
                a.revision_estado
            FROM archivos_subidos a
            LEFT JOIN plantillas p ON a.plantilla_id = p.id
            LEFT JOIN proveedores pr ON a.proveedor_id = pr.id
            LEFT JOIN usuarios u ON a.usuario_id = u.id_usuarios
            WHERE a.usuario_id = ?
            ORDER BY a.fecha_subida DESC
            LIMIT ?, ?";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        die("Error en la preparación de la consulta archivos: " . $conexion->error);
    }

    // Enlazamos usuario_id + paginación.
    $stmt->bind_param("iii", $usuario_id, $inicio, $filas_por_pagina);
    $stmt->execute();
    $archivosRes = $stmt->get_result();

} else {
    // Si el rol es inválido o no está contemplado, no se devuelven resultados.
    $total_filas = 0;
    $archivosRes = null;
}

// Número total de páginas = total de filas dividido entre filas por página (redondeado hacia arriba).
$total_paginas = ($total_filas > 0) ? ceil($total_filas / $filas_por_pagina) : 1;

// ==========================
// CONSULTA DE PLANTILLAS
// ==========================

// Obtenemos todas las plantillas (se usarán en el modal de subida de archivo).
$plantillasRes = $conexion->query("SELECT id, nombre FROM plantillas");
if (!$plantillasRes) {
    die("Error en consulta de plantillas: " . $conexion->error);
}
$estados_revision = ['pendiente', 'aprobado', 'rechazado'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <!-- HEAD: Aquí van configuraciones globales y recursos externos -->
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Archivos Subidos</title>

  <!-- Bootstrap CSS: estilos predefinidos -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Bootstrap Icons: iconos vectoriales -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />

  <!-- Estilos personalizados para mejorar la UI -->
  <style>
    .form-label { color: grey; }
    .table-scroll table { width: 100%; margin-bottom: 0; }
    .pagination-container { margin-top: 15px; }
    .modal-header { background-color: #0d6efd; color: white; }
    .btn-close-white { filter: invert(1); }
    /* Nuevas clases para los estados de revisión */
    .estado-pendiente { background-color: #fff3cd; color: #856404; }
    .estado-aprobado { background-color: #d1e7dd; color: #0f5132; }
    .estado-rechazado { background-color: #f8d7da; color: #721c24; }
  </style>
</head>

<!-- BODY: aquí se muestra la interfaz al usuario -->
<body class="p-4">

  <!-- ==========================
       MENSAJES FLASH (ÉXITO/ERROR)
       ========================== -->
  <?php if ($mensaje_exito): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $mensaje_exito ?>
        <!-- Botón para cerrar el mensaje -->
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if ($mensaje_error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $mensaje_error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- ==========================
       MODAL: SUBIR NUEVO ARCHIVO
       ========================== -->
  <?php if ($rol == 'consultor'|| $rol === 'proveedor'): ?>
    <div class="modal fade" id="modalSubirArchivo" tabindex="-1" aria-labelledby="modalSubirArchivoLabel" aria-hidden="true">
      <div class="modal-dialog">
        <!-- El formulario envía los datos al script "subir_archivo_rellenado.php" -->
        <form id="formSubirArchivoModal" action="subir_archivo_rellenado.php" method="post" enctype="multipart/form-data">
          <div class="modal-content">
            <div class="modal-header bg-mi-color text-white">
              <h5 class="modal-title" id="modalSubirArchivoLabel">Subir Nuevo Archivo</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
              <!-- Campo de archivo (solo acepta PDF) -->
              <div class="mb-3">
                <label for="archivo" class="form-label fw-bold">Seleccionar Archivo</label>
                <input type="file" class="form-control" id="archivo-modal" name="archivo" required accept=".pdf">
                <div class="form-text">Formatos permitidos: PDF</div>
              </div>
              <!-- Desplegable con todas las plantillas -->
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
              <!-- Mensajes dinámicos de la subida AJAX -->
              <div id="mensajeRespuesta" class="alert d-none"></div>
              <!-- Campos ocultos para enviar el usuario y proveedor que suben -->
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

  <!-- ==========================
       TABLA DE ARCHIVOS SUBIDOS
       ========================== -->
  <div>
    <?php if (isset($archivosRes) && $archivosRes && $archivosRes->num_rows > 0): ?>
      <table class="table table-bordered table-hover archivos-table">
        <thead>
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
          <?php while ($row = $archivosRes->fetch_assoc()): ?>
            <tr id="fila-<?= $row['id'] ?>">
              <td><?= htmlspecialchars($row['nombre_archivo']) ?></td>
              <td><?= htmlspecialchars($row['nombre_plantilla'] ?? 'Sin plantilla') ?></td>
              <td><?= htmlspecialchars($row['fecha_subida']) ?></td>
              <td><?= htmlspecialchars($row['nombre_empresa'] ?? '-') ?></td>
              <td><?= isset($row['correo_usuario']) ? htmlspecialchars(explode('@', $row['correo_usuario'])[0]) : '-' ?></td>
              <td
                <?php
                  $estado = strtolower($row['revision_estado'] ?? 'pendiente');
                  $claseEstado = '';
                  if ($estado === 'pendiente') $claseEstado = 'estado-pendiente';
                  elseif ($estado === 'aprobado') $claseEstado = 'estado-aprobado';
                  elseif ($estado === 'rechazado') $claseEstado = 'estado-rechazado';
                ?>
                class="<?= $claseEstado ?>"
              >
                <?php if ($rol === 'administrador' || $rol === 'consultor'): ?>
                  <select class="form-select form-select-sm estado-select" data-id="<?= $row['id'] ?>">
                    <?php foreach ($estados_revision as $estadoOpt): ?>
                      <option value="<?= $estadoOpt ?>" <?= $row['revision_estado'] === $estadoOpt ? 'selected' : '' ?>><?= ucfirst($estadoOpt) ?></option>
                    <?php endforeach; ?>
                  </select>
                <?php else: ?>
                  <?= ucfirst(htmlspecialchars($row['revision_estado'] ?? 'pendiente')) ?>
                <?php endif; ?>
              </td>
              <td>
                <a href="visualizar_archivo.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-info me-1" title="Ver documento">
                  <i class="bi bi-eye"></i>
                </a>
                <button class="btn btn-sm btn-danger" onclick="mostrarModalEliminarArchivo('<?= $row['id'] ?>', '<?= htmlspecialchars($row['nombre_archivo'], ENT_QUOTES) ?>')" title="Eliminar Archivo">
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

  <!-- ==========================
       PAGINACIÓN
       ========================== -->
  <?php
  // Se usa una función auxiliar para generar los enlaces de paginación.
  $url_base = '?vista=archivos';
  if (function_exists('generar_paginacion')) {
      echo generar_paginacion($url_base, $pagina_actual, $total_paginas);
  }
  ?>

  <!-- ==========================
       MODAL CONFIRMACIÓN ELIMINAR
       ========================== -->
  <div class="modal fade" id="modalEliminarArchivo" tabindex="-1" aria-labelledby="modalEliminarArchivoLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-mi-color text-white">
          <h5 class="modal-title" id="modalEliminarArchivoLabel">Eliminar Archivo</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <!-- Aquí se inserta dinámicamente el nombre del archivo a eliminar -->
          <p id="eliminarArchivoTexto"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-danger" id="btnConfirmarEliminarArchivo">Eliminar</button>
        </div>
        <!-- Formulario oculto que realmente enviará la petición de eliminación -->
        <form id="formEliminarArchivo" action="eliminar_archivos.php" method="post">
          <input type="hidden" name="id_archivo" id="id_archivo_a_eliminar">
        </form>
      </div>
    </div>
  </div>

  <!-- ==========================
       SCRIPTS JAVASCRIPT
       ========================== -->

  <!-- Bootstrap JS: necesario para modales y componentes dinámicos -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Script: Mostrar modal para eliminar archivo -->
  <script>
  /**
   * Función que abre el modal de confirmación de eliminación de archivo.
   * Rellena dinámicamente el texto del modal con el nombre del archivo y
   * asocia el botón de confirmación con el formulario oculto que envía la eliminación.
   */
  function mostrarModalEliminarArchivo(id, nombreArchivo) {
    const modalElement = document.getElementById('modalEliminarArchivo');
    const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
    
    // Mensaje de confirmación con el nombre real del archivo.
    document.getElementById('eliminarArchivoTexto').textContent = 
      `¿Estás seguro de que deseas eliminar el archivo "${nombreArchivo}"?`;
    
    // Guardamos en un input oculto el ID del archivo que se eliminará.
    document.getElementById('id_archivo_a_eliminar').value = id;

    // Cuando el usuario confirma, enviamos el formulario oculto.
    document.getElementById('btnConfirmarEliminarArchivo').onclick = function () {
      document.getElementById('formEliminarArchivo').submit();
    };

    modal.show();
  }
  </script>

  <!-- Script: Subida de archivos vía AJAX -->
  <script>
  /**
   * Maneja el envío del formulario de subida de archivo.
   * En lugar de recargar la página, se hace con fetch() para enviar el archivo de forma asíncrona.
   * Se muestra un mensaje al usuario y se recarga la página después de subir correctamente.
   */
  document.getElementById('formSubirArchivo')?.addEventListener('submit', function(e) {
    e.preventDefault(); // Prevenimos el envío normal del formulario.

    const form = e.target;
    const formData = new FormData(form); // Empaquetamos los datos, incluyendo el archivo.
    const mensajeDiv = document.getElementById('mensajeRespuesta');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Bloqueamos el botón de envío para evitar múltiples clics.
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';

    fetch(form.action, {
      method: 'POST',
      body: formData,
    })
    .then(response => response.text()) // Esperamos respuesta en texto plano (puede ser HTML).
    .then data => {
      // Mostramos mensaje de éxito.
      mensajeDiv.classList.remove('d-none', 'alert-danger');
      mensajeDiv.classList.add('alert-success');
      mensajeDiv.innerHTML = data;
      // Recargamos la página para actualizar la tabla después de 1.5s.
      setTimeout(() => location.reload(), 1500);
    })
    .catch(error => {
      // Mostramos mensaje de error si la petición falla.
      mensajeDiv.classList.remove('d-none', 'alert-success');
      mensajeDiv.classList.add('alert-danger');
      mensajeDiv.innerHTML = 'Error al subir el archivo: ' + error.message;
    })
    .finally(() => {
      // Restauramos el botón al estado inicial.
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="bi bi-upload"></i> Subir Archivo';
    });
  });
  </script>

  <!-- Script: Validación de archivo (solo PDF) -->
  <script>
  /**
   * Antes de enviar el formulario, validamos que:
   * 1. Se haya seleccionado un archivo.
   * 2. El archivo tenga extensión .pdf (en minúsculas).
   * Esto evita que el usuario suba archivos no permitidos.
   */
  document.getElementById('formSubirArchivoModal').addEventListener('submit', function(e) {
    const archivoInput = document.getElementById('archivo-modal');
    const archivo = archivoInput.files[0];

    if (!archivo) {
      alert('Por favor selecciona un archivo.');
      e.preventDefault();
      return false;
    }

    const nombreArchivo = archivo.name.toLowerCase();

    if (!nombreArchivo.endsWith('.pdf')) {
      alert('Solo se permiten archivos PDF.');
      e.preventDefault();
      return false;
    }

    return true;
  });
  </script>

  <script>
document.querySelectorAll('.estado-select').forEach(function(select) {
  select.addEventListener('change', function() {
    const id = this.dataset.id;
    const nuevoEstado = this.value;
    this.disabled = true;
    fetch('actualizar_estado_revision.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id=${encodeURIComponent(id)}&estado=${encodeURIComponent(nuevoEstado)}`
    })
    .then(res => res.text())
    .then(msg => {
      this.disabled = false;
      // Cambia el color de la celda según el nuevo estado
      const td = this.closest('td');
      td.classList.remove('estado-pendiente', 'estado-aprobado', 'estado-rechazado');
      if (nuevoEstado === 'pendiente') td.classList.add('estado-pendiente');
      else if (nuevoEstado === 'aprobado') td.classList.add('estado-aprobado');
      else if (nuevoEstado === 'rechazado') td.classList.add('estado-rechazado');
    })
    .catch(() => {
      alert('Error al actualizar el estado');
      this.disabled = false;
    });
  });
});
</script>
</body>
</html>
