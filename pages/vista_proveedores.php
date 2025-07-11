<div style="max-height: 90%; overflow-y: auto;">
<?php
// Suponiendo que ya tienes conexión en $conexion
$sql = "SELECT u.correo, p.nombre_empresa
        FROM proveedores p
        JOIN usuarios u ON p.usuario_id = u.id_usuarios
        ORDER BY p.id";

$result = $conexion->query($sql);
?>
<div style="max-height: 80%; overflow-y: auto;">

<table class="table table-bordered border-secondary"  id="tablaProveedores">
    <thead>
        <tr>
            
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

    </div><div id="paginacion" class="mt-3 d-flex justify-content-center gap-2"></div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const tabla = document.getElementById('tablaProveedores');
  if (!tabla) return;

  const filasPorPagina = 10;
  let paginaActual = 1;
  const tbody = tabla.querySelector('tbody');
  const filas = Array.from(tbody.querySelectorAll('tr'));
  const pagDiv = document.getElementById('paginacion');

  function mostrarPagina(pagina) {
    const inicio = (pagina - 1) * filasPorPagina;
    const fin = inicio + filasPorPagina;

    filas.forEach((fila, i) => {
      fila.style.display = i >= inicio && i < fin ? '' : 'none';
      fila.querySelector('th').textContent = i + 1; // actualizar numeración
    });
  }

  function crearPaginacion() {
    pagDiv.innerHTML = '';
    const totalPaginas = Math.ceil(filas.length / filasPorPagina);

    for (let i = 1; i <= totalPaginas; i++) {
      const btn = document.createElement('button');
      btn.textContent = i;
      btn.className = 'btn ' + (i === paginaActual ? 'btn-primary' : 'btn-outline-primary');
      btn.addEventListener('click', () => {
        paginaActual = i;
        mostrarPagina(paginaActual);
        crearPaginacion();
      });
      pagDiv.appendChild(btn);
    }
  }

  mostrarPagina(paginaActual);
  crearPaginacion();
});
</script>

</div>

