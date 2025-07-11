<?php
header('Content-Type: application/json');
require_once 'Usuario.php';

try {
    $usuarioModel = new Usuario();

    $query = isset($_GET['query']) ? trim($_GET['query']) : '';

    if ($query === '') {
        $usuarios = $usuarioModel->getAllUsuarios();
    } else {
        $usuarios = $usuarioModel->buscarUsuariosPorNombreOId($query);
    }

    echo json_encode($usuarios);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$sql = "SELECT u.id_usuarios, u.correo, t.nombre AS tipo_usuario, u.verificado
        FROM usuarios u
        LEFT JOIN tipo_usuario t ON u.tipo_usuario_id = t.id_tipo_usuario
        ORDER BY u.id_usuarios
        LIMIT $perPage OFFSET $offset";
