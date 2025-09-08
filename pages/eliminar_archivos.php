<?php
// Inicia la sesión.
session_start();

// Incluye el archivo de conexión a la base de datos.
require_once '../api/includes/conexion.php';

// --- Procesamiento de la Solicitud POST ---
// Verifica si se ha recibido un valor para 'id_archivo' a través de POST.
if (isset($_POST['id_archivo'])) {
    // Convierte el ID a un entero para asegurar su tipo y prevenir inyecciones SQL.
    $id = intval($_POST['id_archivo']);

    // --- Obtener la Ruta del Archivo desde la Base de Datos ---
    // Prepara una consulta para seleccionar la URL (ruta) del archivo a eliminar, basándose en su ID.
    $stmt = $conexion->prepare('SELECT archivo_url FROM archivos_subidos WHERE id = ?');
    // Vincula el ID a la consulta. 'i' indica que es un entero.
    $stmt->bind_param('i', $id);
    // Ejecuta la consulta.
    $stmt->execute();
    // Vincula el resultado de la consulta a la variable `$ruta`.
    $stmt->bind_result($ruta);
    
    // Si se encuentra un registro en la base de datos...
    if ($stmt->fetch()) {
        // Cierra la declaración para liberar recursos.
        $stmt->close();

        // --- Preparar la Ruta Absoluta del Archivo Físico ---
        // Define la ruta base donde se guardan los archivos. `__DIR__` es la constante del directorio del script actual. Debes asegurarte de que esta ruta sea la correcta.
        $base_path = __DIR__ . '/documentos_subidos/';
        // Concatena la ruta base con la ruta del archivo de la base de datos y usa `realpath()` para obtener la ruta absoluta y verificar su existencia.
        $ruta_completa = realpath($base_path . $ruta);

        // --- Eliminar el Registro de la Base de Datos ---
        // Prepara una consulta para eliminar el registro del archivo de la tabla `archivos_subidos`.
        $stmtDel = $conexion->prepare('DELETE FROM archivos_subidos WHERE id = ?');
        // Vincula el ID del archivo.
        $stmtDel->bind_param('i', $id);
        
        // Si la eliminación del registro en la base de datos es exitosa...
        if ($stmtDel->execute()) {
            // --- Eliminar el Archivo Físico ---
            // Comprueba si la ruta completa del archivo es válida y si el archivo existe en el servidor.
            if ($ruta_completa && file_exists($ruta_completa)) {
                // Si el archivo existe, intenta eliminarlo con `unlink()`.
                if (unlink($ruta_completa)) {
                    // Si se elimina correctamente, almacena un mensaje de éxito.
                    $_SESSION['success'] = 'Archivo eliminado correctamente.';
                } else {
                    // Si falla la eliminación física, almacena un mensaje de error.
                    $_SESSION['error'] = 'No se pudo borrar el archivo físicamente.';
                }
            } else {
                // Si el archivo no se encuentra en el servidor, almacena un mensaje de error.
                $_SESSION['error'] = 'Archivo no encontrado en el servidor.';
            }
        } else {
            // Si falla la eliminación del registro en la base de datos, almacena un error.
            $_SESSION['error'] = 'Error al eliminar el archivo de la base de datos.';
        }
        // Cierra la declaración de eliminación.
        $stmtDel->close();
    } else {
        // Si el archivo no se encuentra en la base de datos, almacena un error.
        $_SESSION['error'] = 'Archivo no encontrado en la base de datos.';
        // Cierra la declaración inicial.
        $stmt->close();
    }

    // Redirige al usuario de vuelta a la página de archivos.
    header('Location: plantillaUsers.php?vista=archivos');
    // Termina la ejecución.
    exit;
}

// Si la solicitud no contenía la ID del archivo, almacena un error.
$_SESSION['error'] = 'No se recibió el ID.';
// Redirige al usuario.
header('Location: plantillaUsers.php?vista=archivos');
// Termina la ejecución.
exit;