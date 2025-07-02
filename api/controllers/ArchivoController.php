<?php
require_once __DIR__ . '/../models/ArchivoModel.php';

class ArchivoController {
    private $model;

    public function __construct() {
        $this->model = new ArchivoModel();
        session_start();
    }

    public function subir() {
        // Verificar si usuario está autenticado
        if (!isset($_SESSION['id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $data['usuario_id'] = $_SESSION['id'];

        if ($this->model->subirArchivo($data)) {
            echo json_encode(['mensaje' => 'Archivo subido con éxito']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al subir archivo']);
        }
    }

    // Otros métodos: listar, eliminar, etc.
}
