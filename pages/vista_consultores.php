<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../api/includes/conexion.php';

$rol = $_SESSION['rol'] ?? '';
$isAdmin = ($rol === 'administrador');

// Manejo de eliminar
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
        $stmtDelConsultor = $conexion->prepare("DELETE FROM consultores WHERE id = ?");
        $stmtDelConsultor->bind_param("i", $consultor_id);
        $success1 = $stmtDelConsultor->execute();
        $stmtDelConsultor->close();

        $stmtDelUsuario = $conexion->prepare("DELETE FROM usuarios WHERE id_usuarios = ?");
        $stmtDelUsuario->bind_param("i", $usuario_id);
        $success2 = $stmtDelUsuario->execute();
        $stmtDelUsuario->close();

        if ($success1 && $success2) {
            $_SESSION['mensaje'] = "Consultor eliminado correctamente";
        } else {
            $_SESSION['error'] = "Error al eliminar el consultor o el usuario.";
        }
    } else {
        $_SESSION['error'] = "No se pudo encontrar el usuario del consultor";
    }

    header("Location: vista_consultores.php");
    exit;
}

// Manejo de actualización vía POST (desde el modal)
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_consultor'])) {
    $consultor_id = intval($_POST['consultor_id']);
    $nuevo_correo = $_POST['correo'] ?? '';

    if (filter_var($nuevo_correo, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conexion->prepare("SELECT usuario_id FROM consultores WHERE id = ?");
        $stmt->bind_param("i", $consultor_id);
        $stmt->execute();
        $stmt->bind_result($usuario_id);
        $stmt->fetch();
        $stmt->close();

        if ($usuario_id) {
            $stmtUpd = $conexion->prepare("UPDATE usuarios SET correo = ? WHERE id_usuarios = ?");
            $stmtUpd->bind_param("si", $nuevo_correo, $usuario_id);
            if ($stmtUpd->execute()) {
                $_SESSION['mensaje'] = "Consultor actualizado correctamente";
            } else {
                $_SESSION['error'] = "Error al actualizar el consultor";
            }
            $stmtUpd->close();
        } else {
            $_SESSION['error'] = "Usuario no encontrado para este consultor";
        }
    } else {
        $_SESSION['error'] = "Correo inválido";
    }

    header("Location: vista_consultores.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Listado Consultores</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  table.consultores-table th:first-child,
  table.consultores-table td:first-child {
    display: none;
  }
</style>
</head>
<body>

<div class="container mt-4" style="max-height: 90%; overflow-y: auto;">
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
                      <button class='btn btn-sm btn-primary btnEditar' 
                              data-id='{$consultor_id}' data-correo='{$correo}'>
                        Editar
                      </button>
                      <a href='vista_consultores.php?eliminar={$consultor_id}' class='btn btn-sm btn-danger' onclick='return confirm(\"¿Eliminar este consultor?\")'>Eliminar</a>
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

<!-- Modal Bootstrap para mensajes -->
<div class="modal fade" id="mensajeModal" tabindex="-1" aria-labelledby="mensajeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="mensajeModalLabel">Mensaje</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="mensajeModalBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Editar Consultor -->
<div class="modal fade" id="editarConsultorModal" tabindex="-1" aria-labelledby="editarConsultorLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" id="formEditarConsultor" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editarConsultorLabel">Editar Consultor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="consultor_id" id="consultor_id" />
        <div class="mb-3">
          <label for="correo" class="form-label">Correo</label>
          <input type="email" class="form-control" id="correo" name="correo" required />
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="editar_consultor" class="btn btn-primary">Guardar Cambios</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Paginación
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
</body>
</html>