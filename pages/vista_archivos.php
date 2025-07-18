<div style="max-height: 90%; overflow-y: none;">
    <!-- El buscador se gestiona desde plantillaUsers.php -->
    <table class="table table-bordered border-secondary w-100" id="tablaArchivos">
        <thead>
            <tr>
                <th>Nombre del archivo</th>
                <?php if ($rol === 'administrador') echo "<th>UUID</th><th>Proveedor</th>"; ?>
                <th>Fecha</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($rol === 'administrador') {
                $stmt = $conexion->prepare("
                    SELECT a.id, a.nombre_archivo, a.fecha_subida, a.revision_estado, a.archivo_url,
                           u.correo
                    FROM archivos_subidos a
                    LEFT JOIN proveedores p ON a.proveedor_id = p.id
                    LEFT JOIN usuarios u ON p.usuario_id = u.id_usuarios
                    ORDER BY a.fecha_subida DESC
                ");
            } else {
                $stmt = $conexion->prepare("
                    SELECT u.id_usuarios, p.id 
                    FROM usuarios u 
                    JOIN proveedores p ON u.id_usuarios = p.usuario_id 
                    WHERE u.correo = ?
                ");
                $stmt->bind_param("s", $correo);
                $stmt->execute();
                $stmt->bind_result($usuario_id, $proveedor_id);
                $stmt->fetch();
                $stmt->close();

                $stmt = $conexion->prepare("
                    SELECT id, nombre_archivo, fecha_subida, revision_estado, archivo_url 
                    FROM archivos_subidos 
                    WHERE proveedor_id = ?
                    ORDER BY fecha_subida DESC
                ");
                $stmt->bind_param("i", $proveedor_id);
            }

            $stmt->execute();

            if ($rol === 'administrador') {
                $stmt->bind_result($id, $nombre, $fecha, $estado, $archivo_url, $correo_proveedor);
            } else {
                $stmt->bind_result($id, $nombre, $fecha, $estado, $archivo_url);
            }

            while ($stmt->fetch()) {
                $ruta_fisica = realpath(__DIR__ . '/../' . $archivo_url);
                if (file_exists($ruta_fisica)) {
                    echo "<tr>
                        <td><a href='download.php?archivo=" . urlencode($archivo_url) . "' style='color: inherit; text-decoration: underline;'>" . htmlspecialchars($nombre) . "</a></td>";


                    if ($rol === 'administrador') {
                        echo "<td>" . htmlspecialchars($id) . "</td>";
                        echo "<td>" . htmlspecialchars($correo_proveedor ?? 'Desconocido') . "</td>";
                    }

                    echo "<td>{$fecha}</td>
                      <td class='text-center'>{$estado}</td>
                      </tr>";
                }
            }
            $stmt->close();
            ?>
        </tbody>
    </table>
</div>



<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Archivos Subidos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<style>
  .form-label{
    color:grey;
  }
</style>
<body class="p-4">

<?php if (strtolower($rol) === 'consultor'): ?>
  <div class="alert alert-warning">Los consultores no pueden subir archivos.</div>

<?php endif; ?>


<?php if (strtolower($rol) !== 'consultor'): ?>
<div class="modal fade" id="modalSubirArchivo" tabindex="-1" aria-labelledby="modalSubirArchivoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="subir_Archivo_rellenado.php" method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalSubirArchivoLabel">Subir Archivo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="archivo" class="form-label">Archivo</label>
          <input type="file" class="form-control" id="archivo" name="archivo" required>
        </div>
        <div class="mb-3">
          <label for="plantilla_id" class="form-label">Plantillas Asociadas</label>
          <select name="plantilla_id" id="plantilla_id" class="form-select" required>
            <option value="">-- Seleccione --</option>
            <?php if ($plantillasRes): ?>
              <?php while ($f = $plantillasRes->fetch_assoc()): ?>
                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nombre']) ?></option>
              <?php endwhile; ?>
            <?php else: ?>
              <option disabled>No hay plantillas</option>
            <?php endif; ?>
          </select>
        </div>
        <div id="mensajeRespuesta" class="mt-2"></div>

        <input type="hidden" name="usuario_id" value="<?= $usuario_id ?>">
        <input type="hidden" name="proveedor_id" value="<?= $prov_id ?>">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Subir</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<script>
document.getElementById('formSubirArchivo').addEventListener('submit', function(e) {
    e.preventDefault(); // evitar que recargue la página

    const form = e.target;
    const formData = new FormData(form);
    const mensajeDiv = document.getElementById('mensajeRespuesta');

    fetch(form.action, {
        method: 'POST',
        body: formData,
    })
    .then(response => response.text())
    .then(data => {
        mensajeDiv.innerHTML = `<div class="alert alert-info">${data}</div>`;
        // Opcional: limpiar el formulario o cerrar el modal si quieres:
        // form.reset();
        // bootstrap.Modal.getInstance(document.getElementById('modalSubirArchivo')).hide();
    })
    .catch(error => {
        mensajeDiv.innerHTML = `<div class="alert alert-danger">Error al subir el archivo.</div>`;
    });
});
</script>

</html>

