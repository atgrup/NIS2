<?php
// visualizar_archivo.php

// --- 1. Verificación de Entrada ---
// Comprueba si el parámetro 'id' está presente en la URL.
if (!isset($_GET['id'])) {
    // Si no se proporciona un ID, establece el código de estado HTTP a 400 (Bad Request).
    http_response_code(400);
    // Muestra un mensaje de error en la página.
    echo "Falta el parámetro ID del archivo.";
    // Detiene la ejecución del script.
    exit;
}

// Convierte el ID a un entero para asegurar su tipo y evitar posibles ataques.
$id = intval($_GET['id']);

// --- 2. Conexión a la Base de Datos y Búsqueda ---
// Incluye el archivo de conexión a la base de datos.
require_once __DIR__ . '/../api/includes/conexion.php';

// Prepara una consulta SQL para obtener el nombre del archivo y el correo del usuario (para construir la ruta del archivo) a partir de su ID.
$sql = "SELECT a.nombre_archivo, a.proveedor_id, u.correo
        FROM archivos_subidos a
        JOIN usuarios u ON a.usuario_id = u.id_usuarios
        WHERE a.id = ?";
$stmt = $conexion->prepare($sql);
// Vincula el ID a la consulta. 'i' indica que el parámetro es un entero.
$stmt->bind_param("i", $id);
// Ejecuta la consulta.
$stmt->execute();
// Obtiene el conjunto de resultados.
$res = $stmt->get_result();

// Si la consulta no devuelve ninguna fila, el archivo no existe en la base de datos.
if ($res->num_rows === 0) {
    // Establece el código de estado HTTP a 404 (Not Found).
    http_response_code(404);
    echo "Archivo no encontrado.";
    exit;
}

// Obtiene los datos del archivo como un array asociativo.
$row = $res->fetch_assoc();

// --- 3. Construcción y Verificación de la Ruta del Archivo Físico ---
// Almacena el correo y el nombre del archivo en variables.
$correo = $row['correo'];
$archivo = $row['nombre_archivo'];
// Construye la ruta completa del archivo en el servidor y usa `realpath()` para resolver la ruta absoluta y verificar si existe.
$ruta = realpath(__DIR__ . '/../documentos_subidos/' . $correo . '/' . $archivo);

// Comprueba si la ruta no es válida o si el archivo no existe en la ubicación esperada.
if (!$ruta || !file_exists($ruta)) {
    // Establece el código de estado HTTP a 404 (Not Found).
    http_response_code(404);
    echo "El archivo no existe en el servidor.";
    exit;
}

// --- 4. Preparación y Envío del Archivo al Navegador ---
// Abre una base de datos de tipos MIME para determinar el tipo de contenido del archivo.
$finfo = finfo_open(FILEINFO_MIME_TYPE);
// Obtiene el tipo MIME del archivo.
$mime = finfo_file($finfo, $ruta);
// Cierra la base de datos de tipos MIME.
finfo_close($finfo);

// Establece las cabeceras HTTP necesarias para mostrar el archivo en el navegador.
// `Content-Type`: Indica el tipo de contenido (por ejemplo, 'application/pdf', 'image/jpeg').
header("Content-Type: $mime");
// `Content-Disposition`: `inline` le dice al navegador que muestre el archivo en lugar de descargarlo. También se especifica el nombre del archivo.
header('Content-Disposition: inline; filename="' . basename($ruta) . '"');
// `Content-Length`: Indica el tamaño del archivo en bytes.
header('Content-Length: ' . filesize($ruta));

// Lee el archivo físico y lo envía directamente al navegador.
readfile($ruta);
// Termina la ejecución para evitar que se envíe contenido adicional.
exit;