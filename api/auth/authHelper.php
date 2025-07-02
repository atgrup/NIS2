<?php
function checkAuthAndGetRole() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
    $token = str_replace('Bearer ', '', $headers['Authorization']);
    
    // Aquí implementa la función para decodificar JWT, validar, etc.
    $payload = decodeJwt($token);
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido']);
        exit;
    }
    return $payload->rol;
}
