<div style="max-height: 100%; overflow-y: auto;">
    <table id="tablaUsuarios" class="table table-bordered border-secondary">
        <thead>
            <tr>
                <th>#</th>
                <th>Correo</th>
                <th>Tipo de usuario</th>
                <th>Verificado</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT u.id_usuarios, u.correo, t.nombre AS tipo_usuario, u.verificado
                    FROM usuarios u
                    LEFT JOIN tipo_usuario t ON u.tipo_usuario_id = t.id_tipo_usuario
                    ORDER BY u.id_usuarios";

            $result = $conexion->query($sql);
            $i = 1;

            while ($row = $result->fetch_assoc()) {
                $verificado = $row['verificado'] ? 'Sí' : 'No';
                echo "<tr>
                        <td>{$i}</td>
                        <td>" . htmlspecialchars($row['correo']) . "</td>
                        <td>" . htmlspecialchars($row['tipo_usuario']) . "</td>
                        <td class='text-center'>{$verificado}</td>
                    </tr>";
                $i++;
            }
            ?>
        </tbody>
    </table>
</div>
<div id="paginacion" class="mt-3 d-flex justify-content-center gap-2"></div>
