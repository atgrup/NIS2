<?php
session_start();
require_once '../api/includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo']);
    $contrasena = $_POST['contrasena'];
    $contrasena2 = $_POST['contrasena2'];

    if ($contrasena !== $contrasena2) {
        $_SESSION['error'] = "Las contraseñas no coinciden.";
        header("Location: plantillaUsers.php?vista=usuarios");
        exit;
    }

    $stmt = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $_SESSION['error'] = "El correo ya está registrado.";
        header("Location: plantillaUsers.php?vista=usuarios");
        exit;
    }

    $password_hash = password_hash($contrasena, PASSWORD_DEFAULT);

    $stmt = $conexion->prepare("INSERT INTO usuarios (correo, password, tipo_usuario_id, verificado) VALUES (?, ?, 1, 0)");
    $stmt->bind_param("ss", $correo, $password_hash);

    if ($stmt->execute()) {
        $stmt->close();
        $_SESSION['success'] = "Usuario creado correctamente.";
        header("Location: plantillaUsers.php?vista=usuarios");
        exit;
    } else {
        $stmt->close();
        $_SESSION['error'] = "Error al crear el usuario.";
        header("Location: plantillaUsers.php?vista=usuarios");
        exit;
    }
} else {
    header("Location: plantillaUsers.php");
    exit;
}
