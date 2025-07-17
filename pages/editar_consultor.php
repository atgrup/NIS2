<?php
session_start();
require_once '../api/includes/conexion.php';

$rol = $_SESSION['rol'] ?? '';
if ($rol !== 'administrador') {
    header('Location: plantillaUsers.php?vista=consultores');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_consultor'])) {
    $consultor_id = intval($_POST['consultor_id']);
    $correo = filter_var($_POST['correo'], FILTER_SANITIZE_EMAIL);
    $contrasena = $_POST['contrasena'] ?? '';

    // Obtener usuario_id del consultor
    $stmt = $conexion->prepare("SELECT usuario_id FROM consultores WHERE id = ?");
    $stmt->bind_param("i", $consultor_id);
    $stmt->execute();
    $stmt->bind_result($usuario_id);
    $stmt->fetch();
    $stmt->close();

    if ($usuario_id) {
        // Actualizar correo en tabla usuarios
        $stmt = $conexion->prepare("UPDATE usuarios SET correo = ? WHERE id_usuarios = ?");
        $stmt->bind_param("si", $correo, $usuario_id);
        $stmt->execute();
        $stmt->close();

        // Actualizar contraseña si viene (hashearla)
        if (!empty($contrasena)) {
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("UPDATE usuarios SET contrasena = ? WHERE id_usuarios = ?");
            $stmt->bind_param("si", $hash, $usuario_id);
            $stmt->execute();
            $stmt->close();
        }

        $_SESSION['success'] = "Consultor actualizado correctamente.";
    } else {
        $_SESSION['error'] = "Consultor no encontrado.";
    }
}

header('Location: plantillaUsers.php?vista=consultores');
exit;
