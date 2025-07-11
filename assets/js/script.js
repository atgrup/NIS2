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
buscador?.addEventListener('keyup', e => {
  const texto = e.target.value.trim().toLowerCase();

  if (texto.length === 0) {
    cargarUsuarios(1);
    return;
  }

  // Para paginación con búsqueda real, debes hacer backend con filtro SQL.
  // Por ahora: mostrar mensaje o cargar normal
  contenedor.innerHTML = '<p>Búsqueda no implementada aún.</p>';
});

// Carga inicial
window.addEventListener('load', () => {
  cargarUsuarios();
});
