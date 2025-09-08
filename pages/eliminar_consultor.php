<?php
// Inicia la sesión. Esto permite acceder y modificar variables de sesión.
session_start();

// Incluye el archivo de conexión a la base de datos, que contiene la variable $conexion.
require_once '../api/includes/conexion.php';

// --- Verificación de Permisos ---
// Obtiene el rol del usuario de la sesión. Usa el operador de fusión de null `?? ''` para evitar errores si la variable no existe.
$rol = $_SESSION['rol'] ?? '';
// Comprueba si el rol del usuario no es 'administrador'. Si no es un administrador, no tiene permiso para eliminar consultores.
if ($rol !== 'administrador') {
    // Redirige al usuario de vuelta a la página de consultores.
    header('Location: plantillaUsers.php?vista=consultores');
    // Termina la ejecución del script para que no se ejecute más código.
    exit;
}

// --- Procesamiento de la Solicitud GET ---
// Verifica si se ha recibido un parámetro 'id' en la URL a través del método GET.
if (isset($_GET['id'])) {
    // Convierte el ID a un entero usando `intval()` para sanitizar la entrada y prevenir inyecciones SQL.
    $id = intval($_GET['id']);
    
    // --- Obtener el `usuario_id` del consultor ---
    // Prepara una consulta SQL para seleccionar el `usuario_id` de la tabla `consultores` basándose en el ID del consultor recibido. Esto es necesario para poder eliminar el usuario asociado.
    $stmt = $conexion->prepare("SELECT usuario_id FROM consultores WHERE id = ?");
    // Vincula el ID del consultor a la consulta. 'i' indica que el parámetro es un entero.
    $stmt->bind_param("i", $id);
    // Ejecuta la consulta.
    $stmt->execute();
    // Vincula el resultado de la consulta a la variable `$usuario_id`.
    $stmt->bind_result($usuario_id);
    // Obtiene el resultado de la consulta.
    $stmt->fetch();
    // Cierra la declaración para liberar recursos.
    $stmt->close();

    // Comprueba si se encontró un `usuario_id` válido. Si es así, procede con la eliminación.
    if ($usuario_id) {
        // --- Eliminar el registro del consultor ---
        // Prepara una consulta para eliminar la fila de la tabla `consultores`.
        $stmt = $conexion->prepare("DELETE FROM consultores WHERE id = ?");
        // Vincula el ID del consultor a la consulta.
        $stmt->bind_param("i", $id);
        // Ejecuta la eliminación.
        $stmt->execute();
        // Cierra la declaración.
        $stmt->close();

        // --- Eliminar el usuario asociado ---
        // Prepara una consulta para eliminar la fila correspondiente en la tabla `usuarios` usando el `usuario_id` obtenido.
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id_usuarios = ?");
        // Vincula el `usuario_id` a la consulta.
        $stmt->bind_param("i", $usuario_id);
        // Ejecuta la eliminación.
        $stmt->execute();
        // Cierra la declaración.
        $stmt->close();

        // Si ambas eliminaciones fueron exitosas, se almacena un mensaje de éxito en la sesión.
        $_SESSION['success'] = "Consultor y usuario eliminados correctamente.";
    } else {
        // Si no se encontró el consultor con el ID proporcionado, almacena un mensaje de error.
        $_SESSION['error'] = "Consultor no encontrado.";
    }
}

// Redirige al usuario de vuelta a la página de consultores, independientemente de si la operación tuvo éxito o no.
header('Location: plantillaUsers.php?vista=consultores');
// Termina la ejecución del script.
exit;
?>