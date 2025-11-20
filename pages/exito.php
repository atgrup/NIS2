<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>¡Éxito!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: "Instrument Sans", sans-serif;
            height: 100vh;
            background: linear-gradient(270deg, #072989, #0b37b0, #1a4fd8, #072989);
            background-size: 600% 600%;
            animation: gradientMove 15s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .container {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 2.5rem;
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>
    <div class="container col-md-5">
        <h1 class="display-3 text-success">✅ ¡Registro Completado!</h1>
        <p class="lead">Hemos enviado un correo de verificación a tu email.</p>
        <p>Por favor, revisa tu bandeja de entrada (y spam) y haz clic en el enlace para activar tu cuenta.</p>
        <a href="login.php" class="btn btn-primary btn-lg mt-3">Ir a Iniciar Sesión</a>
    </div>
</body>
</html>