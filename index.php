<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIS2</title>
    <link rel="stylesheet" href="assets/styles/style.css">
    <!--google fonts-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&display=swap"
        rel="stylesheet">

</head>
<body>
    <nav class="indexNav container d-flex justify-content-between align-items-center py-3 flex-nowrap">
    <h1 class="tituloIndice text-truncate m-0" style="max-width: 60vw;">Bienvenido/a</h1>
    <div class="btnsNav d-flex gap-3 flex-shrink-0">
        <a href="pages/registro.php" class="auth-btn btn btn-outline-primary px-3 py-2 fw-bold text-uppercase">REGISTRARSE</a>
        <a href="pages/login.php" class="auth-btn btn btn-outline-primary px-3 py-2 fw-bold text-uppercase">INICIAR SESIÓN</a>
    </div>
    </nav>

  <main class="container my-4">
    <section class="section-fondo text-white rounded p-4">
        <div class="subtitulo-index fs-5 fw-bold mb-3">Nuevas restricciones de la NIS2</div>
        <p>
            Las empresas afectadas deberán cumplir con estrictas obligaciones de gestión de riesgos, notificación de
            incidentes de seguridad en plazos cortos y adoptar medidas técnicas y organizativas adecuadas.
            Para los proveedores, esto implica mayores responsabilidades contractuales y técnicas, ya que las empresas
            deberán garantizar que toda su cadena de suministro cumple también con los requisitos de seguridad, lo que
            podría traducirse en auditorías, controles más estrictos y posibles sanciones en caso de incumplimiento.
        </p>

        <div class="d-flex flex-column align-items-start gap-4">
            <img src="assets/img/candadito.png" class="candadito" alt="Candadito">
            <div class="funcionesIndex border border-white rounded p-3 flex-grow-1">
                <div class="titulo fs-4 fw-bold mb-2">Consultores</div>
                <p>Si eres consultor o auditor, ponte en contacto con tu empresa para darte de alta en esta plataforma.</p>
            </div>
        </div>

    </section>
  </main>

  <!-- Bootstrap JS y dependencias opcionales -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>