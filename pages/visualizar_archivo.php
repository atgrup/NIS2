<?php
// visualizar_archivo.php

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo "Falta el parámetro ID del archivo.";
    exit;
}

$id = intval($_GET['id']);

// Conexión a BD
require_once __DIR__ . '/../api/includes/conexion.php';

// Busca la información del archivo
$sql = "SELECT a.nombre_archivo, a.proveedor_id, u.correo
        FROM archivos_subidos a
        JOIN usuarios u ON a.usuario_id = u.id_usuarios
        WHERE a.id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    http_response_code(404);
    echo "Archivo no encontrado.";
    exit;
}

$row = $res->fetch_assoc();

// Construir ruta física
$correo = $row['correo'];
$archivo = $row['nombre_archivo'];
$ruta = realpath(__DIR__ . '/../documentos_subidos/' . $correo . '/' . $archivo);


if (!$ruta || !file_exists($ruta)) {
    http_response_code(404);
    echo "El archivo no existe en el servidor.";
    exit;
}

// Determinar el tipo MIME
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $ruta);
finfo_close($finfo);

// Mostrar el archivo en el navegador
header("Content-Type: $mime");
header('Content-Disposition: inline; filename="' . basename($ruta) . '"');
header('Content-Length: ' . filesize($ruta));
readfile($ruta);
exit;
