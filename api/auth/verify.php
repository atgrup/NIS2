<?php
require '../includes/conexion.php';

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $code = $_POST['code'];
    $stmt = $conexion->prepare("UPDATE usuarios SET verificado = 1, token_verificacion = NULL WHERE token_verificacion = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $mensaje = "✅ Tu correo ha sido verificado. Ya puedes iniciar sesión.";
    } else {
        $mensaje = "❌ Código inválido o ya verificado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación de correo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">

<div class="card p-4 shadow" style="width: 350px;">
    <h5 class="card-title text-center mb-3">Verificación de correo</h5>

    <?php if(!empty($mensaje)): ?>
        <div class="alert <?php echo (str_contains($mensaje, '✅') ? 'alert-success' : 'alert-danger'); ?>" role="alert">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="code" class="form-label">Introduce tu código de verificación</label>
            <input type="text" class="form-control" id="code" name="code" placeholder="Código enviado por correo" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Verificar</button>
    </form>
</div>

</body>
</html>
