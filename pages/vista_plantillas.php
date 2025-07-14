<style>
  /* Oculta la primera columna (la de los números) */
  table.plantillas-table th:first-child,
  table.plantillas-table td:first-child {
    display: none;
  }
</style>

<div style="max-height: 90%; overflow-y: auto;">
  <table class="table table-bordered border-secondary w-100 plantillas-table">
    <thead>
      <tr>
        <th scope="row"></th> <!-- Esta columna se oculta -->
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

<div id="paginacion" class="mt-3 d-flex justify-content-center gap-2"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const filasPorPagina = 10;
  let paginaActual = 1;

  const tabla = document.querySelector('table.plantillas-table');
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
