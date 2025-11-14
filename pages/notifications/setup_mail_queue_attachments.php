<?php
// Ejecuta el SQL de migrations/alter_mail_queue_add_attachments.sql para añadir columna attachments
require_once __DIR__ . '/../../api/includes/conexion.php';
$sqlFile = __DIR__ . '/../../migrations/alter_mail_queue_add_attachments.sql';
if (!file_exists($sqlFile)) {
    echo "Archivo SQL no encontrado: {$sqlFile}\n";
    exit(1);
}
$sql = file_get_contents($sqlFile);
if (!$sql) {
    echo "No se pudo leer el archivo SQL\n";
    exit(1);
}
if ($conexion->multi_query($sql)) {
    do {
        if ($result = $conexion->store_result()) {
            $result->free();
        }
    } while ($conexion->more_results() && $conexion->next_result());
    echo "Columna attachments añadida a mail_queue (o ya existente).\n";
} else {
    echo "Error al ejecutar SQL: " . $conexion->error . "\n";
}
?>