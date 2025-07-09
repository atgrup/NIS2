<?php
$mensaje = "";
if (isset($_GET['error']) && $_GET['error'] === 'credenciales') {
    $mensaje = "❌ Correo o contraseña incorrectos.";
} elseif (isset($_GET['logout']) && $_GET['logout'] === 'ok') {
    $mensaje = "✅ Has cerrado sesión correctamente.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login NIS2</title>
  <link rel="stylesheet" href="../assets/styles/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="d-flex align-items-center justify-content-center body-auth">
  <main class="main-container container-fluid">
    <div class="row w-100 justify-content-center align-items-center">
      <div class="col-md-5">
        <div class="auth-box text-center shadow">
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

      <div class="col-md-5 info-text">
        <a href="#" onclick="window.history.back(); return false;" class="back-arrow mb-3 d-block">&#8592;</a>
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

