<?php
// =============================
// INICIO DE SESIÓN Y CONEXIÓN
// =============================

// Si no hay sesión activa, la iniciamos
if (session_status() === PHP_SESSION_NONE) session_start();

// Importamos la conexión a la base de datos
include '../api/includes/conexion.php';

// =============================
// VALIDACIÓN DE ROL
// =============================

// Rol actual desde sesión
$rol = $_SESSION['rol'] ?? '';

// Bandera booleana: ¿es administrador?
$isAdmin = ($rol === 'administrador');

// =============================
// ELIMINAR CONSULTOR
// =============================

// Solo un administrador puede eliminar consultores
if ($isAdmin && isset($_GET['eliminar'])) {
    $consultor_id = intval($_GET['eliminar']); // ID del consultor a eliminar

    // Paso 1: obtener el usuario_id vinculado al consultor
    $stmt = $conexion->prepare("SELECT usuario_id FROM consultores WHERE id = ?");
    $stmt->bind_param("i", $consultor_id);
    $stmt->execute();
    $stmt->bind_result($usuario_id);
    $stmt->fetch();
    $stmt->close();

    if ($usuario_id) {
        // Paso 2: eliminar consultor
        $stmtDelConsultor = $conexion->prepare("DELETE FROM consultores WHERE id = ?");
        $stmtDelConsultor->bind_param("i", $consultor_id);
        $success1 = $stmtDelConsultor->execute();
        $stmtDelConsultor->close();

        // Paso 3: eliminar usuario asociado
        $stmtDelUsuario = $conexion->prepare("DELETE FROM usuarios WHERE id_usuarios = ?");
        $stmtDelUsuario->bind_param("i", $usuario_id);
        $success2 = $stmtDelUsuario->execute();
        $stmtDelUsuario->close();

        // Paso 4: verificar éxito y guardar mensaje en sesión
        if ($success1 && $success2) {
            $_SESSION['mensaje'] = "Consultor eliminado correctamente";
        } else {
            $_SESSION['error'] = "Error al eliminar el consultor o el usuario.";
        }
    } else {
        $_SESSION['error'] = "No se pudo encontrar el usuario del consultor";
    }

    // Redirigir a la vista de consultores
    echo "<script>window.location.href='plantillaUsers.php?vista=consultores';</script>";
    exit;
}

// =============================
// ACTUALIZAR CONSULTOR
// =============================

// Solo un administrador puede actualizar datos
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_consultor'])) {
    $consultor_id = intval($_POST['consultor_id']);
    $nuevo_correo = $_POST['correo'] ?? '';

    // Validar formato de correo
    if (filter_var($nuevo_correo, FILTER_VALIDATE_EMAIL)) {
        // Buscar el usuario vinculado al consultor
        $stmt = $conexion->prepare("SELECT usuario_id FROM consultores WHERE id = ?");
        $stmt->bind_param("i", $consultor_id);
        $stmt->execute();
        $stmt->bind_result($usuario_id);
        $stmt->fetch();
        $stmt->close();

        if ($usuario_id) {
            // Actualizar el correo en la tabla usuarios
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

    // Redirigir a la vista de consultores
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

  <!-- Bootstrap CSS y iconos -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    /* Ocultar la primera columna (#) en la tabla */
    table.consultores-table th:first-child,
    table.consultores-table td:first-child {
      display: none;
    }
  </style>
</head>
<body>
<div class="container mt-4">
  <!-- Tabla de consultores -->
  <table class="table table-bordered table-hover consultores-table" id="tablaConsultores">
    <thead class="table-light">
      <tr>
        <th>#</th>
        <th>Correo</th>
        <th>Nombre</th>
        <?php if ($isAdmin == true): ?>
          <th data-no-sort>Acciones</th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php
      // =============================
      // CONSULTA Y PAGINACIÓN
      // =============================

      // Contar número total de consultores
      $sql_total = "SELECT COUNT(*) as total FROM consultores";
      $result_total = $conexion->query($sql_total);
      $total_filas = $result_total->fetch_assoc()['total'];

      // Calcular número de páginas
      $total_paginas = ceil($total_filas / $filas_por_pagina);

      // Traer consultores y sus correos (JOIN con usuarios)
      $sql = "SELECT c.id, u.correo 
              FROM consultores c 
              JOIN usuarios u ON c.usuario_id = u.id_usuarios 
              ORDER BY c.id 
              LIMIT $inicio, $filas_por_pagina";
      $result = $conexion->query($sql);

      // Recorrer los resultados
      $i = 1;
      while ($row = $result->fetch_assoc()):
          $correo = htmlspecialchars($row['correo']); // Sanitizar
          $nombre = htmlspecialchars(strstr($correo, '@', true)); // Nombre = parte antes de @
          $consultor_id = intval($row['id']);
      ?>
        <tr>
          <th scope="row"><?= $i ?></th>
          <td><?= $correo ?></td>
          <td><?= $nombre ?></td>
          <?php if ($isAdmin == true): ?>
            <td class="text-center">
              <!-- Botón editar -->
              <button type="button" class="btn btn-sm btn-warning me-1 btnEditar"
                      data-id="<?= $consultor_id ?>" data-correo="<?= $correo ?>">
                <i class="bi bi-pencil"></i>
              </button>
              <!-- Botón eliminar -->
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

<?php
// Renderizar controles de paginación
$url_base = '?vista=consultores';
echo generar_paginacion($url_base, $pagina_actual, $total_paginas);
?>

<!-- =============================
     MODAL EDITAR CONSULTOR
============================= -->
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

<!-- =============================
     MODAL ELIMINAR CONSULTOR
============================= -->
<div class="modal fade" id="eliminarConsultorModal" tabindex="-1" aria-labelledby="eliminarConsultorLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-mi-color text-white">
        <h5 class="modal-title" id="eliminarConsultorLabel">Eliminar Consultor</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <!-- Texto dinámico con el nombre del consultor -->
        <p id="nombreConsultorEliminar"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" id="btnConfirmarEliminar" class="btn btn-danger">Eliminar</a>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// =============================
// LÓGICA DE LOS MODALES
// =============================
document.addEventListener('DOMContentLoaded', () => {
  // --- Modal editar ---
  const modalElement = document.getElementById('editarConsultorModal');
  let editarConsultorModalInstance = new bootstrap.Modal(modalElement);

  // Botones editar → abren el modal y cargan datos
  document.querySelectorAll('.btnEditar').forEach(btn =>
    btn.addEventListener('click', () => {
      document.getElementById('consultor_id').value = btn.dataset.id;
      document.getElementById('correo').value = btn.dataset.correo;
      document.getElementById('contrasena').value = '';
      editarConsultorModalInstance.show();
    })
  );

  // --- Modal eliminar ---
  document.querySelectorAll('.btnEliminar').forEach(btn =>
    btn.addEventListener('click', () => {
      document.getElementById('nombreConsultorEliminar').textContent = 
        `¿Seguro que quieres eliminar el consultor "${btn.dataset.nombre}"? Se eliminará también el usuario asociado.`;
      // Cambiar el href del botón confirmar
      document.getElementById('btnConfirmarEliminar').href = 
        `plantillaUsers.php?vista=consultores&eliminar=${btn.dataset.id}`;
      new bootstrap.Modal(document.getElementById('eliminarConsultorModal')).show();
    })
  );
});
</script>
</body>
</html>
