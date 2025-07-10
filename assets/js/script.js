let seccionActual = null;

const botones = document.querySelectorAll('.cajaArchivos button[data-section], .cajaArchivos a[data-section]');
const contenedor = document.getElementById('contenido-dinamico');
const buscador = document.getElementById('buscadorUsuarios');

const placeholders = {
  usuarios: "Buscar usuario...",
  archivos: "Buscar archivo...",
  consultores: "Buscar consultor...",
  proveedores: "Buscar proveedor...",
  plantillas: "Buscar plantilla...",
  default: "Buscar..."
};

// Función para actualizar placeholder según sección
function actualizarPlaceholder(seccion) {
  buscador.placeholder = placeholders[seccion] || placeholders.default;
}

// Evento para los botones y links con data-section
botones.forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.preventDefault(); // evitar navegación en links <a>
    const section = btn.getAttribute('data-section');
    seccionActual = section;
    actualizarPlaceholder(section);

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
        cargarConsultores();
        break;
      case 'proveedores':
        cargarProveedores();
        break;
      case 'plantillas':
        cargarPlantillas(); // crea esta función si tienes plantillas
        break;
      default:
        contenedor.innerHTML = "<p>Sección desconocida</p>";
    }
  });
});


buscador.addEventListener('keyup', function () {
  const filtro = buscador.value.toLowerCase();
  const tablaVisible = contenedor.querySelector('table');
  if (!tablaVisible) return;

  const filas = tablaVisible.querySelectorAll('tbody tr');
  filas.forEach(fila => {
    // Supongamos que columna 0 = ID, columna 2 = Nombre (ajusta según tabla)
    const celdas = fila.querySelectorAll('td');
const id = celdas[0]?.textContent.toLowerCase() || '';
const correo = celdas[1]?.textContent.toLowerCase() || '';
const nombre = celdas[2]?.textContent.toLowerCase() || '';

fila.style.display = (id.includes(filtro) || nombre.includes(filtro) || correo.includes(filtro)) ? '' : 'none';
 });
});


// Aquí cargas usuarios, archivos, consultores, proveedores...
// Ejemplo cargarUsuarios() igual que tu versión

function cargarUsuarios() {
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

  fetch('http://localhost/NIS2/api/models/Usuario.php')
    .then(response => response.json())
    .then(data => {
      const tabla = document.getElementById('tabla-usuarios');
      const tbody = tabla.querySelector('tbody');
      tbody.innerHTML = '';

      data.forEach(user => {
        tbody.innerHTML += `
          <tr>
            <td>${user.id_usuarios}</td>
            <td>${user.correo}</td>
            <td>${user.nombre}</td>
            <td>${user.rol}</td>
          </tr>
        `;
      });

      contenedor.querySelector('p')?.remove();
      tabla.style.display = 'table';
    })
    .catch(error => {
      console.error(error);
      contenedor.innerHTML = `<div class="alert alert-danger mt-4">Error cargando usuarios.</div>`;
    });
}

// Similar para cargarArchivos(), cargarConsultores(), cargarProveedores(), cargarPlantillas()

// Ejemplo para archivos:
function cargarArchivos() {
  contenedor.innerHTML = `
    <p>Cargando archivos...</p>
    <table class="table table-bordered mt-3" id="tabla-archivos" style="display: none;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre del Archivo</th>
          <th>Fecha de Subida</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  `;

  fetch('http://localhost/NIS2/api/models/get_archivos.php')
    .then(response => response.json())
    .then(data => {
      const tabla = document.getElementById('tabla-archivos');
      const tbody = tabla.querySelector('tbody');
      tbody.innerHTML = '';

      if (data.length === 0) {
        contenedor.innerHTML = `<div class="alert alert-info mt-4">No hay archivos subidos.</div>`;
        return;
      }

      data.forEach(archivo => {
        tbody.innerHTML += `
          <tr>
            <td>${archivo.id}</td>
            <td>${archivo.nombre_archivo}</td>
            <td>${archivo.fecha_subida}</td>
          </tr>
        `;
      });

      contenedor.querySelector('p')?.remove();
      tabla.style.display = 'table';
    })
    .catch(error => {
      console.error(error);
      contenedor.innerHTML = `<div class="alert alert-danger mt-4">Error cargando archivos.</div>`;
    });
}


// Ejemplo función vacía para plantillas, crea según tu lógica
function cargarPlantillas() {
  contenedor.innerHTML = `
    <p>Aquí carga tus plantillas...</p>
  `;
}

// Inicializamos con la sección que haya cargado inicialmente si quieres
window.addEventListener('load', () => {
  if(seccionActual) actualizarPlaceholder(seccionActual);
});

window.addEventListener('load', () => {
  let vistaInicial = new URLSearchParams(window.location.search).get('vista') || 'archivos';
  seccionActual = vistaInicial;
  actualizarPlaceholder(vistaInicial);

  // Disparar carga según vista inicial
  switch (vistaInicial) {
    case 'usuarios':
      cargarUsuarios();
      break;
    case 'archivos':
      cargarArchivos();
      break;
    case 'consultores':
      cargarConsultores();
      break;
    case 'proveedores':
      cargarProveedores();
      break;
    case 'plantillas':
      cargarPlantillas();
      break;
    default:
      contenedor.innerHTML = `<p>Sección desconocida</p>`;
  }
});
//creacion de consultor en el panel de administrador
document.getElementById('formCrearUsuario').addEventListener('submit', async (e) => {
  e.preventDefault();

  const form = e.target;
  const formData = new FormData(form);
  formData.append('accion', 'crear_usuario');
  formData.append('tipo_usuario_id', 3); // ID para consultor

 try {
  const response = await fetch('http://localhost/NIS2/api/models/Usuario.php', {
    method: 'POST',
    body: formData
  });

  const text = await response.text(); // lee como texto

  console.log('Respuesta del servidor:', text);

  // Intenta parsear JSON solo si no está vacío
  if (text.trim().length === 0) {
    alert('Respuesta vacía del servidor');
    return;
  }

  const result = JSON.parse(text); // parsea JSON

  if (result.success) {
    alert('✅ Usuario consultor creado correctamente');
    form.reset();
    const modalEl = document.getElementById('modalCrearUsuario');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
    cargarConsultores();
  } else {
    alert('❌ Error: ' + (result.message || 'Error desconocido'));
  }
} catch (error) {
  console.error('Error al procesar respuesta:', error);
  alert('❌ Error al enviar formulario o respuesta inválida');
  form.reset();
}

});
