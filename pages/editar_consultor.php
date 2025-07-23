<?php
session_start();
require_once '../api/includes/conexion.php';

$rol = $_SESSION['rol'] ?? '';
if (strtolower($rol) !== 'administrador') {
    header('Location: plantillaUsers.php?vista=consultores');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_consultor'])) {
    $consultor_id = intval($_POST['consultor_id']);
    $correo = filter_var($_POST['correo'], FILTER_SANITIZE_EMAIL);
    $contrasena = $_POST['contrasena'] ?? '';

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Correo inválido";
        header('Location: plantillaUsers.php?vista=consultores');
        exit;
    }

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
        if (!$stmt->execute()) {
            $_SESSION['error'] = "Error al actualizar correo: " . $stmt->error;
            $stmt->close();
            header('Location: plantillaUsers.php?vista=consultores');
            exit;
        }
        $stmt->close();

        // Actualizar contraseña si se proporciona (y se hashea)
        if (!empty($contrasena)) {
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("UPDATE usuarios SET password = ? WHERE id_usuarios = ?");
            $stmt->bind_param("si", $hash, $usuario_id);
            if (!$stmt->execute()) {
                $_SESSION['error'] = "Error al actualizar contraseña: " . $stmt->error;
                $stmt->close();
                header('Location: plantillaUsers.php?vista=consultores');
                exit;
            }
            $stmt->close();
        }

        $_SESSION['success'] = "Consultor actualizado correctamente.";
    } else {
        $_SESSION['error'] = "Consultor no encontrado.";
    }

    header('Location: plantillaUsers.php?vista=consultores');
    exit;
}
