<?php
// visualizar_archivo_split.php
// Visualizador de PDF + info + comentarios
session_start();
$ruta_conexion = __DIR__ . '/../api/includes/conexion.php';
if (!file_exists($ruta_conexion)) {
    die("Error: No se encontró el archivo de conexión en $ruta_conexion");
}
require $ruta_conexion;
$conn = $conexion;

// Obtener el id del archivo por GET
if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger">No se ha especificado el archivo.</div>';
    exit;
}
$id_archivo = intval($_GET['id']);

// Obtener info del archivo

$stmt = $conn->prepare("SELECT a.id, a.nombre_archivo, p.nombre AS nombre_plantilla, a.fecha_subida, pr.nombre_empresa, u.correo AS correo_usuario, a.revision_estado, a.comentario FROM archivos_subidos a LEFT JOIN plantillas p ON a.plantilla_id = p.id LEFT JOIN proveedores pr ON a.proveedor_id = pr.id LEFT JOIN usuarios u ON a.usuario_id = u.id_usuarios WHERE a.id = ?");
$stmt->bind_param('i', $id_archivo);
$stmt->execute();
$result = $stmt->get_result();
$archivo = $result->fetch_assoc();
if (!$archivo) {
    echo '<div class="alert alert-danger">Archivo no encontrado.</div>';
    exit;
}

// Procesar cambio de estado de revisión

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['revision_estado']) || isset($_POST['comentario']))) {
    $nuevo_estado = $_POST['revision_estado'] ?? $archivo['revision_estado'];
    $nuevo_comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : $archivo['comentario'];
    $estados_validos = ['pendiente', 'aprobado', 'rechazado'];
    if (in_array($nuevo_estado, $estados_validos)) {
        $stmt = $conn->prepare("UPDATE archivos_subidos SET revision_estado = ?, comentario = ? WHERE id = ?");
        $stmt->bind_param('ssi', $nuevo_estado, $nuevo_comentario, $id_archivo);
        $stmt->execute();
        $stmt->close();
        // Refrescar los datos del archivo tras el cambio
        $stmt = $conn->prepare("SELECT a.id, a.nombre_archivo, p.nombre AS nombre_plantilla, a.fecha_subida, pr.nombre_empresa, u.correo AS correo_usuario, a.revision_estado, a.comentario FROM archivos_subidos a LEFT JOIN plantillas p ON a.plantilla_id = p.id LEFT JOIN proveedores pr ON a.proveedor_id = pr.id LEFT JOIN usuarios u ON a.usuario_id = u.id_usuarios WHERE a.id = ?");
        $stmt->bind_param('i', $id_archivo);
        $stmt->execute();
        $result = $stmt->get_result();
        $archivo = $result->fetch_assoc();
        $stmt->close();
        $mensaje_estado = 'Información actualizada correctamente.';
    } else {
        $mensaje_estado = 'Estado no válido.';
    }
}

// Ruta del PDF (ya no se usa directamente, solo por iframe)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Visualizar archivo</title>
    <link rel="stylesheet" href="../assets/styles/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
    .split-container {
        display: flex;
        flex-direction: row;
        height: 100vh;
        background: #f8f9fa;
    }
    .pdf-viewer {
        flex: 1 1 60%;
        border-right: 2px solid #e3e3e3;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
    }
    .pdf-viewer iframe {
        width: 95%;
        height: 90vh;
        border: none;
        box-shadow: 0 0 10px #ccc;
    }
    .info-panel {
        flex: 1 1 40%;
        padding: 0 0;
        background: #fff;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        height: 100vh;
        min-height: 100vh;
        justify-content: flex-start;
    }
    .info-panel > div {
        height: 100%;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: flex-start;
        margin-top: 0;
        background: #fff;
        border-radius: 0;
        box-shadow: none;
        padding-bottom: 0;
    }
    .info-title {
        width: 100%;
        background: #072989;
        color: #fff;
        font-weight: 900;
        font-size: 1.6rem;
        margin-bottom: 28px;
        text-align: center;
        letter-spacing: 1px;
        margin-top: 0;
        padding: 24px 32px 18px 32px;
        border-radius: 0;
    }
    .info-list {
        width: 100%;
        margin: 0;
        list-style: none;
        padding: 0 32px;
        background: #fff;
    }
    .info-list li {
        display: flex;
        align-items: center;
        margin-bottom: 18px;
        font-size: 1.08rem;
    }
    .info-list li strong {
        color: #072989;
        min-width: 140px;
        font-weight: 700;
        font-size: 1.05rem;
    }
    .info-list li span {
        color: #222;
        font-weight: 400;
        margin-left: 8px;
    }
    form[method="POST"] {
        margin-left: 32px;
    }
    .btn.bg-mi-color {
        background-color: #072989!important;
        color: white!important;
        border-radius: 10px !important;
        padding: 10px 22px !important;
        font-family: "Instrument Sans", sans-serif !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        margin-top: 18px;
        font-size: 1rem;
        box-shadow: 0 2px 8px #07298933;
        border: none;
    }
    .btn.bg-mi-color:hover {
        background-color: #0a3bb5!important;
        color: #fff!important;
        opacity: 0.92;
    }
    @media (max-width: 900px) {
        .split-container {
            flex-direction: column;
        }
        .pdf-viewer, .info-panel {
            flex: 1 1 100%;
            border: none;
            height: 50vh;
            padding: 20px;
        }
        .pdf-viewer iframe {
            height: 40vh;
        }
    }
    </style>
</head>
<body>
<div class="split-container">
    <div class="pdf-viewer">
        <iframe src="visualizar_archivo.php?id=<?php echo $archivo['id']; ?>"></iframe>
    </div>
    <div class="info-panel">
        <div>
            <div class="info-title">Información del archivo</div>
            <ul class="info-list">
                <li><strong>Nombre:</strong> <span><?= htmlspecialchars($archivo['nombre_archivo']); ?></span></li>
                <li><strong>Fecha de subida:</strong> <span><?= htmlspecialchars($archivo['fecha_subida']); ?></span></li>
                <li><strong>Plantilla asociada:</strong> <span><?= htmlspecialchars($archivo['nombre_plantilla'] ?? 'N/A'); ?></span></li>
                <li><strong>Empresa:</strong> <span><?= htmlspecialchars($archivo['nombre_empresa'] ?? 'N/A'); ?></span></li>
                <li><strong>Email:</strong> <span><?= isset($archivo['correo_usuario']) ? htmlspecialchars($archivo['correo_usuario']) : 'N/A'; ?></span></li>

                <?php if (isset($_SESSION['rol']) && strtolower($_SESSION['rol']) === 'proveedor'): ?>
                    <li><strong>Estado de revisión:</strong> <span><?= ucfirst($archivo['revision_estado']) ?></span></li>
                    <?php if (!empty($archivo['comentario'])): ?>
                        <li>
                            <div>
                                <strong>Comentario:</strong>
                                <div style="word-break: break-word;">
                                    <?= nl2br(htmlspecialchars($archivo['comentario'])) ?>
                                </div>
                            </div>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <?php if (!isset($_SESSION['rol']) || strtolower($_SESSION['rol']) !== 'proveedor'): ?>
                <form method="POST" style="margin-top: 28px; width:100%; max-width:420px;">
                    <div class="mb-3" style="display:flex; align-items:center;">
                        <strong style="color:#072989; min-width:140px; font-weight:700; font-size:1.05rem;">Estado de revisión:</strong>
                        <select class="form-select ms-2" id="revision_estado" name="revision_estado" style="width:auto; min-width:140px;">
                            <?php
                            $estados = [
                                'pendiente' => 'Pendiente',
                                'aprobado' => 'Aprobado',
                                'rechazado' => 'Rechazado'
                            ];
                            foreach ($estados as $valor => $texto) {
                                $selected = ($archivo['revision_estado'] === $valor) ? 'selected' : '';
                                echo "<option value='$valor' $selected>$texto</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3" style="width:100%;">
                                                <strong style="color:#072989; font-weight:700; font-size:1.05rem; display:block; margin-bottom:6px;">Comentario:</strong>
                                                <div style="position:relative;">
                                <textarea class="form-control" name="comentario" rows="3" maxlength="500" style="width:calc(100% + 60px); max-width:none; border:2px solid #072989; border-radius:10px; margin-bottom:8px; position:relative; left:0; right:-60px;" placeholder="Escribe un comentario para el proveedor..."><?= htmlspecialchars($archivo['comentario'] ?? '') ?></textarea>
                                                </div>
                    </div>
                    <button type="submit" class="btn bg-mi-color">Guardar</button>
                </form>
            <?php endif; ?>
            <?php if (isset($mensaje_estado)): ?>
                <div class="alert alert-success mt-2" style="max-width:420px;"> <?= htmlspecialchars($mensaje_estado) ?> </div>
            <?php endif; ?>
        </div>
        <!-- Aquí iría el botón de acción si lo necesitas -->
    </div>
</div>
<script>
// Autocompletar comentario si se selecciona 'aprobado' y limpiar si se cambia a otra opción
document.addEventListener('DOMContentLoaded', function() {
    var selectEstado = document.getElementById('revision_estado');
    var textareaComentario = document.querySelector('textarea[name="comentario"]');
    if (selectEstado && textareaComentario) {
        selectEstado.addEventListener('change', function() {
            if (this.value === 'aprobado') {
                if (textareaComentario.value === '' || textareaComentario.value === 'El archivo ha sido aprobado.') {
                    textareaComentario.value = 'El archivo ha sido aprobado.';
                }
            } else {
                if (textareaComentario.value === 'El archivo ha sido aprobado.') {
                    textareaComentario.value = '';
                }
            }
        });
    }
});

// AJAX para actualizar el estado de revisión y comentario sin recargar
const formEstado = document.querySelector('form[method="POST"]');
if (formEstado) {
  formEstado.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = formEstado.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Actualizando...';

    // Enviar TODOS los campos del formulario (estado + comentario)
    const formData = new FormData(formEstado);

    fetch(window.location.href, {
      method: 'POST',
      body: formData
    })
    .then(res => res.text())
    .then(html => {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const msg = doc.querySelector('.alert-success');
      const oldMsg = document.querySelector('.alert-success');
      if (oldMsg) oldMsg.remove();
      if (msg) formEstado.insertAdjacentElement('afterend', msg);
      btn.disabled = false;
      btn.textContent = 'Guardar';
      localStorage.setItem('recargarTablaArchivos', Date.now());
      location.reload();
    })
    .catch(() => {
      alert('Error al actualizar el estado');
      btn.disabled = false;
      btn.textContent = 'Guardar';
    });
  });
}
</script>

</body>
</html>
