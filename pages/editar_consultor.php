<?php
// Inicia la sesión. Esto permite acceder y modificar variables de sesión.
session_start();

// Incluye el archivo de conexión a la base de datos.
require_once '../api/includes/conexion.php';

// --- Verificación de Permisos ---
// Obtiene el rol del usuario de la sesión. Si no está definido, asigna una cadena vacía.
$rol = $_SESSION['rol'] ?? '';
// Comprueba si el rol del usuario no es 'administrador' (insensible a mayúsculas/minúsculas).
if (strtolower($rol) !== 'administrador') {
    // Si no es un administrador, lo redirige de vuelta a la página de consultores.
    header('Location: plantillaUsers.php?vista=consultores');
    // Termina la ejecución del script.
    exit;
}

// --- Procesamiento del Formulario POST ---
// Verifica si la solicitud es de tipo POST y si el botón de "editar_consultor" fue presionado.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_consultor'])) {
    // Convierte la ID del consultor a un entero para asegurar que es un número válido.
    $consultor_id = intval($_POST['consultor_id']);
    // Filtra el correo electrónico para eliminar caracteres no deseados, mejorando la seguridad.
    $correo = filter_var($_POST['correo'], FILTER_SANITIZE_EMAIL);
    // Obtiene la contraseña del formulario. El operador `?? ''` asegura que si no se envía, la variable sea una cadena vacía.
    $contrasena = $_POST['contrasena'] ?? '';

    // --- Validación de Correo ---
    // Valida que el correo tenga un formato de email válido.
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        // Almacena un mensaje de error en la sesión.
        $_SESSION['error'] = "Correo inválido";
        // Redirige al usuario.
        header('Location: plantillaUsers.php?vista=consultores');
        // Termina la ejecución.
        exit;
    }

    // --- Obtener la ID de Usuario del Consultor ---
    // Prepara una consulta para obtener la ID de usuario asociada al consultor. Esto es necesario porque el correo y la contraseña están en la tabla `usuarios`, no en la de `consultores`.
    $stmt = $conexion->prepare("SELECT usuario_id FROM consultores WHERE id = ?");
    // Vincula la ID del consultor a la consulta. 'i' indica que es un entero.
    $stmt->bind_param("i", $consultor_id);
    // Ejecuta la consulta.
    $stmt->execute();
    // Vincula el resultado de la consulta a la variable `$usuario_id`.
    $stmt->bind_result($usuario_id);
    // Obtiene los resultados.
    $stmt->fetch();
    // Cierra la declaración.
    $stmt->close();

    // --- Actualización de Datos ---
    // Comprueba si se encontró una ID de usuario válida.
    if ($usuario_id) {
        // --- Actualizar Correo ---
        // Prepara una consulta para actualizar el correo en la tabla `usuarios`.
        $stmt = $conexion->prepare("UPDATE usuarios SET correo = ? WHERE id_usuarios = ?");
        // Vincula los parámetros: 's' para el correo y 'i' para la ID de usuario.
        $stmt->bind_param("si", $correo, $usuario_id);
        // Si la ejecución falla...
        if (!$stmt->execute()) {
            // Almacena un mensaje de error detallado.
            $_SESSION['error'] = "Error al actualizar correo: " . $stmt->error;
            // Cierra la declaración.
            $stmt->close();
            // Redirige.
            header('Location: plantillaUsers.php?vista=consultores');
            exit;
        }
        // Cierra la declaración.
        $stmt->close();

        // --- Actualizar Contraseña (Opcional) ---
        // Comprueba si se ha proporcionado una nueva contraseña.
        if (!empty($contrasena)) {
            // Crea un hash seguro de la nueva contraseña.
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            // Prepara la consulta para actualizar la contraseña.
            $stmt = $conexion->prepare("UPDATE usuarios SET password = ? WHERE id_usuarios = ?");
            // Vincula el hash y la ID de usuario.
            $stmt->bind_param("si", $hash, $usuario_id);
            // Si la ejecución falla...
            if (!$stmt->execute()) {
                // Almacena un mensaje de error detallado.
                $_SESSION['error'] = "Error al actualizar contraseña: " . $stmt->error;
                // Cierra la declaración.
                $stmt->close();
                // Redirige.
                header('Location: plantillaUsers.php?vista=consultores');
                exit;
            }
            // Cierra la declaración.
            $stmt->close();
        }

        // --- Resultado Exitoso ---
        // Si todo va bien, almacena un mensaje de éxito.
        $_SESSION['success'] = "Consultor actualizado correctamente.";
    } else {
        // Si no se encontró el consultor, almacena un mensaje de error.
        $_SESSION['error'] = "Consultor no encontrado.";
    }

    // --- Redirección Final ---
    // Redirige al usuario de vuelta a la página de consultores.
    header('Location: plantillaUsers.php?vista=consultores');
    // Termina la ejecución del script.
    exit;
}