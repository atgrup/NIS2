<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Archivos Subidos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    .form-label {
      color: grey;
    }
  </style>
</head>
<body class="p-4">



<?php if (strtolower($rol) === 'consultor'): ?>
  <div class="alert alert-warning">Los consultores no pueden subir archivos.</div>
<?php endif; ?>

<?php if (strtolower($rol) !== 'consultor'): ?>

        <div class="modal-body">
          <div class="mb-3">
            <label for="archivo" class="form-label">Archivo</label>
            <input type="file" class="form-control" id="archivo" name="archivo" required />
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

<div class="table-responsive mt-4" id="tablaArchivosContainer">
  <table class="table table-bordered table-hover archivos-table" id="tablaArchivos">
    <thead class="table-light">
      <tr>
        <th>Nombre archivo</th>
        <th>Fecha subida</th>
        <th>Estado</th>
        <th>Empresa</th>
        <th>Usuario</th>
        <th data-no-sort>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $conexion = new mysqli('jordio35.sg-host.com', 'u74bscuknwn9n', 'ad123456-', 'dbs1il8vaitgwc');
      if ($conexion->connect_error) {
          echo '<tr><td colspan="6" class="text-center alert alert-danger">Error de conexión a la base de datos.</td></tr>';
      } else {
          $sql = "SELECT a.id, a.nombre_archivo, a.archivo_url, a.fecha_subida, a.revision_estado, p.nombre_empresa, u.correo as correo_usuario FROM archivos_subidos a LEFT JOIN proveedores p ON a.proveedor_id = p.id LEFT JOIN usuarios u ON p.usuario_id = u.id_usuarios ORDER BY a.fecha_subida DESC";
          $res = $conexion->query($sql);
          if ($res && $res->num_rows > 0) {
              while ($row = $res->fetch_assoc()) {
                  echo '<tr>';
                  echo '<td><a href="../' . htmlspecialchars($row['archivo_url']) . '" target="_blank">' . htmlspecialchars($row['nombre_archivo']) . '</a></td>';
                  echo '<td>' . $row['fecha_subida'] . '</td>';
                  echo '<td>' . htmlspecialchars($row['revision_estado']) . '</td>';
                  echo '<td>' . htmlspecialchars($row['nombre_empresa'] ?? '-') . '</td>';
                  echo '<td>' . htmlspecialchars($row['correo_usuario'] ?? '-') . '</td>';
                  echo '<td>';
                  echo '<a href="../' . htmlspecialchars($row['archivo_url']) . '" target="_blank" class="btn btn-sm btn-primary" title="Ver Archivo"><i class="bi bi-eye"></i></a>';
                  echo '<a href="../api/download.php?id=' . $row['id'] . '" class="btn btn-sm btn-success" title="Descargar Archivo"><i class="bi bi-download"></i></a>';
                  echo '</td>';
                  echo '</tr>';
              }
          } else {
              echo '<tr><td colspan="6" class="text-center">No hay archivos subidos.</td></tr>';
          }
          $res->free();
          $conexion->close();
      }
      ?>
    </tbody>
  </table>
</div>
<div id="paginacion" class="mt-3 d-flex justify-content-center gap-2"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('formSubirArchivo')?.addEventListener('submit', function(e) {
  e.preventDefault();

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
  })
  .catch(() => {
    mensajeDiv.innerHTML = `<div class="alert alert-danger">Error al subir el archivo.</div>`;
  });
});
document.addEventListener('DOMContentLoaded', () => {
    const filasPorPagina = 10;
    let paginaActual = 1;
    const tabla = document.getElementById('tablaArchivos');
    const filas = tabla.querySelectorAll('tbody tr');
    const paginacionContainer = document.getElementById('paginacion');

    function mostrarPagina(pagina) {
        const inicio = (pagina - 1) * filasPorPagina;
        const fin = inicio + filasPorPagina;
        filas.forEach((fila, index) => {
            fila.style.display = (index >= inicio && index < fin) ? '' : 'none';
        });
    }

    function setupPaginacion() {
        paginacionContainer.innerHTML = '';
        const totalPaginas = Math.ceil(filas.length / filasPorPagina);
        if (totalPaginas <= 1) return;

        const btnPrimera = document.createElement('button');
        btnPrimera.innerHTML = '⏮️';
        btnPrimera.className = 'btn btn-outline-primary';
        btnPrimera.disabled = paginaActual === 1;
        btnPrimera.addEventListener('click', () => {
            paginaActual = 1;
            mostrarPagina(paginaActual);
            setupPaginacion();
        });
        paginacionContainer.appendChild(btnPrimera);

        for (let i = 1; i <= totalPaginas; i++) {
            const btn = document.createElement('button');
            btn.innerText = i;
            btn.className = `btn ${i === paginaActual ? 'btn-primary' : 'btn-outline-primary'}`;
            btn.addEventListener('click', () => {
                paginaActual = i;
                mostrarPagina(paginaActual);
                setupPaginacion();
            });
            paginacionContainer.appendChild(btn);
        }

        const btnUltima = document.createElement('button');
        btnUltima.innerHTML = '⏭️';
        btnUltima.className = 'btn btn-outline-primary';
        btnUltima.disabled = paginaActual === totalPaginas;
        btnUltima.addEventListener('click', () => {
            paginaActual = totalPaginas;
            mostrarPagina(paginaActual);
            setupPaginacion();
        });
        paginacionContainer.appendChild(btnUltima);
    }

    mostrarPagina(1);
    setupPaginacion();
});
</script>

</body>
</html>
