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
    
    // Ensure Appwrite client-side config is available
    if (isset($config['appwrite'])) {
        // Remove API key for security - it should only be used server-side
        if (isset($config['appwrite']['apiKey'])) {
            unset($config['appwrite']['apiKey']);
        }
    }
} else {
    // Default configuration
    $config = [
        'siteTitle' => 'ADULTFLIX',
        'telegramLink' => 'https://t.me/your_telegram_contact',
        'paypalClientId' => 'AUt9AbUUPcy16ZYJenWPNfQHdWvMkDnqFWDXo3UgVPx14hBl9qFSGo9AzN9Z3CUzZ-mftWxyoKpsvYTN',
        'telegramUsername' => '',
        'appwrite' => [
            'endpoint' => 'https://cloud.appwrite.io/v1',
            'projectId' => '6852ab51002ca9bf6bd4',
            'databaseId' => '681f818100229727cfc0',
            'videoCollectionId' => '681f81a4001d1281896e',
            'thumbnailBucketId' => '681f82280005e6182fdd',
            'videoBucketId' => '681f820d00319f2aa58b'
        ],
        'debug' => true
    ];
}

echo json_encode($config);
?> 