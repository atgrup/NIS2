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

    <!-- El filtrado y paginación se gestiona desde plantillaUsers.js -->
