<?php
// eliminar_usuario.php
session_start();
require_once '../api/includes/conexion.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Eliminar usuario
    $stmt = $conexion->prepare('DELETE FROM usuarios WHERE id_usuarios = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Usuario eliminado correctamente.';
    } else {
        $_SESSION['error'] = 'Error al eliminar usuario.';
    }
    $stmt->close();
    header('Location: plantillaUsers.php?vista=usuarios');
    exit;
}
header('Location: plantillaUsers.php?vista=usuarios');
exit;
