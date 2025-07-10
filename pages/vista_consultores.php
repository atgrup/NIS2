<div style="max-height: 90%; overflow-y: auto;">
<?php
// Suponiendo que ya tienes conexión en $conexion
$sql = "SELECT u.correo, c.nombre
        FROM consultores c
        JOIN usuarios u ON c.usuario_id = u.id_usuarios
        ORDER BY c.id";

$result = $conexion->query($sql);
?>

<table class="table table-bordered border-secondary">
    <thead>
        <tr>
            <th>#</th>
            <th>Correo</th>
            <th>Nombre Consultor</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $i = 1;
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <th scope='row'>{$i}</th>
                    <td>" . htmlspecialchars($row['correo']) . "</td>
                    <td>" . htmlspecialchars($row['nombre'] ?? '') . "</td>
                  </tr>";
            $i++;
        }
        ?>
    </tbody>
</table>
</div>