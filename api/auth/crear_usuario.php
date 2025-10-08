<?php
session_start();
// Validar que sea admin para crear usuarios
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'ADMINISTRADOR') {
    http_response_code(403);
    echo "No autorizado.";
    exit;
}

// Verifica que el método de la petición sea POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Filtra y valida que el correo tenga formato válido
    $correo = filter_var($_POST['correo'], FILTER_VALIDATE_EMAIL);
    // Obtiene la contraseña sin validación especial (solo se usará más adelante)
    $password = $_POST['password'];

    // Verifica que el correo sea válido y la contraseña tenga al menos 6 caracteres
    if (!$correo || strlen($password) < 6) {
        echo "Datos inválidos";
        exit;
    }

    // Tipo de usuario fijo: 3 (Consultor)
    $tipo_usuario_id = 3;

    // Hashea la contraseña de forma segura usando el algoritmo por defecto (bcrypt)
    $hash_password = password_hash($password, PASSWORD_DEFAULT);

    // Conexión a la base de datos (⚠️ las credenciales están escritas en el código, poco seguro)
    $conexion = new mysqli('jordio35.sg-host.com', 'u74bscuknwn9n', 'ad123456-', 'dbs1il8vaitgwc');
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    // Inserta el nuevo usuario en la tabla `usuarios`
    $stmt = $conexion->prepare("INSERT INTO usuarios (correo, password, tipo_usuario_id, verificado) VALUES (?, ?, ?, 0)");
    $stmt->bind_param("ssi", $correo, $hash_password, $tipo_usuario_id);
    
    if ($stmt->execute()) {
        // Obtiene el ID autogenerado del nuevo usuario
        $nuevo_usuario_id = $stmt->insert_id;

        // Crea el registro relacionado en la tabla `consultores`
        $stmt2 = $conexion->prepare("INSERT INTO consultores (usuario_id, nombre) VALUES (?, ?)");
        // Usa como nombre la parte del correo antes del @
        $nombre = explode('@', $correo)[0];
        $stmt2->bind_param("is", $nuevo_usuario_id, $nombre);
        $stmt2->execute();
        $stmt2->close();

        // Cierra los statement y la conexión
        $stmt->close();
        $conexion->close();

        // Redirige al panel de admin con un mensaje de éxito
        header("Location: admin_panel.php?msg=usuario_creado");
        exit;
    } else {
        // Si falla la inserción, muestra el error
        echo "Error al crear usuario: " . $stmt->error;
    }
} else {
    // Si no es POST, devuelve error
    echo "Método no permitido";
}
?>
