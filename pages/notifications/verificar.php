<?php
// Conectas a tu base de datos y recibes el token
$token = $_GET['token'] ?? '';
// Aquí buscarías el token en tu tabla de usuarios
// y si existe, marcas el usuario como verificado
// Ejemplo (simplificado):
if ($token) {
    echo "<h3>¡Tu correo ha sido verificado correctamente!</h3>";
} else {
    echo "<h3>Token inválido o expirado.</h3>";
}
?>