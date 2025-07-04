<?php
require_once __DIR__ . '/../includes/conexion.php';
require_once 'Consultor.php';

class ConsultorModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM consultores WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new Consultor($row) : null;
    }
    public function consultorTieneAcceso($consultorId, $proveedorId)
    {
        // Por ahora devolver true si todos los consultores tienen acceso libre
        return true;

        // O si hay una tabla intermedia consultor_proveedor:
        /*
        $stmt = $this->db->prepare("SELECT 1 FROM consultor_proveedor WHERE consultor_id = ? AND proveedor_id = ?");
        $stmt->execute([$consultorId, $proveedorId]);
        return $stmt->fetchColumn() !== false;
        */
    }

    public function findAll()
    {
        $stmt = $this->db->query("SELECT * FROM consultores");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Consultor($row), $rows);
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE consultores SET nombre = ?, email = ?, nombre_empresa = ? WHERE id = ?
        ");
        return $stmt->execute([
            $data['nombre'],
            $data['email'],
            $data['nombre_empresa'],
            $id
        ]);
    }
    public function obtenerHistorialArchivo($proveedorId, $nombreArchivo)
    {
        // Ejemplo de implementación
        $stmt = $this->db->prepare("
        SELECT * FROM historial_archivos 
        WHERE proveedor_id = ? AND nombre_archivo = ?
        ORDER BY fecha_subida DESC
    ");
        $stmt->execute([$proveedorId, $nombreArchivo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Buscar proveedores por nombre de empresa
    public function buscarProveedoresPorEmpresa($nombreEmpresa)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM proveedores WHERE nombre_empresa LIKE ?
        ");
        $stmt->execute(["%$nombreEmpresa%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener archivos de un proveedor
    public function obtenerArchivosProveedor($proveedorId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM archivos WHERE proveedor_id = ?
        ");
        $stmt->execute([$proveedorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
