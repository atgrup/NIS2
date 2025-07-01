<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conexion = new mysqli('jordio35.sg-host.com', 'u74bscuknwn9n', 'ad123456-', 'dbs1il8vaitgwc');

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Solo seleccionamos la columna password para verificar la contraseña
    $stmt = $conexion->prepare("SELECT password FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($hash);
        $stmt->fetch();

        if (password_verify($password, $hash)) {
            // Login exitoso: aquí puedes iniciar sesión, por ejemplo
            $_SESSION['usuario'] = $email;
            header("Location: plantillaUsers.php"); // Cambia por tu página protegida
            exit;
        } else {
            header("Location: login.php?error=contraseña");
            exit;
        }
    } else {
        header("Location: login.php?error=usuario_no_existe");
        exit;
    }

    $stmt->close();
    $conexion->close();
}
?>

