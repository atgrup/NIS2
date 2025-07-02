<?php
// routes/routes.php

require_once __DIR__ . '/../controllers/UsuarioController.php';
require_once __DIR__ . '/../controllers/ProveedorController.php';
require_once __DIR__ . '/../controllers/ConsultorController.php';

function handleRequest($uri, $method) {
    // Por ejemplo, asumiendo rutas así:
    // /api/usuarios
    // /api/proveedores
    // /api/consultores

    // Dividimos la ruta para analizar partes
    $segments = explode('/', trim($uri, '/')); // Quita slashes y separa

    // Si la ruta no tiene al menos 2 segmentos, no existe
    if (count($segments) < 2) {
        http_response_code(404);
        echo json_encode(['error' => 'Ruta no encontrada']);
        return;
    }

    $resource = $segments[1]; // usuarios, proveedores, consultores

    // Según el recurso, dirigimos al controlador
    switch ($resource) {
        case 'usuarios':
            routeUsuario($method, $segments);
            break;

        case 'proveedores':
            routeProveedor($method, $segments);
            break;

        case 'consultores':
            routeConsultor($method, $segments);
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Recurso no encontrado']);
            break;
    }
}

// Rutas para usuarios
function routeUsuario($method, $segments) {
    // Ejemplo: GET /api/usuarios -> listar
    //          GET /api/usuarios/5 -> ver usuario 5
    //          POST /api/usuarios -> crear usuario

    if ($method == 'GET' && count($segments) == 2) {
        UsuarioController::listar();
    } elseif ($method == 'GET' && count($segments) == 3) {
        $id = $segments[2];
        UsuarioController::ver($id);
    } elseif ($method == 'POST' && count($segments) == 2) {
        UsuarioController::crear();
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Ruta usuarios no encontrada']);
    }
}

// Rutas para proveedores
function routeProveedor($method, $segments) {
    if ($method == 'GET' && count($segments) == 2) {
        ProveedorController::listar();
    } elseif ($method == 'GET' && count($segments) == 3) {
        $id = $segments[2];
        ProveedorController::ver($id);
    } elseif ($method == 'POST' && count($segments) == 2) {
        ProveedorController::crear();
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Ruta proveedores no encontrada']);
    }
}

// Rutas para consultores
function routeConsultor($method, $segments) {
    if ($method == 'GET' && count($segments) == 2) {
        ConsultorController::listar();
    } elseif ($method == 'GET' && count($segments) == 3) {
        $id = $segments[2];
        ConsultorController::ver($id);
    } elseif ($method == 'POST' && count($segments) == 2) {
        ConsultorController::crear();
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Ruta consultores no encontrada']);
    }
}