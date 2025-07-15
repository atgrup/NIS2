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

            // Obtener id_tipo_usuario para consultor
            $stmt = $conexion->prepare("SELECT id_tipo_usuario FROM tipo_usuario WHERE nombre = ?");
            $tipo_nombre = 'consultor';
            $stmt->bind_param('s', $tipo_nombre);
            $stmt->execute();
            $stmt->bind_result($tipo_usuario_id);
            $stmt->fetch();
            $stmt->close();

            if (!$tipo_usuario_id) {
                $_SESSION['error'] = "Tipo de usuario consultor no encontrado en la base de datos.";
                header('Location: plantillasUsers.php?vista=consultores');
                exit;
            }

            // Insertar usuario
            $stmt = $conexion->prepare("INSERT INTO usuarios (correo, password, tipo_usuario_id) VALUES (?, ?, ?)");
            $stmt->bind_param('ssi', $correo, $hash, $tipo_usuario_id);

            if ($stmt->execute()) {
                $usuario_id = $stmt->insert_id;
                $stmt->close();

                // Insert en consultores
                $stmt2 = $conexion->prepare("INSERT INTO consultores (usuario_id) VALUES (?)");
                $stmt2->bind_param('i', $usuario_id);

                if ($stmt2->execute()) {
                    $_SESSION['mensaje'] = "Consultor creado correctamente";
                } else {
                    $_SESSION['error'] = "Error al crear consultor: " . $conexion->error;
                }
                $stmt2->close();
            } else {
                $_SESSION['error'] = "Error al crear usuario consultor: " . $conexion->error;
            }
        }
    }
}

header('Location: plantillasUsers.php?vista=consultores');
exit;

?>
