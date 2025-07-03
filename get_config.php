<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$configFile = 'config.json';
$config = [];

if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    
    // Remove sensitive data
    if (isset($config['adminPassword'])) {
        unset($config['adminPassword']);
    }
} else {
    $config = [
        'siteTitle' => 'FreakLeaks',
        'telegramLink' => 'https://t.me/SUPORT_FOLDER',
        'paypalClientId' => 'AfE6gBNBJPKNWhjKno-7NrJZIQfVRve4G376qg1yhfFsACrKoKvuf9waJnUg0ewTMcAiY5BQTCrr8NNN',
        'telegramUsername' => ''
    ];
}

echo json_encode($config);
?> 