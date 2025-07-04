<?php
require_once __DIR__ . '/../models/ProveedorModel.php';
require_once __DIR__ . '/../auth/AuthHelper.php';

class ProveedorController {
    private static $model;

    private static function getModel() {
        if (!self::$model) {
            self::$model = new ProveedorModel();  
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

    public static function ver($id) {
        $rol = self::checkAuthAndGetRole();

        if ($rol !== 'admin' && $_SESSION['id'] != $id) {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }

        $usuario = self::getModel()->findById($id);
        if ($usuario) {
            echo json_encode($usuario);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Proveedor no encontrado']);
        }
    }

    public static function actualizar($id) {
        $rol = self::checkAuthAndGetRole();

        if ($rol !== 'admin' && $_SESSION['id'] != $id) {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['nombre'], $data['email'], $data['tipo_usuario'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan datos']);
            return;
        }

        if (self::getModel()->update($id, $data)) {
            echo json_encode(['mensaje' => 'Proveedor actualizado']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar proveedor']);
        }
    }

    // 👇 Nuevo método para subir una nueva versión de archivo
    public static function subirArchivo($idProveedor) {
        $rol = self::checkAuthAndGetRole();

        if ($rol !== 'proveedor' || $_SESSION['id'] != $idProveedor) {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }

        if (!isset($_FILES['archivo'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No se ha enviado ningún archivo']);
            return;
        }

        $archivo = $_FILES['archivo'];
        $nombre = basename($archivo['name']);
        $carpeta = __DIR__ . "/../uploads/$idProveedor";

        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        // Obtener la última versión registrada
        $ultima = self::getModel()->obtenerUltimaVersion($idProveedor, $nombre);
        $nuevaVersion = $ultima ? $ultima['version'] + 1 : 1;
        $rutaFinal = "$carpeta/{$nuevaVersion}_$nombre";

        if (move_uploaded_file($archivo['tmp_name'], $rutaFinal)) {
            // Guardar en la base de datos
            $datos = [
                'proveedor_id' => $idProveedor,
                'nombre_archivo' => $nombre,
                'ruta_archivo' => $rutaFinal,
                'version' => $nuevaVersion
            ];

            if (self::getModel()->guardarVersion($datos)) {
                echo json_encode(['mensaje' => 'Archivo subido con nueva versión']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al guardar en base de datos']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar archivo']);
        }
    }

    //  Listar historial de versiones de un archivo
    public static function historial($idProveedor, $nombreArchivo) {
        $rol = self::checkAuthAndGetRole();

        if ($rol !== 'admin' && $_SESSION['id'] != $idProveedor) {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }

        $historial = self::getModel()->obtenerHistorial($idProveedor, $nombreArchivo);
        echo json_encode($historial);
    }
}
