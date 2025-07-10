<?php
session_start();
// Validar que sea admin para crear usuarios
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'ADMINISTRADOR') {
    http_response_code(403);
    echo "No autorizado.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = filter_var($_POST['correo'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if (!$correo || strlen($password) < 6) {
        echo "Datos inválidos";
        exit;
    }

    $tipo_usuario_id = 3; // ID fijo para Consultor

    // Hashear la contraseña
    $hash_password = password_hash($password, PASSWORD_DEFAULT);

    // Conexión a la base de datos
    $conexion = new mysqli('jordio35.sg-host.com', 'u74bscuknwn9n', 'ad123456-', 'dbs1il8vaitgwc');
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    // Insertar usuario
    $stmt = $conexion->prepare("INSERT INTO usuarios (correo, password, tipo_usuario_id, verificado) VALUES (?, ?, ?, 0)");
    $stmt->bind_param("ssi", $correo, $hash_password, $tipo_usuario_id);
    if ($stmt->execute()) {
        $nuevo_usuario_id = $stmt->insert_id;

        // Crear entrada en tabla consultores relacionada
        $stmt2 = $conexion->prepare("INSERT INTO consultores (usuario_id, nombre) VALUES (?, ?)");
        $nombre = explode('@', $correo)[0];
        $stmt2->bind_param("is", $nuevo_usuario_id, $nombre);
        $stmt2->execute();
        $stmt2->close();

        $stmt->close();
        $conexion->close();

        // Redirigir o mostrar mensaje
        header("Location: admin_panel.php?msg=usuario_creado");
        exit;
    } else {
        echo "Error al crear usuario: " . $stmt->error;
    }
} else {
    echo "Método no permitido";
}
?>
