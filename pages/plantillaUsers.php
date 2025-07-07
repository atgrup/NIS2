<?php
session_start();

// Verificación de sesión
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

// SUBIDA DE ARCHIVO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo'];
    $nombre_original = basename($archivo['name']);
    $nombre_archivo = time() . "_" . $nombre_original;

    // Obtener ID proveedor
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

        header("Location: PlantillaUsers.php");
        exit;
    } else {
        echo "<script>alert('❌ Error al subir el archivo');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos</title>
    <link rel="stylesheet" href="../assets/styles/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .bg-mi-color {
            background-color: #072989;
            color: white;
            border-radius: 40px;
        }
    </style>
</head>

<body class="stencilBody">
<main class="stencil">
    <nav class="indexStencil">
        <h1 class="tituloNIS">NIS2</h1>
        <h4>Hola, <?php echo htmlspecialchars($nombre); ?></h4>
        <div class="menuNav">
            <?php if ($rol === 'administrador'): ?>
                <div class="cajaArchivos"><button class="textoStencil">USUARIOS</button></div>
                <div class="cajaArchivos"><button class="textoStencil">CONSULTORES</button></div>
                <div class="cajaArchivos"><button class="textoStencil">PROVEEDORES</button></div>
                <div class="cajaArchivos"><button class="textoStencil">PLANTILLAS</button></div>
                <div class="cajaArchivos"><button class="textoStencil">ARCHIVOS</button></div>
            <?php elseif ($rol === 'consultor'): ?>
                <div class="cajaArchivos"><button class="textoStencil">PLANTILLAS</button></div>
                <div class="cajaArchivos"><button class="textoStencil">ARCHIVOS</button></div>
                <div class="cajaArchivos"><button class="textoStencil">PROVEEDORES</button></div>
            <?php else: ?>
                <div class="cajaArchivos"><button class="textoStencil">PLANTILLAS</button></div>
                <div class="cajaArchivos"><button class="textoStencil">ARCHIVOS</button></div>
            <?php endif; ?>
            <div class="footerNaV">
                <form action="../api/auth/logout.php" method="post">
                    <button type="submit">Cerrar sesión</button>
                </form>
                <p>Política de cookies</p>
                <p>Términos y condiciones</p>
            </div>
        </div>
    </nav>

    <div class="contenedorTablaStencil">
        <div class="btns">
            <button type="button" class="btn bg-mi-color btn-lg">
                <a href="./index.php"> <img src="../assets/img/Arrow 1.png"></a>
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
            <table class="table table-bordered border-secondary">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre del archivo</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($correo) {
                        $stmt = $conexion->prepare("SELECT u.id_usuarios, p.id FROM usuarios u JOIN proveedores p ON u.id_usuarios = p.usuario_id WHERE u.correo = ?");
                        $stmt->bind_param("s", $correo);
                        $stmt->execute();
                        $stmt->bind_result($usuario_id, $proveedor_id);
                        $stmt->fetch();
                        $stmt->close();

                        $stmt = $conexion->prepare("SELECT id, nombre_archivo, fecha_subida, revision_estado, archivo_url FROM archivos_subidos WHERE proveedor_id = ?");
                        $stmt->bind_param("i", $proveedor_id);
                        $stmt->execute();
                        $stmt->bind_result($id, $nombre, $fecha, $estado, $archivo_url);

                        $i = 1;
                        while ($stmt->fetch()) {
                            $ruta_fisica = realpath(__DIR__ . '/../' . $archivo_url);
                            if (file_exists($ruta_fisica)) {
                                echo "<tr>
                                    <th scope='row'>{$i}</th>
                                    <td><a href='download.php?archivo=" . urlencode($archivo_url) . "' style='color: inherit; text-decoration: underline;'>" . htmlspecialchars($nombre) . "</a></td>
                                    <td>{$fecha}</td>
                                    <td class='text-center'>{$estado}</td>
                                </tr>";
                                $i++;
                            } else {
                                // El archivo no existe físicamente, se puede eliminar de la base si se desea
                            }
                        }
                        $stmt->close();
                    }
                    $conexion->close();
                    ?>
                </tbody>
            </table>
            <img src="../assets/img/banderita.png" class="imgEmpresa">
        </div>
    </div>
</main>
</body>
</html>
