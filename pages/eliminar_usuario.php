<?php
// Inicia la sesión.
session_start();

// Incluye el archivo de conexión a la base de datos.
require_once '../api/includes/conexion.php';

// --- Procesamiento de la Solicitud GET ---
// Verifica si se ha recibido un ID a través de la URL.
if (isset($_GET['id'])) {
    // Convierte el ID a un entero para asegurar su tipo y prevenir inyecciones SQL.
    $id = intval($_GET['id']);
    
    // --- Lógica de Eliminación ---
    // Prepara una consulta para eliminar el registro de la tabla `usuarios` basándose en el ID recibido.
    $stmt = $conexion->prepare('DELETE FROM usuarios WHERE id_usuarios = ?');
    // Vincula el ID a la consulta. 'i' indica que el parámetro es un entero.
    $stmt->bind_param('i', $id);
    
    // Ejecuta la consulta y verifica si fue exitosa.
    if ($stmt->execute()) {
        // Si la eliminación fue exitosa, almacena un mensaje de éxito en la sesión.
        $_SESSION['success'] = 'Usuario eliminado correctamente.';
    } else {
        // Si la eliminación falló, almacena un mensaje de error en la sesión.
        $_SESSION['error'] = 'Error al eliminar usuario.';
    }
    
    // Cierra la declaración para liberar recursos.
    $stmt->close();
    
    // Redirige al usuario de vuelta a la página de gestión de usuarios.
    header('Location: plantillaUsers.php?vista=usuarios');
    // Termina la ejecución del script.
    exit;
}

// Si no se recibió un ID en la URL, simplemente redirige a la página de usuarios.
header('Location: plantillaUsers.php?vista=usuarios');
exit;