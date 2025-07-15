<?php
include '../api/includes/conexion.php'; // Asegúrate de que la ruta es correcta

if (!isset($conexion)) {
    die(json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (empty($_POST['correo']) || empty($_POST['password'])) {
        echo json_encode(['success' => false, 'error' => 'Correo o contraseña vacíos']);
        exit;
    }

    $correo = $_POST['correo'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $conexion->prepare("INSERT INTO usuarios (correo, password, tipo_usuario_id) VALUES (?, ?, 1)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => $conexion->error]);
        exit;
    }

    $stmt->bind_param("ss", $correo, $password);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
}
?>

