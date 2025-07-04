<?php
function checkAuthAndGetRole() {
    session_start();
    if (!isset($_SESSION['rol']) || !isset($_SESSION['id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
    return $_SESSION['rol'];
}

function getUserId() {
    session_start();
    if (!isset($_SESSION['id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
    return $_SESSION['id'];

}
