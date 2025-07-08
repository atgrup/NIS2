<div style="max-height: 90%; overflow-y: auto;">
<table class="table table-bordered border-secondary">
    <thead>
        <tr>
            <th>#</th>
                <th>Nombre del archivo</th>
                <th>Fecha</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $conexion->prepare("SELECT u.id_usuarios, p.id FROM usuarios u JOIN proveedores p ON u.id_usuarios = p.usuario_id WHERE u.correo = ?");
            $stmt->bind_param("s", $correo);
            $stmt->execute();
            $stmt->bind_result($usuario_id, $proveedor_id);
            $stmt->fetch();
            $stmt->close();

            $stmt = $conexion->prepare("SELECT id, nombre_archivo, fecha_subida, revision_estado, archivo_url FROM archivos_subidos WHERE proveedor_id = ?");
            $stmt->bind_param("i", $proveedor_id);
            $stmt->execute();
            $stmt->bind_result($id, $nombre, $fecha, $estado, $archivo_url);

            $i = 1;
            while ($stmt->fetch()) {
                $ruta_fisica = realpath(__DIR__ . '/../' . $archivo_url);
                if (file_exists($ruta_fisica)) {
                    echo "<tr>
                            <th scope='row'>{$i}</th>
                            <td><a href='download.php?archivo=" . urlencode($archivo_url) . "' style='color: inherit; text-decoration: underline;'>" . htmlspecialchars($nombre) . "</a></td>
                            <td>{$fecha}</td>
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