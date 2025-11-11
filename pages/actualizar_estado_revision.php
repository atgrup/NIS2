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
    // --- Notificar al proveedor asociado al archivo ---
    try {
        // Obtener datos del archivo y del proveedor (email)
        $q = "SELECT a.nombre_archivo, a.proveedor_id, pr.nombre_empresa, u.correo AS proveedor_correo
              FROM archivos_subidos a
              LEFT JOIN proveedores pr ON a.proveedor_id = pr.id
              LEFT JOIN usuarios u ON pr.usuario_id = u.id_usuarios
              WHERE a.id = ? LIMIT 1";
        $stmt2 = $conexion->prepare($q);
        if ($stmt2) {
            $stmt2->bind_param('i', $id);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($res2 && $res2->num_rows > 0) {
                $row = $res2->fetch_assoc();
                $archivoNombre = $row['nombre_archivo'];
                $proveedorCorreo = $row['proveedor_correo'];
                $proveedorNombre = $row['nombre_empresa'] ?: '';

                if ($proveedorCorreo) {
                    // Llamar al helper de notificaciones
                    require_once __DIR__ . '/notifications/mail_notification.php';
                    $asunto = "Estado de su archivo: {$estado}";
                    $html = "<p>Hola {$proveedorNombre},</p>\n";
                    $html .= "<p>El archivo <b>{$archivoNombre}</b> ha cambiado su estado a <b>{$estado}</b>.</p>";
                    // Puedes añadir más detalles o comentarios desde POST si los envías
                    $comentario = $_POST['comentario'] ?? '';
                    if ($comentario) $html .= "\n<p>Comentario: {$comentario}</p>";

                    // Encolar la notificación para procesamiento asíncrono
                    require_once __DIR__ . '/notifications/mail_notification.php';
                    $queueId = enqueueEmail($proveedorCorreo, $proveedorNombre, $asunto, $html, '', "Cambio de estado: {$estado}", false);
                    // Si se encoló correctamente, crear un token de acción y añadir link en el cuerpo del correo (para permitir acción desde el email)
                    if ($queueId) {
                        $meta = ['new_state' => $estado, 'comentario' => $comentario ?? '', 'changed_by' => $_SESSION['user_id'] ?? null];
                        $token = createEmailActionToken($queueId, 'change_state', $id, $meta, 72);
                        if ($token) {
                            // Construir link absoluto: preferir APP_URL si está configurada, si no, usar host de la petición
                            $base = getenv('APP_URL') ?: '';
                            if (empty($base) && !empty($_SERVER['HTTP_HOST'])) {
                                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                $base = $scheme . '://' . $_SERVER['HTTP_HOST'];
                            }
                            $link = ($base ? $base : '') . '/pages/notifications/action.php?t=' . urlencode($token);
                            $append = "<p>Puede ver el archivo y confirmar la acción desde aquí: <a href=\"{$link}\">Ver / Confirmar</a></p>";
                            $upd = $conexion->prepare("UPDATE mail_queue SET body_html = CONCAT(body_html, ?) WHERE id = ?");
                            if ($upd) {
                                $upd->bind_param('si', $append, $queueId);
                                $upd->execute();
                                $upd->close();
                            }
                        }
                    }
                }
            }
            $stmt2->close();
        }
    } catch (Exception $e) {
        // Registrar en log; no interrumpir la respuesta al cliente
        error_log('Error al notificar proveedor tras actualizar estado: ' . $e->getMessage());
    }
} else {
    http_response_code(500);    // Error interno del servidor
    echo "Error al actualizar";
}
