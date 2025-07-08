
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
        if (userRol !== 'administrador') {
          alert("⚠️ No tienes permisos para acceder a la sección de usuarios.");
          contenedor.innerHTML = `
      <div class="alert alert-danger mt-4" role="alert">
        Acceso denegado. Esta sección solo está disponible para administradores.
      </div>
    `;
        } else {
          cargarUsuarios();
        }
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
  // Ponemos la tabla vacía en el contenedor
  contenedor.innerHTML = `
    <p>Cargando usuarios...</p>
    <table class="table table-bordered border-secondary mt-3" id="tabla-usuarios" style="display:none;">
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

function cargarDatosUsuarios() {
  fetch('http://localhost/NIS2/api/models/Usuario.php')
    .then(response => response.json())
    .then(data => {
      const usuarios = data;

      const tabla = document.getElementById('tabla-usuarios');
      const tbody = tabla.querySelector('tbody');
      tbody.innerHTML = ''; // limpio filas anteriores

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

      // Quitamos mensaje y mostramos tabla
      contenedor.querySelector('p').remove();
      tabla.style.display = 'table';
    })
    .catch(error => {
      alert('Error al cargar usuarios');
      console.error(error);
      contenedor.innerHTML = `<div class="alert alert-danger mt-4">Error cargando usuarios.</div>`;
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
document.addEventListener('DOMContentLoaded', function () {
  const buscador = document.getElementById('buscadorUsuarios');

  buscador.addEventListener('keyup', function () {
    const filtro = buscador.value.toLowerCase();

    const tabla = document.querySelector('table');
    if (!tabla) return;

    const filas = tabla.querySelectorAll('tbody tr');

    filas.forEach(function (fila) {
      const texto = fila.textContent.toLowerCase();
      fila.style.display = texto.includes(filtro) ? '' : 'none';
    });
  });
});

