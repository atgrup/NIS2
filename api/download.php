<?php
session_start();
include 'includes/conexion.php';
if (!isset($_GET['id'])) {
    die("ID de archivo no especificado.");
}
$id = intval($_GET['id']);
// Traemos la ruta relativa del archivo desde la base de datos
$stmt = $conexion->prepare("SELECT archivo_url, nombre_archivo FROM archivos_subidos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($ruta_relativa, $nombre_archivo);
if ($stmt->fetch()) {
    $stmt->close();
    $ruta_archivo = realpath(__DIR__ . '/../' . $ruta_relativa);
    if ($ruta_archivo && file_exists($ruta_archivo)) {
        // Forzar descarga
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($nombre_archivo) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($ruta_archivo));
        flush();
        readfile($ruta_archivo);
        exit;
    } else {
        die(":x: Archivo no encontrado en el servidor.");
    }
} else {
    die(":x: Archivo no encontrado en la base de datos.");
}
