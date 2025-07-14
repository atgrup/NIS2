<div style="max-height: 90%; overflow-y: none;">
    <table class="table table-bordered border-secondary w-100">
        <thead>
            <tr>
                <th scope="row"></th>
                <th>Nombre de la plantilla</th>
                <th>Tipo</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $plantillas_dir = __DIR__ . '/../plantillas_disponibles/';
            $archivos = scandir($plantillas_dir);
            $i = 1;

            foreach ($archivos as $archivo) {
                if ($archivo !== '.' && $archivo !== '..') {
                    $ruta_url = '../plantillas_disponibles/' . $archivo;
                    echo "<tr>
                            <th scope='row'>{$i}</th>
                            <td><a href='" . htmlspecialchars($ruta_url) . "' download class='text-reset text-decoration-underline'>" . htmlspecialchars($archivo) . "</a></td>
                            <td>Plantilla</td>
                        </tr>";
                    $i++;
                }
            }
            ?>
        </tbody>
    </table>
</div>