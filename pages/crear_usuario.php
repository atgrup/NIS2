<?php
session_start();
include '../api/includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $contrasena2 = $_POST['contrasena2'] ?? '';

    if (!$correo || !$contrasena || !$contrasena2) {
        $_SESSION['error'] = "Todos los campos son obligatorios";
    } elseif ($contrasena !== $contrasena2) {
        $_SESSION['error'] = "Las contraseñas no coinciden";
    } else {
        // Verificar si correo ya existe
        $stmt = $conexion->prepare("SELECT id_usuarios FROM usuarios WHERE correo = ?");
        $stmt->bind_param('s', $correo);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $_SESSION['error'] = "El correo ya está registrado";
            $stmt->close();
        } else {
            $stmt->close();
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);

            $stmt = $conexion->prepare("INSERT INTO usuarios (correo, password, rol) VALUES (?, ?, 'usuario')");
            $stmt->bind_param('ss', $correo, $hash);

            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Usuario creado correctamente";
            } else {
                $_SESSION['error'] = "Error al crear usuario: " . $conexion->error;
            }
            $stmt->close();
        }
    }
}

header('Location: plantillasUsers.php?vista=usuarios');
exit;
