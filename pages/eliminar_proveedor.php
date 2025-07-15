<?php
// eliminar_proveedor.php
session_start();
require_once '../api/includes/conexion.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Obtener usuario_id
    $stmt = $conexion->prepare('SELECT usuario_id FROM proveedores WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($usuario_id);
    $stmt->fetch();
    $stmt->close();

    // Eliminar proveedor
    $stmt = $conexion->prepare('DELETE FROM proveedores WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    // Eliminar usuario
    if ($usuario_id) {
        $stmt = $conexion->prepare('DELETE FROM usuarios WHERE id_usuarios = ?');
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $stmt->close();
    }
    $_SESSION['success'] = 'Proveedor y usuario eliminados correctamente.';
    header('Location: plantillaUsers.php?vista=proveedores');
    exit;
}
header('Location: plantillaUsers.php?vista=proveedores');
exit;
    $id = intval($_GET['id']);
    // Obtener el usuario_id asociado al proveedor
    $stmt = $conexion->prepare('SELECT usuario_id FROM proveedores WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($usuario_id);
    $stmt->fetch();
    $stmt->close();

    if ($usuario_id) {
        // Eliminar proveedor
        $stmt = $conexion->prepare('DELETE FROM proveedores WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        // Eliminar usuario
        $stmt = $conexion->prepare('DELETE FROM usuarios WHERE id_usuarios = ?');
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success'] = 'Proveedor y usuario eliminados correctamente.';
    }
    header('Location: plantillaUsers.php?vista=proveedores');
    exit;
