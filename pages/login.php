<?php
$mensaje = "";
$mostrarModal = false;

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'credenciales') {
        $mensaje = "❌ Correo o contraseña incorrectos.";
        $mostrarModal = true;
    } elseif ($_GET['error'] === 'no_verificado') {
        $mensaje = "❌ Usuario no verificado. Revisa tu correo.";
        $mostrarModal = true;
    }
} elseif (isset($_GET['logout']) && $_GET['logout'] === 'ok') {
    $mensaje = "✅ Has cerrado sesión correctamente.";
    $mostrarModal = true;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login NIS2</title>

  <link rel="stylesheet" href="../assets/styles/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

  <style>
    body.body-auth {
      font-family: "Instrument Sans", sans-serif;
      height: 100vh;
      margin: 0;
      overflow: hidden;
      background: linear-gradient(270deg, #072989, #0b37b0, #1a4fd8, #072989);
      background-size: 600% 600%;
      animation: gradientMove 15s ease infinite;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
    }

    @keyframes gradientMove {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .auth-box {
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 16px;
      padding: 2rem;
      color: #fff;
      backdrop-filter: blur(10px);
    }

    .auth-box h3 {
      color: #fff;
      font-weight: 700;
    }

    .form-label {
      color: #f8f9fa;
    }

    .form-control {
      background-color: rgba(255,255,255,0.1);
      color: white;
      border: 1px solid rgba(255,255,255,0.3);
    }

    .form-control::placeholder {
      color: rgba(255,255,255,0.7);
    }

    .btn-outline-light:hover {
      background-color: rgba(255,255,255,0.2);
    }

    .info-text {
      color: #fff;
      font-weight: 500;
    }

    .info-text h3 {
      color: #fff;
      font-weight: 700;
      text-shadow: 0 2px 5px rgba(0,0,0,0.4);
    }

    .info-text p {
      color: rgba(255,255,255,0.85);
    }

    .back-arrow {
      font-size: 1.2rem;
      text-decoration: none;
      color: #fff;
      opacity: 0.8;
      transition: opacity 0.2s;
    }

    .back-arrow:hover {
      opacity: 1;
    }

    .europe-icon {
      width: 50px;
      margin-top: 1rem;
    }
  </style>
</head>

<body class="d-flex align-items-center justify-content-center body-auth">
  <main class="main-container container-fluid">
    <div class="row w-100 justify-content-center align-items-center">
      <div class="col-md-5">
        <div class="auth-box text-center">
          <h3 class="mb-4">NIS2</h3>

          <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo (str_starts_with($mensaje, '✅')) ? 'success' : 'danger'; ?>">
              <?php echo htmlspecialchars($mensaje); ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="../api/auth/procesar_login.php">
            <div class="form-group text-start mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" name="email" id="email" class="form-control" placeholder="Ingresa tu email" required>
            </div>
            <div class="form-group text-start mb-4">
              <label for="password" class="form-label">Contraseña</label>
              <input type="password" name="password" id="password" class="form-control" placeholder="Ingresa tu contraseña" required>
            </div>
            <button type="submit" class="btn btn-outline-light w-100 mt-2">INICIAR SESIÓN</button>
          </form>
        </div>
      </div>

      <div class="col-md-5 info-text text-center text-md-start mt-4 mt-md-0">
        <a href="../index.php" class="back-arrow mb-3 d-block">&#8592;</a>
        <h3>¿Ya eres proveedor? Inicia sesión para acceder a tu cuenta.</h3>
        <p>Si aún no te has registrado, verifica que cuentas con la documentación actualizada y que cumples con los requisitos de la normativa NIS2 antes de darte de alta.</p>
        <div class="register-section">
          <img src="https://upload.wikimedia.org/wikipedia/commons/b/b7/Flag_of_Europe.svg" alt="UE" class="europe-icon">
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
