<?php
require '../includes/conexion.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        header("Location: /NIS2/pages/login.php?error=credenciales");
        exit;
    }

    // Buscar usuario
    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        header("Location: /NIS2/pages/login.php?error=credenciales");
        exit;
    }

    // Verificar contraseña
    if (!password_verify($password, $user['password'])) {
        header("Location: /NIS2/pages/login.php?error=credenciales");
        exit;
    }

    // Determinar rol basado en tipo_usuario_id
    switch ($user['tipo_usuario_id']) {
        case 1:
            $rol = 'administrador'; 
            break;
        case 2:
            $rol = 'proveedor';
            if ($user['verificado'] == 0) {
                header("Location: /NIS2/pages/login.php?error=no_verificado");
                exit;
            }
            break;
        case 3:
            $rol = 'consultor';
            break;
        default:
            $rol = 'usuario';
            break;
    }

    // ------ INICIO DEL CAMBIO ------
    // Guardar sesión CON TODAS LAS VARIABLES
    $_SESSION['id_usuarios'] = $user['id_usuarios'];     // El script de subida necesita este
    $_SESSION['tipo_usuario_id'] = $user['tipo_usuario_id']; // El script de subida necesita este
    $_SESSION['rol'] = $rol;                         // plantillaUsers.php necesita este
    $_SESSION['correo'] = $user['correo'];             // Ambos scripts necesitan este
    // ------ FIN DEL CAMBIO ------

    // Redirigir al panel principal
    header("Location: /NIS2/pages/plantillaUsers.php");
    exit;

} else {
    header("Location: /NIS2/pages/login.php");
    exit;
}
?>