<?php
// Incluye el archivo de conexión a la base de datos.
require_once '../api/includes/conexion.php';

// Inicia la sesión.
session_start();

// --- Procesamiento de la Solicitud POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Recoge los datos del formulario
    $id_proveedor   = $_POST['id_proveedor'] ?? null;
    $correo         = $_POST['correo'] ?? null;
    $nombre_empresa = $_POST['nombre_empresa'] ?? null;
    $pais_origen    = $_POST['pais_origen'] ?? null; // 🔹 Nuevo campo
    $contrasena     = $_POST['contrasena'] ?? '';

    // --- Validación de Datos Obligatorios ---
    if (!$id_proveedor || !$correo || !$nombre_empresa) {
        $_SESSION['error'] = 'Todos los campos obligatorios son requeridos';
        header('Location: plantillaUsers.php?vista=proveedores');
        exit;
    }

    // --- Inicio de Transacción ---
    $conexion->begin_transaction();

    try {
        // 1️⃣ Obtener usuario_id del proveedor
        $stmt = $conexion->prepare('SELECT usuario_id FROM proveedores WHERE id = ?');
        $stmt->bind_param('i', $id_proveedor);
        $stmt->execute();
        $stmt->bind_result($usuario_id);

        if (!$stmt->fetch()) {
            throw new Exception("No se encontró el proveedor");
        }
        $stmt->close();

        // 2️⃣ Actualizar tabla proveedores (ahora incluye pais_origen)
        $stmt = $conexion->prepare('UPDATE proveedores SET nombre_empresa = ?, pais_origen = ? WHERE id = ?');
        $stmt->bind_param('ssi', $nombre_empresa, $pais_origen, $id_proveedor);

        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar proveedor: " . $conexion->error);
        }
        $stmt->close();

        // 3️⃣ Actualizar tabla usuarios
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

        // 4️⃣ Confirmar transacción
        $conexion->commit();
        $_SESSION['success'] = 'Proveedor actualizado correctamente';

    } catch (Exception $e) {
        // 5️⃣ Revertir en caso de error
        $conexion->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
}

// Redirigir siempre de vuelta
header('Location: plantillaUsers.php?vista=proveedores');
exit;
?>
