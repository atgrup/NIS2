<div style="max-height: 90%; overflow-y: none;">
<table class="table table-bordered border-secondary w-100">
    <thead>
        <tr>
            
            <th>Nombre del archivo</th>
            <?php if ($rol === 'administrador') echo "<th>Proveedor</th>"; ?>
            <th>Fecha</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($rol === 'administrador') {
            // ✅ Mostrar todos los archivos con el correo del proveedor
            $stmt = $conexion->prepare("
                SELECT a.id, a.nombre_archivo, a.fecha_subida, a.revision_estado, a.archivo_url,
                       u.correo
                FROM archivos_subidos a
                LEFT JOIN proveedores p ON a.proveedor_id = p.id
                LEFT JOIN usuarios u ON p.usuario_id = u.id_usuarios
                ORDER BY a.fecha_subida DESC
            ");
        } else {
            // ✅ Solo los archivos del proveedor logueado
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

        $i = 1;
        while ($stmt->fetch()) {
            $ruta_fisica = realpath(__DIR__ . '/../' . $archivo_url);
            if (file_exists($ruta_fisica)) {
                echo "<tr>
                        
                        <td><a href='download.php?archivo=" . urlencode($archivo_url) . "' style='color: inherit; text-decoration: underline;'>" . htmlspecialchars($nombre) . "</a></td>";

                if ($rol === 'administrador') {
                    echo "<td>" . htmlspecialchars($correo_proveedor ?? 'Desconocido') . "</td>";
                }

                echo "<td>{$fecha}</td>
                      <td class='text-center'>{$estado}</td>
                      </tr>";
                $i++;
            }
        }
        $stmt->close();
        ?>
    </tbody>
</table>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const tabla = document.querySelector('table');
  const filasPorPagina = 10;
  let paginaActual = 1;
  const tbody = tabla.querySelector('tbody');
  const filas = Array.from(tbody.querySelectorAll('tr'));
  const pagDiv = document.createElement('div');
  pagDiv.id = 'paginacion';
  pagDiv.className = 'mt-3 d-flex justify-content-center gap-2';
  tabla.parentElement.appendChild(pagDiv);

  // 🔒 Oculta la columna de índice si existe
  const thead = tabla.querySelector('thead tr');
  const tieneIndice = thead.firstElementChild?.textContent.trim() === '#' || thead.firstElementChild?.textContent === '';
  if (tieneIndice) {
    // Quitar la celda del encabezado
    thead.removeChild(thead.firstElementChild);
    // Quitar la celda de cada fila
    filas.forEach(fila => fila.removeChild(fila.firstElementChild));
  }

  function mostrarPagina(pagina) {
    const inicio = (pagina - 1) * filasPorPagina;
    const fin = inicio + filasPorPagina;

    filas.forEach((fila, i) => {
      fila.style.display = i >= inicio && i < fin ? '' : 'none';
    });
  }

  function crearPaginacion() {
    pagDiv.innerHTML = '';
    const totalPaginas = Math.ceil(filas.length / filasPorPagina);

    const crearBoton = (text, page, disabled = false) => {
      const btn = document.createElement('button');
      btn.textContent = text;
      btn.className = 'btn btn-outline-primary';
      if (disabled) btn.disabled = true;
      btn.addEventListener('click', () => {
        paginaActual = page;
        mostrarPagina(paginaActual);
        crearPaginacion();
      });
      return btn;
    };

    // Botón primera página
    pagDiv.appendChild(crearBoton('⏮️', 1, paginaActual === 1));

    // Botones n

