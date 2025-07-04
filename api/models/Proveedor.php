<?php
require_once 'Usuario.php';

class Proveedor extends Usuario {
    public $nombre_empresa;   // Aquí puedes cargar las plantillas específicas del proveedor
    public $normativa;
    public $otros_datos;

    public function __construct($data = []) {
        parent::__construct($data);
        $this->plantillas = $data['plantillas'] ?? [];
    }

    // Métodos específicos para proveedores, por ejemplo subir archivos, asignar plantillas...
 public function cargarUsuario() {
        if ($this->usuario_id) {
            require_once 'UsuarioModel.php';
            $usuarioModel = new UsuarioModel();
            $this->usuario = $usuarioModel->findById($this->usuario_id);
        }
    }
    //esto es porque como esta la fk  de usuario_id en la de usuarios que hereda pues
    //una funcion para que lo busque y que se complete en la tabla y punch
}