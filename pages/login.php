<?php
// login.php
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
  <!-- HEAD: Metadatos y recursos externos -->

  <!-- Define la codificación de caracteres a UTF-8 para admitir todos los caracteres. -->
  <meta charset="UTF-8" />
  <!-- Configura la vista para que sea compatible con dispositivos móviles (diseño responsive). -->
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Título de la página que se muestra en la pestaña del navegador. -->
  <title>Login NIS2</title>
  <!-- Enlaza a la hoja de estilos CSS personalizada de la aplicación. -->
  <link rel="stylesheet" href="../assets/styles/style.css">
  <!-- Enlaza a la fuente de Google Fonts 'Roboto'. -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
  <!-- Enlaza a la hoja de estilos de Bootstrap para el diseño responsivo y componentes de la UI (User Interface). -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<!-- BODY: Contenido visible de la página -->

<!-- El cuerpo de la página. Las clases de Bootstrap `d-flex`, `align-items-center` y `justify-content-center` centran el contenido principal en la pantalla. `body-auth` es una clase CSS personalizada para el estilo de la página de login. -->
<body class="d-flex align-items-center justify-content-center body-auth">
  <main class="main-container container-fluid">
    <div class="row w-100 justify-content-center align-items-center">
      <!-- Columna de la izquierda para el formulario de login. `col-md-5` define su tamaño en pantallas medianas y grandes. -->
      <div class="col-md-5">
        <!-- Contenedor del formulario con estilos de caja, centrado de texto y una sombra. -->
        <div class="auth-box text-center shadow">
            <h3 class="mb-4">NIS2</h3>
            <!-- Sección para mostrar mensajes de estado al usuario. Solo se renderiza si la variable `$mensaje` no está vacía. -->
            <?php 
            // PHP SCRIPTING: Manejo de mensajes de estado
            $mensaje = "";
            // Comprueba si se recibió un parámetro 'error' en la URL a través del método GET y si su valor es 'credenciales'.
            if (isset($_GET['error']) && $_GET['error'] === 'credenciales') {
                $mensaje = "❌ Correo o contraseña incorrectos.";
            } elseif (isset($_GET['logout']) && $_GET['logout'] === 'ok') {
                $mensaje = "✅ Has cerrado sesión correctamente.";
            }
            if (!empty($mensaje)): ?>
              <!-- Un div de alerta de Bootstrap. La clase `alert-success` (para éxito) o `alert-danger` (para error) se asigna dinámicamente usando una función de PHP que verifica si el mensaje comienza con '✅'. -->
              <div class="alert alert-<?php echo (str_starts_with($mensaje, '✅')) ? 'success' : 'danger'; ?>">
                  <!-- Muestra el mensaje de forma segura usando `htmlspecialchars()` para prevenir ataques de scripting entre sitios (XSS), ya que los datos provienen de la URL. -->
                  <?php echo htmlspecialchars($mensaje); ?>
              </div>
            <?php endif; ?>

            <!-- Formulario de inicio de sesión. -->
            <!-- El atributo `method="POST"` envía los datos de forma segura. El atributo `action` especifica el script PHP que procesará los datos del formulario (`procesar_login.php`). -->
            <form method="POST" action="../api/auth/procesar_login.php">
              <!-- Grupo de formulario para el campo de email. `text-start` alinea el texto a la izquierda y `mb-3` añade un margen inferior. -->
              <div class="form-group text-start mb-3">
                <label for="email" class="form-label">Email</label>
                <!-- Campo de entrada para el correo electrónico. El atributo `required` lo hace obligatorio para enviar el formulario. -->
                <input type="email" name="email" id="email" class="form-control" placeholder="Ingresa tu email" required>
              </div>
              <!-- Grupo de formulario para el campo de contraseña. -->
              <div class="form-group text-start mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <!-- Campo de entrada para la contraseña. `type="password"` oculta los caracteres que el usuario escribe. -->
                <input type="password" name="password" id="password" class="form-control" placeholder="Ingresa tu contraseña" required>
              </div>
              <!-- Botón para enviar el formulario. Las clases de Bootstrap lo estilizan. `w-100` lo hace ocupar todo el ancho. -->
              <button type="submit" class="btn btn-outline-light w-100 mt-2">INICIAR SESIÓN</button>
            </form>
        </div>
      </div>

      <!-- Columna de la derecha para información adicional. -->
      <div class="col-md-5 info-text">
        <!-- Un enlace para volver a la página anterior. `onclick="window.history.back(); return false;"` utiliza JavaScript para navegar hacia atrás sin recargar la página. -->
        <a href="../index.php" class="back-arrow mb-3 d-block">&#8592;</a>
        <h3>¿Ya eres proveedor? Inicia sesión para acceder a tu cuenta.</h3>
        <p>Si aún no te has registrado, verifica que cuentas con la documentación actualizada y que cumples con los requisitos de la normativa NIS2 antes de darte de alta.</p>

        <div class="register-section">
          <!-- Muestra una imagen de la bandera de la UE. -->
          <img src="https://upload.wikimedia.org/wikipedia/commons/b/b7/Flag_of_Europe.svg" alt="UE" class="europe-icon">
        </div>
      </div>
    </div>
  </main>

  <!-- Enlaza al archivo JavaScript de Bootstrap. Es necesario para el funcionamiento de algunos componentes, aunque en este caso no se usa de forma visible. -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

