<?php
require '../includes/conexion.php';

$mensaje = "";
$tipo_alerta = "danger";
$verificado_ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $code = $_POST['code'];

    // Buscar el usuario con ese token
    $stmt = $conexion->prepare("UPDATE usuarios SET verificado = 1, token_verificacion = NULL WHERE token_verificacion = ?");
    if (!$stmt) {
        die("Error preparando la verificación: " . $conexion->error);
    }
    $stmt->bind_param("s", $code);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $mensaje = "✅ Tu correo ha sido verificado. Ya puedes iniciar sesión.";
        $tipo_alerta = "success";
        $verificado_ok = true;
    } else {
        $mensaje = "❌ Código inválido o ya verificado.";
        $tipo_alerta = "danger";
    }
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

<div class="card p-4 shadow" style="width: 350px;">
    <h5 class="card-title text-center mb-3">Verificación de correo</h5>

    <div class="alert <?php echo $tipo_alerta === 'success' ? 'alert-success' : 'alert-danger'; ?>" role="alert">
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
    <a href="../../pages/login.php" class="btn btn-success w-100">Ir a iniciar sesión</a>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
