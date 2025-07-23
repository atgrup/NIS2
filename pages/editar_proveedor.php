<?php
// editar_proveedor.php
session_start();
require_once '../api/includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario (nombres exactos de tu modal)
    $id_proveedor = $_POST['id_proveedor'] ?? null;
    $correo = $_POST['correo'] ?? null; // Nombre del campo en tu modal: name="correo"
    $nombre_empresa = $_POST['nombre_empresa'] ?? null;
    $contrasena = $_POST['contrasena'] ?? ''; // Nombre del campo en tu modal: name="contrasena"

    // Validación básica
    if (!$id_proveedor || !$correo || !$nombre_empresa) {
        $_SESSION['error'] = 'Todos los campos obligatorios son requeridos';
        header('Location: plantillaUsers.php?vista=proveedores');
        exit;
    }

    // Iniciar transacción
    $conexion->begin_transaction();

    try {
        // 1. Obtener el usuario_id del proveedor
        $stmt = $conexion->prepare('SELECT usuario_id FROM proveedores WHERE id = ?');
        $stmt->bind_param('i', $id_proveedor);
        $stmt->execute();
        $stmt->bind_result($usuario_id);
        
        if (!$stmt->fetch()) {
            throw new Exception("No se encontró el proveedor");
        }
        $stmt->close();

        // 2. Actualizar la tabla proveedores (solo nombre_empresa)
        $stmt = $conexion->prepare('UPDATE proveedores SET nombre_empresa = ? WHERE id = ?');
        $stmt->bind_param('si', $nombre_empresa, $id_proveedor);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar proveedor: " . $conexion->error);
        }
        $stmt->close();

        // 3. Actualizar la tabla usuarios (correo y contraseña si se proporcionó)
        if (!empty($contrasena)) {
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare('UPDATE usuarios SET correo = ?, password = ? WHERE id_usuarios = ?');
            $stmt->bind_param('ssi', $correo, $hash, $usuario_id);
        } else {
            $stmt = $conexion->prepare('UPDATE usuarios SET correo = ? WHERE id_usuarios = ?');
            $stmt->bind_param('si', $correo, $usuario_id);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar usuario: " . $conexion->error);
        }
        $stmt->close();

        // Confirmar transacción
        $conexion->commit();
        $_SESSION['success'] = 'Proveedor actualizado correctamente';

    } catch (Exception $e) {
        // Revertir en caso de error
        $conexion->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
}

header('Location: plantillaUsers.php?vista=proveedores');
exit;
?>