<?php
session_start();
require_once('../api/includes/conexion.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Método no permitido");
}

if (!isset($_SESSION['id_usuario'])) {
    die("Usuario no autenticado");
}

$usuario_id = $_SESSION['id_usuario'];  // Este es el id del usuario que sube, guardamos en consultor_id
$correo = $_SESSION['correo'] ?? 'desconocido';
$proveedor_id = $_SESSION['proveedor_id'] ?? null;
if (!$proveedor_id) {
    die("No se pudo determinar el proveedor asociado.");
}

// Desde el modal: select plantilla_id, input file
$plantilla_id = $_POST['plantilla_id'] ?? null;

if (!$plantilla_id || !isset($_FILES['archivo'])) {
    die("Faltan datos del formulario");
}

// Validar plantilla
$stmt = $conexion->prepare("SELECT id, nombre FROM plantillas WHERE id = ?");
$stmt->bind_param("i", $plantilla_id);
$stmt->execute();
$res = $stmt->get_result();
$plantilla = $res->fetch_assoc();

if (!$plantilla) {
    die("Plantilla no encontrada.");
}

// Guardar archivo
$nombre_original = $_FILES['archivo']['name'];
$tmp = $_FILES['archivo']['tmp_name'];
$carpeta = 'documentos_subidos/' . $correo;

if (!is_dir($carpeta)) {
    mkdir($carpeta, 0777, true);
}

$archivo_guardado = $carpeta . '/' . time() . '_' . basename($nombre_original);
if (!move_uploaded_file($tmp, $archivo_guardado)) {
    die("Error al guardar archivo");
}

// Insertar en la base de datos SIN rol_usuario ni comentarios
$stmt = $conexion->prepare("
    INSERT INTO archivos_subidos (plantilla_id, consultor_id, proveedor_id, archivo_url, nombre_archivo)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("iiiss", $plantilla_id, $usuario_id, $proveedor_id, $archivo_guardado, $nombre_original);

if ($stmt->execute()) {
    echo "OK";
} else {
    echo "Error al guardar en base de datos: " . $stmt->error;
}
?>
