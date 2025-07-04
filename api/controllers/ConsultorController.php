<?php
require_once __DIR__ . '/../models/ConsultorModel.php';
require_once __DIR__ . '/../auth/AuthHelper.php';
<<<<<<< Updated upstream

class ConsultorController
{
    private static $model;

    private static function getModel()
    {
        if (!self::$model) {
            self::$model = new ConsultorModel();  // modelo específico para consultor
=======
class ConsultorController {
    private static $model;

    private static function getModel() {
        if (!self::$model) {
            self::$model = new ConsultorModel();
>>>>>>> Stashed changes
        }
        return self::$model;
    }

<<<<<<< Updated upstream
    private static function checkAuthAndGetRole()
    {
=======
    private static function checkAuthAndGetRole() {
>>>>>>> Stashed changes
        session_start();
        if (!isset($_SESSION['rol'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        return $_SESSION['rol'];
    }

<<<<<<< Updated upstream
    // Consultor puede ver su propio perfil (opcional)
    public static function verPerfil($id)
    {
        $rol = self::checkAuthAndGetRole();
        if ($rol !== 'consultor' || $_SESSION['id'] != $id) {
=======
    public static function listar() {
        $rol = self::checkAuthAndGetRole();
        // Por ejemplo, sólo admin puede listar todos
        if ($rol !== 'admin') {
>>>>>>> Stashed changes
            http_response_code(403);
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }
<<<<<<< Updated upstream
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
=======

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
>>>>>>> Stashed changes
            http_response_code(403);
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }

<<<<<<< Updated upstream
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
=======
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
>>>>>>> Stashed changes
            http_response_code(403);
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }

<<<<<<< Updated upstream
        $consultorId = $_SESSION['id'];
        if (!self::getModel()->consultorTieneAcceso($consultorId, $idProveedor)) {
            http_response_code(403);
            echo json_encode(['error' => 'No tiene acceso a este proveedor']);
            exit;
        }

        $historial = self::getModel()->obtenerHistorialArchivo($idProveedor, $nombreArchivo);
        echo json_encode($historial);
=======
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
>>>>>>> Stashed changes
    }
}
