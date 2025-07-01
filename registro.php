<?php
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conexion = new mysqli('jordio35.sg-host.com', 'u74bscuknwn9n', 'ad123456-', 'dbs1il8vaitgwc');

    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    $correo = $_POST['email'];
    $password = $_POST['password'];
    $repeat = $_POST['repeat-password'];
    $tipo_usuario_id = 2; // cliente por defecto

    if ($password !== $repeat) {
        $mensaje = "❌ Las contraseñas no coinciden.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conexion->prepare("INSERT INTO usuarios (correo, password, tipo_usuario_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $correo, $hash, $tipo_usuario_id);

        if ($stmt->execute()) {
            $mensaje = "✅ Registro exitoso. Ya puedes iniciar sesión.";
        } else {
            $mensaje = "❌ Error al registrar: " . $stmt->error;
        }

        $stmt->close();
    }

    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Registro NIS2</title>
  <link rel="stylesheet" href="style.css">

  <!-- Google Fonts: Roboto -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
  <div class="main-container container-fluid">
    <div class="row w-100 justify-content-center align-items-center">
      <div class="col-md-5">
        <div class="register-box text-center shadow">
            <h3 class="mb-4">NIS2</h3>

            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-info"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group text-start mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Ingresa tu email" required>
                </div>
                <div class="form-group text-start mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Ingresa tu contraseña" required>
                </div>
                <div class="form-group text-start mb-4">
                    <label for="repeat-password" class="form-label">Repite la contraseña</label>
                    <input type="password" id="repeat-password" name="repeat-password" class="form-control" placeholder="Repite tu contraseña" required>
                </div>
                <button type="submit" class="btn btn-outline-light w-100 mt-2">REGISTRARSE</button>
            </form>
        </div>
      </div>

      <div class="col-md-5 info-text">
        <a href="#" class="back-arrow mb-3 d-block">&#8592;</a>
        <h3>Si ya eres proveedor o en caso que necesites darte de alta como uno….</h3>
        <p>Puedes revisar si tienes los documentos necesarios y actuales que cumplen con la normativa de la NIS2.</p>

        <div class="register-section">
          <img src="https://upload.wikimedia.org/wikipedia/commons/b/b7/Flag_of_Europe.svg" alt="UE" class="europe-icon">
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

