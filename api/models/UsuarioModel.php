<?php

class Usuario {
    public $id;
    public $nombre;
    public $email;
    public $tipo_usuario;  // la wea esta de admin prov consultor de la bd 

    public function __construct($data = []) {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }
}