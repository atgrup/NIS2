<?php
class Proveedor extends Usuario {
    public $usuario_id;  // <-- DECLARARLA AQUÍ
    public $nombre_empresa;
    public $normativa;
    public $otros_datos;

    public function __construct($data = []) {
        parent::__construct($data);
        $this->plantillas = $data['plantillas'] ?? [];
    }

    public function cargarUsuario() {
        if ($this->usuario_id) {
            require_once 'UsuarioModel.php';
            $usuarioModel = new UsuarioModel();
            $this->usuario = $usuarioModel->findById($this->usuario_id);
        }
    }
}
?>