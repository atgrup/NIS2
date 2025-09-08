<?php
// Inicia la sesión.
session_start();

// Incluye el archivo de conexión a la base de datos.
require_once '../api/includes/conexion.php';

// --- Procesamiento de la Solicitud GET ---
// Verifica si se ha recibido un ID a través de la URL (método GET).
if (isset($_GET['id'])) {
    // Convierte el ID a un entero para sanitizar la entrada y prevenir inyecciones SQL.
    $id = intval($_GET['id']);
    
    // --- 1. Obtener la ID de Usuario del Proveedor ---
    // Prepara una consulta para obtener la ID de usuario asociada al proveedor. Esto es necesario porque el registro del proveedor y el usuario se encuentran en tablas separadas.
    $stmt = $conexion->prepare('SELECT usuario_id FROM proveedores WHERE id = ?');
    // Vincula el ID del proveedor (`i` indica que es un entero).
    $stmt->bind_param('i', $id);
    // Ejecuta la consulta.
    $stmt->execute();
    // Vincula el resultado de la consulta a la variable `$usuario_id`.
    $stmt->bind_result($usuario_id);
    // Obtiene el resultado.
    $stmt->fetch();
    // Cierra la declaración.
    $stmt->close();

    // Si se encontró una ID de usuario válida, procede con la eliminación.
    if ($usuario_id) {
        // --- 2. Eliminar el Proveedor ---
        // Prepara una consulta para eliminar el registro de la tabla `proveedores`.
        $stmt = $conexion->prepare('DELETE FROM proveedores WHERE id = ?');
        // Vincula el ID del proveedor (`i`).
        $stmt->bind_param('i', $id);
        // Ejecuta la eliminación.
        $stmt->execute();
        // Cierra la declaración.
        $stmt->close();
        
        // --- 3. Eliminar el Usuario Asociado ---
        // Prepara una consulta para eliminar el registro de la tabla `usuarios` usando la `usuario_id` que se obtuvo previamente.
        $stmt = $conexion->prepare('DELETE FROM usuarios WHERE id_usuarios = ?');
        // Vincula la ID de usuario (`i`).
        $stmt->bind_param('i', $usuario_id);
        // Ejecuta la eliminación.
        $stmt->execute();
        // Cierra la declaración.
        $stmt->close();
    }
    
    // Almacena un mensaje de éxito en la sesión, ya sea que la operación haya eliminado el usuario o no (la verificación `if ($usuario_id)` asegura que solo se intente si se encontró).
    $_SESSION['success'] = 'Proveedor y usuario eliminados correctamente.';
    // Redirige al usuario a la página de proveedores.
    header('Location: plantillaUsers.php?vista=proveedores');
    // Termina la ejecución del script.
    exit;
}

// Si no se recibió un ID en la URL, simplemente redirige a la página de proveedores.
header('Location: plantillaUsers.php?vista=proveedores');
exit;
?>