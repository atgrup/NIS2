// Script para hacer todas las tablas ordenables por encabezado
// Inclúyelo al final de tu HTML o en tu plantilla principal

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('table').forEach(function (table) {
    const ths = table.querySelectorAll('th');
    ths.forEach((th, colIdx) => {
      th.style.cursor = 'pointer';
      let asc = true;
      th.addEventListener('click', function () {
        const tbody = table.tBodies[0];
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
          let aText = a.cells[colIdx]?.textContent.trim() || '';
          let bText = b.cells[colIdx]?.textContent.trim() || '';
          // Detectar si es número
          if (!isNaN(aText) && !isNaN(bText) && aText !== '' && bText !== '') {
            aText = parseFloat(aText);
            bText = parseFloat(bText);
          }
          if (aText < bText) return asc ? -1 : 1;
          if (aText > bText) return asc ? 1 : -1;
          return 0;
        });
        // Alternar orden
        asc = !asc;
        // Quitar filas y volver a ponerlas ordenadas
        rows.forEach(row => tbody.appendChild(row));
        // Opcional: marcar visualmente la columna ordenada
        ths.forEach(h => h.classList.remove('ordenado-asc', 'ordenado-desc'));
        th.classList.add(asc ? 'ordenado-asc' : 'ordenado-desc');
      });
    });
  });
});

// Puedes añadir estilos CSS para .ordenado-asc y .ordenado-desc si quieres flechitas visuales.
