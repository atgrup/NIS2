<?php
require '../includes/conexion.php';
// Load mail helper to verify signed tokens if present
require_once __DIR__ . '/../../pages/notifications/enviar_correo.php';

$mensaje = "";
$tipo_alerta = "danger";
$verificado_ok = false;

// Helper: given possibly-signed code, return raw code or false
function resolveSignedCodeToRaw($code)
{
    // if not signed, return as is
    if (strpos($code, '.') === false) return $code;
    // signed format raw.sig -> extract raw and try to find user by raw
    list($raw, $sig) = explode('.', $code, 2);
    global $conexion;
    $stmt = $conexion->prepare("SELECT correo FROM usuarios WHERE token_verificacion = ? LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param('s', $raw);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) {
        $stmt->close();
        return false;
    }
    $row = $res->fetch_assoc();
    $stmt->close();
    $email = $row['correo'] ?? null;
    if (!$email) return false;
    if (function_exists('verifySignedTokenWithEmail')) {
        $ok = verifySignedTokenWithEmail($code, $email);
        return $ok === false ? false : $raw;
    }
    return false;
}

if (isset($_POST['code']) || isset($_GET['code'])) {
    $code = $_POST['code'] ?? $_GET['code'];
    // If code is signed, resolve to raw using stored user email
    $raw = resolveSignedCodeToRaw($code);
    if ($raw === false) {
        $mensaje = "❌ Código inválido o firma incorrecta.";
        $tipo_alerta = "danger";
    } else {
        // Buscar el usuario con ese token (raw)
        $stmt = $conexion->prepare("UPDATE usuarios SET verificado = 1, token_verificacion = NULL WHERE token_verificacion = ?");
        if (!$stmt) {
            die("Error preparando la verificación: " . $conexion->error);
        }
        $stmt->bind_param("s", $raw);
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
