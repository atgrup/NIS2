<?php
session_start();
require_once('conexion.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Método no permitido");
}

if (!isset($_SESSION['id_usuario'])) {
    die("Usuario no autenticado");
}

$uuid = $_POST['uuid'] ?? '';
$comentarios = $_POST['comentarios'] ?? '';
$proveedor_id = $_SESSION['proveedor_id'] ?? null;

if (!$uuid || !$proveedor_id || !isset($_FILES['archivo'])) {
    die("Faltan datos obligatorios");
}

// Buscar la plantilla por UUID
$stmt = $conexion->prepare("SELECT id FROM plantillas WHERE uuid = ?");
$stmt->bind_param("s", $uuid);
$stmt->execute();
$res = $stmt->get_result();
$plantilla = $res->fetch_assoc();

if (!$plantilla) {
    die("Plantilla no encontrada con ese UUID.");
}

$plantilla_id = $plantilla['id'];

// Guardar el archivo en el servidor
$nombre_original = $_FILES['archivo']['name'];
$tmp = $_FILES['archivo']['tmp_name'];
$carpeta = 'documentos_subidos/' . $_SESSION['correo']; // o nombre proveedor
if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);

$archivo_guardado = $carpeta . '/' . time() . '_' . basename($nombre_original);
move_uploaded_file($tmp, $archivo_guardado);

// Registrar en la base de datos
$stmt = $conexion->prepare("INSERT INTO archivos_subidos (plantilla_id, proveedor_id, archivo_url, nombre_archivo, comentarios) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iisss", $plantilla_id, $proveedor_id, $archivo_guardado, $nombre_original, $comentarios);
$stmt->execute();

echo "Archivo subido correctamente.";
?>
