<?php
require 'conexion.php'; // tu conexión a la base de datos

// Solo procesa si la petición es POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoge datos del formulario
    $correo = $_POST['email'];
    $password = $_POST['password'];
    $repeat = $_POST['repeat-password'];
    $nombre_empresa = $_POST['nombre_empresa'];
    
    $tipo_usuario_id = 2; // ID fijo para "PROVEEDOR"

    // Validación: contraseñas deben coincidir
    if ($password !== $repeat) {
        header("Location: ../../pages/registro.php?error=contraseña");
        exit;
    }

    // Verificar si el correo ya existe en la tabla `usuarios`
    $stmt_check = $conexion->prepare("SELECT id_usuarios FROM usuarios WHERE correo = ? LIMIT 1");
    $stmt_check->bind_param("s", $correo);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // Si ya existe, cierra conexiones y redirige con error
        $stmt_check->close();
        $conexion->close();
        header("Location: ../../pages/registro.php?error=correo");
        exit;
    }

    // Hashear la contraseña de manera segura
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar usuario en tabla `usuarios`
    $stmt = $conexion->prepare("INSERT INTO usuarios (correo, password, tipo_usuario_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $correo, $hash, $tipo_usuario_id);

    // Insertar el usuario con email no verificado
    $stmt = $conn->prepare("INSERT INTO usuarios (email, nombre_empresa, password, email_verified, verification_code) VALUES (?, ?, ?, 0, ?)");
    $stmt->bind_param("ssss", $email, $nombre_empresa, $password_hash, $verification_code);
    if ($stmt->execute()) {
        // Obtener el ID del usuario recién insertado
        $usuario_id = $conexion->insert_id;

        // Insertar en la tabla `proveedores` vinculada con el usuario
        $stmt2 = $conexion->prepare("INSERT INTO proveedores (usuario_id, nombre_empresa) VALUES (?, ?)");
        $stmt2->bind_param("is", $usuario_id, $nombre_empresa);

        if ($stmt2->execute()) {
            // Registro exitoso
            $stmt2->close();
            $stmt->close();
            $conexion->close();
            header("Location: ../../pages/login.php?registro=ok");
            exit;
        } else {
            // Error al insertar en `proveedores`
            $stmt2->close();
            $stmt->close();
            $conexion->close();
            header("Location: ../../pages/registro.php?error=bd_proveedor");
            exit;
        }
    } else {
        // Error al insertar en `usuarios`
        $stmt->close();
        $conexion->close();
        header("Location: ../../pages/registro.php?error=bd_usuario");
        exit;
    }
} else {
    // Acceso directo no permitido
    header("Location: ../../registro.php");
    exit;
}
?>
