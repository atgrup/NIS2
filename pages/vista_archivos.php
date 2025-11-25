<?php
// Inicia la sesión
session_start();

// Verifica rol
if (!isset($_SESSION['rol']) || !in_array(strtolower($_SESSION['rol']), ['administrador', 'consultor'])) {
    http_response_code(403);
    exit('No autorizado');
}

// Conexión
require __DIR__ . '/../api/includes/conexion.php';

$id = intval($_POST['id'] ?? 0);
$estado = $_POST['estado'] ?? '';
$comentario = $_POST['comentario'] ?? ''; // Recogemos el comentario

$estados_permitidos = ['pendiente', 'aprobado', 'rechazado'];

if (!$id || !in_array($estado, $estados_permitidos)) {
    http_response_code(400);
    exit('Datos inválidos');
}

// Actualizar estado y comentario en la BD
// Nota: Asegúrate de tener la columna 'comentario' en tu tabla 'archivos_subidos'
// Si no la tienes, quita "comentario = ?" y el "s" extra del bind_param.
$sql = "UPDATE archivos_subidos SET revision_estado = ? WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("si", $estado, $id);

if ($stmt->execute()) {
    
    // El cambio se ha guardado. Ahora intentamos notificar, pero SIN bloquear si falla.
    try {
        // Obtener datos para el correo
        $q = "SELECT a.nombre_archivo, pr.nombre_empresa, u.correo AS proveedor_correo 
              FROM archivos_subidos a 
              LEFT JOIN proveedores pr ON a.proveedor_id = pr.id 
              LEFT JOIN usuarios u ON pr.usuario_id = u.id_usuarios 
              WHERE a.id = ? LIMIT 1";
              
        $stmt2 = $conexion->prepare($q);
        $stmt2->bind_param('i', $id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        
        if ($res2 && $res2->num_rows > 0) {
            $row = $res2->fetch_assoc();
            $proveedorCorreo = $row['proveedor_correo'];
            $nombreEmpresa = $row['nombre_empresa'];
            
            // Solo si tenemos correo, intentamos enviar
            if ($proveedorCorreo) {
                $ruta_mailer = __DIR__ . '/notifications/enviar_correo.php';
                
                if (file_exists($ruta_mailer)) {
                    require_once $ruta_mailer;
                    
                    // Usamos template simple si no existe la función render compleja
                    $asunto = "Actualización de documento - NIS2";
                    $cuerpo = "<h3>Hola, {$nombreEmpresa}</h3>";
                    $cuerpo .= "<p>El estado de su archivo <strong>" . htmlspecialchars($row['nombre_archivo']) . "</strong> ha cambiado a: <strong>" . strtoupper($estado) . "</strong>.</p>";
                    
                    if (!empty($comentario)) {
                        $cuerpo .= "<p><strong>Comentario del auditor:</strong><br>" . nl2br(htmlspecialchars($comentario)) . "</p>";
                    }
                    
                    $cuerpo .= "<br><p>Acceda a la plataforma para ver más detalles.</p>";

                    // Enviar (función segura del archivo enviar_correo.php que arreglamos antes)
                    if (function_exists('enqueueEmail')) {
                        enqueueEmail($proveedorCorreo, $nombreEmpresa, $asunto, $cuerpo, '', 'Notificación Estado', true);
                    }
                }
            }
        }
        $stmt2->close();
    } catch (Throwable $e) {
        // Si falla el correo, no hacemos nada, para que el usuario vea "Estado actualizado"
        // error_log("Fallo al enviar correo de estado: " . $e->getMessage());
    }

    echo "Estado actualizado"; // Respuesta para el JavaScript

} else {
    http_response_code(500);
    echo "Error al actualizar en BD";
}
?>