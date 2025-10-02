<?php
require 'conexion.php'; // tu conexión a la DB

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $stmt = $conn->prepare("UPDATE usuarios SET email_verified = 1, verification_code = NULL WHERE verification_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "✅ Tu correo ha sido verificado. Ya puedes iniciar sesión.";
    } else {
        echo "❌ Código inválido o ya verificado.";
    }
}
?>
