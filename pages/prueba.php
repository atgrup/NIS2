<?php
session_start();

// Simula el rol del usuario (prueba con 'administrador', 'consultor' o 'proveedor')
$_SESSION['rol'] = $_SESSION['rol'] ?? 'proveedor';
$rol = $_SESSION['rol'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=, initial-scale=1.0">
    <title>Panel NIS2</title>
    <link rel="stylesheet" href="../assets/styles/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans&display=swap" rel="stylesheet" />
    <style>
        .bg-mi-color {
            background-color: #072989;
            color: white;
            border-radius: 40px;
        }
        .stencil {
            display: flex;
        }
        nav.indexStencil {
            width: 250px;
            background-color: #f0f0f0;
            padding: 15px;
        }
        .contenedorTablaStencil {
            flex-grow: 1;
            padding: 15px;
        }
    </style>
</head>
<body class="stencilBody">
    <main class="stencil">
        <nav class="indexStencil">
            <h1 class="tituloNIS">NIS2</h1>
            <div class="menuNav">
                <?php if ($rol === 'administrador'): ?>
                    <div class="cajaArchivos">
                        <a class="textoStencil">Usuarios</a>
                    </div>
                    <div class="cajaArchivos">
                        <a class="textoStencil">Consultores</a>
                    </div>
                    <div class="cajaArchivos">
                        <a class="textoStencil">Proveedores</a>
                    </div>
                    <div class="cajaArchivos">
                        <a class="textoStencil">Plantillas</a>
                    </div>
                    <div class="cajaArchivos">
                        <a class="textoStencil">Archivos</a>
                    </div>
                <?php elseif ($rol === 'consultor'): ?>
                    <div class="cajaArchivos">
                        <a class="textoStencil">Plantillas</a>
                    </div>
                    <div class="cajaArchivos">
                        <a class="textoStencil">Archivos</a>
                    </div>
                    <div class="cajaArchivos">
                        <a class="textoStencil">Proveedores</a>
                    </div>
                <?php else: /* proveedor */ ?>
                    <div class="cajaArchivos">
                        <a class="textoStencil">Plantillas</a>
                    </div>
                    <div class="cajaArchivos">
                        <a class="textoStencil">Archivos</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="footerNaV">
                Política de cookies<br>
                Términos y condiciones
            </div>
        </nav>

        <div class="contenedorTablaStencil">
            <?php if ($rol === 'administrador'): ?>
                <h2>Bienvenido Administrador</h2>
                <!-- Aquí contenido para administrador -->
                <table class="table table-bordered border-secondary">
                    <thead>
                        <tr>
                            <th>#</th><th>Nombre</th><th>Apellido</th><th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>1</td><td>Mark</td><td>Otto</td><td>@admin</td></tr>
                        <tr><td>2</td><td>Anna</td><td>Smith</td><td>@admin2</td></tr>
                    </tbody>
                </table>
            <?php elseif ($rol === 'consultor'): ?>
                <h2>Bienvenido Consultor</h2>
                <!-- Aquí contenido para consultor -->
                <button class="btn bg-mi-color btn-lg">Ver Normativas</button>
                <button class="btn bg-mi-color btn-lg">Ver Criterios</button>
            <?php else: /* proveedor */ ?>
                <h2>Bienvenido Proveedor</h2>
                <!-- Aquí contenido para proveedor -->
                <button class="btn bg-mi-color btn-lg">Subir Documento</button>
                <button class="btn bg-mi-color btn-lg">Mis Documentos</button>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
