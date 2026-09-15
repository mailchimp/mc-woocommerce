<?php
// Test-only loopback receiver. Do not serve through a site's PHP/FPM handler.
if (PHP_SAPI !== 'cli-server' || $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
    http_response_code(404);
    exit;
}
header('Content-Type: application/json');
echo json_encode(array(
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => array_change_key_case(getallheaders(), CASE_LOWER),
    'body' => file_get_contents('php://input'),
));
