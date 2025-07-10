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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
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
        $stmt->bind_param("iss", $proveedor_id, $ruta_para_bd, $nombre_original);
        $stmt->execute();
        $stmt->close();
        header("Location: plantillaUsers.php?vista=archivos");
        exit;
    } else {
        echo "<script>alert('❌ Error al subir el archivo');</script>";
    }
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

    <!-- ✅ ESTILOS EXTRA PARA RESPONSIVIDAD -->
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
                <button type="button" class="btn bg-mi-color btn-lg">
                    <a href="./index.php"><img src="../assets/img/Arrow 1.png" alt="Volver"></a>
                </button>
                <div class="col-sm">
                    <button type="button" class="btn bg-mi-color btn-lg">Normativas</button>
                    <form method="POST" enctype="multipart/form-data" class="d-inline">
                        <label for="archivo" class="btn bg-mi-color btn-lg">Subir archivo</label>
                        <input type="file" name="archivo" id="archivo" class="d-none" onchange="this.form.submit()" required>
                    </form>
                </div>
            </div>

            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text"><img src="../assets/img/search.png" alt="Buscar"></span>
                <input type="text" class="form-control" placeholder="Buscar usuario..." id="buscadorUsuarios">
                <div id="contenido-dinamico"></div>
            </div>
        </div>

        <div class="headertable">
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
            ?>
        </div>
    </div>
</main>

<!-- JS -->
<script src="../assets/js/script.js"></script>

<!-- ✅ ESTILOS RESPONSIVE -->
<style>
  html, body {
    overflow-x: hidden;
    margin: 0;
    padding: 0;
    max-width: 100%;
  }

  .sin-scroll {
    overflow-y: auto;
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

  .contenedorTablaStencil {
    overflow-x: auto;
  }

  @media (max-width: 768px) {
    .menuNav .cajaArchivos {
      flex: 1 1 100%;
    }
  }
</style>
</body>
</html>
