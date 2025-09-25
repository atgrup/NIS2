<?php 
// =============================
// INICIO DE SESIÓN Y CONEXIÓN
// =============================
if (session_status() === PHP_SESSION_NONE) {
  session_start(); // Inicia sesión si no existe
}

require_once dirname(__DIR__) . '/api/includes/conexion.php'; // Conexión DB

// =============================
// VALIDAR USUARIO
// =============================
$usuario_id = $_SESSION['id_usuario'] ?? null;
$is_admin = false; // Flag para admin (se usará más adelante)

if (!$usuario_id) {
  // Si no hay sesión, no permitimos el acceso
  echo "<p>No estás autenticado. Por favor, inicia sesión.</p>";
  exit;
}

// =============================
// VERIFICAR SI ES ADMINISTRADOR
// =============================
// (tipo_usuario_id = 1 → Administrador)
$tipo_usuario_id = null;
$stmtAdmin = $conexion->prepare("SELECT tipo_usuario_id FROM usuarios WHERE id_usuarios = ?");
$stmtAdmin->bind_param("i", $usuario_id);
$stmtAdmin->execute();
$stmtAdmin->bind_result($tipo_usuario_id);
$stmtAdmin->fetch();
$stmtAdmin->close();

// =============================
// CONSULTA DE PLANTILLAS
// =============================

// Contar total de plantillas para la paginación
$sql_total = "SELECT COUNT(*) as total FROM plantillas";
$result_total = $conexion->query($sql_total);
$total_filas = $result_total->fetch_assoc()['total'];
$total_paginas = ceil($total_filas / $filas_por_pagina);

// Consulta principal con JOIN a consultores
$sql = "
  SELECT p.id, p.nombre, p.consultor_id, c.nombre AS nombre_consultor
  FROM plantillas p
  LEFT JOIN consultores c ON p.consultor_id = c.id
  ORDER BY p.nombre
  LIMIT $inicio, $filas_por_pagina
";
$result = $conexion->query($sql);

if (!$result) {
  echo "<p>Error al consultar plantillas: " . $conexion->error . "</p>";
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listado de Plantillas</title>
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container py-4">
  <div>
    <!-- =============================
         TABLA DE PLANTILLAS
    ============================== -->
    <table class="table table-bordered table-hover plantillas-table">
      <thead class="table-light">
        <tr>
          <th>Nombre de la plantilla</th>
          <th>Autor</th>
          <th>Tipo</th>
          <th data-no-sort>Acciones</th>
        </tr>
      </thead>
      <tbody>
       <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <?php
            // =============================
            // PROCESAR CADA FILA
            // =============================
            $nombre = htmlspecialchars($row['nombre']); // Nombre de la plantilla
            $id = $row['id']; // ID de la plantilla
            $nombre_consultor_raw = $row['nombre_consultor'] ?? '';

            // Consultor = parte antes de la @ o texto por defecto si no existe
            $nombre_consultor_trimmed = $nombre_consultor_raw ? 
              trim(explode('@', $nombre_consultor_raw)[0]) : '<i>Sin consultor</i>';

            // Ruta al archivo en carpeta plantillas
            $ruta_url = '../plantillas_disponibles/' . urlencode($nombre);
            ?>
            <!-- =============================
                 FILA DE TABLA
            ============================== -->
            <tr data-id="<?= $id ?>">
              <!-- Nombre con enlace de descarga -->
              <td><a href="<?= $ruta_url ?>" download class="text-reset text-decoration-underline"><?= $nombre ?></a></td>
              <!-- Consultor -->
              <td><?= $nombre_consultor_trimmed ?></td>
              <!-- Tipo fijo: Plantilla -->
              <td>Plantilla</td>
              <!-- Acciones -->
              <td class="text-center">
                <!-- Ver documento -->
                <a href="<?= $ruta_url ?>" target="_blank" class="btn btn-sm btn-info me-1" title="Ver documento">
                  <i class="bi bi-eye"></i>
                </a>
                <!-- Eliminar (si no es tipo_usuario_id 2 = consultor/proveedor) -->
                <?php if ($tipo_usuario_id !== 2): ?>
                  <button class="btn btn-sm btn-danger btn-eliminar"
                          data-nombre="<?= htmlspecialchars($nombre, ENT_QUOTES) ?>"
                          data-id="<?= htmlspecialchars($id, ENT_QUOTES) ?>">
                    <i class="bi bi-trash"></i>
                  </button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <!-- Si no hay plantillas -->
          <tr><td colspan="4" class="text-center">No hay plantillas.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php
  // =============================
  // PAGINACIÓN
  // =============================
  $url_base = '?vista=plantillas';
  echo generar_paginacion($url_base, $pagina_actual, $total_paginas);
  ?>
  <!-- =============================
       MODAL ELIMINAR PLANTILLA
  ============================== -->
  <div class="modal fade" id="modalEliminarPlantilla" tabindex="-1" aria-labelledby="modalEliminarPlantillaLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-mi-color text-white">
          <h5 class="modal-title" id="modalEliminarPlantillaLabel">Eliminar Plantilla</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <p id="eliminarPlantillaTexto"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-danger" id="btnConfirmarEliminarPlantilla">Eliminar</button>
        </div>
      </div>
    </div>
  </div>
  <script>
// =============================
// ELIMINAR PLANTILLA (con modal + fetch AJAX)
// =============================

// Detectar click en botón eliminar dentro de la tabla
document.querySelector('.plantillas-table tbody').addEventListener('click', function(e) {
  const btn = e.target.closest('button.btn-eliminar');
  if (!btn) return;

  const nombre = btn.getAttribute('data-nombre');
  const id = btn.getAttribute('data-id');
  if (!id) return;

  mostrarModalEliminarPlantilla(nombre, id);
});

// Función que muestra modal y ejecuta petición AJAX
function mostrarModalEliminarPlantilla(nombre, id) {
  const modalElement = document.getElementById('modalEliminarPlantilla');
  const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
  
  // Texto dinámico en el modal
  document.getElementById('eliminarPlantillaTexto').textContent = 
    `¿Estás seguro de que deseas eliminar la plantilla "${nombre}"?`;

  // Clonamos el botón para reiniciar listeners anteriores
  const oldBtn = document.getElementById('btnConfirmarEliminarPlantilla');
  const newBtn = oldBtn.cloneNode(true);
  oldBtn.parentNode.replaceChild(newBtn, oldBtn);

  // Al confirmar, hacemos la petición AJAX
  newBtn.addEventListener('click', function () {
    fetch('eliminar_plantilla.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id=${encodeURIComponent(id)}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Eliminamos la fila de la tabla
        const fila = document.querySelector(`tr[data-id="${id}"]`);
        if (fila) fila.remove();
        modal.hide();
      } else {
        alert('Error: ' + (data.error || 'Error desconocido'));
      }
    })
    .catch(error => {
      console.error('Error de red:', error);
      alert('Ocurrió un error. Intenta de nuevo.');
    });
  });

  // Mostrar modal
  modal.show();
}
  </script>
</body>
</html>
