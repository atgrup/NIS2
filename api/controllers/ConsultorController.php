<?php
require_once __DIR__ . '/../models/ConsultorModel.php';
require_once __DIR__ . '/../auth/AuthHelper.php';

class ConsultorController
{
    private static $model;

    private static function getModel()
    {
        if (!self::$model) {
            self::$model = new ConsultorModel();  // modelo específico para consultor
        }
        return self::$model;
    }

    private static function checkAuthAndGetRole()
    {
        session_start();
        if (!isset($_SESSION['rol'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        return $_SESSION['rol'];
    }

    // Consultor puede ver su propio perfil (opcional)
    public static function verPerfil($id)
    {
        $rol = self::checkAuthAndGetRole();
        if ($rol !== 'consultor' || $_SESSION['id'] != $id) {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }
        $consultor = self::getModel()->findById($id);
        if ($consultor) {
            echo json_encode($consultor);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Consultor no encontrado']);
        }
    }

    // Consultor puede ver archivos de proveedores asignados o en proceso
    // Aquí `$idProveedor` puede ser un parámetro para filtrar archivos de ese proveedor
    public static function verArchivosProveedor($idProveedor)
    {
        $rol = self::checkAuthAndGetRole();

        if ($rol !== 'consultor') {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }

        // Aquí se puede añadir lógica para validar si el consultor tiene acceso a este proveedor
        // Por simplicidad, asumo que self::getModel()->consultorTieneAcceso($consultorId, $idProveedor)
        $consultorId = $_SESSION['id'];
        if (!self::getModel()->consultorTieneAcceso($consultorId, $idProveedor)) {
            http_response_code(403);
            echo json_encode(['error' => 'No tiene acceso a este proveedor']);
            exit;
        }

        $archivos = self::getModel()->obtenerArchivosProveedor($idProveedor);

        echo json_encode($archivos);
    }

    // Consultor puede ver el historial de versiones de un archivo de proveedor
    public static function historialArchivo($idProveedor, $nombreArchivo)
    {
        $rol = self::checkAuthAndGetRole();

        if ($rol !== 'consultor') {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }

        $consultorId = $_SESSION['id'];
        if (!self::getModel()->consultorTieneAcceso($consultorId, $idProveedor)) {
            http_response_code(403);
            echo json_encode(['error' => 'No tiene acceso a este proveedor']);
            exit;
        }

        $historial = self::getModel()->obtenerHistorialArchivo($idProveedor, $nombreArchivo);
        echo json_encode($historial);
    }
}
