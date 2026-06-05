<?php

header('Content-Type: application/json');

echo json_encode([
    'sistema' => 'ResiData API',
    'versao' => '1.0.0',
    'status' => 'online'
]);