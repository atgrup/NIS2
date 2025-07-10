<?php
session_start();
if (!isset($_SESSION['rol'])) {
    header("Location: ../api/auth/login.php");
    exit;
}
if (!isset($tipo_usuario_id)) {
    $tipo_usuario_id = null;
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


    // Paso 1: Obtener ID y tipo de usuario con validación
    $tipo_usuario_id = null;
    $usuario_id = null;
    $stmt = $conexion->prepare("SELECT id_usuarios, tipo_usuario_id FROM usuarios WHERE correo = ?");

    $stmt = $conexion->prepare("SELECT u.id_usuarios, p.id FROM usuarios u JOIN proveedores p ON u.id_usuarios = p.usuario_id WHERE u.correo = ?");

    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->bind_result($usuario_id_result, $tipo_usuario_id_result);
    if ($stmt->fetch()) {
        $usuario_id = $usuario_id_result;
        $tipo_usuario_id = $tipo_usuario_id_result;
    }
    $stmt->close();


    // Paso 2: Si es proveedor, obtener ID del proveedor
    $proveedor_id = null;
    if ($tipo_usuario_id == 2 && $usuario_id !== null) {
        $stmt = $conexion->prepare("SELECT id FROM proveedores WHERE usuario_id = ?");
        if (!$stmt) {
            die("Error en la consulta: " . $conexion->error);
        }
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $stmt->bind_result($proveedor_id_result);
        if ($stmt->fetch()) {
            $proveedor_id = $proveedor_id_result;
        }
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

        // ✅ Si es proveedor, guardar proveedor_id
        if ($tipo_usuario_id == 2 && $proveedor_id !== null) {
            $stmt = $conexion->prepare("
                INSERT INTO archivos_subidos (proveedor_id, archivo_url, nombre_archivo, revision_estado) 
                VALUES (?, ?, ?, 'pendiente')
            ");
            if (!$stmt) {
                die("Error en la consulta: " . $conexion->error);
            }
            $stmt->bind_param("iss", $proveedor_id, $ruta_para_bd, $nombre_original);
        } else {
            // 🔁 Consultor o admin — sin proveedor
            $stmt = $conexion->prepare("
                INSERT INTO archivos_subidos (proveedor_id, archivo_url, nombre_archivo, revision_estado) 
                VALUES (NULL, ?, ?, 'pendiente')
            ");
            if (!$stmt) {
                die("Error en la consulta: " . $conexion->error);
            }
            $stmt->bind_param("ss", $ruta_para_bd, $nombre_original);
        }
$stmt->execute();
$stmt->close();
echo "<script>alert('✅ Archivo subido correctamesnte');</script>";

$stmt = $conexion->prepare("INSERT INTO archivos_subidos (proveedor_id, archivo_url, nombre_archivo, revision_estado) VALUES (?, ?, ?, 'pendiente')");
$stmt->bind_param("iss", $proveedor_id, $ruta_para_bd, $nombre_original);
$stmt->execute();
$stmt->close();


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
    <link rel="stylesheet" href="../assets/styles/style.css?v=<?php echo time(); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;700&display=swap" rel="stylesheet">
</head>

<script>
    const userRol = "<?php echo strtolower($_SESSION['rol']); ?>";
</script>

<body class="stencilBody">
    <main class="stencil">
        <nav class="indexStencil">
            <h1 class="tituloNIS">NIS2</h1>
            <h4>Hola, <?php echo htmlspecialchars($nombre); ?></h4>

            <div class="menuNav">
                <?php if ($rol === 'administrador'): ?>
                    <div class="cajaArchivos mb-2">
                        <a class="btn btn-outline-light w-100" href="?vista=usuarios" data-section="usuarios">USUARIOS</a>
                    </div>
                    <div class="cajaArchivos mb-2">
                        <a class="btn btn-outline-light w-100" href="?vista=consultores"
                            data-section="consultores">CONSULTORES</a>

                    </div>
                    <div class="cajaArchivos mb-2">
                        <a class="btn btn-outline-light w-100" href="?vista=proveedores"
                            data-section="proveedores">PROVEEDORES</a>
                    </div>
                    <div class="cajaArchivos mb-2">
                        <a href="?vista=archivos" class="btn btn-outline-light w-100">ARCHIVOS</a>
                    </div>
                <?php elseif ($rol === 'consultor'): ?>
                    <div class="cajaArchivos">
                        <button class="textoStencil btnFiltro" data-section="plantillas">PLANTILLAS</button>
                    </div>
                    <div class="cajaArchivos">
                        <button class="textoStencil btnFiltro" data-section="archivos">ARCHIVOS</button>
                    </div>
                    <div class="cajaArchivos">
                        <button class="textoStencil btnFiltro" data-section="proveedores">PROVEEDORES</button>
                    </div>
                <?php else: ?>
                    <div class="cajaArchivos">
                        <button class="textoStencil btnFiltro" data-section="plantillas">PLANTILLAS</button>
                    </div>
                    <div class="cajaArchivos">
                        <button class="textoStencil btnFiltro" data-section="archivos">ARCHIVOS</button>
                    </div>

                <?php endif; ?>

                <!-- Botones comunes -->
                <div class="cajaArchivos mb-2">
                    <a href="?vista=plantillas" class="btn btn-outline-light w-100">PLANTILLAS</a>
                </div>
                <div class="cajaArchivos">
                    <form action="../api/auth/logout.php" method="post" class="mb-2">
                        <button type="submit" class="btn btn-outline-light w-100">Cerrar sesión</button>
                    </form>

                </div>

        </nav>

        <div class="contenedorTablaStencil">
            <div class="btns">
                <button type="button" class="btn bg-mi-color btn-lg">
                    <a href="./index.php"><img src="../assets/img/Arrow 1.png" alt="Volver"></a>
                </button>
                <div class="col-sm">
                    <button type="button" class="btn bg-mi-color btn-lg">Normativas</button>
                    <form method="POST" enctype="multipart/form-data" class="d-inline">
                        <label for="archivo" class="btn bg-mi-color btn-lg">Subir archivo</label>
                        <input type="file" name="archivo" id="archivo" class="d-none" onchange="this.form.submit()"
                            required>
                    </form>
                </div>

            </div>

            <div class="table-responsive" style="max-height: 80%; overflow-y: auto; margin-top: 15px;">
                <img src="../assets/img/banderita.png" class="imgEmpresa">



                <?php if ($rol === 'consultor'): ?>
                    <div class="cajaArchivos mb-2">
                        <a class="btn btn-outline-light w-100" href="#">PROVEEDORES</a>
                    </div>
                <?php endif; ?>



                <div class="d-flex justify-content-end mb-3">
                    <div class="input-group" style="width: 300px; position:absolute; top:80px; right:100px;">
                        <span class="input-group-text"><img src="../assets/img/search.png"></img></span>
                        <input type="text" class="form-control" placeholder="Buscar usuario..." id="buscadorUsuarios">
                    </div>
                </div>
                <div id="contenido-dinamico" style="margin-top: 100px;"></div>
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

    <script src="../assets/js/script.js"></script>
</body>

</html>