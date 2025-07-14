<style>
  /* Oculta la primera columna (la de numeración) */
  table.consultores-table th:first-child,
  table.consultores-table td:first-child {
    display: none;
  }
</style>

<div style="max-height: 90%; overflow-y: auto;">
  <table class="table table-bordered border-secondary w-100 consultores-table">
    <thead>
      <tr>
        <th>#</th> <!-- Esta columna la ocultamos -->
        <th>Correo</th>
        <th>Nombre Consultor</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $sql = "SELECT u.correo, c.nombre
              FROM consultores c
              JOIN usuarios u ON c.usuario_id = u.id_usuarios
              ORDER BY c.id";
      $result = $conexion->query($sql);
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

<div id="paginacion" class="mt-3 d-flex justify-content-center gap-2"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const filasPorPagina = 10;
  let paginaActual = 1;

  const tabla = document.querySelector('table.consultores-table');
  const tbody = tabla.querySelector('tbody');
  const filas = Array.from(tbody.querySelectorAll('tr'));
  const pagDiv = document.getElementById('paginacion');

  function mostrarPagina(pagina) {
    const inicio = (pagina - 1) * filasPorPagina;
    const fin = inicio + filasPorPagina;

    filas.forEach((fila, i) => {
      fila.style.display = (i >= inicio && i < fin) ? '' : 'none';
    });
  }

  function crearPaginacion() {
    pagDiv.innerHTML = '';
    const totalPaginas = Math.ceil(filas.length / filasPorPagina);

    // Botón "Primera página"
    const btnPrimera = document.createElement('button');
    btnPrimera.innerHTML = '⏮️';
    btnPrimera.className = 'btn btn-outline-primary';
    btnPrimera.disabled = paginaActual === 1;
    btnPrimera.addEventListener('click', () => {
      paginaActual = 1;
      mostrarPagina(paginaActual);
      crearPaginacion();
    });
    pagDiv.appendChild(btnPrimera);

    // Botones numéricos
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

    // Botón "Última página"
    const btnUltima = document.createElement('button');
    btnUltima.innerHTML = '⏭️';
    btnUltima.className = 'btn btn-outline-primary';
    btnUltima.disabled = paginaActual === totalPaginas;
    btnUltima.addEventListener('click', () => {
      paginaActual = totalPaginas;
      mostrarPagina(paginaActual);
      crearPaginacion();
    });
    pagDiv.appendChild(btnUltima);
  }

  mostrarPagina(paginaActual);
  crearPaginacion();
});
</script>
