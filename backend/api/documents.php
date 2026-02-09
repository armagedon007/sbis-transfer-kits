<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../services/SbisService.php';
$config = require __DIR__ . '/../config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['fromWarehouse'], $input['toWarehouse'], $input['kits'])) {
        throw new Exception('Неверные параметры запроса');
    }
    
    $service = new SbisService($config);
    $result = $service->createTransferDocument(
        $input['fromWarehouse'],
        $input['toWarehouse'],
        $input['kits']
    );
    
    echo json_encode([
        'success' => true,
        'data' => $result,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
