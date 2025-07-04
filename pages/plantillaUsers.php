<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=
    , initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../assets/styles/style.css">
    <!--google fonts-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&display=swap"
        rel="stylesheet">

</head>
<style>
    .bg-mi-color {
    background-color: #072989;
    color: white;
    border-radius: 40px;
}
    </style>
<body class="stencilBody">
    <main class="stencil">
        <nav class="indexStencil">
            <h1 class="tituloNIS">NIS2</h1>
            <div class="menuNav">
                <div class="cajaArchivos">
                    <div class="textoStencil">Gernador 1</div>
                    <div class="textoStencil"> imagen</div>
                </div>
                <div class="cajaArchivos">
                    <div class="textoStencil">Gernador 1</div>
                    <div class="textoStencil"> imagen</div>


                </div>
                <div class="cajaArchivos">
                    <div class="textoStencil">Gernador 1</div>
                    <div class="textoStencil"> imagen</div>
                </div>
            </div>
            <div class="footerNaV">
                Política de cookies<br>
                Terminos y condiciones
            </div>
        </nav>

        <div class="contenedorTablaStencil">
            <div class="btns">
                <button type="button" class="btn bg-mi-color  btn-lg">
                    <img src="../assets/img/Arrow 1.png">
                </button>
                <div class="col-sm">
                    <button type="button" class="btn bg-mi-color  btn-lg">
                        Normativas
                    </button>
                    <button type="button" class="btn bg-mi-color  btn-lg">
                        Criterios de la NIS2
                    </button>
                </div>
            </div>
            <div class="headertable">
                <table class="table table-bordered border-secondary">
                    <thead>
  <tr>
    <th scope="col">#</th>
    <th scope="col">Nombre del archivo</th>
    <th scope="col">Fecha</th>
    <th scope="col">Estado</th>
  </tr>
</thead>
<tbody>
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$conexion = new mysqli('jordio35.sg-host.com', 'u74bscuknwn9n', 'ad123456-', 'dbs1il8vaitgwc');

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
        $estado_icono = match($estado) {
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
        </div>

    </main>
</body>

</html>