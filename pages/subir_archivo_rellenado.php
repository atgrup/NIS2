<?php
// =====================================
// 1️⃣ INICIAR SESIÓN Y CONEXIÓN A LA BASE DE DATOS
// =====================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../api/includes/conexion.php'; // Conexión a la base de datos

// =====================================
// 2️⃣ OBTENER DATOS DE SESIÓN
// =====================================
$correo = $_SESSION['correo'] ?? '';           // Correo del usuario logueado
$usuario_id = $_SESSION['id_usuario'] ?? null; // ID de usuario (si existe)
$rol = strtolower($_SESSION['rol'] ?? '');     // Rol (admin, proveedor, consultor...)

// =====================================
// 3️⃣ VALIDAR SESIÓN
// =====================================
// Si no hay correo, no hay sesión válida
if (empty($correo)) {
    die("Usuario no autenticado.");
}

// No forzamos error si no hay usuario_id (algunos proveedores pueden no tenerlo)
if (empty($usuario_id)) {
    // Solo registramos aviso en el log (no detenemos)
    error_log("⚠️ Usuario sin id_usuario pero con sesión válida: {$correo}");
}

// =====================================
// 4️⃣ OBTENER ID DEL PROVEEDOR (SOLO SI ES PROVEEDOR)
// =====================================
$proveedor_id = null;

if ($rol === 'proveedor') {
    // Intentamos primero con usuario_id
    if (!empty($usuario_id)) {
        $stmt = $conexion->prepare("SELECT id FROM proveedores WHERE usuario_id = ? LIMIT 1");
        $stmt->bind_param("i", $usuario_id);
    } else {
        // Si no hay usuario_id, buscamos por correo (campo nombre_empresa o correo_contacto)
        $stmt = $conexion->prepare("
            SELECT id 
            FROM proveedores p 
            JOIN usuarios u ON p.usuario_id = u.id_usuarios 
            WHERE u.correo = ? 
            LIMIT 1
        ");
        $stmt->bind_param("s", $correo);
    }

    if ($stmt) {
        $stmt->execute();
        $stmt->bind_result($proveedor_id);
        $stmt->fetch();
        $stmt->close();
    }

    // Si aún no se encuentra, solo lo dejamos como null (sin cortar ejecución)
    if (!$proveedor_id) {
        error_log("⚠️ No se encontró proveedor para el correo: {$correo}");
    }
}

// =====================================
// 5️⃣ PROCESO DE SUBIDA DEL ARCHIVO
// =====================================
if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['archivo'];
    $nombre_original = basename($archivo['name']); // Nombre limpio del archivo

    // Carpeta donde se guardará el archivo (por correo del usuario)
    $ruta_usuario = 'documentos_subidos/' . $correo . '/';
    if (!is_dir('../' . $ruta_usuario)) {
        mkdir('../' . $ruta_usuario, 0777, true);
    }

    $ruta_destino = $ruta_usuario . $nombre_original;

    // Mover archivo temporal al destino final
    if (move_uploaded_file($archivo['tmp_name'], '../' . $ruta_destino)) {

        // ID de plantilla si el formulario lo envía
        $plantilla_id = !empty($_POST['plantilla_id']) ? (int)$_POST['plantilla_id'] : null;

        // =====================================
        // 6️⃣ GUARDAR INFORMACIÓN EN LA BASE DE DATOS
        // =====================================
        $stmt = $conexion->prepare("
            INSERT INTO archivos_subidos 
            (nombre_archivo, archivo_url, proveedor_id, usuario_id, plantilla_id, fecha_subida, revision_estado)
            VALUES (?, ?, ?, ?, ?, NOW(), 'pendiente')
        ");

        if (!$stmt) {
            die("Error al preparar inserción SQL: " . $conexion->error);
        }

        $stmt->bind_param(
            "ssiii",
            $nombre_original,
            $ruta_destino,
            $proveedor_id,
            $usuario_id,
            $plantilla_id
        );

        if ($stmt->execute()) {
            $_SESSION['success_subida'] = "✅ Archivo subido y registrado correctamente.";
            // Guardar el id insertado para uso posterior (para generar links en emails)
            $archivoId = $stmt->insert_id;
        } else {
            $_SESSION['error_subida'] = "❌ Error al guardar en la base de datos: " . $stmt->error;
            $archivoId = null;
        }

        $stmt->close();

        // --- Notificar a los consultores que hay un nuevo archivo ---
        try {
            // Obtener lista de correos de consultores (join con usuarios)
            $sqlCons = "SELECT u.correo, c.nombre FROM consultores c JOIN usuarios u ON c.usuario_id = u.id_usuarios";
            $resCons = $conexion->query($sqlCons);
            $consultors = [];
            if ($resCons) {
                while ($r = $resCons->fetch_assoc()) {
                    if (!empty($r['correo'])) $consultors[] = ['email' => $r['correo'], 'name' => $r['nombre'] ?? ''];
                }
            }

            if (!empty($consultors)) {
                require_once __DIR__ . '/notifications/mail_notification.php';
                $subject = "Nuevo archivo subido por {$correo}";
                $htmlList = "<p>El proveedor <b>{$correo}</b> ha subido un nuevo archivo:</p>\n<ul>";
                $htmlList .= "<li>{$nombre_original}</li>";
                $htmlList .= "</ul>";
                // Encolar notificaciones para cada consultor
                foreach ($consultors as $c) {
                    $to = $c['email'];
                    $toName = $c['name'] ?? '';
                    $queueId = enqueueEmail($to, $toName, $subject, $htmlList, '', "Archivo subido: {$nombre_original}", false);
                    // Si se encoló correctamente, crear token y añadir links de visualización/revisión en la cola
                    if ($queueId && !empty($archivoId)) {
                        $meta = ['archivo_id' => $archivoId];
                        $token = createEmailActionToken($queueId, 'change_state', $archivoId, $meta, 72);
                        $base = getenv('APP_URL') ?: '';
                        if (!$base && !empty($_SERVER['HTTP_HOST'])) {
                            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                            $base = $scheme . '://' . $_SERVER['HTTP_HOST'];
                        }
                        $base = rtrim($base, '/');
                        $linkView = ($base ? $base : '') . '/pages/visualizar_archivo_split.php?id=' . $archivoId;
                        $linkAction = ($base ? $base : '') . '/pages/notifications/action.php?t=' . urlencode($token);
                        $append = "<p>Ver archivo: <a href=\"{$linkView}\">Ver</a></p><p>Revisar / Modificar estado: <a href=\"{$linkAction}\">Abrir revisión</a></p>";
                        $upd = $conexion->prepare("UPDATE mail_queue SET body_html = CONCAT(body_html, ?) WHERE id = ?");
                        if ($upd) {
                            $upd->bind_param('si', $append, $queueId);
                            $upd->execute();
                            $upd->close();
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Error al notificar consultores tras subida: ' . $e->getMessage());
        }

        // Redirigir al listado
        header("Location: plantillaUsers.php?vista=archivos");
        exit;
    } else {
        echo "❌ Error al mover el archivo al destino final.";
    }
} else {
    echo "⚠️ No se seleccionó ningún archivo o hubo un error al subirlo.";
}
?>
