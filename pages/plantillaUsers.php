<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['rol'])) {
    header("Location: ../api/auth/login.php");
    exit;
}

$rol = strtolower($_SESSION['rol']);

// Obtener el correo para saludo o nombre de carpeta
$correo = isset($_SESSION['correo']) ? $_SESSION['correo'] : null;
$nombre = $correo ? explode('@', $correo)[0] : 'Invitado';

// Conexión a la base de datos
$conexion = new mysqli('jordio35.sg-host.com', 'u74bscuknwn9n', 'ad123456-', 'dbs1il8vaitgwc');
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// SUBIDA DE ARCHIVO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo'];
    $nombre_original = basename($archivo['name']);
    $nombre_archivo = time() . "_" . $nombre_original;

    // Obtener ID del proveedor a partir del correo
    $stmt = $conexion->prepare("
        SELECT u.id_usuarios, p.id 
        FROM usuarios u 
        JOIN proveedores p ON u.id_usuarios = p.usuario_id 
        WHERE u.correo = ?
    ");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->bind_result($usuario_id, $proveedor_id);
    $stmt->fetch();
    $stmt->close();

    // Crear carpeta personalizada por correo si no existe
    $carpeta_usuario = __DIR__ . '/../documentos_subidos/' . $correo;
    $carpeta_url = '../documentos_subidos/' . $correo;

    if (!is_dir($carpeta_usuario)) {
        mkdir($carpeta_usuario, 0775, true);
    }

    $ruta_fisica = $carpeta_usuario . '/' . $nombre_archivo;
    $ruta_para_bd = $carpeta_url . '/' . $nombre_archivo;

    if (move_uploaded_file($archivo['tmp_name'], $ruta_fisica)) {
        // Insertar registro del archivo
        $stmt = $conexion->prepare("
            INSERT INTO archivos_subidos (proveedor_id, archivo_url, nombre_archivo, revision_estado) 
            VALUES (?, ?, ?, 'pendiente')
        ");
        $stmt->bind_param("iss", $proveedor_id, $ruta_para_bd, $nombre_original);
        $stmt->execute();
        $stmt->close();

        echo "<script>alert('✅ Archivo subido correctamente');</script>";
    } else {
        echo "<script>alert('❌ Error al subir el archivo');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../assets/styles/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&display=swap"
        rel="stylesheet">

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
                    <div class="cajaArchivos">
                        <a class="textoStencil" data-section="usuarios">USUARIOS</a>
                    </div>
                    <div class="cajaArchivos">
                        <a class="textoStencil" data-section="consultores">CONSULTORES</a>
                    </div>
                    <div class="cajaArchivos">
                        <a class="textoStencil" data-section="proveedores">PROVEEDORES</a>
                    </div>
                    <div class="cajaArchivos">
                        <a class="textoStencil">PLANTILLAS</a></div>
                    <div class="cajaArchivos">
                        <a class="textoStencil">ARCHIVOS</a></div>
                <?php elseif ($rol === 'consultor'): ?>
                    <div class="cajaArchivos">
                        <a class="textoStencil">PLANTILLAS</a></div>
                    <div class="cajaArchivos">
                        <a class="textoStencil">ARCHIVOS</a></div>
                    <div class="cajaArchivos">
                        <a class="textoStencil">PROVEEDORES</a></div>
                <?php else: ?>
                    <div class="cajaArchivos">
                        <a class="textoStencil">PLANTILLAS</a></div>
                    <div class="cajaArchivos">
                        <a class="textoStencil">ARCHIVOS</a></div>
                <?php endif; ?>
                <div class="footerNaV">
                    <form action="../api/auth/logout.php" method="post">
                        <button class="logout" type="submit">Cerrar sesión</button>
                    </form>

                    <p>Política de cookies</p><br>
                    <p>Terminos y condiciones</p>
                </div>
        </nav>
        <div class="contenedorTablaStencil">
            <div class="btnsnav justify-content-end">
                <button type="button" class="btn bg-mi-color  btn-md align-items-center btnUser">
                    <a href="./index.php"> <img src="../assets/img/Arrow 1.png"></a>
                </button>
                <div class="col-sm">
                    <button type="button" class="btn bg-mi-color  btn-lg text-center btnUser " >
                        Normativas
                    </button>
                    <form method="POST" enctype="multipart/form-data" class="d-inline">
                        <label for="archivo" class="btn bg-mi-color btn-lg text-center btnUser">
                            Subir archivo <img src="../assets/img/descarga.png">
                        </label>
                        <input type="file" name="archivo" id="archivo" class="d-none" onchange="this.form.submit()"
                            required>
                    </form>
                </div>
            </div>
            <div class="table-responsive" style="max-height: 80%; overflow-y: auto; margin-top: 15px;">
                <table class="table table-bordered border-secondary">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre del archivo</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-body">
                        <?php
                        error_reporting(E_ALL);
                        ini_set('display_errors', 1);

                        if (session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }

                        $correo = $_SESSION['correo'] ?? null;

                        if ($correo) {
                            $stmt = $conexion->prepare("SELECT u.id_usuarios, p.id FROM usuarios u JOIN proveedores p ON u.id_usuarios = p.usuario_id WHERE u.correo = ?");
                            $stmt->bind_param("s", $correo);
                            $stmt->execute();
                            $stmt->bind_result($usuario_id, $proveedor_id);
                            $stmt->fetch();
                            $stmt->close();

                            $stmt = $conexion->prepare("SELECT id, nombre_archivo, fecha_subida, revision_estado FROM archivos_subidos WHERE proveedor_id = ?");
                            $stmt->bind_param("i", $proveedor_id);
                            $stmt->execute();
                            $stmt->bind_result($id, $nombre, $fecha, $estado);

                            $i = 1;
                            while ($stmt->fetch()) {
                                $estado_icono = match ($estado) {
                                    'aprobado' => "<span class='text-success'>&#10004;</span>",
                                    'rechazado' => "<span class='text-danger'>&#10006;</span>",
                                    default => "<span class='text-muted'>Pendiente</span>",
                                };

                                echo "<tr>
                                        <th scope='row'>{$i}</th>
                                        <td>" . htmlspecialchars($nombre) . "</td>
                                        <td>{$fecha}</td>
                                        <td class='text-center'>{$estado_icono}</td>
                                      </tr>";
                                $i++;
                            }

                            $stmt->close();
                        }

                        $conexion->close();
                        ?>
                    </tbody>
                </table>
                <img src="../assets/img/banderita.png" class="imgEmpresa">
            </div>
            
            <div class="d-flex justify-content-end mb-3">
                <div class="input-group" style="width: 300px; position:absolute; bottom:60px; right:100px;">
                    <span class="input-group-text"><img src="../assets/img/search.png"></img></span>
                    <input type="text" class="form-control" placeholder="Buscar usuario..." id="buscadorUsuarios">
                <div id="contenido-dinamico"></div>
                </div>
            </div>
        </div>
    </main>
        <script src="../assets/js/script.js"></script>
</body>
</html>