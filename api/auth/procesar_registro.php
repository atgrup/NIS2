<?php
require_once '../includes/conexion.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['email'];
    $password = $_POST['password'];
    $repeat = $_POST['repeat-password'];
    $nombre_empresa = $_POST['nombre_empresa'];
    
    $tipo_usuario_id = 2; // PROVEEDOR

    if ($password !== $repeat) {
        header("Location: ../../pages/registro.php?error=contraseña");
        exit;
    }

    // Hashear contraseña
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar usuario
    $stmt = $conexion->prepare("INSERT INTO usuarios (correo, password, tipo_usuario_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $correo, $hash, $tipo_usuario_id);

    if ($stmt->execute()) {
        // Obtener id del usuario insertado
        $usuario_id = $conexion->insert_id;

        // Insertar en proveedores
        $stmt2 = $conexion->prepare("INSERT INTO proveedores (usuario_id, nombre_empresa) VALUES (?, ?)");
        $stmt2->bind_param("is", $usuario_id, $nombre_empresa);

        if ($stmt2->execute()) {
            $stmt2->close();
            $stmt->close();
            $conexion->close();
            header("Location: ../../pages/login.php?registro=ok");
            exit;
        } else {
            // Error insertando proveedor
            $stmt2->close();
            $stmt->close();
            $conexion->close();
            header("Location: ../../pages/registro.php?error=bd_proveedor");
            exit;
        }
    } else {
        // Error insertando usuario
        $stmt->close();
        $conexion->close();
        header("Location: ../../pages/registro.php?error=bd_usuario");
        exit;
    }
}
?>
