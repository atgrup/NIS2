<?php
session_start();

if (!isset($_SESSION['rol'])) {
    header("Location: ../api/auth/login.php");
    exit;
}

if (!isset($_GET['archivo']) || empty($_GET['archivo'])) {
    die("No se especificó archivo para descargar.");
}

$archivo_relativo = $_GET['archivo'];
$baseDir = realpath(__DIR__ . '/../documentos_subidos');

if (strpos($archivo_relativo, '..') !== false) {
    die("Ruta no válida.");
}

$ruta_archivo = realpath(__DIR__ . '/../' . $archivo_relativo);

if ($ruta_archivo === false || !file_exists($ruta_archivo)) {
    die("Archivo no encontrado.");
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($ruta_archivo) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($ruta_archivo));
flush();
readfile($ruta_archivo);
exit;
?>
