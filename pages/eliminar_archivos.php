<?php
session_start();
require_once '../api/includes/conexion.php';

if (isset($_POST['id_archivo'])) {
    $id = intval($_POST['id_archivo']);

    // Obtener la ruta del archivo
    $stmt = $conexion->prepare('SELECT archivo_url FROM archivos_subidos WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($ruta);
    if ($stmt->fetch()) {
        $stmt->close();

        // Ajustar ruta absoluta (modifica esta según tu estructura)
        $base_path = __DIR__ . '/documentos_subidos/';  // Ajusta según la ubicación del script
        $ruta_completa = realpath($base_path . $ruta);

        // Eliminar registro en la base de datos
        $stmtDel = $conexion->prepare('DELETE FROM archivos_subidos WHERE id = ?');
        $stmtDel->bind_param('i', $id);
        if ($stmtDel->execute()) {
            // Borrar archivo físico si existe
            if ($ruta_completa && file_exists($ruta_completa)) {
                if (unlink($ruta_completa)) {
                    $_SESSION['success'] = 'Archivo eliminado correctamente.';
                } else {
                    $_SESSION['error'] = 'No se pudo borrar el archivo físicamente.';
                }
            } else {
                $_SESSION['error'] = 'Archivo no encontrado en el servidor.';
            }
        } else {
            $_SESSION['error'] = 'Error al eliminar el archivo de la base de datos.';
        }
        $stmtDel->close();
    } else {
        $_SESSION['error'] = 'Archivo no encontrado en la base de datos.';
        $stmt->close();
    }

    header('Location: plantillaUsers.php?vista=archivos');
    exit;
}

$_SESSION['error'] = 'No se recibió el ID.';
header('Location: plantillaUsers.php?vista=archivos');
exit;
