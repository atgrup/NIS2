<?php
require_once 'Conexion.php';

class ArchivoModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexion::getInstance()->getConnection();
    }

    // Guardar registro de archivo subido
    public function subirArchivo($data) {
        $sql = "INSERT INTO archivos_subidos (usuario_id, nombre_archivo, ruta_archivo, fecha_subida) VALUES (:usuario_id, :nombre_archivo, :ruta_archivo, NOW())";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':usuario_id' => $data['usuario_id'],
            ':nombre_archivo' => $data['nombre_archivo'],
            ':ruta_archivo' => $data['ruta_archivo'],
        ]);
    }

    // Obtener archivos de un usuario específico
    public function obtenerArchivosPorUsuario($usuario_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM archivos_subidos WHERE usuario_id = ? ORDER BY fecha_subida DESC");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener archivo por ID
    public function obtenerArchivoPorId($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM archivos_subidos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Eliminar archivo (registro)
    public function eliminarArchivo($id) {
        $stmt = $this->pdo->prepare("DELETE FROM archivos_subidos WHERE id = ?");
        return $stmt->execute([$id]);
    }
}