<?php
session_start();

if (!isset($_SESSION['rol'])) {
    header("Location: ../api/auth/login.php");
    exit;
}

$rol = strtolower($_SESSION['rol']);

$correo = isset($_SESSION['correo']) ? $_SESSION['correo'] : null;
$nombre = $correo ? explode('@', $correo)[0] : 'Invitado';
?>


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
            <h4>Hola, <?php echo htmlspecialchars($nombre); ?></h4>
            <div class="menuNav">
                <?php if ($rol === 'administrador'): ?>
                    <div class="cajaArchivos">
                        <button class="textoStencil" data-section="usuarios">USUARIOS</button>
                    </div>
                    <div class="cajaArchivos">
                        <button class="textoStencil" data-section="consultores">CONSULTORES</button>
                    </div>
                    <div class="cajaArchivos">
                        <button class="textoStencil" data-section="proveedores">PROVEEDORES</button>
                    </div>
                    <div class="cajaArchivos">
                        <button class="textoStencil">PLANTILLAS</button>
                    </div>
                    <div class="cajaArchivos">
                        <button class="textoStencil">ARCHIVOS</button>
                    </div>
                <?php elseif ($rol === 'consultor'): ?>
                    <div class="cajaArchivos">
                        <button class="textoStencil">PLANTILLAS</button>
                    </div>
                    <div class="cajaArchivos">
                        <button class="textoStencil">ARCHIVOS</button>
                    </div>
                    <div class="cajaArchivos">
                        <button class="textoStencil">PROVEEDORES</button>
                    </div>
                <?php else: /* proveedor */ ?>
                    <div class="cajaArchivos">
                        <button class="textoStencil">PLANTILLAS</button>
                    </div>
                    <div class="cajaArchivos">
                        <button class="textoStencil">ARCHIVOS</button>
                    </div>
                <?php endif; ?>
            <div class="footerNaV">
                <form action="../api/auth/logout.php" method="post">
                    <button type="submit">Cerrar sesión</button>
                </form>

                <p>Política de cookies</p><br>
                <p>Terminos y condiciones</p>
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
                            <th scope="col">First</th>
                            <th scope="col">Last</th>
                            <th scope="col">Handle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">1</th>
                            <td>Mark</td>
                            <td>Otto</td>
                            <td>@mdo</td>
                        </tr>
                        <tr>
                            <th scope="row">2</th>
                            <td>Jacob</td>
                            <td>Thornton</td>
                            <td>@fat</td>
                        </tr>
                        <tr>
                            <th scope="row">3</th>
                            <td>John</td>
                            <td>Doe</td>
                            <td>@social</td>
                        </tr>
                    </tbody>
                </table>
                <img src="../assets/img/banderita.png" class="imgEmpresa">
            </div>
        </div>

    </main>
</body>

</html>