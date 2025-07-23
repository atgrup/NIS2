<?php
// Suponiendo que ya tienes conexión en $conexion
$sql = "SELECT u.correo, p.nombre_empresa
        FROM proveedores p
        JOIN usuarios u ON p.usuario_id = u.id_usuarios
        ORDER BY p.id";

$result = $conexion->query($sql);
?>

<!-- CONTENEDOR DE LA TABLA CON SCROLL -->
<div>
    <table class="table table-bordered table-hover" id="tablaProveedores">
        <thead class="table-light">
            <tr>
                <th>Correo</th>
                <th>Nombre Empresa</th>
                <th data-no-sort>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 1;
// Mostrar todos los proveedores, aunque no tengan usuario asociado o archivos subidos
// Contar total de filas
$sql_total = "SELECT COUNT(*) as total FROM proveedores";
$result_total = $conexion->query($sql_total);
$total_filas = $result_total->fetch_assoc()['total'];
$total_paginas = ceil($total_filas / $filas_por_pagina);

$sql = "SELECT p.id, p.nombre_empresa, u.correo FROM proveedores p LEFT JOIN usuarios u ON p.usuario_id = u.id_usuarios ORDER BY p.id LIMIT $inicio, $filas_por_pagina";
$result = $conexion->query($sql);
while ($row = $result->fetch_assoc()) {
    $proveedorId = $row['id'];
    $correo = htmlspecialchars($row['correo'] ?? '');
    $nombreEmpresa = htmlspecialchars($row['nombre_empresa'] ?? '');
    echo "<tr>
            <td>{$correo}</td>
            <td>{$nombreEmpresa}</td>
            <td class='text-center'>
                <button class='btn btn-sm btn-warning me-1' data-bs-toggle='modal' data-bs-target='#modalEditarProveedor' data-id='{$proveedorId}' data-correo='{$correo}' data-nombre='{$nombreEmpresa}'><i class='bi bi-pencil'></i></button>
                <button class='btn btn-sm btn-danger' onclick=\"mostrarModalEliminarProveedor({$proveedorId}, '{$correo}')\"><i class='bi bi-trash'></i></button>
            </td>
        </tr>";
    $i++;
}
            ?>
        </tbody>
    </table>
</div>

<!-- CONTENEDOR DE LA PAGINACIÓN FUERA DEL SCROLL -->
<?php
$url_base = '?vista=proveedores';
echo generar_paginacion($url_base, $pagina_actual, $total_paginas);
?>

<!-- SCRIPT DE PAGINACIÓN -->
<script>
</script>

<!-- Modal Editar Proveedor -->
<div class="modal fade" id="modalEditarProveedor" tabindex="-1" aria-labelledby="modalEditarProveedorLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formEditarProveedor" method="POST" action="editar_proveedor.php">
      <div class="modal-content">
        <div class="modal-header bg-mi-color text-white">
          <h5 class="modal-title" id="modalEditarProveedorLabel">Modificar Proveedor</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_proveedor" id="editarProveedorId">
          <div class="mb-3">
            <label for="editarCorreoProveedor" class="form-label-popup">Correo del proveedor</label>
            <input type="email" class="form-control" name="correo" id="editarCorreoProveedor" required>
          </div>
          <div class="mb-3">
            <label for="editarNombreEmpresa" class="form-label-popup">Nombre de empresa</label>
            <input type="text" class="form-control" name="nombre_empresa" id="editarNombreEmpresa" required>
          </div>
          <div class="mb-3">
            <label for="editarContrasenaProveedor" class="form-label-popup">Contraseña (dejar en blanco para no cambiar)</label>
            <input type="password" class="form-control" name="contrasena" id="editarContrasenaProveedor" placeholder="Nueva contraseña">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal Confirmar Eliminación Proveedor -->
<div class="modal fade" id="modalEliminarProveedor" tabindex="-1" aria-labelledby="modalEliminarProveedorLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-mi-color text-white">
        <h5 class="modal-title" id="modalEliminarProveedorLabel">Eliminar Proveedor</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p id="eliminarProveedorTexto" class="mb-3"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="btnConfirmarEliminarProveedor">Eliminar</button>
      </div>
    </div>
  </div>
</div>

<script>
// Rellenar el modal de edición con los datos del proveedor
  document.addEventListener('DOMContentLoaded', function () {
    var modalEditar = document.getElementById('modalEditarProveedor');
    modalEditar.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      var id = button.getAttribute('data-id');
      var correo = button.getAttribute('data-correo');
      var nombreEmpresa = button.getAttribute('data-nombre');
      document.getElementById('editarProveedorId').value = id;
      document.getElementById('editarCorreoProveedor').value = correo;
      document.getElementById('editarNombreEmpresa').value = nombreEmpresa;
    });
  });

// Modal de confirmación de eliminación proveedor
let proveedorEliminarId = null;
function mostrarModalEliminarProveedor(id, correo) {
  proveedorEliminarId = id;
  document.getElementById('eliminarProveedorTexto').textContent = '¿Seguro que quieres eliminar el proveedor ' + correo + '? Se eliminará también el usuario asociado.';
  var modal = new bootstrap.Modal(document.getElementById('modalEliminarProveedor'));
  modal.show();
}
document.getElementById('btnConfirmarEliminarProveedor').onclick = function() {
  if (proveedorEliminarId) {
    window.location.href = 'eliminar_proveedor.php?id=' + proveedorEliminarId;
  }
};
</script>
