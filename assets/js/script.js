const botones = document.querySelectorAll('.cajaArchivos button');
const contenedor = document.getElementById('contenido-dinamico');

botones.forEach(btn => {
  btn.addEventListener('click', () => {
    const section = btn.getAttribute('data-section');

    if (section === 'usuarios') {
      cargarUsuarios();
    } else if (section === 'consultores') {
      contenedor.innerHTML = "<p>Consultores: Aquí va tu contenido</p>";
    } else if (section === 'proveedores') {
      contenedor.innerHTML = "<p>Proveedores: Aquí va tu contenido</p>";
    }
  });
});

function cargarUsuarios() {
  contenedor.innerHTML = `
    // <label for="filtro-usuarios">Filtrar por tipo:</label>
    // <select id="filtro-usuarios">
    //   <option value="todos">Todos</option>
    //   <option value="administrador">Administrador</option>
    //   <option value="consultor">Consultor</option>
    //   <option value="proveedor">Proveedor</option>
    // </select>

    <table class="table table-bordered border-secondary mt-3" id="tabla-usuarios">
      <thead>
        <tr>
          <th>ID</th>
          <th>Correo</th>
          <th>Nombre</th>
          <th>Rol</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  `;

  cargarDatosUsuarios(); // carga todos inicialmente

  document.getElementById('filtro-usuarios').addEventListener('change', function () {
    cargarDatosUsuarios(this.value);
  });
}

function cargarDatosUsuarios(filtro = 'todos') {
  fetch('api/usuarios.php')
    .then(response => response.json())
    .then(data => {
      let usuarios = data;

      // Excluir usuario con ID 1 (administrador principal)
      usuarios = usuarios.filter(user => user.id_usuarios != 1);

      //  Filtrar por rol si es necesario
      if (filtro !== 'todos') {
        usuarios = usuarios.filter(user => user.rol.toLowerCase() === filtro.toLowerCase());
      }

      const tbody = document.querySelector('#tabla-usuarios tbody');
      tbody.innerHTML = '';

      usuarios.forEach(user => {
        tbody.innerHTML += `
          <tr>
            <td>${user.id_usuarios}</td>
            <td>${user.correo}</td>
            <td>${user.nombre}</td>
            <td>${user.rol}</td>
          </tr>
        `;
      });
    })
    .catch(error => {
      alert('Error al cargar usuarios');
      console.error(error);
    });
}

//esto es lo del buscador de las plantilla del admin y el consultor 
usuarios.forEach(user => {
  tbody.innerHTML += `
    <tr>
      <td>${user.id_usuarios}</td>
      <td>${user.correo}</td>
      <td>${user.nombre}</td>
      <td>${user.rol}</td>
    </tr>
  `;
});

// 🔍 Buscador dinámico
document.getElementById('buscadorUsuarios').addEventListener('input', function () {
  const texto = this.value.toLowerCase();
  const filas = document.querySelectorAll('#tabla-usuarios tbody tr');

  filas.forEach(fila => {
    const contenidoFila = fila.textContent.toLowerCase();
    fila.style.display = contenidoFila.includes(texto) ? '' : 'none';
  });
});
