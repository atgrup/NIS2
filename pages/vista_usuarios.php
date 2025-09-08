<div>
    <table id="tablaUsuarios" class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <!-- Encabezados de la tabla -->
                <th>Correo</th>
                <th>Tipo de usuario</th>
                <th>Verificado</th>
                <th data-no-sort>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // 1. Contar el total de usuarios para calcular la paginación
            $sql_total = "SELECT COUNT(*) as total FROM usuarios";
            $result_total = $conexion->query($sql_total);
            $total_filas = $result_total->fetch_assoc()['total'];
            $total_paginas = ceil($total_filas / $filas_por_pagina);

            // 2. Seleccionar los usuarios junto con su tipo (LEFT JOIN asegura que muestre aunque no tenga tipo)
            $sql = "SELECT u.id_usuarios, u.correo, t.nombre AS tipo_usuario, u.verificado
                    FROM usuarios u
                    LEFT JOIN tipo_usuario t ON u.tipo_usuario_id = t.id_tipo_usuario
                    ORDER BY u.id_usuarios
                    LIMIT $inicio, $filas_por_pagina";

            $result = $conexion->query($sql);
            $i = 1;

            // 3. Generar filas de la tabla dinámicamente
            while ($row = $result->fetch_assoc()) {
                $verificado = $row['verificado'] ? 'Sí' : 'No'; // Convertimos booleano a texto
                $usuarioId = $row['id_usuarios'];
                $correo = htmlspecialchars($row['correo']); // Sanitizar salida contra XSS
                $tipoUsuario = htmlspecialchars($row['tipo_usuario']);

                echo '<tr>';
                echo '<td>' . $correo . '</td>';
                echo '<td>' . $tipoUsuario . '</td>';
                echo '<td class="text-center">' . $verificado . '</td>';
                echo '<td class="text-center">';
                // Botón para abrir modal de edición con atributos data-* para pasar info
                echo '<button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#modalEditarUsuario" data-id="' . $usuarioId . '" data-correo="' . $correo . '" data-tipo="' . $tipoUsuario . '"><i class="bi bi-pencil"></i></button>';
                // Botón para eliminar (abre modal de confirmación)
                echo '<button class="btn btn-sm btn-danger" onclick="mostrarModalEliminarUsuario(' . $usuarioId . ', \'' . $correo . '\', \'' . $tipoUsuario . '\')"><i class="bi bi-trash"></i></button>';
                echo '</td>';
                echo '</tr>';
                $i++;
            }
            ?>
        </tbody>
    </table>
</div>

<?php
// 4. Generar paginación debajo de la tabla
$url_base = '?vista=usuarios';
echo generar_paginacion($url_base, $pagina_actual, $total_paginas);
?>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-labelledby="modalEditarUsuarioLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formEditarUsuario" method="POST" action="editar_usuario.php">
      <div class="modal-content">
        <div class="modal-header bg-mi-color text-white">
          <h5 class="modal-title" id="modalEditarUsuarioLabel">Modificar Usuario</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <!-- ID oculto para identificar al usuario -->
          <input type="hidden" name="id_usuarios" id="editarUsuarioId">
          <!-- Campo de correo -->
          <div class="mb-3">
            <label for="editarCorreo" class="form-label-popup">Correo del usuario</label>
            <input type="email" class="form-control" name="correo" id="editarCorreo" required>
          </div>
          <!-- Select de tipo de usuario -->
          <div class="mb-3">
            <label for="editarTipo" class="form-label-popup">Tipo de usuario</label>
            <select class="form-select" name="tipo_usuario" id="editarTipo" required>
              <option value="Administrador">Administrador</option>
              <option value="Consultor">Consultor</option>
              <option value="Proveedor">Proveedor</option>
            </select>
          </div>
          <!-- Campo de contraseña (opcional) -->
          <div class="mb-3">
            <label for="editarContrasena" class="form-label-popup">Contraseña (dejar en blanco para no cambiar)</label>
            <input type="password" class="form-control" name="contrasena" id="editarContrasena" placeholder="Nueva contraseña">
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
<div class="modal fade" id="modalEliminarUsuario" tabindex="-1" aria-labelledby="modalEliminarUsuarioLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-mi-color text-white">
        <h5 class="modal-title" id="modalEliminarUsuarioLabel">Eliminar Usuario</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <!-- Texto dinámico con el correo del usuario a eliminar -->
        <p id="eliminarUsuarioTexto" class="mb-3"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">Eliminar</button>
      </div>
    </div>
  </div>
</div>

<script>
// ----------------- SCRIPT FRONTEND -----------------

// 1. Cargar datos en el modal de edición al abrirlo
document.addEventListener('DOMContentLoaded', function () {
  var modalEditar = document.getElementById('modalEditarUsuario');
  modalEditar.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget; // Botón que disparó el modal
    var id = button.getAttribute('data-id');
    var correo = button.getAttribute('data-correo');
    var tipo = button.getAttribute('data-tipo');

    // Rellenar los inputs del formulario
    document.getElementById('editarUsuarioId').value = id;
    document.getElementById('editarCorreo').value = correo;

    // Seleccionar la opción correspondiente en el <select>
    var selectTipo = document.getElementById('editarTipo');
    for (var i = 0; i < selectTipo.options.length; i++) {
      if (selectTipo.options[i].value.toLowerCase() === tipo.toLowerCase()) {
        selectTipo.selectedIndex = i;
        break;
      }
    }
  });
});

// 2. Modal de confirmación de eliminación
let usuarioEliminarId = null;
let tipoUsuarioEliminar = null;

function mostrarModalEliminarUsuario(id, correo, tipoUsuario) {
  usuarioEliminarId = id;
  tipoUsuarioEliminar = tipoUsuario;

  if (tipoUsuario.toLowerCase() === 'administrador') {
    // Solo administradores pueden ser eliminados aquí
    document.getElementById('eliminarUsuarioTexto').textContent = 
      '¿Seguro que quieres eliminar el usuario ' + correo + '?';
    var modal = new bootstrap.Modal(document.getElementById('modalEliminarUsuario'));
    modal.show();
  } else {
    // Otros roles no se eliminan desde aquí, se muestra alerta
    let alertMsg = '';
    if (tipoUsuario.toLowerCase() === 'consultor') {
      alertMsg = 'Solo puedes eliminar un usuario consultor desde la sección de Consultores.';
    } else if (tipoUsuario.toLowerCase() === 'proveedor') {
      alertMsg = 'Solo puedes eliminar un usuario proveedor desde la sección de Proveedores.';
    } else {
      alertMsg = 'No tienes permisos para eliminar este usuario.';
    }
    mostrarAlertaBootstrap(alertMsg, 'danger');
  }
}

// 3. Acción de confirmar eliminación (redirección a script PHP)
document.getElementById('btnConfirmarEliminar').onclick = function() {
  if (usuarioEliminarId && tipoUsuarioEliminar && tipoUsuarioEliminar.toLowerCase() === 'administrador') {
    window.location.href = 'eliminar_usuario.php?id=' + usuarioEliminarId;
  }
}

// 4. Función auxiliar para mostrar alertas dinámicas de Bootstrap
function mostrarAlertaBootstrap(mensaje, tipo = 'info') {
  let alertDiv = document.createElement('div');
  alertDiv.className = 'alert alert-' + tipo + ' alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
  alertDiv.setAttribute('role', 'alert');
  alertDiv.style.zIndex = 2000;
  alertDiv.innerHTML = mensaje + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>';

  // Insertar alerta en el DOM
  document.body.appendChild(alertDiv);

  // Eliminar automáticamente después de 4 segundos
  setTimeout(() => {
    if (alertDiv) alertDiv.classList.remove('show');
    setTimeout(() => { 
      if (alertDiv && alertDiv.parentNode) alertDiv.parentNode.removeChild(alertDiv); 
    }, 500);
  }, 4000);
}
</script>
