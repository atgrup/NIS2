<?php
// Ejecuta el SQL de migrations/create_history_and_actions.sql para crear tablas de historial y acciones
require_once __DIR__ . '/../../api/includes/conexion.php';
$sqlFile = __DIR__ . '/../../migrations/create_history_and_actions.sql';
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
    echo "Tablas de historial/acciones creadas o ya existentes.\n";
} else {
    echo "Error al ejecutar SQL: " . $conexion->error . "\n";
}
?>