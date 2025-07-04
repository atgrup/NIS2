<?php
require_once 'Usuario.php';

class Consultor extends Usuario {
    public $plantillas;   // como los clientes en plan atgroup de eso que puede ver archivos de sus propios consultores

    public function __construct($data = []) {
        parent::__construct($data);
        $this->plantillas = $data['plantillas'] ?? [];
    }

    // Métodos específicos para proveedores, por ejemplo subir archivos, asignar plantillas...
}