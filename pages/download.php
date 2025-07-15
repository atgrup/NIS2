<?php
session_start();

require_once('fpdf/fpdf.php'); // Asegúrate de tener FPDF
require_once('conexion.php');  // Tu archivo de conexión

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../api/auth/login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("No se especificó una plantilla para descargar.");
}

$plantillaId = intval($_GET['id']);

// 1. Obtener datos de la plantilla
$stmt = $conexion->prepare("SELECT id, nombre, archivo_url, uuid FROM plantillas WHERE id = ?");
$stmt->bind_param("i", $plantillaId);
$stmt->execute();
$resultado = $stmt->get_result();
$plantilla = $resultado->fetch_assoc();

if (!$plantilla) {
    die("Plantilla no encontrada.");
}

$uuid = $plantilla['uuid'];
$nombre = preg_replace('/[^A-Za-z0-9_\-]/', '_', $plantilla['nombre']); // limpio para filename

// 2. Generar PDF dinámico con UUID
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,'Contenido original de la plantilla: ' . $nombre, 0, 1);
$pdf->Ln(10);
$pdf->SetFont('Arial','',10);
$pdf->MultiCell(0,10,'Esta plantilla ha sido descargada con el siguiente UUID único para seguimiento:');
$pdf->Ln(5);
$pdf->SetFont('Courier','',12);
$pdf->Cell(0,10, $uuid);
$pdf->Ln(20);
$pdf->SetFont('Arial','I',8);
$pdf->Cell(0,10, 'Documento generado automáticamente. No modificar este UUID.', 0, 1);

// 3. Preparar filename con UUID
$filename = $nombre . '_' . $uuid . '.pdf';

// 4. Guardar la descarga en la base de datos
$usuario_id = $_SESSION['id_usuario'];
$logStmt = $conexion->prepare("INSERT INTO descargas_archivos (usuario_id, uuid_plantilla) VALUES (?, ?)");
$logStmt->bind_param("is", $usuario_id, $uuid);
$logStmt->execute();

// 5. Descargar
$pdf->Output('D', $filename); // 'D' fuerza descarga

exit;
?>
