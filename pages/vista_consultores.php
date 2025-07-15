<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../api/includes/conexion.php';

$rol = $_SESSION['rol'] ?? '';
$isAdmin = ($rol === 'administrador');

// Eliminar consultor (solo si eres admin)
if ($isAdmin && isset($_GET['eliminar'])) {
    $consultor_id = intval($_GET['eliminar']);

    // Obtener usuario_id relacionado
    $stmt = $conexion->prepare("SELECT usuario_id FROM consultores WHERE id = ?");
    $stmt->bind_param("i", $consultor_id);
    $stmt->execute();
    $stmt->bind_result($usuario_id);
    $stmt->fetch();
    $stmt->close();

    if ($usuario_id) {
        $conexion->query("DELETE FROM consultores WHERE id = $consultor_id");
        $conexion->query("DELETE FROM usuarios WHERE id_usuarios = $usuario_id");
        $_SESSION['mensaje'] = "Consultor eliminado correctamente";
    } else {
        $_SESSION['error'] = "No se pudo encontrar el usuario del consultor";
    }

    header("Location: vistaconsultores.php");
    exit;
}
?>

<style>
  table.consultores-table th:first-child,
  table.consultores-table td:first-child {
    display: none;
  }
</style>

<div style="max-height: 90%; overflow-y: auto;">
  <table class="table table-bordered border-secondary w-100 consultores-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Correo</th>
        <th>Nombre Consultor</th>
        <?php if ($isAdmin): ?>
          <th>Acciones</th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php
      $sql = "SELECT c.id, u.correo FROM consultores c JOIN usuarios u ON c.usuario_id = u.id_usuarios ORDER BY c.id";
      $result = $conexion->query($sql);
      $i = 1;

      while ($row = $result->fetch_assoc()) {
          $correo = htmlspecialchars($row['correo']);
          $nombre = strstr($correo, '@', true);
          $consultor_id = $row['id'];

          echo "<tr>
                  <th scope='row'>{$i}</th>
                  <td>{$correo}</td>
                  <td>" . htmlspecialchars($nombre) . "</td>";

          if ($isAdmin) {
              echo "<td>
                      <a href='editar_consultor.php?id={$consultor_id}' class='btn btn-sm btn-primary'>Editar</a>
                      <a href='vistaconsultores.php?eliminar={$consultor_id}' class='btn btn-sm btn-danger' onclick='return confirm(\"¿Eliminar este consultor?\")'>Eliminar</a>
                    </td>";
          }

          echo "</tr>";
          $i++;
      }
      ?>
    </tbody>
  </table>
</div>

<div id="paginacion" class="mt-3 d-flex justify-content-center gap-2"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const filasPorPagina = 10;
  let paginaActual = 1;
  const tabla = document.querySelector('table.consultores-table');
  const tbody = tabla.querySelector('tbody');
  const filas = Array.from(tbody.querySelectorAll('tr'));
  const pagDiv = document.getElementById('paginacion');

  function mostrarPagina(pagina) {
    const inicio = (pagina - 1) * filasPorPagina;
    const fin = inicio + filasPorPagina;

    filas.forEach((fila, i) => {
      fila.style.display = (i >= inicio && i < fin) ? '' : 'none';
    });
  }

  function crearPaginacion() {
    pagDiv.innerHTML = '';
    const totalPaginas = Math.ceil(filas.length / filasPorPagina);

    const btnPrimera = document.createElement('button');
    btnPrimera.innerHTML = '⏮️';
    btnPrimera.className = 'btn btn-outline-primary';
    btnPrimera.disabled = paginaActual === 1;
    btnPrimera.addEventListener('click', () => {
      paginaActual = 1;
      mostrarPagina(paginaActual);
      crearPaginacion();
    });
    pagDiv.appendChild(btnPrimera);

    for (let i = 1; i <= totalPaginas; i++) {
      const btn = document.createElement('button');
      btn.textContent = i;
      btn.className = 'btn ' + (i === paginaActual ? 'btn-primary' : 'btn-outline-primary');
      btn.addEventListener('click', () => {
        paginaActual = i;
        mostrarPagina(paginaActual);
        crearPaginacion();
      });
      pagDiv.appendChild(btn);
    }

    const btnUltima = document.createElement('button');
    btnUltima.innerHTML = '⏭️';
    btnUltima.className = 'btn btn-outline-primary';
    btnUltima.disabled = paginaActual === totalPaginas;
    btnUltima.addEventListener('click', () => {
      paginaActual = totalPaginas;
      mostrarPagina(paginaActual);
      crearPaginacion();
    });
    pagDiv.appendChild(btnUltima);
  }

  mostrarPagina(paginaActual);
  crearPaginacion();
});
</script>
