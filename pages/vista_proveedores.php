<div style="max-height: 90%; overflow-y: auto;">
<?php
// Suponiendo que ya tienes conexión en $conexion
$sql = "SELECT u.correo, p.nombre_empresa
        FROM proveedores p
        JOIN usuarios u ON p.usuario_id = u.id_usuarios
        ORDER BY p.id";

$result = $conexion->query($sql);
?>

<table class="table table-bordered border-secondary">
    <thead>
        <tr>
            <th>#</th>
            <th>Correo</th>
            <th>Nombre Empresa</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $i = 1;
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <th scope='row'>{$i}</th>
                    <td>" . htmlspecialchars($row['correo']) . "</td>
                    <td>" . htmlspecialchars($row['nombre_empresa'] ?? '') . "</td>
                  </tr>";
            $i++;
        }
        ?>
    </tbody>
</table>
</div>
