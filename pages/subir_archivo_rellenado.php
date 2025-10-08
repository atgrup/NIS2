<?php
// Inicia la sesión si no está activa. Esto previene errores si la sesión ya se inició en otro script.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluye el archivo de conexión a la base de datos.
include '../api/includes/conexion.php';

// Obtiene el correo, ID de usuario y rol de la sesión.
$correo = $_SESSION['correo'] ?? '';
$usuario_id = $_SESSION['id_usuario'] ?? null;
$rol = strtolower($_SESSION['rol'] ?? '');

// Si no hay correo, no hay sesión válida (esto sí aplica a todos)
if (!$correo) {
    die("Usuario no autenticado.");
}

// Solo los proveedores requieren tener un id_usuario válido
if ($rol === 'proveedor' && !$usuario_id) {
    die("Usuario proveedor no autenticado correctamente.");
}


// Inicializa la variable para el ID del proveedor. Se usará si el usuario es un proveedor.
$proveedor_id = null;

// Si el rol del usuario es 'proveedor', busca su ID en la tabla de proveedores.
if ($rol === 'proveedor') {
    // Prepara una consulta SQL para unir las tablas `proveedores` y `usuarios` y encontrar el ID del proveedor.
    $stmt = $conexion->prepare("
        SELECT p.id 
        FROM proveedores p 
        JOIN usuarios u ON p.usuario_id = u.id_usuarios 
        WHERE u.correo = ?
    ");
    // Vincula el correo del usuario a la consulta.
    $stmt->bind_param("s", $correo);
    // Ejecuta la consulta.
    $stmt->execute();
    // Vincula el resultado a la variable `$proveedor_id`.
    $stmt->bind_result($proveedor_id);
    // Obtiene el resultado.
    $stmt->fetch();
    // Cierra la declaración.
    $stmt->close();

    // Si el ID del proveedor no se encuentra, detiene el script con un error.
    if (!$proveedor_id) {
        die("Proveedor no encontrado.");
    }
}

// --- Lógica para Subir el Archivo ---
// Verifica si se subió un archivo y si no hubo errores en el proceso de subida.
if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
    // Asigna el array del archivo a una variable para mayor comodidad.
    $archivo = $_FILES['archivo'];
    // Obtiene el nombre original del archivo de forma segura.
    $nombre_original = basename($archivo['name']);

    // Define la carpeta de destino basada en el correo del usuario.
    $carpeta_usuario = $correo;
    $ruta_usuario = 'documentos_subidos/' . $carpeta_usuario . '/';

    // Crea la carpeta si no existe. `../` es para subir un nivel desde el directorio actual.
    if (!is_dir('../' . $ruta_usuario)) {
        // `0777` son los permisos de la carpeta y `true` permite la creación de directorios anidados.
        mkdir('../' . $ruta_usuario, 0777, true);
    }

    // Define la ruta completa donde se guardará el archivo.
    $ruta_destino = $ruta_usuario . $nombre_original;

    // Mueve el archivo temporal desde el servidor a la ruta de destino.
    if (move_uploaded_file($archivo['tmp_name'], '../' . $ruta_destino)) {
        // Obtiene el ID de la plantilla del formulario, si existe. Si no, es `null`.
        $plantilla_id = !empty($_POST['plantilla_id']) ? $_POST['plantilla_id'] : null;

        // Prepara una consulta para insertar los detalles del archivo en la base de datos.
        $stmt = $conexion->prepare("
            INSERT INTO archivos_subidos 
            (nombre_archivo, archivo_url, proveedor_id, usuario_id, plantilla_id, fecha_subida, revision_estado)
            VALUES (?, ?, ?, ?, ?, NOW(), 'Pendiente')
        ");
        // Vincula los parámetros a la consulta: `ssiii` indica 2 strings y 3 enteros.
        $stmt->bind_param(
            "ssiii",
            $nombre_original, // Nombre para mostrar.
            $ruta_destino,    // Ruta de almacenamiento.
            $proveedor_id,    // ID del proveedor (puede ser null).
            $usuario_id,      // ID del usuario.
            $plantilla_id     // ID de la plantilla (puede ser null).
        );

        // Ejecuta la consulta y verifica si fue exitosa.
        if ($stmt->execute()) {
            // Si la inserción fue exitosa, almacena un mensaje de éxito en la sesión.
            $_SESSION['success_subida'] = "Archivo subido correctamente.";
        } else {
            // Si hubo un error en la base de datos, almacena un mensaje de error.
            $_SESSION['error_subida'] = "Error al guardar en la base de datos.";
        }
        // Cierra la declaración.
        $stmt->close();

        // Redirige al usuario a la página de archivos.
        header("Location: plantillaUsers.php?vista=archivos");
        // Detiene la ejecución.
        exit;

    } else {
        // Si el archivo no se pudo mover, muestra un mensaje de error.
        echo "Error al mover el archivo.";
    }
} else {
    // Si no se seleccionó un archivo o hubo un error al subirlo, muestra un mensaje de error.
    echo "No se seleccionó ningún archivo o hubo un error al subirlo.";
}