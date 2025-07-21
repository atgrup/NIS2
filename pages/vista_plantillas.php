<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once dirname(__DIR__) . '/api/includes/conexion.php';

$usuario_id = $_SESSION['id_usuario'] ?? null;
$is_admin = false;

if (!$usuario_id) {
  echo "<p>No estás autenticado. Por favor, inicia sesión.</p>";
  exit;
}

// Verificar si el usuario es administrador (tipo_usuario_id = 1)
$stmtAdmin = $conexion->prepare("SELECT tipo_usuario_id FROM usuarios WHERE id_usuarios = ?");
$stmtAdmin->bind_param("i", $usuario_id);
$stmtAdmin->execute();
$stmtAdmin->bind_result($tipo_usuario_id);
if ($stmtAdmin->fetch() && $tipo_usuario_id == 1) {
  $is_admin = true;
}
$stmtAdmin->close();

// Consultar plantillas con nombre del consultor
$sql = "
  SELECT p.nombre, p.uuid, p.consultor_id, c.nombre AS nombre_consultor
  FROM plantillas p
  LEFT JOIN consultores c ON p.consultor_id = c.id
  ORDER BY p.nombre
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
  </style>
</head>

<body class="container py-4">

  <div style="max-height: 80vh; overflow-y: auto;">
    <table class="table table-bordered table-hover plantillas-table">
      <thead class="table-light">
        <tr>
          <th>Nombre de la plantilla</th>
          <?php if ($is_admin): ?>
            <th>UUID</th>
          <?php endif; ?>
          <th>Consultor</th>
          <th>Tipo</th>
          <th data-no-sort>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php $i = 1; ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <?php
            $nombre = htmlspecialchars($row['nombre']);
            $uuid_raw = $row['uuid'] ?? '';
            $uuid_display = !empty($uuid_raw) ? htmlspecialchars($uuid_raw) : '<i>sin UUID</i>';

            $nombre_consultor_raw = $row['nombre_consultor'] ?? '';
            if ($nombre_consultor_raw) {
              $nombre_consultor_trimmed = trim(explode('@', $nombre_consultor_raw)[0]);
            } else {
              $nombre_consultor_trimmed = '<i>Sin consultor</i>';
            }

            $ruta_url = '../plantillas_disponibles/' . urlencode($nombre);
            ?>
            <tr data-uuid="<?= $uuid_raw ?>">
              <td><a href="<?= $ruta_url ?>" download class="text-reset text-decoration-underline"><?= $nombre ?></a></td>
              <?php if ($is_admin): ?>
                <td><code><?= $uuid_display ?></code></td>
              <?php endif; ?>
              <td><?= $nombre_consultor_trimmed ?></td>
              <td>Plantilla</td>
             <td class="text-center">
              <!-- Botón para ver en nueva pestaña -->
              <a href="<?= $ruta_url ?>" target="_blank" class="btn btn-sm btn-info me-1" title="Ver documento">
                <i class="bi bi-eye"></i>
              </a>

              <!-- Botón para eliminar -->

              <button class="btn btn-sm btn-danger"
                      onclick="mostrarModalEliminarPlantilla('<?= addslashes($nombre) ?>', '<?= addslashes($uuid_raw) ?>')"
                      <?= empty($uuid_raw) ? 'disabled' : '' ?>>
                <i class="bi bi-trash"></i>
              </button>
            </td>

            <?php $i++; ?>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="<?= $is_admin ? 5 : 4 ?>" class="text-center">No hay plantillas.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div id="paginacion" class="mt-3 d-flex justify-content-center gap-2"></div>

  <!-- Modal Confirmar Eliminación -->
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
    document.addEventListener('DOMContentLoaded', () => {
      const filasPorPagina = 10;
      let paginaActual = 1;

      const filas = [...document.querySelectorAll('table.plantillas-table tbody tr')];
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

    function mostrarModalEliminarPlantilla(nombre, uuid) {
        const modalElement = document.getElementById('modalEliminarPlantilla');
        const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
        
        const textoModal = document.getElementById('eliminarPlantillaTexto');
        const btnConfirmar = document.getElementById('btnConfirmarEliminarPlantilla');

        textoModal.textContent = `¿Estás seguro de que deseas eliminar la plantilla "${nombre}"?`;
        
        btnConfirmar.onclick = function() {
            fetch('eliminar_plantilla.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `uuid=${encodeURIComponent(uuid)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const filaParaEliminar = document.querySelector(`tr[data-uuid="${uuid}"]`);
                    if (filaParaEliminar) {
                        filaParaEliminar.remove();
                    }
                    modal.hide();
                } else {
                    alert('Error al eliminar la plantilla: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error en la petición de eliminación:', error);
                alert('Ocurrió un error de red. Por favor, inténtalo de nuevo.');
            });
        };

        modal.show();
    }
  </script>

</body>
</html>
