<script>
  // Paginación clásica SOLO para la tabla de usuarios
  document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('tablaUsuarios')) {
      const filasPorPagina = 10;
      let paginaActual = 1;
      const tabla = document.getElementById('tablaUsuarios');
      const tbody = tabla.querySelector('tbody');
      const filas = Array.from(tbody.querySelectorAll('tr'));
      const pagDiv = document.getElementById('paginacion');
      const buscador = document.getElementById('buscadorUsuarios');

      function mostrarPagina(pagina, datosFiltrados) {
        const inicio = (pagina - 1) * filasPorPagina;
        const fin = inicio + filasPorPagina;
        filas.forEach(fila => fila.style.display = 'none');
        datosFiltrados.slice(inicio, fin).forEach(fila => fila.style.display = '');
      }

      function crearPaginacion(datosFiltrados) {
        pagDiv.innerHTML = '';
        const totalPaginas = Math.ceil(datosFiltrados.length / filasPorPagina);
        // Botón "Primera página"
        const btnPrimera = document.createElement('button');
        btnPrimera.innerHTML = '⏮️';
        btnPrimera.className = 'btn btn-outline-primary';
        btnPrimera.disabled = paginaActual === 1;
        btnPrimera.addEventListener('click', () => {
          paginaActual = 1;
          mostrarPagina(paginaActual, datosFiltrados);
          crearPaginacion(datosFiltrados);
        });
        pagDiv.appendChild(btnPrimera);
        // Botones de números de página
        for (let i = 1; i <= totalPaginas; i++) {
          const btn = document.createElement('button');
          btn.textContent = i;
          btn.className = 'btn ' + (i === paginaActual ? 'btn-primary' : 'btn-outline-primary');
          btn.addEventListener('click', () => {
            paginaActual = i;
            mostrarPagina(paginaActual, datosFiltrados);
            crearPaginacion(datosFiltrados);
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
          mostrarPagina(paginaActual, datosFiltrados);
          crearPaginacion(datosFiltrados);
        });
        pagDiv.appendChild(btnUltima);
      }

      function filtrarTabla() {
        const texto = buscador.value.toLowerCase();
        const filasFiltradas = filas.filter(fila => {
          return Array.from(fila.cells).some(celda =>
            celda.textContent.toLowerCase().includes(texto)
          );
        });
        paginaActual = 1;
        mostrarPagina(paginaActual, filasFiltradas);
        crearPaginacion(filasFiltradas);
      }

      buscador.addEventListener('input', filtrarTabla);
      filtrarTabla();
    }
  });
</script>
<?php
session_start();

if (!isset($_SESSION['rol'])) {
  header("Location: ../api/auth/login.php");
  exit;
}

$rol = strtolower($_SESSION['rol']);
$correo = $_SESSION['correo'] ?? null;
$nombre = $correo ? explode('@', $correo)[0] : 'Invitado';

// Conexión BD
$conexion = new mysqli('jordio35.sg-host.com', 'u74bscuknwn9n', 'ad123456-', 'dbs1il8vaitgwc');
if ($conexion->connect_error) {
  die("Error de conexión: " . $conexion->connect_error);
}

// SUBIR ARCHIVO
// SUBIR ARCHIVO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo'];
    $nombre_original = basename($archivo['name']);
    $nombre_archivo = time() . "_" . $nombre_original;

    $stmt = $conexion->prepare("SELECT u.id_usuarios, p.id FROM usuarios u JOIN proveedores p ON u.id_usuarios = p.usuario_id WHERE u.correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->bind_result($usuario_id, $proveedor_id);
    $stmt->fetch();
    $stmt->close();

    $carpeta_usuario = __DIR__ . '/../documentos_subidos/' . $correo;
    $carpeta_url = 'documentos_subidos/' . $correo;

    if (!is_dir($carpeta_usuario)) {
      mkdir($carpeta_usuario, 0775, true);
    }

    $ruta_fisica = $carpeta_usuario . '/' . $nombre_archivo;
    $ruta_para_bd = $carpeta_url . '/' . $nombre_archivo;

    if (move_uploaded_file($archivo['tmp_name'], $ruta_fisica)) {
      $stmt = $conexion->prepare("INSERT INTO archivos_subidos (proveedor_id, archivo_url, nombre_archivo, revision_estado) VALUES (?, ?, ?, 'pendiente')");
      if ($proveedor_id) {
        $stmt->bind_param("iss", $proveedor_id, $ruta_para_bd, $nombre_original);
      } else {
        $null = null;
        $stmt->bind_param("iss", $null, $ruta_para_bd, $nombre_original);
      }
      $stmt->execute();
      $stmt->close();

      echo "<script>alert('✅ Archivo subido correctamente'); window.location.href='plantillaUsers.php?vista=archivos';</script>";
      exit;
    } else {
      echo "<script>alert('❌ Error al mover el archivo');</script>";
    }
  } else {
    echo "<script>alert('❌ No se ha seleccionado archivo');</script>";
  }
}
$vista = $_GET['vista'] ?? 'archivos';
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Documentos</title>
  <link rel="stylesheet" href="../assets/styles/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;700&display=swap" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>



</head>

<body class="stencilBody sin-scroll">
  <main class="stencil container-fluid p-0 overflow-hidden">
    <nav class="indexStencil">
      <h1 class="tituloNIS">NIS2</h1>
      <h4>Hola, <?php echo htmlspecialchars($nombre); ?></h4>

      <div class="menuNav">
        <?php if ($rol === 'administrador'): ?>
          <div class="cajaArchivos mb-2">
            <a class="btn btn-outline-light w-100" href="?vista=usuarios">USUARIOS</a>
          </div>
          <div class="cajaArchivos mb-2">
            <a class="btn btn-outline-light w-100" href="?vista=consultores">CONSULTORES</a>
          </div>
          <div class="cajaArchivos mb-2">
            <a class="btn btn-outline-light w-100" href="?vista=proveedores">PROVEEDORES</a>
          </div>
        <?php endif; ?>

        <!-- Botones comunes -->
        <div class="cajaArchivos mb-2">
          <a href="?vista=plantillas" class="btn btn-outline-light w-100">PLANTILLAS</a>
        </div>
        <div class="cajaArchivos mb-2">
          <a href="?vista=archivos" class="btn btn-outline-light w-100">ARCHIVOS</a>
        </div>

        <?php if ($rol === 'consultor'): ?>
          <div class="cajaArchivos mb-2">
            <a class="btn btn-outline-light w-100" href="?vista=proveedores">PROVEEDORES</a>
          </div>
        <?php endif; ?>

        <div class="footerNaV mt-3">
          <form action="../api/auth/logout.php" method="post" class="mb-2">
            <button type="submit" class="btn btn-outline-light w-100">Cerrar sesión</button>
          </form>
        </div>
      </div>
    </nav>

    <div class="contenedorTablaStencil">
      <!-- Buscador en línea con los botones -->
      <div class="d-flex align-items-center flex-wrap gap-2 mt-3 px-3" style="padding-bottom: 1.5rem;">
        <div class="btns me-auto d-flex flex-wrap gap-2">
          <?php if ($vista === 'archivos'): ?>
            <form method="POST" enctype="multipart/form-data" class="d-inline">
              <label for="archivo" class="btn bg-mi-color w-100">Subir archivo</label>
              <input type="file" name="archivo" id="archivo" class="d-none" onchange="this.form.submit()" required>
            </form>
          <?php endif; ?>
          <?php if ($vista === 'plantillas' && ($rol === 'administrador' || $rol === 'consultor')): ?>
            <form method="POST" enctype="multipart/form-data" class="d-inline mb-3">
              <label for="plantilla" class="btn bg-mi-color w-100">Subir plantilla</label>
              <input type="file" name="plantilla" id="plantilla" class="d-none" onchange="this.form.submit()" required>
            </form>
          <?php endif; ?>
          <?php if ($rol === 'administrador'): ?>
            <div class="d-flex flex-wrap gap-2 px-3 mt-2">
              <?php if ($vista === 'usuarios'): ?>
                <button class="btn bg-mi-color w-100" data-bs-toggle="modal" data-bs-target="#crearUsuarioModal">Crear Usuario</button>
              <?php elseif ($vista === 'consultores'): ?>
                <button class="btn bg-mi-color w-100" data-bs-toggle="modal" data-bs-target="#crearConsultorModal">Crear Consultor</button>
              <?php elseif ($vista === 'proveedores'): ?>
                <button class="btn bg-mi-color w-100" data-bs-toggle="modal" data-bs-target="#crearProveedorModal">Crear Proveedor</button>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Buscador -->
        <div class="input-group" style="max-width: 300px;">
          <span class="input-group-text">
            <img src="../assets/img/search.png" alt="Buscar">
          </span>
          <input type="text" class="form-control" placeholder="Buscar..." id="buscadorUsuarios"> 
        </div>
      </div> <!-- Cierre d-flex align-items-center -->

      <div class="headertable">
        <?php
          switch ($vista) {
            case 'plantillas':
              include 'vista_plantillas.php';
              break;
            case 'usuarios':
              include 'vista_usuarios.php';
              break;
            case 'consultores':
              include 'vista_consultores.php';
              break;
            case 'proveedores':
              include 'vista_proveedores.php';
              break;
            default:
              include 'vista_archivos.php';
              break;
          }
        ?>
      </div>
    </div> <!-- Cierre contenedorTablaStencil -->

  </main> <!-- Cierre main -->
<?php

$alertaPassword = isset($_SESSION['error']) && $_SESSION['error'] === "Las contraseñas no coinciden.";
$alertaCorreo = isset($_SESSION['error']) && $_SESSION['error'] === "El correo ya está registrado.";
$alertaExito = isset($_SESSION['success']) && $_SESSION['success'] === "Usuario creado correctamente";
$mostrarModal = $alertaPassword || $alertaCorreo || $alertaExito;
?>

  <!-- Modal Crear admin -->
 <div class="modal fade" id="crearUsuarioModal" tabindex="-1" aria-labelledby="crearUsuarioLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="crear_admin.php" onsubmit="return validarContrasenas('usuario')">
      <div class="modal-content">
        <div class="modal-header bg-mi-color text-white">
          <h5 class="modal-title" id="crearUsuarioLabel">Crear Administrador</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">

          <!-- ALERTAS -->
          <div id="alerta-password" class="alert alert-danger alert-dismissible fade show" role="alert" style="display:none;">
            <span>Las contraseñas no coinciden</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
          </div>

          <div id="alerta-correo" class="alert alert-danger alert-dismissible fade show" role="alert" style="display:none;">
            <span>El correo ya está registrado</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
          </div>

          <div id="alerta-exito" class="alert alert-success alert-dismissible fade show" role="alert" style="display:none;">
            <span>Usuario creado correctamente</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
          </div>

          <!-- FORMULARIO -->
          <div class="mb-3">
            <label for="correoUsuario" class="form-label-popup">Correo</label>
            <input type="email" class="form-control" id="correoUsuario" name="correo" required>
          </div>
          <div class="mb-3">
            <label for="contrasenaUsuario" class="form-label-popup">Contraseña</label>
            <input type="password" class="form-control" id="contrasenaUsuario" name="contrasena" required>
          </div>
          <div class="mb-3">
            <label for="contrasenaUsuario2" class="form-label-popup">Repetir Contraseña</label>
            <input type="password" class="form-control" id="contrasenaUsuario2" name="contrasena2" required>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Crear Usuario</button>
        </div>
      </div>
    </form>
  </div>
</div>

  <!-- Modal Crear Consultor -->
  <div class="modal fade" id="crearConsultorModal" tabindex="-1" aria-labelledby="crearConsultorLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form method="POST" action="crear_consultor.php" onsubmit="return validarContrasenas('consultor')">
        <div class="modal-content">
          <div class="modal-header bg-mi-color text-white">
            <h5 class="modal-title" id="crearConsultorLabel">Crear Consultor</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="correoConsultor" class="form-label-popup">Correo</label>
              <input type="email" class="form-control" id="correoConsultor" name="correo" required>
            </div>
            <div class="mb-3">
              <label for="contrasenaConsultor" class="form-label-popup">Contraseña</label>
              <input type="password" class="form-control" id="contrasenaConsultor" name="contrasena" required>
            </div>
            <div class="mb-3">
              <label for="contrasenaConsultor2" class="form-label-popup">Repetir Contraseña</label>
              <input type="password" class="form-control" id="contrasenaConsultor2" name="contrasena2" required>
            </div>
            <div id="errorConsultor" class="text-danger" style="display:none;">Las contraseñas no coinciden</div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Crear Consultor</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Crear Proveedor -->
  <div class="modal fade" id="crearProveedorModal" tabindex="-1" aria-labelledby="crearProveedorLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form method="POST" action="crear_proveedor.php" id="formCrearProveedor" onsubmit="return validarContrasenas('proveedor')">
        <div class="modal-content">
          <div class="modal-header bg-mi-color text-white">
            <h5 class="modal-title" id="crearProveedorLabel">Crear Proveedor</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="correoProveedor" class="form-label-popup">Correo</label>
              <input type="email" class="form-control" id="correoProveedor" name="email" required>
            </div>
            <div class="mb-3">
              <label for="nombreEmpresa" class="form-label-popup">Nombre de Empresa</label>
              <input type="text" class="form-control" id="nombreEmpresa" name="nombre_empresa" required>
            </div>
            <div class="mb-3">
              <label for="contrasenaProveedor" class="form-label-popup">Contraseña</label>
              <input type="password" class="form-control" id="contrasenaProveedor" name="password" required>
            </div>
            <div class="mb-3">
              <label for="contrasenaProveedor2" class="form-label-popup">Repetir Contraseña</label>
              <input type="password" class="form-control" id="contrasenaProveedor2" name="repeat-password" required>
            </div>
            <div id="errorProveedor" class="text-danger" style="display:none;">Las contraseñas no coinciden</div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Crear Proveedor</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</main>

</body>
<!-- <script>
  document.getElementById('formCrearProveedor').addEventListener('submit', function (e) {
    e.preventDefault();

    const pass1 = document.getElementById('contrasenaProveedor').value;
    const pass2 = document.getElementById('contrasenaProveedor2').value;
    const errorDiv = document.getElementById('errorProveedor');

    if (pass1 !== pass2) {
      errorDiv.innerText = 'Las contraseñas no coinciden';
      errorDiv.style.display = 'block';
      return;
    }
    errorDiv.style.display = 'none';

    const formData = new FormData(this);
    fetch('crear_proveedor.php', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          // Cierra el modal
          const modal = bootstrap.Modal.getInstance(document.getElementById('modalCrearProveedor'));
          modal.hide();


          // Redirige a vista=proveedores
          window.location.href = "plantillasUsers.php?vista=proveedores";
        } else {
          errorDiv.innerText = data.message || 'Error al crear proveedor';
          errorDiv.style.display = 'block';
        }
      })
      .catch(err => {
        console.error('Error al enviar el formulario:', err);
        errorDiv.innerText = 'Error en el servidor';
        errorDiv.style.display = 'block';
      });
  });

</script> -->
<script>
  let seccionActual = null;
  let usuariosData = [];
  let consultoresData = [];
  let proveedoresData = [];
  let plantillasData = [];
  let archivosData = [];
  let usuariosPorPagina = 10;
  let consultoresPorPagina = 10;
  let proveedoresPorPagina = 10;
  let plantillasPorPagina = 10;
  let archivosPorPagina = 10;
  let paginaActual = 1;

  const botones = document.querySelectorAll('.cajaArchivos button[data-section], .cajaArchivos a[data-section]');
  let buscador = document.getElementById('buscadorUsuarios');
  const contenedor = document.getElementById('contenido-dinamico');

  // Reasignar el buscador tras renderizar cada tabla dinámica
  function rebindBuscador() {
    buscador = document.getElementById('buscadorUsuarios');
    if (buscador) {
      buscador.placeholder = placeholders[seccionActual] || placeholders.default;
      buscador.onkeyup = function () {
        const texto = buscador.value.trim().toLowerCase();
        if (seccionActual === "usuarios") {
          paginaActual = 1;
          const filtrados = usuariosData.filter(user =>
            (user.nombre && user.nombre.toLowerCase().includes(texto)) ||
            (user.correo && user.correo.toLowerCase().includes(texto)) ||
            (user.rol && user.rol.toLowerCase().includes(texto))
          );
          renderizarUsuarios(filtrados);
        } else if (seccionActual === "consultores") {
          paginaActual = 1;
          const filtrados = consultoresData.filter(c =>
            (c.nombre && c.nombre.toLowerCase().includes(texto)) ||
            (c.correo && c.correo.toLowerCase().includes(texto)) ||
            (c.rol && c.rol.toLowerCase().includes(texto))
          );
          renderizarConsultores(filtrados);
        } else if (seccionActual === "proveedores") {
          paginaActual = 1;
          const filtrados = proveedoresData.filter(p =>
            (p.nombre_empresa && p.nombre_empresa.toLowerCase().includes(texto)) ||
            (p.email && p.email.toLowerCase().includes(texto))
          );
          renderizarProveedores(filtrados);
        } else if (seccionActual === "plantillas") {
          paginaActual = 1;
          const filtrados = plantillasData.filter(pl =>
            (pl.nombre_archivo && pl.nombre_archivo.toLowerCase().includes(texto))
          );
          renderizarPlantillas(filtrados);
        } else if (seccionActual === "archivos") {
          paginaActual = 1;
          const filtrados = archivosData.filter(a =>
            (a.nombre_archivo && a.nombre_archivo.toLowerCase().includes(texto)) ||
            (a.archivo_url && a.archivo_url.toLowerCase().includes(texto))
          );
          renderizarArchivos(filtrados);
        }
      };
    }
  }

  const placeholders = {
    usuarios: "Buscar usuario...",
    archivos: "Buscar archivo...",
    consultores: "Buscar consultor...",
    proveedores: "Buscar proveedor...",
    plantillas: "Buscar plantilla...",
    default: "Buscar..."
  };

  function actualizarPlaceholder(seccion) {
    if (buscador) {
      buscador.placeholder = placeholders[seccion] || placeholders.default;
    }
  }

  buscador?.addEventListener("keyup", function () {
    const texto = buscador.value.trim().toLowerCase();
    if (seccionActual === "usuarios") {
      paginaActual = 1;
      const filtrados = usuariosData.filter(user =>
        (user.nombre && user.nombre.toLowerCase().includes(texto)) ||
        (user.correo && user.correo.toLowerCase().includes(texto)) ||
        (user.rol && user.rol.toLowerCase().includes(texto))
      );
      renderizarUsuarios(filtrados);
    } else if (seccionActual === "consultores") {
      paginaActual = 1;
      const filtrados = consultoresData.filter(c =>
        (c.nombre && c.nombre.toLowerCase().includes(texto)) ||
        (c.correo && c.correo.toLowerCase().includes(texto)) ||
        (c.rol && c.rol.toLowerCase().includes(texto))
      );
      renderizarConsultores(filtrados);
    } else if (seccionActual === "proveedores") {
      paginaActual = 1;
      const filtrados = proveedoresData.filter(p =>
        (p.nombre_empresa && p.nombre_empresa.toLowerCase().includes(texto)) ||
        (p.email && p.email.toLowerCase().includes(texto))
      );
      renderizarProveedores(filtrados);
    } else if (seccionActual === "plantillas") {
      paginaActual = 1;
      const filtrados = plantillasData.filter(pl =>
        (pl.nombre_archivo && pl.nombre_archivo.toLowerCase().includes(texto))
      );
      renderizarPlantillas(filtrados);
    } else if (seccionActual === "archivos") {
      paginaActual = 1;
      const filtrados = archivosData.filter(a =>
        (a.nombre_archivo && a.nombre_archivo.toLowerCase().includes(texto)) ||
        (a.archivo_url && a.archivo_url.toLowerCase().includes(texto))
      );
      renderizarArchivos(filtrados);
    }
  });

  botones.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const section = btn.getAttribute('data-section');
      seccionActual = section;
      actualizarPlaceholder(section);

      switch (section) {
        case 'usuarios':
          if (userRol !== 'administrador') {
            contenedor.innerHTML = `<div class="alert alert-danger">No tienes permisos para ver usuarios.</div>`;
          } else {
            cargarUsuarios();
          }
          break;
        case 'archivos':
          cargarArchivos(); break;
        case 'consultores':
          cargarConsultores(); break;
        case 'proveedores':
          cargarProveedores(); break;
        case 'plantillas':
          cargarPlantillas(); break;
        default:
          contenedor.innerHTML = `<p>Sección desconocida</p>`;
      }
    });
  });

  // Usuarios
  function cargarUsuarios() {
    contenedor.innerHTML = `<p>Cargando usuarios...</p>`;
    fetch('http://localhost/NIS2/api/models/Usuario.php')
      .then(res => res.json())
      .then(data => {
        usuariosData = data;
        paginaActual = 1;
        renderizarUsuarios(usuariosData);
      })
      .catch(err => {
        console.error(err);
        contenedor.innerHTML = `<div class="alert alert-danger">Error al cargar usuarios.</div>`;
      });
  }
  function renderizarUsuarios(data = []) {
    const inicio = (paginaActual - 1) * usuariosPorPagina;
    const fin = inicio + usuariosPorPagina;
    let tabla = `
    <table class="table table-bordered mt-3" id="tablaUsuarios">
      <thead>
        <tr><th>Correo</th><th>Nombre</th><th>Rol</th></tr>
      </thead>
      <tbody>
        ${data.slice(inicio, fin).map(u => `
          <tr>
            <td>${u.correo}</td>
            <td>${u.nombre}</td>
            <td>${u.rol}</td>
          </tr>`).join('')}
      </tbody>
    </table>
    <div id="paginacion" class="mt-3 d-flex justify-content-center gap-2"></div>
  `;
    contenedor.innerHTML = `
      <div class="input-group" style="max-width: 300px; margin-top: 10px;">
        <span class="input-group-text">
          <img src="../assets/img/search.png" alt="Buscar">
        </span>
        <input type="text" class="form-control" placeholder="Buscar..." id="buscadorUsuarios">
      </div>
      ` + tabla;
    renderizarPaginacion(data, renderizarUsuarios);
    rebindBuscador();
  }

  // Consultores
  function cargarConsultores() {
    contenedor.innerHTML = `<p>Cargando consultores...</p>`;
    fetch('http://localhost/NIS2/api/models/Consultor.php')
      .then(res => res.json())
      .then(data => {
        consultoresData = data;
        paginaActual = 1;
        renderizarConsultores(consultoresData);
      })
      .catch(err => {
        console.error(err);
        contenedor.innerHTML = `<div class="alert alert-danger">Error al cargar consultores.</div>`;
      });
  }
  function renderizarConsultores(data = []) {
    const inicio = (paginaActual - 1) * consultoresPorPagina;
    const fin = inicio + consultoresPorPagina;
    const consultoresPagina = data.slice(inicio, fin);
    let tabla = `
    <table class="table table-bordered mt-3">
      <thead>
        <tr><th>Correo</th><th>Nombre</th><th>Rol</th></tr>
      </thead>
      <tbody>
        ${consultoresPagina.map(c => `
          <tr>
            <td>${c.correo}</td>
            <td>${c.nombre}</td>
            <td>${c.rol}</td>
          </tr>`).join('')}
      </tbody>
    </table>
    <div id="paginacion" class="mt-3 d-flex justify-content-center gap-2"></div>
  `;
    contenedor.innerHTML = `
      <div class="input-group" style="max-width: 300px; margin-top: 10px;">
        <span class="input-group-text">
          <img src="../assets/img/search.png" alt="Buscar">
        </span>
        <input type="text" class="form-control" placeholder="Buscar..." id="buscadorUsuarios">
      </div>
      ` + tabla;
    renderizarPaginacion(data, renderizarConsultores);
    rebindBuscador();
  }

  // Proveedores
  function cargarProveedores() {
    contenedor.innerHTML = `<p>Cargando proveedores...</p>`;
    fetch('http://localhost/NIS2/api/models/Proveedor.php')
      .then(res => res.json())
      .then(data => {
        proveedoresData = data;
        paginaActual = 1;
        renderizarProveedores(proveedoresData);
      })
      .catch(err => {
        console.error(err);
        contenedor.innerHTML = `<div class="alert alert-danger">Error al cargar proveedores.</div>`;
      });
  }
  function renderizarProveedores(data = []) {
    const inicio = (paginaActual - 1) * proveedoresPorPagina;
    const fin = inicio + proveedoresPorPagina;
    const proveedoresPagina = data.slice(inicio, fin);
    let tabla = `
    <table class="table table-bordered mt-3">
      <thead>
        <tr><th>Email</th><th>Empresa</th></tr>
      </thead>
      <tbody>
        ${proveedoresPagina.map(p => `
          <tr>
            <td>${p.email}</td>
            <td>${p.nombre_empresa}</td>
          </tr>`).join('')}
      </tbody>
    </table>
    <div id="paginacion" class="mt-3 d-flex justify-content-center gap-2"></div>
  `;
    contenedor.innerHTML = `
      <div class="input-group" style="max-width: 300px; margin-top: 10px;">
        <span class="input-group-text">
          <img src="../assets/img/search.png" alt="Buscar">
        </span>
        <input type="text" class="form-control" placeholder="Buscar..." id="buscadorUsuarios">
      </div>
      ` + tabla;
    renderizarPaginacion(data, renderizarProveedores);
    rebindBuscador();
  }

  // Plantillas
  function cargarPlantillas() {
    contenedor.innerHTML = `<p>Cargando plantillas...</p>`;
    fetch('http://localhost/NIS2/api/models/get_archivos.php?tipo=plantillas')
      .then(res => res.json())
      .then(data => {
        plantillasData = data;
        paginaActual = 1;
        renderizarPlantillas(plantillasData);
      })
      .catch(err => {
        console.error(err);
        contenedor.innerHTML = `<div class="alert alert-danger">Error al cargar plantillas.</div>`;
      });
  }
  function renderizarPlantillas(data = []) {
    const inicio = (paginaActual - 1) * plantillasPorPagina;
    const fin = inicio + plantillasPorPagina;
    const plantillasPagina = data.slice(inicio, fin);
    let tabla = `
    <table class="table table-bordered mt-3">
      <thead>
        <tr><th>Nombre archivo</th></tr>
      </thead>
      <tbody>
        ${plantillasPagina.map(pl => `
          <tr>
            <td>${pl.nombre_archivo}</td>
          </tr>`).join('')}
      </tbody>
    </table>
    <div id="paginacion" class="mt-3 d-flex justify-content-center gap-2"></div>
  `;
    contenedor.innerHTML = `
      <div class="input-group" style="max-width: 300px; margin-top: 10px;">
        <span class="input-group-text">
          <img src="../assets/img/search.png" alt="Buscar">
        </span>
        <input type="text" class="form-control" placeholder="Buscar..." id="buscadorUsuarios">
      </div>
      ` + tabla;
    renderizarPaginacion(data, renderizarPlantillas);
    rebindBuscador();
  }

  // Archivos
  function cargarArchivos() {
    contenedor.innerHTML = `<p>Cargando archivos...</p>`;
    fetch('http://localhost/NIS2/api/models/get_archivos.php?tipo=archivos')
      .then(res => res.json())
      .then(data => {
        archivosData = data;
        paginaActual = 1;
        renderizarArchivos(archivosData);
      })
      .catch(err => {
        console.error(err);
        contenedor.innerHTML = `<div class="alert alert-danger">Error al cargar archivos.</div>`;
      });
  }
  function renderizarArchivos(data = []) {
    const inicio = (paginaActual - 1) * archivosPorPagina;
    const fin = inicio + archivosPorPagina;
    const archivosPagina = data.slice(inicio, fin);
    let tabla = `
    <table class="table table-bordered mt-3">
      <thead>
        <tr><th>Nombre archivo</th><th>URL</th></tr>
      </thead>
      <tbody>
        ${archivosPagina.map(a => `
          <tr>
            <td>${a.nombre_archivo}</td>
            <td>${a.archivo_url}</td>
          </tr>`).join('')}
      </tbody>
    </table>
    <div id="paginacion" class="mt-3 d-flex justify-content-center gap-2"></div>
  `;
    contenedor.innerHTML = `
      <div class="input-group" style="max-width: 300px; margin-top: 10px;">
        <span class="input-group-text">
          <img src="../assets/img/search.png" alt="Buscar">
        </span>
        <input type="text" class="form-control" placeholder="Buscar..." id="buscadorUsuarios">
      </div>
      ` + tabla;
    renderizarPaginacion(data, renderizarArchivos);
    rebindBuscador();
  }

  function renderizarPaginacion(data, renderFunction) {
    let porPagina = usuariosPorPagina;
    if (renderFunction === renderizarUsuarios) porPagina = usuariosPorPagina;
    else if (renderFunction === renderizarConsultores) porPagina = consultoresPorPagina;
    else if (renderFunction === renderizarProveedores) porPagina = proveedoresPorPagina;
    else if (renderFunction === renderizarPlantillas) porPagina = plantillasPorPagina;
    else if (renderFunction === renderizarArchivos) porPagina = archivosPorPagina;
    let totalPaginas = Math.ceil(data.length / porPagina);
    if (totalPaginas < 1) totalPaginas = 1; // Siempre al menos una página
    const pagDiv = document.getElementById("paginacion");
    pagDiv.innerHTML = '';
    for (let i = 1; i <= totalPaginas; i++) {
      const btn = document.createElement('button');
      btn.textContent = i;
      btn.classList.add('btn', i === paginaActual ? 'btn-primary' : 'btn-outline-primary');
      btn.addEventListener('click', () => {
        paginaActual = i;
        renderFunction(data);
      });
      pagDiv.appendChild(btn);
    }
  }

  window.addEventListener('load', () => {
    const vista = new URLSearchParams(window.location.search).get('vista') || 'archivos';
    seccionActual = vista;
    actualizarPlaceholder(vista);
    switch (vista) {
      case 'usuarios': cargarUsuarios(); break;
      case 'archivos': cargarArchivos(); break;
      case 'consultores': cargarConsultores(); break;
      case 'proveedores': cargarProveedores(); break;
      case 'plantillas': cargarPlantillas(); break;
      default: contenedor.innerHTML = `<p>Vista desconocida</p>`;
    }
  });

  document.getElementById('formCrearProveedor').addEventListener('submit', function (e) {
    e.preventDefault();
    if (!validarContrasenas('proveedor')) return;
    const formData = new FormData(this);
    fetch('../pages/crear_proveedor.php', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const modal = bootstrap.Modal.getInstance(document.getElementById('crearProveedorModal'));
          modal.hide();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
      });
  });

<script src="../assets/js/popup.js"></script>
<script src="../assets/js/script.js"></script>
<script src="../assets/js/sortable-tables.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($mostrarModal): ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var modal = new bootstrap.Modal(document.getElementById('crearUsuarioModal'));
    modal.show();
    // Mostrar el alert correspondiente
    <?php if ($alertaPassword): ?>
      document.getElementById('alerta-password').style.display = 'block';
    <?php endif; ?>
    <?php if ($alertaCorreo): ?>
      document.getElementById('alerta-correo').style.display = 'block';
    <?php endif; ?>
    <?php if ($alertaExito): ?>
      document.getElementById('alerta-exito').style.display = 'block';
    <?php endif; ?>
  });
</script>
<?php 
  unset($_SESSION['error']);
  unset($_SESSION['success']);
?>
<?php endif; ?>