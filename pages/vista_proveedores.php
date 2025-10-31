<?php
// =============================
// CONSULTA DE PROVEEDORES CON ESTADO GLOBAL Y DETALLES DE ARCHIVOS
// =============================
$sql = "SELECT 
    p.id,
    p.nombre_empresa,
    p.pais_origen,
    u.correo,
    CASE
        WHEN COUNT(a.id) = 0 THEN 'sin_contenido'
        WHEN SUM(a.revision_estado = 'pendiente') > 0 THEN 'pendiente'
        WHEN SUM(a.revision_estado = 'rechazado') = COUNT(a.id) THEN 'rechazado'
        WHEN SUM(a.revision_estado = 'aprobado') = COUNT(a.id) THEN 'aprobado'
        ELSE 'pendiente'
    END AS estado,
    COUNT(a.id) AS total_archivos,
    SUM(a.revision_estado = 'aprobado') AS total_aprobado,
    SUM(a.revision_estado = 'pendiente') AS total_pendiente,
    SUM(a.revision_estado = 'rechazado') AS total_rechazado
FROM proveedores p
LEFT JOIN usuarios u ON p.usuario_id = u.id_usuarios
LEFT JOIN archivos_subidos a ON a.proveedor_id = p.id
GROUP BY p.id
ORDER BY p.id
LIMIT $inicio, $filas_por_pagina";



$result = $conexion->query($sql);

if (!$result) {
  die("Error en la consulta SQL: " . $conexion->error);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Listado de proveedores</title>
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container py-4">
  <div>
    <table class="table table-bordered table-hover" id="tablaProveedores">
      <thead class="table-light">
        <tr>
          <th>Correo</th>
          <th>Nombre Empresa</th>
          <th>País de Origen</th>
          <th>Estado</th>
          <th data-no-sort>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php
        while ($row = $result->fetch_assoc()) {
          $proveedorId = $row['id'];
          $correo = htmlspecialchars($row['correo'] ?? '');
          $paisOrigen = htmlspecialchars($row['pais_origen'] ?? '');
          $nombreEmpresa = htmlspecialchars($row['nombre_empresa'] ?? '');
          $estado = $row['estado'];

          // Determinar clase de fila y badge según estado
          switch ($estado) {
            case 'pendiente':
              $rowClass = 'fila-revision';
              $badge = "<span class='badge text-dark fs-6'>Pendiente</span>";
              break;
            case 'aprobado':
              $rowClass = 'fila-correcto';
              $badge = "<span class='badge text-dark fs-6'>Aprobado</span>";
              break;
            case 'rechazado':
              $rowClass = 'fila-incorrecto';
              $badge = "<span class='badge text-dark fs-6'>Rechazado</span>";
              break;
            case 'sin_contenido':
            default:
              $rowClass = 'fila-sin-contenido';
              $badge = "<span class='badge text-dark fs-6'>Sin contenido</span>";
              break;
          }
          echo "<tr class='{$rowClass}'>
        <td>{$correo}</td>
        <td>{$nombreEmpresa}</td>
        <td>{$paisOrigen}</td>
        <td>{$badge}</td>
        <td class='text-center'>
            <button class='btn btn-sm btn-warning me-1' 
                    data-bs-toggle='modal' 
                    data-bs-target='#modalEditarProveedor' 
                    data-id='{$proveedorId}' 
                    data-correo='{$correo}' 
                    data-nombre='{$nombreEmpresa}' 
                    data-pais='{$paisOrigen}'>
                <i class='bi bi-pencil'></i>
            </button>
            <button class='btn btn-sm btn-danger' 
                    onclick=\"mostrarModalEliminarProveedor({$proveedorId}, '{$correo}')\">
                <i class='bi bi-trash'></i>
            </button>
        </td>
      </tr>";

        }
        ?>
      </tbody>
    </table>
  </div>
  <!-- =============================
     MODAL EDITAR PROVEEDOR
     ============================= -->
  <div class="modal fade" id="modalEditarProveedor" tabindex="-1" aria-labelledby="modalEditarProveedorLabel"
    aria-hidden="true">
    <div class="modal-dialog">
      <form id="formEditarProveedor" method="POST" action="editar_proveedor.php">
        <div class="modal-content">
          <div class="modal-header bg-mi-color text-white">
            <h5 class="modal-title" id="modalEditarProveedorLabel">Editar Proveedor</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
              aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <!-- ID oculto -->
            <input type="hidden" name="id_proveedor" id="editarProveedorId">

            <!-- Correo -->
            <div class="mb-3">
              <label for="editarCorreoProveedor" class="form-label-popup">Correo del proveedor</label>
              <input type="email" class="form-control" name="correo" id="editarCorreoProveedor" required>
            </div>

            <!-- Nombre de empresa -->
            <div class="mb-3">
              <label for="editarNombreEmpresa" class="form-label-popup">Nombre de empresa</label>
              <input type="text" class="form-control" name="nombre_empresa" id="editarNombreEmpresa" required>
            </div>
            <!-- País de origen -->
            <div class="mb-3">
              <label for="editarPaisOrigen" class="form-label-popup">País de origen</label>
              <input type="text" class="form-control" name="pais_origen" id="editarPaisOrigen" placeholder="Ej: España">
            </div>

            <!-- Contraseña opcional -->
            <div class="mb-3">
              <label for="editarContrasenaProveedor" class="form-label-popup">Contraseña (dejar en blanco para no
                cambiar)</label>
              <input type="password" class="form-control" name="contrasena" id="editarContrasenaProveedor"
                placeholder="Nueva contraseña">
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  <!-- =============================
     MODAL ELIMINAR PROVEEDOR
     ============================= -->
  <div class="modal fade" id="modalEliminarProveedor" tabindex="-1" aria-labelledby="modalEliminarProveedorLabel"
    aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-mi-color text-white">
          <h5 class="modal-title" id="modalEliminarProveedorLabel">Eliminar Proveedor</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <!-- Texto dinámico según el proveedor -->
          <p id="eliminarProveedorTexto" class="mb-3"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-danger" id="btnConfirmarEliminarProveedor">Eliminar</button>
        </div>
      </div>
    </div>
  </div>
</body>

</html>

<!-- Estilos de filas por estado -->
<style>
  .fila-revision td {
    background-color: #ffe082 !important;
  }

  /* amarillo */
  .fila-correcto td {
    background-color: #a5d6a7 !important;
  }

  /* verde */
  .fila-incorrecto td {
    background-color: #ef9a9a !important;
  }

  /* rojo */
  .fila-sin-contenido td {
    background-color: #90caf9 !important;
  }

  /* gris */
</style>

<script>
// =============================
// RELLENAR MODAL EDITAR
// =============================
document.addEventListener('DOMContentLoaded', function () {
  var modalEditar = document.getElementById('modalEditarProveedor');
  modalEditar.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var id = button.getAttribute('data-id');
    var correo = button.getAttribute('data-correo');
    var nombreEmpresa = button.getAttribute('data-nombre');
    var pais = button.getAttribute('data-pais'); // 👈 Nueva línea

    document.getElementById('editarProveedorId').value = id;
    document.getElementById('editarCorreoProveedor').value = correo;
    document.getElementById('editarNombreEmpresa').value = nombreEmpresa;
    document.getElementById('editarPaisOrigen').value = pais; // 👈 Nueva línea
  });
});
;

  // =============================
  // MODAL ELIMINAR PROVEEDOR
  // =============================
  let proveedorEliminarId = null;

  function mostrarModalEliminarProveedor(id, correo) {
    proveedorEliminarId = id;
    document.getElementById('eliminarProveedorTexto').textContent =
      '¿Seguro que quieres eliminar el proveedor ' + correo + '? Se eliminará también el usuario asociado.';

    var modal = new bootstrap.Modal(document.getElementById('modalEliminarProveedor'));
    modal.show();
  }

  document.getElementById('btnConfirmarEliminarProveedor').onclick = function () {
    if (proveedorEliminarId) {
      window.location.href = 'eliminar_proveedor.php?id=' + proveedorEliminarId;
    }
  };
</script>