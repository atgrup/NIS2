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
require_once dirname(__DIR__) . '/api/includes/conexion.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Manejar subida de plantillas
    if (isset($_FILES['plantilla'])) {
        $archivo = $_FILES['plantilla'];
        $nombre_original = basename($archivo['name']);
        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

        if ($extension !== 'pdf') {
            echo "<script>alert('❌ Error: Solo se permiten archivos PDF.'); window.location.href='plantillaUsers.php?vista=plantillas';</script>";
            exit;
        }

        // Obtener consultor_id desde la sesión
        $stmt = $conexion->prepare("SELECT id FROM consultores WHERE usuario_id = (SELECT id_usuarios FROM usuarios WHERE correo = ?)");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $stmt->bind_result($consultor_id);
        $stmt->fetch();
        $stmt->close();

        $carpeta_plantillas = __DIR__ . '/../plantillas_disponibles/';
        if (!is_dir($carpeta_plantillas)) {
            mkdir($carpeta_plantillas, 0775, true);
        }

        $ruta_fisica = $carpeta_plantillas . $nombre_original;

        if (move_uploaded_file($archivo['tmp_name'], $ruta_fisica)) {  
          // Preparar consulta con columnas: nombre, descripcion, consultor_id, archivo_url, fecha_subida (fecha con NOW())
          $stmt = $conexion->prepare("INSERT INTO plantillas (nombre, descripcion, consultor_id, archivo_url, fecha_subida) VALUES (?, ?, ?, ?, NOW())");

          if ($stmt === false) {
              die("Error en la preparación: " . $conexion->error);
          }

          $stmt->bind_param("ssis", $nombre_original, $descripcion, $consultor_id, $ruta_fisica);
          $stmt->execute();
          $stmt->close();

          echo "<script>window.location.href='plantillaUsers.php?vista=plantillas';</script>";
          exit;
        }
    }
// Manejar subida de archivos
elseif (isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo'];
    $nombre_original = basename($archivo['name']);
    $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
    
    // Validar solo PDF
    if ($extension !== 'pdf') {
        echo "<script>alert('❌ Error: Solo se permiten archivos PDF.'); window.location.href='plantillaUsers.php?vista=archivos';</script>";
        exit;
    }

    $nombre_archivo = time() . "_" . $nombre_original;

    // Obtener usuario_id desde la tabla usuarios
    $stmt = $conexion->prepare("SELECT id_usuarios, tipo_usuario_id FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->bind_result($usuario_id, $rol_db);
    $stmt->fetch();
    $stmt->close();

    // Inicializar proveedor_id como null
    $proveedor_id = null;

    // Si el rol es proveedor, obtener proveedor_id
    if (strtolower($rol_db) === 'proveedor') {
        $stmt = $conexion->prepare("SELECT id FROM proveedores WHERE usuario_id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $stmt->bind_result($proveedor_id);
        $stmt->fetch();
        $stmt->close();
    }

    $carpeta_usuario = __DIR__ . '/../documentos_subidos/' . $correo;
    $carpeta_url = 'documentos_subidos/' . $correo;

    if (!is_dir($carpeta_usuario)) {
        mkdir($carpeta_usuario, 0775, true);
    }

    $ruta_fisica = $carpeta_usuario . '/' . $nombre_archivo;
    $ruta_para_bd = $carpeta_url . '/' . $nombre_archivo;

    if (move_uploaded_file($archivo['tmp_name'], $ruta_fisica)) {
        $stmt = $conexion->prepare("INSERT INTO archivos_subidos (proveedor_id, archivo_url, nombre_archivo, revision_estado) VALUES (?, ?, ?, 'pendiente')");
        // proveedor_id puede ser null, usar bind_param según corresponda
        if ($proveedor_id !== null) {
            $stmt->bind_param("iss", $proveedor_id, $ruta_para_bd, $nombre_original);
        } else {
            // Para null, hay que pasar un valor NULL y usar "iss" sigue correcto porque proveedor_id es int nullable
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
  }
}

$vista = $_GET['vista'] ?? 'archivos';

// Paginación
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$filas_por_pagina = 10;
$inicio = ($pagina_actual - 1) * $filas_por_pagina;

function generar_paginacion($url_base, $pagina_actual, $total_paginas) {
    if ($total_paginas <= 1) {
        return '';
    }

    $html = '<div class="d-flex justify-content-center gap-2">';

    // Botón "Primera"
    $disabled_primera = $pagina_actual <= 1 ? 'disabled' : '';
    $html .= '<a href="' . $url_base . '&pagina=1" class="btn btn-outline-primary btn-paginacion ' . $disabled_primera . '">⏮️</a>';

    // Botón "Anterior"
    $disabled_anterior = $pagina_actual <= 1 ? 'disabled' : '';
    $html .= '<a href="' . $url_base . '&pagina=' . ($pagina_actual - 1) . '" class="btn btn-outline-primary btn-paginacion ' . $disabled_anterior . '">‹</a>';


    // Números de página
    for ($i = 1; $i <= $total_paginas; $i++) {
        $active_class = $i == $pagina_actual ? 'btn-primary active-paginacion' : 'btn-outline-primary';
        $html .= '<a href="' . $url_base . '&pagina=' . $i . '" class="btn btn-paginacion ' . $active_class . '">' . $i . '</a>';
    }

    // Botón "Siguiente"
    $disabled_siguiente = $pagina_actual >= $total_paginas ? 'disabled' : '';
    $html .= '<a href="' . $url_base . '&pagina=' . ($pagina_actual + 1) . '" class="btn btn-outline-primary btn-paginacion ' . $disabled_siguiente . '">›</a>';

    // Botón "Última"
    $disabled_ultima = $pagina_actual >= $total_paginas ? 'disabled' : '';
    $html .= '<a href="' . $url_base . '&pagina=' . $total_paginas . '" class="btn btn-outline-primary btn-paginacion ' . $disabled_ultima . '">⏭️</a>';


    $html .= '</div>';
    return $html;
}
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
             <div class="mb-3">
              <button type="button" class="btn bg-mi-color w-100" data-bs-toggle="modal" data-bs-target="#modalSubirArchivo">
                Subir Archivo
              </button>
            </div>
          <?php endif; ?>
          <?php if ($vista === 'plantillas' && ($rol === 'administrador' || $rol === 'consultor')): ?>
            <form method="POST" enctype="multipart/form-data" class="d-inline mb-3">
              <label for="plantilla" class="btn bg-mi-color w-100">Subir plantilla</label>
              <input type="file" name="plantilla" id="plantilla" class="d-none" onchange="this.form.submit()" required accept=".pdf">
            </form>
          <?php endif; ?>
          <?php if ($rol === 'administrador'): ?>
            <div class="d-flex flex-wrap gap-2 px-3 mt-2">
              <?php if ($vista === 'usuarios'): ?>
                <button class="btn bg-mi-color w-100" data-bs-toggle="modal" data-bs-target="#crearUsuarioModal">Crear Administrador</button>
              <?php elseif ($vista === 'consultores'): ?>
                <button class="btn bg-mi-color w-100" data-bs-toggle="modal" data-bs-target="#crearConsultorModal">Crear Consultor</button>
              <?php elseif ($vista === 'proveedores'): ?>
                <button class="btn bg-mi-color w-100" data-bs-toggle="modal" data-bs-target="#crearProveedorModal">Crear Proveedor</button>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Buscador -->
        <?php if ($vista === 'archivos'): ?>
          <!-- Buscador para proveedores -->
          <div class="input-group" style="max-width: 300px;">
            <span class="input-group-text">
              <img src="../assets/img/search.png" alt="Buscar">
            </span>
            <input type="text" class="form-control" placeholder="Buscar archivos..." id="buscadorArchivos"> 
          </div>
        <?php else: ?>
          <!-- Buscador para otros roles -->
          <div class="input-group" style="max-width: 300px;">
            <span class="input-group-text">
              <img src="../assets/img/search.png" alt="Buscar">
            </span>
            <input type="text" class="form-control" placeholder="Buscar..." id="buscadorUsuarios"> 
          </div>
        <?php endif; ?>
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
          <button type="submit" class="btn btn-primary">Crear Administrador</button>
        </div>
      </div>
    </form>
  </div>
</div>

  <!-- Modal Crear Consultor -->
  <div class="modal fade" id="crearConsultorModal" tabindex="-1" aria-labelledby="crearConsultorLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form method="POST" action="crear_consultor.php" id="formCrearConsultor" onsubmit="return validarContrasenas('consultor')">
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


  <!-- Modal Subir Archivo -->
<!-- Botón para abrir el modal (por si lo necesitas) -->

<!-- Modal -->
<div class="modal fade" id="modalSubirArchivo" tabindex="-1" aria-labelledby="modalSubirArchivoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content"> <!-- FONDO DEL MODAL -->
      <div class="modal-header bg-mi-color text-white">
        <h5 class="modal-title" id="modalSubirArchivoLabel">Subir archivo</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="formSubirArchivoModal" method="POST" enctype="multipart/form-data" action="subir_archivo_rellenado.php">
          <div class="mb-3">
            <label for="archivo" class="form-label">Selecciona un archivo</label>
            <input type="file" class="form-control" id="archivo-modal" name="archivo" required accept=".pdf">
          </div>

          <div class="mb-3">
            <label for="plantilla" class="form-label success">Selecciona una plantilla</label>
            <select class="form-select " id="plantilla" name="plantilla_id">
              <?php
                // Mostrar todas las plantillas disponibles
                require_once dirname(__DIR__) . '/api/includes/conexion.php';
                $queryPlantillas = "SELECT id, nombre FROM plantillas ORDER BY fecha_subida DESC";
                $resultPlantillas = $conexion->query($queryPlantillas);
                if ($resultPlantillas && $resultPlantillas->num_rows > 0) {
                  while ($row = $resultPlantillas->fetch_assoc()) {
                    echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nombre']) . '</option>';
                  }
                } else {
                  echo '<option value="">No hay plantillas disponibles</option>';
                }
              ?>
            </select>
          </div>

          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Subir</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
<script>
  document.getElementById('formCrearConsultor').addEventListener('submit', function(e) {
    // Validación básica de contraseñas
    const password = document.getElementById('contrasenaConsultor').value;
    const repeat_password = document.getElementById('contrasenaConsultor2').value;
    
    if (password !== repeat_password) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
        return false;
    }
    
    // Validación de campos obligatorios
    const correo = document.getElementById('correoConsultor').value;
    
    if (!correo || !password) {
        e.preventDefault();
        alert('Todos los campos son obligatorios');
        return false;
    }
    console.log('Formulario validado correctamente. Enviando...');
    return true;
});
document.getElementById('formCrearProveedor').addEventListener('submit', function(e) {
    // Validación básica de contraseñas
    const contrasena = document.getElementById('contrasenaProveedor').value;
    const contrasena2 = document.getElementById('contrasenaProveedor2').value;
    
    if (contrasena !== contrasena2) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
        return false;
    }
    
    // Validación de campos obligatorios
    const correo = document.getElementById('correoProveedor').value;
    const nombreEmpresa = document.getElementById('nombreEmpresa').value;
    
    if (!correo || !nombreEmpresa || !contrasena) {
        e.preventDefault();
        alert('Todos los campos son obligatorios');
        return false;
    }
    
    // El formulario se enviará normalmente si pasa las validaciones
    return true;
});
</script> <!-- Cierre correcto del script -->

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