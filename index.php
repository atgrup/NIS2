<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8"> <!-- Configuración de codificación de caracteres -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Escalado responsivo en dispositivos móviles -->
    <title>NIS2</title> <!-- Título de la pestaña del navegador -->

    <!-- Archivo CSS personalizado -->
    <link rel="stylesheet" href="assets/styles/style.css">

    <!-- Google Fonts (optimización de conexión previa) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Bootstrap CSS (para estilos y componentes predefinidos) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Fuente personalizada desde Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&display=swap"
        rel="stylesheet">
</head>

<body class="d-flex flex-column 100%">
    <!-- Barra de navegación -->
    <nav class="container d-flex justify-content-between align-items-center py-3 flex-nowrap" style="height: 20%;">
        <!-- Título de la página (recortado si es demasiado largo) -->
        <h1 class="tituloIndice text-truncate m-0" style="max-width: 60vw;">Bienvenido/a</h1>

        <!-- Botones de navegación: Registro e inicio de sesión -->
        <div class="btnsNav d-flex gap-3 flex-shrink-0">
            <a href="pages/registro.php" class="auth-btn btn btn-outline-primary px-3 py-2 fw-bold text-uppercase">REGISTRARSE</a>
            <a href="pages/login.php" class="auth-btn btn btn-outline-primary px-3 py-2 fw-bold text-uppercase">INICIAR SESIÓN</a>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="d-flex justify-content-center align-items-center my-4" style="height: 80%;">
        <section class=" container section-fondo text-white rounded p-4 d-flex flex-column justify-content-center ">
            <!-- Subtítulo -->
            <div class="subtitulo-index fs-5 fw-bold mb-3">Nuevas restricciones de la NIS2</div>

            <!-- Párrafo de introducción -->
            <p>
                Las empresas afectadas deberán cumplir con estrictas obligaciones de gestión de riesgos, notificación de
                incidentes de seguridad en plazos cortos y adoptar medidas técnicas y organizativas adecuadas.
                Para los proveedores, esto implica mayores responsabilidades contractuales y técnicas, ya que las empresas
                deberán garantizar que toda su cadena de suministro cumple también con los requisitos de seguridad, lo que
                podría traducirse en auditorías, controles más estrictos y posibles sanciones en caso de incumplimiento.
            </p>

            <!-- Imagen y bloque de información adicional -->
            <div class="d-flex flex-column align-items-start gap-4" style="height: 100%;">
                <!-- Imagen decorativa -->
                <img src="assets/img/candadito.png" class="candadito" alt="Candadito">

                <!-- Bloque para consultores -->
                <div class="funcionesIndex border border-white rounded p-3 flex-grow-1">
                    <div class="titulo fs-4 fw-bold mb-2">Consultores</div>
                    <p>Si eres consultor o auditor, ponte en contacto con tu empresa para darte de alta en esta plataforma.</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Bootstrap JS con dependencias opcionales (Popper incluido en bundle) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
