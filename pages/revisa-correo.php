<?php
$mensaje = "";
$codigo_verificacion = "";
$es_error = false;

// 1. Si llega por POST (después de ocultar el token de éxito)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $titulo = "✅ ¡Registro exitoso!";
    $mensaje = "Se ha enviado un correo de verificación a tu email. Por favor, revisa tu bandeja de entrada (y spam).";
    $codigo_verificacion = $_POST['token'];
}
// 2. Si llega por GET con ÉXITO (la primera vez, desde el registro)
elseif (isset($_GET['success']) && $_GET['success'] === '1' && isset($_GET['token'])) {
    $codigo_verificacion = $_GET['token'];
    ?>
    <form id="hideTokenForm" method="POST" action="revisa-correo.php">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($codigo_verificacion); ?>">
    </form>
    <script>
      document.getElementById('hideTokenForm').submit();
    </script>
    <?php
    exit; // Detiene la carga de la página para que el formulario se envíe
}
// 3. Si llega por GET con ERROR
elseif (isset($_GET['error'])) {
    $es_error = true;
    $titulo = "❌ Ha ocurrido un error";
    switch ($_GET['error']) {
        case 'pass':
            $mensaje = "Las contraseñas no coinciden. Por favor, vuelve a intentarlo.";
            break;
        case 'email':
            $mensaje = "Este correo electrónico ya está registrado. Por favor, intenta iniciar sesión.";
            break;
        default:
            $mensaje = "Ha ocurrido un error desconocido (Código: " . htmlspecialchars($_GET['error']) . "). Por favor, contacta a soporte.";
            break;
    }
}
// 4. Si alguien entra aquí por error
else {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Verifica tu correo - NIS2</title>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
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
      padding: 2.5rem;
      backdrop-filter: blur(10px);
      text-align: center;
    }
    .form-control {
      background-color: rgba(255,255,255,0.1);
      color: white;
      border: 1px solid rgba(255,255,255,0.3);
    }
    .form-control::placeholder { color: rgba(255,255,255,0.7); }
  </style>
</head>
<body class="body-auth">

  <div class="auth-box col-md-4">
    <h3 class="mb-3"><?php echo $titulo; ?></h3>
    <p class="mb-4"><?php echo $mensaje; ?></p>
    
    <?php if (!$es_error): // Muestra esto SOLO si es un registro exitoso ?>
      <div class="mb-3">
          <label for="codigoUsuario" class="form-label">Introduce el código aquí:</label>
          <input type="text" id="codigoUsuario" class="form-control text-center" placeholder="Código de verificación">
          <small class="text-white-50 d-block mt-2">Código (para pruebas): <span id="codigoPrueba" class="fw-bold"><?php echo htmlspecialchars($codigo_verificacion); ?></span></small>
      </div>
      <button type="button" class="btn btn-primary w-100 mb-3" onclick="verificarCodigo()">Verificar Cuenta</button>
    <?php endif; ?>
    
    <?php if ($es_error): ?>
        <a href="registro.php" class="btn btn-outline-light w-100">Volver a Registro</a>
    <?php else: ?>
        <a href="login.php" class="btn btn-outline-light w-100">Volver a Login</a>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <?php if (!$es_error): ?>
  <script>
    // Mostrar token en consola para pruebas
    if ("<?php echo $codigo_verificacion; ?>") {
        console.log("Token de verificación:", "<?php echo $codigo_verificacion; ?>");
    }

    // Función para verificar el código
    window.verificarCodigo = function () {
      const codigoIngresado = document.getElementById('codigoUsuario').value;
      if (!codigoIngresado) {
        alert("Por favor introduce el código.");
        return;
      }

      const formData = new FormData();
      formData.append('code', codigoIngresado);

      fetch("../api/auth/verify.php", {
        method: "POST",
        body: formData
      })
      .then(res => res.text())
      .then(html => {
        if (html.includes("✅")) {
          alert("✅ Verificación exitosa, ya puedes iniciar sesión.");
          window.location.href = "../pages/login.php";
        } else {
          alert("❌ Código inválido o ya verificado.");
        }
      })
      .catch(err => {
        console.error(err);
        alert("Error al conectar con el servidor.");
      });
    };
  </script>
  <?php endif; ?>

</body>
</html>