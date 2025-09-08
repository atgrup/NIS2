<?php
// Inicia la sesión. Esto permite almacenar información del usuario entre diferentes páginas.
session_start();

// Incluye el archivo de conexión a la base de datos.
require_once '../api/includes/conexion.php';

// Comprueba si la solicitud HTTP es de tipo POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Limpia y captura el correo electrónico del formulario.
    $correo = trim($_POST['correo']);
    // Captura las contraseñas del formulario.
    $contrasena = $_POST['contrasena'];
    $contrasena2 = $_POST['contrasena2'];

    // --- Validación de Contraseñas ---
    // Comprueba si las contraseñas no coinciden.
    if ($contrasena !== $contrasena2) {
        // Almacena un mensaje de error en la sesión.
        $_SESSION['error'] = "Las contraseñas no coinciden.";
        // Redirige al usuario a la página de usuarios.
        header("Location: plantillaUsers.php?vista=usuarios");
        // Detiene la ejecución del script.
        exit;
    }

    // --- Verificación de Correo Existente ---
    // Prepara una consulta SQL para contar cuántos usuarios tienen el mismo correo.
    $stmt = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE correo = ?");
    // Vincula el parámetro del correo a la consulta. 's' indica que es un string.
    $stmt->bind_param("s", $correo);
    // Ejecuta la consulta preparada.
    $stmt->execute();
    // Vincula el resultado de la consulta (el conteo) a la variable $count.
    $stmt->bind_result($count);
    // Obtiene el resultado de la consulta.
    $stmt->fetch();
    // Cierra la declaración.
    $stmt->close();

    // Si el conteo es mayor a 0, significa que el correo ya está registrado.
    if ($count > 0) {
        // Almacena un mensaje de error en la sesión.
        $_SESSION['error'] = "El correo ya está registrado.";
        // Redirige al usuario.
        header("Location: plantillaUsers.php?vista=usuarios");
        // Detiene la ejecución.
        exit;
    }

    // --- Creación de Contraseña y Inserción de Usuario ---
    // Crea un hash seguro de la contraseña para almacenarla en la base de datos.
    $password_hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Prepara una consulta SQL para insertar un nuevo usuario.
    $stmt = $conexion->prepare("INSERT INTO usuarios (correo, password, tipo_usuario_id, verificado) VALUES (?, ?, 1, 0)");
    // Vincula los parámetros (correo y el hash de la contraseña). 'ss' indica dos strings.
    $stmt->bind_param("ss", $correo, $password_hash);

    // Ejecuta la consulta de inserción.
    if ($stmt->execute()) {
        // Cierra la declaración.
        $stmt->close();
        // Almacena un mensaje de éxito en la sesión.
        $_SESSION['success'] = "Usuario creado correctamente.";
        // Redirige al usuario.
        header("Location: plantillaUsers.php?vista=usuarios");
        // Detiene la ejecución.
        exit;
    } else {
        // Cierra la declaración.
        $stmt->close();
        // Almacena un mensaje de error en la sesión si la inserción falla.
        $_SESSION['error'] = "Error al crear el usuario.";
        // Redirige al usuario.
        header("Location: plantillaUsers.php?vista=usuarios");
        // Detiene la ejecución.
        exit;
    }
} else {
    // --- Redirección si la solicitud no es POST ---
    // Si la página se accede directamente sin un formulario POST, redirige al usuario.
    header("Location: plantillaUsers.php");
    // Detiene la ejecución.
    exit;
}
