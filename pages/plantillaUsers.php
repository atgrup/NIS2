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
</head>


    <style>
        .sin-scroll {
            overflow-y: auto;
            overflow-x: hidden;
            height: 100vh;
        }

        .menuNav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: start;
        }

        .menuNav .cajaArchivos {
            flex: 1 1 45%;
            min-width: 140px;
        }

        @media (max-width: 768px) {
            .menuNav .cajaArchivos {
                flex: 1 1 100%;
            }

            .input-group {
                width: 100% !important;
                position: static !important;
                margin-top: 1rem;
            }
        }

        .contenedorTablaStencil {
            overflow-x: auto;
        }
    </style>

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
                    <a class="btn btn-outline-light w-100" href="#">PROVEEDORES</a>
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
        <div class="d-flex align-items-center flex-wrap gap-2 mt-3 px-3">
            <div class="btns me-auto d-flex flex-wrap gap-2">
                <?php if ($vista === 'archivos'): ?>
                    <form method="POST" enctype="multipart/form-data" class="d-inline">
                        <label for="archivo" class="btn bg-mi-color w-100">Subir archivo</label>
                        <input type="file" name="archivo" id="archivo" class="d-none" onchange="this.form.submit()" required>
                    </form>


                    <!-- Botón para abrir modal -->
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario">
  Crear Consultor
</button>


                    <!-- Modal de creación de consultor -->
                   <div class="modal fade" id="modalCrearUsuario" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h5 class="modal-title" id="modalLabel">Crear Consultor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        <form id="formCrearUsuario">
          <div class="mb-3">
            <label for="correo" class="form-label-modal">Correo</label>
            <input type="email" name="correo" class="form-control" required />
          </div>
          <div class="mb-3">
            <label for="password" class="form-label-modal">Contraseña</label>
            <input type="password" name="password" class="form-control" required minlength="6" />
          </div>
          <button type="submit" class="btn btn-primary">Crear Consultor</button>
        </form>
      </div>

    </div>
  </div>
</div>




                <?php elseif ($vista === 'plantillas' && ($rol === 'administrador' || $rol === 'consultor')): ?>
                    <form method="POST" enctype="multipart/form-data" class="d-inline">
                        <label for="plantilla" class="btn bg-mi-color w-100">Subir plantilla</label>
                        <input type="file" name="plantilla" id="plantilla" class="d-none" onchange="this.form.submit()" required>
                    </form>
        <?php endif; ?>
                <?php if ($rol === 'administrador'): ?>
                <div class="d-flex flex-wrap gap-2 px-3 mt-2">
                    <?php if ($vista === 'consultores'): ?>
                    <button class="btn bg-mi-color w-100" data-bs-toggle="modal" data-bs-target="#crearConsultorModal">Crear Consultor</button>
                    <?php elseif ($vista === 'proveedores'): ?>
                    <button class="btn bg-mi-color w-100" data-bs-toggle="modal" data-bs-target="#crearProveedorModal">Crear Proveedor</button>
                    <?php endif; ?>

                </div>
                <?php endif; ?>
            </div>

            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text"><img src="../assets/img/search.png" alt="Buscar"></span>
                <input type="text" class="form-control" placeholder="Buscar usuario..." id="buscadorUsuarios">
                <div id="contenido-dinamico"></div>
            </div>
        </div>

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
    </div>
</main>

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

            <!-- <div class="headertable">
                <?php
                $vista = $_GET['vista'] ?? 'archivos';
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

                ?>-->
                <img src="../assets/img/banderita.png" class="imgEmpresa" alt="bandera">
            </div> 
        </div>
 </main>
<!-- JS para validar contraseñas -->
 
<script src="../assets/js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>


<script>
document.getElementById('formCrearProveedor').addEventListener('submit', function(e) {
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
 // Aquí actualizar la tabla
      // Opción 1: recargar toda la página para que la tabla se actualice
      // location.reload();

      // Opción 2: hacer una llamada fetch para actualizar sólo la tabla (más avanzado)
      // actualizarTablaProveedores();

</script>
    
</body>


</html>
