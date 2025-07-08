<div style="max-height: 80%; overflow-y: auto;">
    <table class="table table-bordered border-secondary">
        <thead>
            <tr>
                <th>#</th>
                <th>Correo</th>
                <th>Tipo de usuario</th>
                <th>Verificado</th>
                <th>Acciones</th>
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
                $id = $row['id_usuarios'];
                $correo = htmlspecialchars($row['correo']);
                $tipo = htmlspecialchars($row['tipo_usuario']);
                ?>
                <tr>
                    <th scope="row"><?= $i ?></th>
                    <td><?= $correo ?></td>
                    <td><?= $tipo ?></td>
                    <td class="text-center"><?= $verificado ?></td>
                    <td class="text-center">
                        <!-- Botón Modificar -->
                        <button
                            class="btn btn-sm btn-warning me-1"
                            data-bs-toggle="modal"
                            data-bs-target="#modalEditar"
                            data-id="<?= $id ?>"
                            data-correo="<?= $correo ?>"
                            data-tipo="<?= $tipo ?>"
                            data-verificado="<?= $row['verificado'] ?>"
                            title="Modificar"
                        >
                            <i class="bi bi-pencil-square"></i>
                        </button>

                        <!-- Botón Eliminar -->
                        <form action="usuario_borrar.php" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que quieres eliminar este usuario?');">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php
                $i++;
            }
            ?>
        </tbody>
    </table>
</div>
