<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../api/includes/conexion.php';

$correo = $_SESSION['correo'] ?? '';
$usuario_id = $_SESSION['id_usuario'] ?? null;
$rol = strtolower($_SESSION['rol'] ?? '');

if (!$correo || !$usuario_id) {
    die("Usuario no autenticado.");
}

// Inicializar proveedor_id como null
$proveedor_id = null;

// Si el usuario es proveedor, buscar su ID
if ($rol === 'proveedor') {
    $stmt = $conexion->prepare("
        SELECT p.id 
        FROM proveedores p 
        JOIN usuarios u ON p.usuario_id = u.id_usuarios 
        WHERE u.correo = ?
    ");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->bind_result($proveedor_id);
    $stmt->fetch();
    $stmt->close();

    if (!$proveedor_id) {
        die("Proveedor no encontrado.");
    }
}

// Comprobar que se ha subido un archivo
if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['archivo'];
    $nombre_archivo = basename($archivo['name']);
    $ruta_destino = 'documentos_subidos/' . uniqid() . '_' . $nombre_archivo;

    if (move_uploaded_file($archivo['tmp_name'], '../' . $ruta_destino)) {
        $plantilla_id = !empty($_POST['plantilla_id']) ? $_POST['plantilla_id'] : null;

        $stmt = $conexion->prepare("
            INSERT INTO archivos_subidos 
            (nombre_archivo, archivo_url, proveedor_id, usuario_id, plantilla_id, fecha_subida, revision_estado)
            VALUES (?, ?, ?, ?, ?, NOW(), 'Pendiente')
        ");
        $stmt->bind_param(
            "ssiii",
            $nombre_archivo,
            $ruta_destino,
            $proveedor_id,
            $usuario_id,
            $plantilla_id
        );
    
        $stmt->close();

        echo "Archivo subido correctamente.";
    } else {
        echo "Error al mover el archivo.";
    }
} else {
    echo "No se seleccionó ningún archivo o hubo un error al subirlo.";
}
