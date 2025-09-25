<?php
// PHP SCRIPTING: Manejo de mensajes de estado

// Inicializa la variable `$mensaje` que se usará para mostrar mensajes de estado al usuario.
$mensaje = "";

// Comprueba si se recibió un parámetro 'success' en la URL y si su valor es '1'.
// Esto indica que el registro se completó con éxito.
if (isset($_GET['success']) && $_GET['success'] === '1') {
    // Asigna un mensaje de éxito.
    $mensaje = "✅ Registro exitoso. Ya puedes iniciar sesión.";
// Comprueba si se recibió un parámetro 'error'.
} elseif (isset($_GET['error'])) {
    // Si el error es 'pass', las contraseñas no coinciden.
    if ($_GET['error'] === 'pass') {
        $mensaje = "❌ Las contraseñas no coinciden.";
    // Si el error es 'email', el correo ya está registrado.
    } elseif ($_GET['error'] === 'email') {
        $mensaje = "❌ El correo ya está registrado.";
    // Para cualquier otro tipo de error.
    } else {
        $mensaje = "❌ Error desconocido al registrar.";
    }
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
  <title>Registro NIS2</title>
  <!-- Enlaza a la hoja de estilos CSS personalizada de la aplicación. -->
  <link rel="stylesheet" href="../assets/styles/style.css">

  <!-- Enlaza a la fuente de Google Fonts 'Roboto'. -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

  <!-- Enlaza a la hoja de estilos de Bootstrap para el diseño responsivo y componentes de la UI. -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<!-- BODY: Contenido visible de la página -->
<body>
  <div class="main-container container-fluid">
    <div class="row w-100 justify-content-center align-items-center">
      <!-- Columna de la izquierda para el formulario de registro. `col-md-5` define su tamaño en pantallas medianas y grandes. -->
      <div class="col-md-5">
        <!-- Contenedor del formulario con estilos de caja, centrado de texto y una sombra. -->
        <div class="auth-box text-center shadow">
            <h3 class="mb-4">NIS2</h3>

            <!-- Sección para mostrar mensajes de estado al usuario. Solo se renderiza si la variable `$mensaje` no está vacía. -->
            <?php if (isset($_GET['error']) && $_GET['error'] === 'correo'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <strong>Error:</strong> El mail ya está en uso.
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php elseif (isset($_GET['error']) && $_GET['error'] === 'contraseña'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <strong>Error:</strong> Las contraseñas no coinciden.
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php elseif (!empty($mensaje)): ?>
                <div class="alert alert-info"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <!-- Formulario de registro. -->
            <!-- El atributo `method="POST"` envía los datos de forma segura. El atributo `action` especifica el script PHP que procesará los datos del formulario (`procesar_registro.php`). -->
            <form method="POST" action="../api/auth/procesar_registro.php">
              <!-- Grupo de formulario para el campo de email. `text-start` alinea el texto a la izquierda y `mb-3` añade un margen inferior. -->
              <div class="form-group text-start mb-3">
                  <label for="email" class="form-label">Email</label>
                  <!-- Campo de entrada para el correo electrónico. El atributo `required` lo hace obligatorio para enviar el formulario. -->
                  <input type="email" id="email" name="email" class="form-control" placeholder="Ingresa tu email" required>
              </div>
              <!-- Grupo de formulario para el nombre de la empresa. -->
              <div class="form-group text-start mb-3">
                  <label for="nombre_empresa" class="form-label">Nombre de la empresa</label>
                  <!-- Campo de entrada para el nombre de la empresa. -->
                  <input type="text" id="nombre_empresa" name="nombre_empresa" class="form-control" placeholder="Ingresa el nombre de tu empresa" required>
              </div>
              <!-- Grupo de formulario para la contraseña. -->
              <div class="form-group text-start mb-3">
                  <label for="password" class="form-label">Contraseña</label>
                  <!-- Campo de entrada para la contraseña. `type="password"` oculta los caracteres que el usuario escribe. -->
                  <input type="password" id="password" name="password" class="form-control" placeholder="Ingresa tu contraseña" required>
              </div>
              <!-- Grupo de formulario para repetir la contraseña. -->
              <div class="form-group text-start mb-4">
                  <label for="repeat-password" class="form-label">Repite la contraseña</label>
                  <!-- Campo de entrada para repetir la contraseña, con el mismo tipo y validación. -->
                  <input type="password" id="repeat-password" name="repeat-password" class="form-control" placeholder="Repite tu contraseña" required>
              </div>
              <!-- Botón para enviar el formulario. `w-100` lo hace ocupar todo el ancho. -->
              <button type="submit" class="btn btn-outline-light w-100 mt-2">REGISTRARSE</button>
            </form>

        </div>
      </div>

      <!-- Columna de la derecha para información adicional y un elemento gráfico. -->
      <div class="col-md-5 info-text">
        <!-- Un enlace para volver a la página anterior. `onclick="window.history.back(); return false;"` utiliza JavaScript para navegar hacia atrás sin recargar la página. -->
        <a href="../index.php" class="back-arrow mb-3 d-block">&#8592;</a>
        <h3>Si ya eres proveedor o en caso que necesites darte de alta como uno….</h3>
        <p>Puedes revisar si tienes los documentos necesarios y actuales que cumplen con la normativa de la NIS2.</p>

        <div class="register-section">
          <!-- Muestra una imagen de la bandera de la UE. -->
          <img src="https://upload.wikimedia.org/wikipedia/commons/b/b7/Flag_of_Europe.svg" alt="UE" class="europe-icon">
        </div>
      </div>
    </div>
  </div>

  <!-- Enlaza al archivo JavaScript de Bootstrap. Es necesario para el funcionamiento de algunos componentes. -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
