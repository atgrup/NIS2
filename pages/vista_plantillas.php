<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once dirname(__DIR__) . '/api/includes/conexion.php'; // Asegúrate que aquí defines $conexion

// Comprobar usuario autenticado y obtener ID
$usuario_id = $_SESSION['rol'] ?? null;
$is_admin = false;

if (!$usuario_id) {
  // No está autenticado: podrías redirigir o mostrar mensaje
  // header("Location: login.php"); exit;
  echo "<p>No estás autenticado. Por favor, inicia sesión.</p>";
  exit;
}

// Verificar si es admin
$stmtAdmin = $conexion->prepare("SELECT tipo_usuario_id FROM usuarios WHERE id_usuarios = ?");
$stmtAdmin->bind_param("i", $usuario_id);
$stmtAdmin->execute();
$stmtAdmin->bind_result($tipo_usuario_id);
if ($stmtAdmin->fetch() && $tipo_usuario_id == 1) {
  $is_admin = true;
  if ($is_admin) {
    $uuid_display = $uuid ? $uuid : '<i>Sin UUID</i>';
    echo "<td><code>$uuid_display</code></td>";
  }

}

$stmtAdmin->close();

// Obtener plantillas
$sql = "SELECT nombre, uuid, consultor_id FROM plantillas ORDER BY nombre";
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
    /* Puedes quitar esta parte si quieres mostrar todas las columnas */
    table.plantillas-table th:first-child,
    table.plantillas-table td:first-child {
      display: none;
    }
  </style>
</head>

<body class="container py-4">

  <div style="max-height: 80vh; overflow-y: auto;">
    <table class="table table-bordered plantillas-table">
      <thead>
        <tr>
          <th scope="col">#</th>
          <th>Nombre de la plantilla</th>
          <?php if ($is_admin): ?>
            <th>UUID</th>
          <?php endif; ?>
          <th>Consultor ID</th>
          <th>Tipo</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php $i = 1; ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <?php
            $nombre = htmlspecialchars($row['nombre']);
            $uuid = htmlspecialchars($row['uuid'] ?? '');
            $consultor_id = htmlspecialchars($row['consultor_id'] ?? '');
            $ruta_url = '../plantillas_disponibles/' . urlencode($nombre);
            ?>
            <tr>
              <th scope="row"><?= $i ?></th>
              <td><a href="<?= $ruta_url ?>" download class="text-reset text-decoration-underline"><?= $nombre ?></a></td>
              <?php if ($is_admin): ?>
                <td><code><?= $uuid ?: '<i>sin UUID</i>' ?></code></td>
              <?php endif; ?>
              <td><?= $consultor_id ?: '<i>Sin consultor</i>' ?></td>
              <td>Plantilla</td>
            </tr>
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

  <!-- Paginación -->
  <div id="paginacion" class="mt-3 d-flex justify-content-center gap-2"></div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const filasPorPagina = 10;
      let paginaActual = 1;

      const tabla = document.querySelector('table.plantillas-table');
      const tbody = tabla.querySelector('tbody');
      const filas = Array.from(tbody.querySelectorAll('tr'));
      const pagDiv = document.getElementById('paginacion');

      function mostrarPagina(pagina) {
        const inicio = (pagina - 1) * filasPorPagina;
        const fin = inicio + filasPorPagina;
        filas.forEach((fila, i) =>
          fila.style.display = (i >= inicio && i < fin) ? '' : 'none'
        );
      }

      function crearPaginacion() {
        pagDiv.innerHTML = '';
        const totalPaginas = Math.ceil(filas.length / filasPorPagina);

        const btnPrimera = document.createElement('button');
        btnPrimera.textContent = '⏮️';
        btnPrimera.className = 'btn btn-outline-primary';
        btnPrimera.disabled = paginaActual === 1;
        btnPrimera.addEventListener('click', () => { paginaActual = 1; mostrarPagina(paginaActual); crearPaginacion(); });
        pagDiv.appendChild(btnPrimera);

        for (let p = 1; p <= totalPaginas; p++) {
          const btn = document.createElement('button');
          btn.textContent = p;
          btn.className = 'btn ' + (p === paginaActual ? 'btn-primary' : 'btn-outline-primary');
          btn.addEventListener('click', () => { paginaActual = p; mostrarPagina(paginaActual); crearPaginacion(); });
          pagDiv.appendChild(btn);
        }

        const btnUltima = document.createElement('button');
        btnUltima.textContent = '⏭️';
        btnUltima.className = 'btn btn-outline-primary';
        btnUltima.disabled = paginaActual === totalPaginas;
        btnUltima.addEventListener('click', () => { paginaActual = totalPaginas; mostrarPagina(paginaActual); crearPaginacion(); });
        pagDiv.appendChild(btnUltima);
      }

      mostrarPagina(paginaActual);
      crearPaginacion();
    });
  </script>
</body>

</html>