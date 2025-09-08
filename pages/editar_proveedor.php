<?php
// Incluye el archivo de conexión a la base de datos.
require_once '../api/includes/conexion.php';

// Inicia la sesión.
session_start();

// --- Procesamiento de la Solicitud POST ---
// Verifica que la solicitud HTTP sea de tipo POST, lo que indica que se ha enviado un formulario.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoge los datos del formulario, usando el operador de fusión de null `??` para evitar errores si no se encuentran.
    // `id_proveedor`: ID del proveedor que se va a editar.
    $id_proveedor = $_POST['id_proveedor'] ?? null;
    // `correo`: Nuevo correo electrónico del proveedor.
    $correo = $_POST['correo'] ?? null;
    // `nombre_empresa`: Nuevo nombre de la empresa del proveedor.
    $nombre_empresa = $_POST['nombre_empresa'] ?? null;
    // `contrasena`: Nueva contraseña (opcional).
    $contrasena = $_POST['contrasena'] ?? '';

    // --- Validación de Datos Obligatorios ---
    // Comprueba si los campos clave están vacíos. Si es así, se considera un error.
    if (!$id_proveedor || !$correo || !$nombre_empresa) {
        // Almacena un mensaje de error en la sesión.
        $_SESSION['error'] = 'Todos los campos obligatorios son requeridos';
        // Redirige a la página de proveedores.
        header('Location: plantillaUsers.php?vista=proveedores');
        // Termina la ejecución.
        exit;
    }

    // --- Inicio de Transacción de la Base de Datos ---
    // Inicia una transacción. Esto garantiza que todas las operaciones (actualizar `proveedores` y `usuarios`) se realicen o, si algo falla, se reviertan por completo.
    $conexion->begin_transaction();

    // Utiliza un bloque `try...catch` para manejar errores de la transacción de forma segura.
    try {
        // 1. Obtener el `usuario_id` del proveedor.
        // Prepara una consulta para buscar la `usuario_id` en la tabla `proveedores` usando la ID del proveedor.
        $stmt = $conexion->prepare('SELECT usuario_id FROM proveedores WHERE id = ?');
        // Vincula el ID del proveedor (`i` de entero).
        $stmt->bind_param('i', $id_proveedor);
        // Ejecuta la consulta.
        $stmt->execute();
        // Vincula el resultado de la consulta a la variable `$usuario_id`.
        $stmt->bind_result($usuario_id);
        
        // Si no se encuentra ningún resultado, lanza una excepción.
        if (!$stmt->fetch()) {
            throw new Exception("No se encontró el proveedor");
        }
        // Cierra la declaración.
        $stmt->close();

        // 2. Actualizar la tabla `proveedores`.
        // Prepara la consulta para actualizar el `nombre_empresa` del proveedor.
        $stmt = $conexion->prepare('UPDATE proveedores SET nombre_empresa = ? WHERE id = ?');
        // Vincula el nombre de la empresa (`s` de string) y el ID del proveedor (`i`).
        $stmt->bind_param('si', $nombre_empresa, $id_proveedor);
        
        // Si la ejecución falla, lanza una excepción.
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar proveedor: " . $conexion->error);
        }
        // Cierra la declaración.
        $stmt->close();

        // 3. Actualizar la tabla `usuarios`.
        // Comprueba si se ha proporcionado una nueva contraseña.
        if (!empty($contrasena)) {
            // Si hay contraseña, la hashea para seguridad.
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            // Prepara una consulta para actualizar el correo y la contraseña.
            $stmt = $conexion->prepare('UPDATE usuarios SET correo = ?, password = ? WHERE id_usuarios = ?');
            // Vincula el correo (`s`), el hash de la contraseña (`s`) y el `usuario_id` (`i`).
            $stmt->bind_param('ssi', $correo, $hash, $usuario_id);
        } else {
            // Si no hay contraseña, solo actualiza el correo.
            $stmt = $conexion->prepare('UPDATE usuarios SET correo = ? WHERE id_usuarios = ?');
            // Vincula el correo (`s`) y el `usuario_id` (`i`).
            $stmt->bind_param('si', $correo, $usuario_id);
        }
        
        // Si la ejecución de esta consulta falla, lanza una excepción.
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar usuario: " . $conexion->error);
        }
        // Cierra la declaración.
        $stmt->close();

        // Confirma la transacción. Todos los cambios se guardan permanentemente.
        $conexion->commit();
        // Almacena un mensaje de éxito en la sesión.
        $_SESSION['success'] = 'Proveedor actualizado correctamente';

    } catch (Exception $e) {
        // En caso de que se lance una excepción, revierte la transacción.
        // Esto deshace todos los cambios realizados en las tablas, asegurando la integridad de los datos.
        $conexion->rollback();
        // Almacena el mensaje de error de la excepción en la sesión.
        $_SESSION['error'] = $e->getMessage();
    }
}

// Redirige al usuario de vuelta a la página de proveedores, sin importar si el proceso fue exitoso o falló.
header('Location: plantillaUsers.php?vista=proveedores');
// Termina la ejecución del script.
exit;
?>