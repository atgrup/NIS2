<?php
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/../models/Usuario.php';

class UsuarioModel {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection(); // Asegúrate de tener esta clase Database creada
    }

    public function findAll() {
        $stmt = $this->db->prepare("SELECT * FROM usuarios");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS, 'Usuario');
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Usuario');
        return $stmt->fetch();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Usuario');
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO usuarios (nombre, email, tipo_usuario) VALUES (?, ?, ?)");
        return $stmt->execute([
            $data['nombre'],
            $data['email'],
            $data['tipo_usuario']
        ]);
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE usuarios SET nombre = ?, email = ?, tipo_usuario = ? WHERE id = ?");
        return $stmt->execute([
            $data['nombre'],
            $data['email'],
            $data['tipo_usuario'],
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }
    public function findByRoles(array $roles) {
    // Construimos placeholders para la consulta
    $placeholders = implode(',', array_fill(0, count($roles), '?'));

    $sql = "SELECT * FROM usuarios WHERE tipo_usuario IN ($placeholders)";
    $stmt = $this->db->prepare($sql);
    $stmt->execute($roles);
    return $stmt->fetchAll(PDO::FETCH_CLASS, 'Usuario');
    }

    public function crearUsuario($correo, $password, $tipo_usuario_id) {
        // Validar si correo existe
        $stmt = $this->conexion->prepare("SELECT id_usuarios FROM usuarios WHERE correo = ?");
        $stmt->bind_param('s', $correo);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return ['success' => false, 'message' => 'Correo ya registrado'];
        }
        $stmt->close();

        // Insertar usuario
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conexion->prepare("INSERT INTO usuarios (correo, password, tipo_usuario_id, verificado) VALUES (?, ?, ?, 0)");
        $stmt->bind_param('ssi', $correo, $password_hash, $tipo_usuario_id);
        if (!$stmt->execute()) {
            return ['success' => false, 'message' => 'Error al crear usuario'];
        }
        $usuario_id = $stmt->insert_id;
        $stmt->close();

        return ['success' => true, 'usuario_id' => $usuario_id];
    }
}


