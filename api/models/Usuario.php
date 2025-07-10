<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Conexión a la base de datos (debes adaptar esta parte)
$host = 'localhost';
$db   = 'dbs1il8vaitgwc';
$user = 'uncil0r4fqfan';
$pass = 'kyqgga3wpytt';
$charset = 'utf8mb4';

$port = 3306; // o el que use tu host
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error conexión DB: ' . $e->getMessage()]);
    exit;
}

// Detectar acción
$accion = $_POST['accion'] ?? null;

if (!$accion) {
    echo json_encode(['success' => false, 'message' => 'No se recibió acción']);
    exit;
}

if ($accion === 'crear_usuario') {
    // Recibir datos del formulario
    $correo = $_POST['correo'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $password = $_POST['password'] ?? ''; // si tienes password
    $tipo_usuario_id = $_POST['tipo_usuario_id'] ?? 3; // default consultor

    // Validar datos mínimos
    if (!$correo || !$nombre || !$password) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
        exit;
    }

    // Aquí deberías validar que el correo no exista ya, etc.

    // Hashear password (muy recomendado)
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar en la base de datos
    $sql = "INSERT INTO usuarios (correo, nombre, password, tipo_usuario_id) VALUES (:correo, :nombre, :password, :tipo_usuario_id)";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([
            ':correo' => $correo,
            ':nombre' => $nombre,
            ':password' => $passwordHash,
            ':tipo_usuario_id' => $tipo_usuario_id,
        ]);

        echo json_encode(['success' => true, 'message' => 'Usuario creado correctamente']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al crear usuario: ' . $e->getMessage()]);
    }

    exit;
}

// Puedes añadir más acciones aquí (editar usuario, borrar, etc.)

// Si acción no reconocida
echo json_encode(['success' => false, 'message' => 'Acción no válida']);
exit;



class Usuario {
    public $id;
    public $nombre;
    public $email;
    public $tipo_usuario;  // la wea esta de admin prov consultor de la bd 

    public function __construct($data = []) {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }
     // Getters
    public function getId() {
        return $this->id;
    }
    public function getNombre() {
        return $this->nombre;
    }
    public function getEmail() {
        return $this->email;
    }
    public function getTipo_usuario() {
        return $this->tipo_usuario;
    }

    // Setters
    public function setId($id) {
        $this->id = $id;
    }
    public function setNombre($nombre) {
        $this->nombre = $nombre;
    }
    public function setEmail($email) {
        $this->email = $email;
    }
    public function setTipo_usuario($tipo_usuario) {
        $this->tipo_usuario = $tipo_usuario;
    }

}