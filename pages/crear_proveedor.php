<?php
session_start();
include '../api/includes/conexion.php'; // Asegúrate de que la ruta es correcta

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $repeat = $_POST['repeat-password'] ?? '';
    $nombre_empresa = $_POST['nombre_empresa'] ?? '';

    $tipo_usuario_id = 2; // ID tipo proveedor

    if (empty($correo) || empty($password) || empty($repeat) || empty($nombre_empresa)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
        exit;
    }

    if ($password !== $repeat) {
        echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar usuario
    $stmt = $conexion->prepare("INSERT INTO usuarios (correo, password, tipo_usuario_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $correo, $hash, $tipo_usuario_id);

    if ($stmt->execute()) {
        $usuario_id = $conexion->insert_id;

        $stmt2 = $conexion->prepare("INSERT INTO proveedores (usuario_id, nombre_empresa) VALUES (?, ?)");
        $stmt2->bind_param("is", $usuario_id, $nombre_empresa);
        if ($stmt2->execute()) {
            echo json_encode(['success' => true]);
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
echo json_encode(['success' => false, 'message' => 'Petición no válida']);
exit;

