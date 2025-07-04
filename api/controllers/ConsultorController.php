<?php
class ConsultorController {
    private static $model;

    private static function getModel() {
        if (!self::$model) {
            self::$model = new ConsultorModel();
        }
        return self::$model;
    }

    private static function checkAuthAndGetRole() {
        session_start();
        if (!isset($_SESSION['rol'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        return $_SESSION['rol'];
    }

    public static function verPerfil($id) {
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

    public static function verArchivosProveedor($idProveedor) {
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
        $archivos = self::getModel()->obtenerArchivosProveedor($idProveedor);
        echo json_encode($archivos);
    }

    public static function historialArchivo($idProveedor, $nombreArchivo) {
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

    // El resto (crear, actualizar, borrar, listar) eliminar o comentar si no se requieren.
}


