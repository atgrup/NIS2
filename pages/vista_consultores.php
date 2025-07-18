<?php

if (session_status() === PHP_SESSION_NONE) session_start();
include '../api/includes/conexion.php';

$rol = $_SESSION['rol'] ?? '';
$isAdmin = ($rol === 'ADMINISTRADOR');
 
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

echo "<script>window.location.href='plantillaUsers.php?vista=consultores';</script>";
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

header("Location: plantillaUsers.php?vista=consultores");
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
  table.consultores-table th:first-child,
  table.consultores-table td:first-child {
    display: none;
  }
</style>
</head>
<body>
<div class="container mt-4" style="max-height:90vh; overflow-y:auto;">
  <table class="table table-bordered border-secondary consultores-table" id="tablaConsultores">
    <thead>
      <tr>
        <th>#</th>
        <th>Correo</th>
        <th>Nombre</th>
        <?php if ($isAdmin == true): ?><th data-no-sort>Acciones</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php
      $sql = "SELECT c.id, u.correo FROM consultores c JOIN usuarios u ON c.usuario_id = u.id_usuarios ORDER BY c.id";
      $result = $conexion->query($sql);
      $i = 1;
      while ($row = $result->fetch_assoc()):
          $correo = htmlspecialchars($row['correo']);
          $nombre = htmlspecialchars(strstr($correo, '@', true));
          $consultor_id = intval($row['id']);
      ?>
        <tr>
          <th scope="row"><?= $i ?></th>
          <td><?= $correo ?></td>
          <td><?= $nombre ?></td>
           <?php if ($isAdmin == true): ?>
            <td class="text-center">
              <button type="button" class="btn btn-sm btn-warning me-1 btnEditar"
                      data-id="<?= $consultor_id ?>" data-correo="<?= $correo ?>">
                <i class="bi bi-pencil"></i>
              </button>
              <button type="button" class="btn btn-sm btn-danger btnEliminar"
                      data-id="<?= $consultor_id ?>" data-nombre="<?= $nombre ?>">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          <?php endif; ?>
        </tr>
      <?php
        $i++;
      endwhile;
      ?>
    </tbody>
  </table>
</div>

<div id="paginacion" class="mt-3 d-flex justify-content-center gap-2"></div>

<!-- Modal Editar Consultor -->
<div class="modal fade" id="editarConsultorModal" tabindex="-1" aria-labelledby="editarConsultorLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formEditarConsultor" method="POST" action="editar_consultor.php">
      <input type="hidden" name="consultor_id" id="consultor_id">
      <input type="hidden" name="editar_consultor" value="1">
      <div class="modal-content">
        <div class="modal-header bg-mi-color text-white">
          <h5 class="modal-title" id="editarConsultorLabel">Modificar Consultor</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="correo" class="form-label">Correo del consultor</label>
            <input type="email" class="form-control" name="correo" id="correo" required>
          </div>
          <div class="mb-3">
            <label for="contrasena" class="form-label">Contraseña (dejar en blanco para no cambiar)</label>
            <input type="password" class="form-control" name="contrasena" id="contrasena" placeholder="Nueva contraseña">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal Confirmar Eliminación -->
<div class="modal fade" id="eliminarConsultorModal" tabindex="-1" aria-labelledby="eliminarConsultorLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-mi-color text-white">
        <h5 class="modal-title" id="eliminarConsultorLabel">Eliminar Consultor</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p id="nombreConsultorEliminar"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" id="btnConfirmarEliminar" class="btn btn-danger">Eliminar</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Lógica para abrir el modal de edición y rellenar campos
let editarConsultorModalInstance = null;
document.addEventListener('DOMContentLoaded', () => {
  const modalElement = document.getElementById('editarConsultorModal');
  editarConsultorModalInstance = new bootstrap.Modal(modalElement);
  // Botones editar
  document.querySelectorAll('.btnEditar').forEach(btn =>
    btn.addEventListener('click', () => {
      document.getElementById('consultor_id').value = btn.dataset.id;
      document.getElementById('correo').value = btn.dataset.correo;
      document.getElementById('contrasena').value = '';
      editarConsultorModalInstance.show();
    })
  );
  // Botones eliminar
 document.querySelectorAll('.btnEliminar').forEach(btn =>
  btn.addEventListener('click', () => {
    document.getElementById('nombreConsultorEliminar').textContent = `¿Seguro que quieres eliminar el consultor "${btn.dataset.nombre}"? Se eliminará también el usuario asociado.`;
    // Cambiar para llamar a esta misma página
    document.getElementById('btnConfirmarEliminar').href = `plantillaUsers.php?vista=consultores&eliminar=${btn.dataset.id}`;
    new bootstrap.Modal(document.getElementById('eliminarConsultorModal')).show();
  })
);


  // Paginación simple
  const rows = Array.from(document.querySelectorAll('#tablaConsultores tbody tr'));
  const rowsPerPage = 10;
  let currentPage = 1;
  const pagDiv = document.getElementById('paginacion');

  function renderPage(page) {
    rows.forEach((r,i) => {
      r.style.display = (i >= (page-1)*rowsPerPage && i < page*rowsPerPage) ? '' : 'none';
    });
  }

  function renderPagination() {
    pagDiv.innerHTML = '';
    const totalPages = Math.ceil(rows.length / rowsPerPage);
    const createBtn = (text, page) => {
      const btn = document.createElement('button');
      btn.textContent = text;
      btn.className = 'btn ' + (page === currentPage ? 'btn-primary' : 'btn-outline-primary');
      btn.disabled = page === currentPage;
      btn.addEventListener('click', () => {
        currentPage = page;
        renderPage(page);
        renderPagination();
      });
      return btn;
    };

    pagDiv.appendChild(createBtn('⏮️', 1));
    for(let p=1; p<=totalPages; p++) {
      pagDiv.appendChild(createBtn(p, p));
    }
    pagDiv.appendChild(createBtn('⏭️', totalPages));
  }

  renderPage(currentPage);
  renderPagination();
});

</script>
</body>
</html>
