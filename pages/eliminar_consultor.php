<?php
session_start();
require_once '../api/includes/conexion.php';

$rol = $_SESSION['rol'] ?? '';
if ($rol !== 'administrador') {
    header('Location: plantillaUsers.php?vista=consultores');
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Obtener usuario_id del consultor
    $stmt = $conexion->prepare("SELECT usuario_id FROM consultores WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($usuario_id);
    $stmt->fetch();
    $stmt->close();

    if ($usuario_id) {
        // Eliminar consultor
        $stmt = $conexion->prepare("DELETE FROM consultores WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Eliminar usuario asociado
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id_usuarios = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success'] = "Consultor y usuario eliminados correctamente.";
    } else {
        $_SESSION['error'] = "Consultor no encontrado.";
    }
}

header('Location: plantillaUsers.php?vista=consultores');
exit;
