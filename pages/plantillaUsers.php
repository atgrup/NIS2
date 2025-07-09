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
        echo "<script>alert('✅ Archivo subido correctamente');</script>";

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
                    <div class="cajaArchivos">
                        <button class="textoStencil btnFiltro" data-section="usuarios">USUARIOS</button>
                    </div>
                    <div class="cajaArchivos">
                        <button class="textoStencil btnFiltro" data-section="consultores">CONSULTORES</button>
                    </div>
                    <div class="cajaArchivos">
                        <button class="textoStencil btnFiltro" data-section="proveedores">PROVEEDORES</button>
                    </div>
                    <div class="cajaArchivos">
                        <button class="textoStencil btnFiltro">PLANTILLAS</button>
                    </div>
                    <div class="cajaArchivos">
                        <button class="textoStencil btnFiltro">ARCHIVOS</button>
                    </div>
                <?php elseif ($rol === 'consultor'): ?>
                    <div class="cajaArchivos">
                        <button class="textoStencil btnFiltro">PLANTILLAS</button>
                    </div>
                    <div class="cajaArchivos">
                        <button class="textoStencil btnFiltro">ARCHIVOS</button>
                    </div>
                    <div class="cajaArchivos">
                        <button class="textoStencil btnFiltro">PROVEEDORES</button>
                    </div>
                <?php else: ?>
                    <div class="cajaArchivos">
                        <button class="textoStencil btnFiltro">PLANTILLAS</button>
                    </div>
                    <div class="cajaArchivos">
                        <button class="textoStencil btnFiltro">ARCHIVOS</button>
                    </div>
                <?php endif; ?>
                <div class="footerNaV">
                    <form action="../api/auth/logout.php" method="post">
                        <button class="logout" type="submit">Cerrar sesión</button>
                    </form>

                    <p>Política de cookies</p><br>
                    <p>Terminos y condiciones</p>
                </div>
        </nav>
        <div class="contenedorTablaStencil" id="contenido-dinamico">
            <div class="btnsnav justify-content-end">
                <button type="button" class="btn bg-mi-color  btn-md align-items-center ">
                    <a href="./index.php"> <img src="../assets/img/Arrow 1.png"></a>
                </button>
                <div class="col-sm">
                    <button type="button" class="btn bg-mi-color  btn-lg text-center ">
                        Normativas
                    </button>
                    <form method="POST" enctype="multipart/form-data" class="d-inline">
                        <label for="archivo" class="btn bg-mi-color btn-lg text-center ">
                            Subir archivo <img src="../assets/img/descarga.png">
                        </label>
                        <input type="file" name="archivo" id="archivo" class="d-none" onchange="this.form.submit()"
                            required>
                    </form>

<body class="stencilBody">
<main class="stencil">
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

            <div class="table-responsive" style="max-height: 80%; overflow-y: auto; margin-top: 15px;">
                <table class="table table-bordered border-secondary">
                    <!--esto es para definiar la f var del tipousuari no borrar sino 
                   no saldran los cambios del header del admin-->
                    <?php
                    // Asegurarse de definir tipo_usuario_id antes del <thead>
                    if (!isset($tipo_usuario_id)) {
                        if (isset($_SESSION['correo'])) {
                            $correo = $_SESSION['correo'];
                            $stmt = $conexion->prepare("SELECT tipo_usuario_id FROM usuarios WHERE correo = ?");
                            $stmt->bind_param("s", $correo);
                            $stmt->execute();
                            $stmt->bind_result($tipo_usuario_id_result);
                            if ($stmt->fetch()) {
                                $tipo_usuario_id = $tipo_usuario_id_result;
                            }
                            $stmt->close();
                        } else {
                            $tipo_usuario_id = null;
                        }
                    }
                    ?>

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre del archivo</th>
                            <th>Fecha</th>
                            <?php if ($tipo_usuario_id == 1): // Solo admins ?>
                                <th>ID Usuario</th>
                                <th>Nombre Usuario</th>
                            <?php endif; ?>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody id="tabla-body">
                        <?php
                        //...
                        
                        if ($correo) {
                            // Inicializar variables para evitar undefined
                            $tipo_usuario_id = null;
                            $usuario_id = null;

                            $stmt = $conexion->prepare("SELECT id_usuarios, tipo_usuario_id FROM usuarios WHERE correo = ?");
                            if (!$stmt) {
                                die("Error en la consulta: " . $conexion->error);
                            }
                            $stmt->bind_param("s", $correo);
                            $stmt->execute();
                            $stmt->bind_result($usuario_id_result, $tipo_usuario_id_result);
                            if ($stmt->fetch()) {
                                $usuario_id = $usuario_id_result;
                                $tipo_usuario_id = $tipo_usuario_id_result;
                            }
                            $stmt->close();

                             if ($tipo_usuario_id == 2 && $usuario_id !== null) {
                                // Proveedor: solo sus archivos
                                $stmt = $conexion->prepare("
            SELECT id, nombre_archivo, fecha_subida, revision_estado 
            FROM archivos_subidos 
            WHERE proveedor_id = (
                SELECT id FROM proveedores WHERE usuario_id = ?
            )
        ");
                                if (!$stmt) {
                                    die("Error en la consulta: " . $conexion->error);
                                }
                                $stmt->bind_param("i", $usuario_id);
                            } else if ($tipo_usuario_id == 1) {
                                // Admin: ver todos los archivos con usuario que los subió
                                $stmt = $conexion->prepare("
            SELECT a.id, a.nombre_archivo, a.fecha_subida, a.revision_estado, u.id_usuarios, u.correo
            FROM archivos_subidos a
            LEFT JOIN proveedores p ON a.proveedor_id = p.id
            LEFT JOIN usuarios u ON p.usuario_id = u.id_usuarios
        ");
                                if (!$stmt) {
                                    die("Error en la consulta: " . $conexion->error);
                                }
                            } else {
                                // Consultor: ver todos los archivos (sin info de usuario)
                                $stmt = $conexion->prepare("
            SELECT id, nombre_archivo, fecha_subida, revision_estado 
            FROM archivos_subidos
        ");
                                if (!$stmt) {
                                    die("Error en la consulta: " . $conexion->error);
                                }
                            }

                            $stmt->execute();

                            if ($tipo_usuario_id == 1) {
                                $stmt->bind_result($id, $nombre_archivo, $fecha, $estado, $id_usuario_subio, $nombre_usuario_subio);
                            } else {
                                $stmt->bind_result($id, $nombre_archivo, $fecha, $estado);
                            }

                            $i = 1;
                            while ($stmt->fetch()) {
                                $estado_icono = match ($estado) {
                                    'aprobado' => "<span class='text-success'>&#10004;</span>",
                                    'rechazado' => "<span class='text-danger'>&#10006;</span>",
                                    default => "<span class='text-muted'>Pendiente</span>",
                                };

                                echo "<tr>
            <th scope='row'>{$i}</th>
            <td>" . htmlspecialchars($nombre_archivo) . "</td>
            <td>{$fecha}</td>";

                                if ($tipo_usuario_id == 1) {
                                    echo "<td>{$id_usuario_subio}</td>
                  <td>" . htmlspecialchars($nombre_usuario_subio) . "</td>";
                                }

                                echo "<td class='text-center'>{$estado_icono}</td>
        </tr>";
                                $i++;
                            }

                            $stmt->close();
                        }
                        ?>
                    </tbody>

                </table>
                <img src="../assets/img/banderita.png" class="imgEmpresa">

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

            <div class="d-flex justify-content-end mb-3">
                <div class="input-group" style="width: 300px; position:absolute; bottom:60px; right:100px;">
                    <span class="input-group-text"><img src="../assets/img/search.png"></img></span>
                    <input type="text" class="form-control" placeholder="Buscar usuario..." id="buscadorUsuarios">
                    <div id="contenido-dinamico"></div>
                </div>
            </div>
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
                    <input type="file" name="archivo" id="archivo" class="d-none" onchange="this.form.submit()" required>
                </form>
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
            <img src="../assets/img/banderita.png" class="imgEmpresa" alt="bandera">
        </div>
    </div>
</main>
    <script src="../assets/js/script.js"></script>
</body>
</html>
