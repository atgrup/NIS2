<?php
// Incluye el archivo de conexión a la base de datos.
require_once '../api/includes/conexion.php';

// Inicia la sesión.
session_start();

// --- Procesamiento de la Solicitud POST ---
// Verifica si la solicitud HTTP es de tipo POST, lo que indica que se ha enviado un formulario.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoge los datos del formulario, usando el operador de fusión de null `??` para evitar errores si no se encuentran.
    // `id`: El ID del usuario que se va a editar.
    $id = $_POST['id_usuarios'] ?? null;
    // `correo`: El nuevo correo electrónico del usuario.
    $correo = $_POST['correo'] ?? null;
    // `tipo_usuario`: El nuevo tipo de usuario (por ejemplo, 'Administrador').
    $tipo_usuario = $_POST['tipo_usuario'] ?? null;
    // `nuevaContrasena`: La nueva contraseña (opcional).
    $nuevaContrasena = $_POST['contrasena'] ?? '';

    // --- Validación de Datos Obligatorios ---
    // Comprueba si los campos clave están vacíos. Si es así, se considera un error.
    if ($id && $correo && $tipo_usuario) {
        // --- Obtener ID del Tipo de Usuario ---
        // Prepara una consulta para buscar la ID del tipo de usuario (`id_tipo_usuario`) en la tabla `tipo_usuario` basándose en el nombre recibido del formulario.
        $stmtTipo = $conexion->prepare('SELECT id_tipo_usuario FROM tipo_usuario WHERE nombre = ?');
        // Vincula el nombre del tipo de usuario (`s` de string).
        $stmtTipo->bind_param('s', $tipo_usuario);
        // Ejecuta la consulta.
        $stmtTipo->execute();
        // Vincula el resultado de la consulta a la variable `$tipoId`.
        $stmtTipo->bind_result($tipoId);
        // Obtiene el resultado.
        $stmtTipo->fetch();
        // Cierra la declaración.
        $stmtTipo->close();

        // Si se encuentra una ID de tipo de usuario válida...
        if ($tipoId) {
            // --- Actualización de Usuario ---
            // Comprueba si se ha proporcionado una nueva contraseña.
            if (!empty($nuevaContrasena)) {
                // Si hay contraseña, la hashea para seguridad.
                $hash = password_hash($nuevaContrasena, PASSWORD_DEFAULT);
                // Prepara una consulta para actualizar el correo, el tipo de usuario y la contraseña.
                $stmt = $conexion->prepare('UPDATE usuarios SET correo = ?, tipo_usuario_id = ?, password = ? WHERE id_usuarios = ?');
                // Vincula los valores: `s` (correo), `i` (tipo de ID), `s` (hash), `i` (ID de usuario).
                $stmt->bind_param('siss', $correo, $tipoId, $hash, $id);
            } else {
                // Si no hay contraseña, solo actualiza el correo y el tipo de usuario.
                $stmt = $conexion->prepare('UPDATE usuarios SET correo = ?, tipo_usuario_id = ? WHERE id_usuarios = ?');
                // Vincula los valores: `s` (correo), `i` (tipo de ID), `i` (ID de usuario).
                $stmt->bind_param('sii', $correo, $tipoId, $id);
            }
            
            // Ejecuta la consulta de actualización.
            $stmt->execute();
            // Cierra la declaración.
            $stmt->close();
            // Almacena un mensaje de éxito en la sesión.
            $_SESSION['success'] = 'Usuario modificado correctamente.';

        } else {
            // Si la ID de tipo de usuario no se encontró, almacena un error.
            $_SESSION['error'] = 'Tipo de usuario no válido.';
        }
    } else {
        // Si los datos obligatorios no se recibieron, almacena un error.
        $_SESSION['error'] = 'Datos incompletos.';
    }
    
    // Redirige al usuario de vuelta a la página de usuarios.
    header('Location: plantillaUsers.php?vista=usuarios');
    // Termina la ejecución del script.
    exit;
}

// Si la página se accede sin un método POST, redirige al usuario a la página de usuarios.
header('Location: plantillaUsers.php?vista=usuarios');
exit;