<?php
// editar_proveedor.php
session_start();
require_once '../api/includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_proveedor'] ?? null;
    $correo = $_POST['correo'] ?? null;
    $nombre_empresa = $_POST['nombre_empresa'] ?? null;
    $nuevaContrasena = $_POST['contrasena'] ?? '';


    if ($id && $correo && $nombre_empresa) {
        // Obtener usuario_id
        $stmt = $conexion->prepare('SELECT usuario_id FROM proveedores WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($usuario_id);
        $stmt->fetch();
        $stmt->close();

        // Actualizar proveedor (solo nombre_empresa, no existe columna correo)
        $stmt = $conexion->prepare('UPDATE proveedores SET nombre_empresa = ? WHERE id = ?');
        $stmt->bind_param('si', $nombre_empresa, $id);
        $stmt->execute();
        $stmt->close();

        // Actualizar usuario
        if (!empty($nuevaContrasena)) {
            $hash = password_hash($nuevaContrasena, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare('UPDATE usuarios SET correo = ?, contrasena = ? WHERE id_usuarios = ?');
            $stmt->bind_param('ssi', $correo, $hash, $usuario_id);
        } else {
            $stmt = $conexion->prepare('UPDATE usuarios SET correo = ? WHERE id_usuarios = ?');
            $stmt->bind_param('si', $correo, $usuario_id);
        }
        $stmt->execute();
        $stmt->close();

        $_SESSION['success'] = 'Proveedor modificado correctamente.';
    } else {
        $_SESSION['error'] = 'Datos incompletos.';
    }
    header('Location: plantillaUsers.php?vista=proveedores');
    exit;
}
header('Location: plantillaUsers.php?vista=proveedores');
exit;

