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
}
