<?php
header('Content-Type: application/json');

$host = 'jordio35.sg-host.com';
$db   = 'dbs1il8vaitgwc';
$user = 'uncil0r4fqfan';
$pass = 'kyqgga3wpytt';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Parámetros paginación
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
    $offset = ($page - 1) * $limit;

    // Total usuarios
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $total = (int)$totalStmt->fetchColumn();

    // Usuarios paginados
    $stmt = $pdo->prepare("SELECT correo, nombre, rol FROM usuarios ORDER BY id_usuarios LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $usuarios = $stmt->fetchAll();

    echo json_encode([
        'total' => $total,
        'usuarios' => $usuarios
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => '❌ Error conexión DB: ' . $e->getMessage()]);
    exit;
}
