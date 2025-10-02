<?php
// Inicia la sesión para poder usar variables de sesión
session_start();

// Verifica si el usuario tiene un rol válido (administrador o consultor).
// Si no está definido el rol o no pertenece a esos, devuelve error 403 (No autorizado).
if (!isset($_SESSION['rol']) || !in_array(strtolower($_SESSION['rol']), ['administrador', 'consultor'])) {
    http_response_code(403);
    exit('No autorizado');
}

// Incluye el archivo de conexión a la base de datos.
require __DIR__ . '/../api/includes/conexion.php';

// Obtiene el ID del archivo desde POST y lo convierte a entero (para evitar inyecciones).
$id = intval($_POST['id'] ?? 0);

// Obtiene el nuevo estado enviado por POST.
$estado = $_POST['estado'] ?? '';

// Define los valores permitidos para el estado.
$estados_permitidos = ['pendiente', 'aprobado', 'rechazado'];

// Si el ID no es válido (cero o nulo) o el estado no está dentro de los permitidos,
// devuelve error 400 (Solicitud incorrecta).
if (!$id || !in_array($estado, $estados_permitidos)) {
    http_response_code(400);
    exit('Datos inválidos');
}

// Prepara una consulta SQL segura para actualizar el estado de revisión del archivo.
$stmt = $conexion->prepare("UPDATE archivos_subidos SET revision_estado = ? WHERE id = ?");

// Asocia los parámetros a la consulta: 
// "s" → string para $estado, "i" → entero para $id
$stmt->bind_param("si", $estado, $id);

// Ejecuta la consulta y comprueba si fue exitosa.
if ($stmt->execute()) {
    echo "Estado actualizado"; // Éxito
} else {
    http_response_code(500);    // Error interno del servidor
    echo "Error al actualizar";
}
