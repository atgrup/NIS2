<?php
session_start();
include '../api/includes/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['email'] ?? '';
    $nombre_empresa = $_POST['nombre_empresa'] ?? '';
    $password = $_POST['password'] ?? '';
    $repeat = $_POST['repeat-password'] ?? '';

    if (!$correo || !$nombre_empresa || !$password || !$repeat) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
        exit;
    }
    if ($password !== $repeat) {
        echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
        exit;
    }

    // Aquí tu código para insertar usuario y proveedor

    // Hash password
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $tipo_usuario_id = 2; // proveedor

    $stmt = $conexion->prepare("SELECT id_usuarios FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Correo ya registrado']);
        exit;
    }
    $stmt->close();

    $stmt = $conexion->prepare("INSERT INTO usuarios (correo, password, tipo_usuario_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $correo, $hash, $tipo_usuario_id);

    if ($stmt->execute()) {
        $usuario_id = $conexion->insert_id;
        $stmt->close();

        $stmt2 = $conexion->prepare("INSERT INTO proveedores (usuario_id, nombre_empresa) VALUES (?, ?)");
        $stmt2->bind_param("is", $usuario_id, $nombre_empresa);

        if ($stmt2->execute()) {
            $stmt2->close();
            echo json_encode(['success' => true, 'message' => 'Proveedor creado correctamente']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear proveedor']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear usuario']);
        exit;
    }
}
