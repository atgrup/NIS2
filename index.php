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
        /* 🌈 Fondo dinámico */
        body {
            font-family: "Instrument Sans", sans-serif;
            color: #fff;
            height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            background: linear-gradient(270deg, #072989, #0b37b0, #1d5cff, #072989);
            background-size: 600% 600%;
            animation: gradientMove 18s ease infinite;
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* 🧭 NAVBAR translúcido */
        nav {
            background: rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(10px);
            color: #fff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .tituloIndice {
            color: #fff;
            font-weight: 600; /* semibold */
        }

        nav a.btn {
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: #fff;
            transition: all 0.3s ease;
            backdrop-filter: blur(4px);
        }

        nav a.btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* 📦 Contenedor principal */
        main {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .section-fondo {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            max-width: 850px;
            width: 100%;
            color: #fff;
            animation: fadeIn 0.5s ease;
            box-shadow: none; /* sin sombra */
        }

        /* 🔘 Botones de pestañas */
        .tab-button {
            border: none;
            background: rgba(255, 255, 255, 0.15);
            font-weight: 600;
            text-transform: uppercase;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 25px;
            color: #fff;
            transition: all 0.3s ease;
            backdrop-filter: blur(6px);
        }

        .tab-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }

        .tab-button.active {
            background: rgba(255, 255, 255, 0.4);
            color: #072989;
        }

        /* 🌊 Contenido de pestañas */
        .tab-content-section {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .tab-content-section.active {
            display: block;
        }

        .candadito {
            width: 100px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ⚙️ Footer */
        footer {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(6px);
            color: rgba(255, 255, 255, 0.8);
        }

        a.link-nis {
            color: #aad1ff;
            text-decoration: none;
        }

        a.link-nis:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

    <!-- NAV -->
    <nav class="container-fluid d-flex justify-content-between align-items-center py-3 px-4 flex-nowrap">
        <h1 class="tituloIndice text-truncate m-0">Bienvenido/a</h1>

        <div class="btnsNav d-flex gap-3 flex-shrink-0">
            <a href="pages/registro.php" class="btn fw-bold text-uppercase px-3 py-2">Registrarse</a>
            <a href="pages/login.php" class="btn fw-bold text-uppercase px-3 py-2">Iniciar sesión</a>
        </div>
    </nav>

    <!-- MAIN -->
    <main class="container my-4 flex-grow-1">
        <section class="section-fondo">
            <div class="text-center mb-4">
                <button class="tab-button active" data-target="home">Home</button>
                <button class="tab-button" data-target="consultor">Consultor</button>
                <button class="tab-button" data-target="proveedor">Proveedor</button>
            </div>

            <!-- HOME -->
            <div id="home" class="tab-content-section active">
                <h4 class="mb-3">NIS2 – Directiva Europea de Ciberseguridad</h4>
                <p>
                    La Directiva NIS2 establece las nuevas medidas que las empresas y entidades deben cumplir para mejorar la ciberseguridad y la resiliencia frente a incidentes.  
                    Su objetivo es reforzar la protección de la información, la infraestructura y los servicios esenciales en toda la Unión Europea.
                </p>
                <p>
                    Afecta tanto a empresas privadas como a organizaciones públicas que gestionen servicios digitales, comunicaciones, energía, transporte, sanidad o sectores críticos.
                </p>
                <p class="mt-3">
                    Normativa oficial:  
                    <a class="link-nis" href="https://eur-lex.europa.eu/legal-content/ES/TXT/?uri=CELEX:32022L2555" target="_blank">Versión en Español</a> |  
                    <a class="link-nis" href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32022L2555" target="_blank">Version in English</a>
                </p>
                <div class="text-center mt-4">
                    <img src="assets/img/candadito.png" class="candadito" alt="Candadito">
                </div>
            </div>

            <!-- CONSULTOR -->
            <div id="consultor" class="tab-content-section">
                <h4 class="mb-3">Rol del Consultor</h4>
                <p>
                    El consultor tiene la función de guiar a las organizaciones en el cumplimiento de la Directiva NIS2, asegurando que las medidas técnicas, legales y organizativas se apliquen correctamente.
                </p>
                <p>
                    También es responsable de revisar y validar la documentación que los <strong>proveedores</strong> suben a la plataforma, incluyendo plantillas, formularios y evidencias de cumplimiento.  
                    Debe garantizar que todos los archivos estén completos y actualizados según los estándares de ciberseguridad definidos.
                </p>
                <ul>
                    <li>Auditorías de cumplimiento y riesgo</li>
                    <li>Validación de plantillas y documentación de proveedores</li>
                    <li>Gestión de roles y revisión de evidencias</li>
                </ul>
                <p class="mt-3">
                    Normativa oficial:  
                    <a class="link-nis" href="https://eur-lex.europa.eu/legal-content/ES/TXT/?uri=CELEX:32022L2555" target="_blank">Versión en Español</a> |  
                    <a class="link-nis" href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32022L2555" target="_blank">Version in English</a>
                </p>
            </div>

            <!-- PROVEEDOR -->
            <div id="proveedor" class="tab-content-section">
                <h4 class="mb-3">Rol del Proveedor</h4>
                <p>
                    El proveedor es responsable de cumplir con las medidas de seguridad exigidas por la NIS2 y de aportar la documentación necesaria para demostrar dicho cumplimiento.
                </p>
                <p>
                    Debe subir a la plataforma todas las <strong>plantillas y formularios</strong> requeridos por el consultor o la organización, asegurando su correcta cumplimentación.  
                    Además, debe mantener actualizada la información sobre los controles de seguridad, auditorías internas y evidencias de mitigación de riesgos.
                </p>
                <ul>
                    <li>Subida de documentación y plantillas requeridas</li>
                    <li>Gestión y actualización de medidas de seguridad</li>
                    <li>Comunicación con consultores para revisión</li>
                </ul>
                <p class="mt-3">
                    Normativa oficial:  
                    <a class="link-nis" href="https://eur-lex.europa.eu/legal-content/ES/TXT/?uri=CELEX:32022L2555" target="_blank">Versión en Español</a> |  
                    <a class="link-nis" href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32022L2555" target="_blank">Version in English</a>
                </p>
            </div>
        </section>
    </main>

    <footer class="text-center py-3 mt-auto small">
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
                buttons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                sections.forEach(sec => sec.classList.remove('active'));
                const target = document.getElementById(btn.dataset.target);
                target.classList.add('active');
            });
        });
    </script>
</body>

</html>
