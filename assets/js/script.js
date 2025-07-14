const contenedor = document.getElementById('contenido-dinamico');
const buscador = document.getElementById('buscadorUsuarios');

let paginaActual = 1;
const usuariosPorPagina = 10;
let totalUsuarios = 0;

async function cargarUsuarios(page = 1, query = '') {
  paginaActual = page;

  let url = `api/models/usuarios.php?page=${paginaActual}&limit=${usuariosPorPagina}`;
  if (query) {
    url += `&query=${encodeURIComponent(query)}`;  // Si agregas búsqueda en backend luego
  }

  contenedor.innerHTML = '<p>Cargando usuarios...</p>';

  try {
    const response = await fetch(url);
    const data = await response.json();

    if (data.error) {
      contenedor.innerHTML = `<p>Error: ${data.error}</p>`;
      return;
    }

    totalUsuarios = data.total;
    renderizarUsuarios(data.usuarios);
    renderizarPaginacion();
  } catch (error) {
    contenedor.innerHTML = '<p>Error al cargar usuarios.</p>';
    console.error(error);
  }
}

function renderizarUsuarios(usuarios) {
  if (!usuarios.length) {
    contenedor.innerHTML = '<p>No hay usuarios.</p>';
    return;
  }

  const tablaHTML = `
    <table class="table table-bordered mt-3">
      <thead>
        <tr>
          <th>Correo</th><th>Nombre</th><th>Rol</th>
        </tr>
      </thead>
      <tbody>
        ${usuarios.map(u => `
          <tr>
            <td>${u.correo}</td>
            <td>${u.nombre}</td>
            <td>${u.rol}</td>
          </tr>
        `).join('')}
      </tbody>
    </table>
  `;

  contenedor.innerHTML = tablaHTML;
}

function renderizarPaginacion() {
  const totalPaginas = Math.ceil(totalUsuarios / usuariosPorPagina);
  const paginacionDiv = document.getElementById('paginacion');

  if (!paginacionDiv) {
    // Crear contenedor si no existe
    const div = document.createElement('div');
    div.id = 'paginacion';
    div.className = 'mt-3 d-flex justify-content-center gap-2';
    contenedor.appendChild(div);
  }

  const paginacion = document.getElementById('paginacion');
  paginacion.innerHTML = '';

  for (let i = 1; i <= totalPaginas; i++) {
    const btn = document.createElement('button');
    btn.classList.add('btn', i === paginaActual ? 'btn-primary' : 'btn-outline-primary');
    btn.textContent = i;
    btn.addEventListener('click', () => {
      cargarUsuarios(i);
    });
    paginacion.appendChild(btn);
  }
}

// Evento para buscar usuarios por nombre (simple filtro local)
buscador?.addEventListener("input", function () {
  const texto = buscador.value.trim().toLowerCase();

  // Busca en cualquier tabla dentro de #contenido-dinamico
  const tabla = contenedor.querySelector("table");
  if (!tabla) return;

  const filas = tabla.querySelectorAll("tbody tr");

  filas.forEach(fila => {
    const visible = Array.from(fila.cells).some(celda =>
      celda.textContent.toLowerCase().includes(texto)
    );
    fila.style.display = visible ? "" : "none";
  });
});

// Carga inicial
window.addEventListener('load', () => {
  cargarUsuarios();
});

  document.addEventListener('DOMContentLoaded', () => {
    let seccionActual = new URLSearchParams(window.location.search).get('vista') || 'archivos';
    const buscador = document.getElementById('buscadorUsuarios');

    // Publica una función modular para filtrar tabla HTML
    function filtrarTablaHTML() {
      const texto = buscador.value.trim().toLowerCase();
      const tabla = document.querySelector('table');
      if (!tabla) return;
      const filas = tabla.querySelectorAll('tbody tr');
      let contador = 1;

      filas.forEach(fila => {
        const celdas = Array.from(fila.querySelectorAll('td'));
        const coincide = celdas.some(td =>
          td.textContent.toLowerCase().includes(texto)
        );
        fila.style.display = coincide ? '' : 'none';
        if (coincide && fila.querySelector('th')) {
          fila.querySelector('th').textContent = contador++;
        }
      });
    }

    buscador.addEventListener('input', () => {
      if (seccionActual === 'usuarios' || seccionActual === 'proveedores' ||
          seccionActual === 'consultores' || seccionActual === 'plantillas') {
        filtrarTablaHTML();
      }
    });

    // Inicializa filtrado después de cargar la vista
    filtrarTablaHTML();
  });
btn.addEventListener('click', async (e) => {
  e.preventDefault();
  const section = btn.getAttribute('data-section');
  seccionActual = section;
  actualizarPlaceholder(section);

  switch (section) {
    case 'usuarios':
      if (userRol !== 'administrador') {
        contenedor.innerHTML = `<div class="alert alert-danger">No tienes permisos para ver usuarios.</div>`;
      } else {
        await cargarUsuarios();
       // mostrarModal('crearUsuarioModal'); // 👈 Mostrar modal tras cargar
      }
      break;
    case 'proveedores':
      await cargarProveedores();
     // mostrarModal('crearProveedorModal'); // 👈 Mostrar modal tras cargar
      break;
    case 'plantillas':
      await cargarPlantillas();
     // mostrarModal('crearPlantillaModal'); // 👈 Mostrar modal tras cargar
      break;
    // Añade más según tus secciones
    default:
      contenedor.innerHTML = `<p>Sección desconocida</p>`;
  }
});
