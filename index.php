<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIS2</title>

    <link rel="stylesheet" href="assets/styles/style.css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Fuente -->
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: "Instrument Sans", sans-serif;
            background-color: #f8f9fa;
        }

        .tab-button {
            border: none;
            background: transparent;
            font-weight: 600;
            text-transform: uppercase;
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease-in-out;
        }

        .tab-button.active {
            color: #0d6efd;
            border-bottom: 3px solid #0d6efd;
        }

        .tab-content-section {
            display: none;
        }

        .tab-content-section.active {
            display: block;
        }

        .section-fondo {
            background-color: white;
            border: 1px solid #dee2e6;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

    <!-- NAV -->
    <nav class="container d-flex justify-content-between align-items-center py-3 flex-nowrap">
        <h1 class="tituloIndice text-truncate m-0">Bienvenido/a</h1>

        <div class="btnsNav d-flex gap-3 flex-shrink-0">
            <a href="pages/registro.php" class="btn btn-outline-primary fw-bold text-uppercase px-3 py-2">REGISTRARSE</a>
            <a href="pages/login.php" class="btn btn-outline-primary fw-bold text-uppercase px-3 py-2">INICIAR SESIÓN</a>
        </div>
    </nav>

    <!-- MAIN -->
    <main class="container my-4 flex-grow-1">
        <section class="section-fondo rounded p-4">
            <div class="text-center mb-4">
                <button class="tab-button active" data-target="home">Home</button>
                <button class="tab-button" data-target="consultor">Consultor</button>
                <button class="tab-button" data-target="proveedor">Proveedor</button>
            </div>

            <!-- HOME -->
            <div id="home" class="tab-content-section active">
                <h4 class="mb-3">Nuevas restricciones de la NIS2</h4>
                <p>
                    Las empresas afectadas deberán cumplir con estrictas obligaciones de gestión de riesgos,
                    notificación de incidentes de seguridad y adoptar medidas técnicas y organizativas adecuadas.
                    Para los proveedores, esto implica mayores responsabilidades contractuales y técnicas, ya que las
                    empresas deberán garantizar que toda su cadena de suministro cumple también con los requisitos
                    de seguridad.
                </p>
                <div class="text-center mt-4">
                    <img src="assets/img/candadito.png" class="candadito" alt="Candadito">
                </div>
            </div>

            <!-- CONSULTOR -->
            <div id="consultor" class="tab-content-section">
                <h4 class="mb-3">Consultores</h4>
                <p>
                    Si eres consultor o auditor, puedes ayudar a las empresas a cumplir con la directiva NIS2 ofreciendo:
                </p>
                <ul>
                    <li>Evaluaciones de riesgos cibernéticos</li>
                    <li>Auditorías de cumplimiento normativo</li>
                    <li>Capacitación en seguridad y cumplimiento</li>
                </ul>
            </div>

            <!-- PROVEEDOR -->
            <div id="proveedor" class="tab-content-section">
                <h4 class="mb-3">Proveedores</h4>
                <p>
                    Los proveedores deben garantizar que sus servicios cumplen los requisitos de seguridad exigidos por la
                    NIS2. Esto incluye:
                </p>
                <ul>
                    <li>Controles de acceso y autenticación segura</li>
                    <li>Gestión de incidentes y vulnerabilidades</li>
                    <li>Auditorías y verificaciones continuas</li>
                </ul>
            </div>
        </section>
    </main>

    <footer class="text-center py-3 mt-auto text-muted small">
        © 2025 Plataforma NIS2 - Todos los derechos reservados
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SCRIPT PARA TOGGLE -->
    <script>
        const buttons = document.querySelectorAll('.tab-button');
        const sections = document.querySelectorAll('.tab-content-section');

        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                // quitar activo a todos los botones
                buttons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // ocultar todas las secciones
                sections.forEach(sec => sec.classList.remove('active'));

                // mostrar la sección correspondiente
                const target = document.getElementById(btn.dataset.target);
                target.classList.add('active');
            });
        });
    </script>
</body>

</html>
