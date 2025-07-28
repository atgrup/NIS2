<?php
// editar_usuario.php
session_start();
require_once '../api/includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_usuarios'] ?? null;
    $correo = $_POST['correo'] ?? null;
    $tipo_usuario = $_POST['tipo_usuario'] ?? null;
    $nuevaContrasena = $_POST['contrasena'] ?? '';

    if ($id && $correo && $tipo_usuario) {
        // Buscar el id_tipo_usuario por nombre
        $stmtTipo = $conexion->prepare('SELECT id_tipo_usuario FROM tipo_usuario WHERE nombre = ?');
        $stmtTipo->bind_param('s', $tipo_usuario);
        $stmtTipo->execute();
        $stmtTipo->bind_result($tipoId);
        $stmtTipo->fetch();
        $stmtTipo->close();

        if ($tipoId) {
            if (!empty($nuevaContrasena)) {
                $hash = password_hash($nuevaContrasena, PASSWORD_DEFAULT);
                $stmt = $conexion->prepare('UPDATE usuarios SET correo = ?, tipo_usuario_id = ?, password = ? WHERE id_usuarios = ?');
                $stmt->bind_param('siss', $correo, $tipoId, $hash, $id);
            } else {
                $stmt = $conexion->prepare('UPDATE usuarios SET correo = ?, tipo_usuario_id = ? WHERE id_usuarios = ?');
                $stmt->bind_param('sii', $correo, $tipoId, $id);
            }
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = 'Usuario modificado correctamente.';
        } else {
            $_SESSION['error'] = 'Tipo de usuario no válido.';
        }
    } else {
        $_SESSION['error'] = 'Datos incompletos.';
    }
    header('Location: plantillaUsers.php?vista=usuarios');
    exit;
}
header('Location: plantillaUsers.php?vista=usuarios');
exit;
