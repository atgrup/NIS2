<?php
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conexion = new mysqli('jordio35.sg-host.com', 'u74bscuknwn9n', 'ad123456-', 'dbs1il8vaitgwc');

    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conexion->prepare("SELECT password FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($hash);
        $stmt->fetch();

        if (password_verify($password, $hash)) {
            // Aquí puedes redirigir o iniciar sesión
            $mensaje = "✅ ¡Login exitoso!";
            // header("Location: dashboard.php"); exit;
        } else {
            $mensaje = "❌ Contraseña incorrecta.";
        }
    } else {
        $mensaje = "❌ El correo no está registrado.";
    }

    $stmt->close();
    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login NIS2</title>
  <link rel="stylesheet" href="style.css">

  <!-- Google Fonts: Roboto -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="d-flex align-items-center justify-content-center">

  <div class="login-box text-center shadow">
    <h3 class="mb-4">NIS2</h3>

    <?php if (!empty($mensaje)): ?>
      <div class="alert alert-info"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
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

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
