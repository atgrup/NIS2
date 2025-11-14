<?php
require '../includes/conexion.php'; // tu conexión a la BD

$mensaje = '';
$tipo_alerta = 'danger';
$verificado_ok = false;

// 🚩 Paso 1: si llega con GET (desde el enlace del correo)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['code'])) {
    $code = $_GET['code'];
    ?>
    <form id="verifyForm" method="POST" action="verify.php">
        <input type="hidden" name="code" value="<?php echo htmlspecialchars($code); ?>">
    </form>
    <script>
        // Autoenvía el formulario por POST y limpia la URL
        document.getElementById('verifyForm').submit();
    </script>
    <?php
    exit;
}

// 🚩 Paso 2: si llega por POST (ya oculto en la URL)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $code = $_POST['code'];

    // Buscar usuario con ese token
    $stmt = $conexion->prepare("SELECT id_usuarios, verificado FROM usuarios WHERE token_verificacion = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($row['verificado'] == 1) {
            $mensaje = "✅ Tu cuenta ya estaba verificada.";
            $tipo_alerta = 'info';
            $verificado_ok = true;
        } else {
            // ------ INICIO DE LA CORRECCIÓN ------
            // Actualizar a verificado Y LIMPIAR EL TOKEN
            $update = $conexion->prepare("UPDATE usuarios SET verificado = 1, token_verificacion = NULL WHERE token_verificacion = ?");
            // ------ FIN DE LA CORRECCIÓN ------
            
            $update->bind_param("s", $code);
            $update->execute();

            $mensaje = "✅ Tu correo ha sido verificado correctamente.";
            $tipo_alerta = 'success';
            $verificado_ok = true;
        }
    } else {
        $mensaje = "❌ Código inválido o expirado.";
        $tipo_alerta = 'danger';
    }
} else {
    $mensaje = "⚠️ Acceso no válido.";
    $tipo_alerta = 'warning';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verificación de correo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">

<div class="card p-4 shadow" style="width: 360px;">
    <h5 class="card-title text-center mb-3">Verificación de correo</h5>
    
    <div class="alert alert-<?php echo $tipo_alerta; ?>" role="alert">
        <?php echo $mensaje; ?>
    </div>

    <?php if (!$verificado_ok): ?>
        <form method="POST">
            <div class="mb-3">
                <label for="code" class="form-label">Introduce tu código de verificación</label>
                <input type="password" class="form-control" id="code" name="code" placeholder="Código enviado por correo" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Verificar</button>
        </form>
    <?php else: ?>
        <a href="../../pages/login.php" class="btn btn-success w-100 mt-2">Ir a iniciar sesión</a>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>