<?php
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../auth/AuthHelper.php';
class UsuarioController {
    private static $model;

    private static function getModel() {
        if (!self::$model) {
            self::$model = new UsuarioModel();
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

    public static function listar() {
        $rol = self::checkAuthAndGetRole();
        // Por ejemplo, sólo admin puede listar todos
        if ($rol !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }

        $usuarios = self::getModel()->findAll();
        echo json_encode($usuarios);
    }

    public static function ver($id) {
        $rol = self::checkAuthAndGetRole();

        // Por ejemplo, usuarios sólo pueden ver su propio ID, admin todo
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
            echo json_encode(['error' => 'Usuario no encontrado']);
        }
    }

    public static function crear() {
        $rol = self::checkAuthAndGetRole();

        // Solo admin o proveedores pueden crear usuarios, ejemplo
        if (!in_array($rol, ['admin', 'proveedor'])) {
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

        if (self::getModel()->create($data)) {
            echo json_encode(['mensaje' => 'Usuario creado']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al crear usuario']);
        }
    }

    public static function actualizar($id) {
        $rol = self::checkAuthAndGetRole();

        // Solo admin o el mismo usuario pueden actualizar
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
            echo json_encode(['mensaje' => 'Usuario actualizado']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar usuario']);
        }
    }

    public static function borrar($id) {
        $rol = self::checkAuthAndGetRole();

        // Solo admin puede borrar usuarios
        if ($rol !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }

        if (self::getModel()->delete($id)) {
            echo json_encode(['mensaje' => 'Usuario eliminado']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar usuario']);
        }
    }
}
