<?php
// Inicia la sesión actual para poder manipularla
session_start();

// Elimina todas las variables de sesión
session_unset();

// Destruye por completo la sesión en el servidor
session_destroy();

// Redirige al usuario a la página principal (index.php en la raíz 2 carpetas arriba)
header("Location: ../../index.php");

// Finaliza el script
exit;
