<?php
$mensaje = "";
$tipo_alerta = "";
$mostrarModal = false;
$codigo_verificacion = "";

if (isset($_GET['error'])) {
  $mostrarModal = true;
  switch ($_GET['error']) {
    case 'pass': $mensaje = "❌ Las contraseñas no coinciden."; $tipo_alerta = "danger"; break;
    case 'email': $mensaje = "❌ Este correo ya está registrado."; $tipo_alerta = "danger"; break;
    case 'unknown': $mensaje = "❌ Error desconocido al registrar."; $tipo_alerta = "danger"; break;
  }
} elseif (isset($_GET['success']) && $_GET['success'] === '1') {
  $mensaje = "✅ Registro exitoso. Revisa tu correo para la verificación.";
  $tipo_alerta = "success";
  $mostrarModal = true;
  if (isset($_GET['token'])) $codigo_verificacion = $_GET['token'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Registro NIS2</title>
  <link rel="stylesheet" href="../assets/styles/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body.body-auth {
      font-family: "Instrument Sans", sans-serif;
      height: 100vh;
      background: linear-gradient(270deg, #072989, #0b37b0, #1a4fd8, #072989);
      background-size: 600% 600%;
      animation: gradientMove 15s ease infinite;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      overflow: hidden;
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

    .auth-box h3 { color: #fff; font-weight: 700; }

    .form-label { color: #f8f9fa; }

    .form-control {
      background-color: rgba(255,255,255,0.1);
      color: white;
      border: 1px solid rgba(255,255,255,0.3);
    }

    .form-control::placeholder { color: rgba(255,255,255,0.7); }

    .btn-outline-light:hover { background-color: rgba(255,255,255,0.2); }

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
    }

    .back-arrow:hover { opacity: 1; }

    .europe-icon { width: 50px; margin-top: 1rem; }
  </style>
</head>

<body class="d-flex align-items-center justify-content-center body-auth">
  <main class="main-container container-fluid">
    <div class="row w-100 justify-content-center align-items-center">
      <div class="col-md-5">
        <div class="auth-box text-center">
          <h3 class="mb-4">NIS2</h3>

          <?php if (!empty($mensaje)): ?>
            <div class="alert <?php echo (str_starts_with($mensaje, '✅') ? 'alert-success' : 'alert-danger'); ?>">
              <?php echo $mensaje; ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="../api/auth/procesar_registro.php">
            <div class="form-group text-start mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" name="email" id="email" class="form-control" placeholder="Ingresa tu email" required>
            </div>
            <div class="form-group text-start mb-3">
              <label for="nombre_empresa" class="form-label">Nombre de la empresa</label>
              <input type="text" name="nombre_empresa" id="nombre_empresa" class="form-control" placeholder="Ingresa el nombre de tu empresa" required>
            </div>
            <div class="form-group text-start mb-3">
              <label for="pais_origen" class="form-label">País de origen</label>
              <select name="pais_origen" id="pais_origen" class="form-select" required>
                <option value="">Selecciona tu país</option>
                <option value="España">España</option>
                <option value="Francia">Francia</option>
                <option value="Alemania">Alemania</option>
                <option value="Italia">Italia</option>
                <option value="Portugal">Portugal</option>
                <option value="Reino Unido">Reino Unido</option>
                <option value="Otro">Otro</option>
              </select>
            </div>
            <div class="form-group text-start mb-3">
              <label for="password" class="form-label">Contraseña</label>
              <input type="password" name="password" id="password" class="form-control" placeholder="Ingresa tu contraseña" required>
            </div>
            <div class="form-group text-start mb-4">
              <label for="repeat-password" class="form-label">Repite la contraseña</label>
              <input type="password" name="repeat_password" id="repeat_password" class="form-control" placeholder="Repite tu contraseña" required>
            </div>
            <button type="submit" class="btn btn-outline-light w-100 mt-2">REGISTRARSE</button>
          </form>
        </div>
      </div>

      <div class="col-md-5 info-text text-center text-md-start mt-4 mt-md-0">
        <a href="../index.php" class="back-arrow mb-3 d-block">&#8592;</a>
        <h3>Si ya eres proveedor o necesitas darte de alta como uno…</h3>
        <p>Puedes revisar si tienes los documentos necesarios y actuales que cumplen con la normativa de la NIS2.</p>
        <div class="register-section">
          <img src="https://upload.wikimedia.org/wikipedia/commons/b/b7/Flag_of_Europe.svg" alt="UE" class="europe-icon">
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
