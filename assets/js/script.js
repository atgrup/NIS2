
let seccionActual = null;

const botones = document.querySelectorAll('.cajaArchivos button');
const contenedor = document.getElementById('contenido-dinamico');

// Evento para los botones de sección
botones.forEach(btn => {
  btn.addEventListener('click', () => {
    const section = btn.getAttribute('data-section');
    seccionActual = section;

    switch (section) {
      case 'usuarios':
        cargarUsuarios();
        break;
      case 'archivos':
        cargarArchivos();
        break;
      case 'consultores':
        contenedor.innerHTML = "<p>Consultores: Aquí va tu contenido</p>";
        break;
      case 'proveedores':
        contenedor.innerHTML = "<p>Proveedores: Aquí va tu contenido</p>";
        break;
      default:
        contenedor.innerHTML = "<p>Sección desconocida</p>";
    }
  });
});

// Carga usuarios en tabla
function cargarUsuarios() {
  contenedor.innerHTML = `
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

  cargarDatosUsuarios();
}

// Simula carga de datos de usuarios (puedes reemplazar con tu fetch real)
function cargarDatosUsuarios(filtro = 'todos') {
  fetch('api/usuarios.php')
    .then(response => response.json())
    .then(data => {
      let usuarios = data;

      // Excluir superadmin
      usuarios = usuarios.filter(user => user.id_usuarios != 1);

      // Si es consultor, filtrar por rol proveedor
      if (typeof userRol !== 'undefined' && userRol === 'consultor') {
        usuarios = usuarios.filter(user => user.rol.toLowerCase() === 'proveedor');
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

// Carga tabla de archivos
function cargarArchivos() {
  contenedor.innerHTML = `
    <table class="table table-bordered mt-3" id="tabla-archivos">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre del Archivo</th>
          <th>Subido por</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>1</td>
          <td>documento_final.pdf</td>
          <td>admin@correo.com</td>
        </tr>
        <tr>
          <td>2</td>
          <td>reporte_anual.xlsx</td>
          <td>consultor@correo.com</td>
        </tr>
        <tr>
          <td>3</td>
          <td>informe_seguridad.docx</td>
          <td>proveedor@correo.com</td>
        </tr>
      </tbody>
    </table>
  `;
}

// Buscador GLOBAL contextual (usuarios, archivos, etc.)
document.getElementById('buscadorUsuarios').addEventListener('input', function () {
  const texto = this.value.toLowerCase();

  switch (seccionActual) {
    case 'usuarios':
      document.querySelectorAll('#tabla-usuarios tbody tr').forEach(fila => {
        fila.style.display = fila.textContent.toLowerCase().includes(texto) ? '' : 'none';
      });
      break;

    case 'archivos':
      document.querySelectorAll('#tabla-archivos tbody tr').forEach(fila => {
        fila.style.display = fila.textContent.toLowerCase().includes(texto) ? '' : 'none';
      });
      break;

    // Puedes agregar más casos aquí si agregas más tablas
  }
});

