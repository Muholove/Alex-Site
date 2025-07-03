<?php
// Allow cross-origin requests (if needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle configuration updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Initialize response
    $response = [
        'success' => false,
        'message' => 'Unknown error occurred'
    ];
    
    // Handle file creation action
    if (isset($input['action']) && $input['action'] === 'create_file') {
        if (!isset($input['filename']) || !isset($input['content'])) {
            $response['message'] = 'Filename and content are required';
            echo json_encode($response);
            exit;
        }
        
        // Simple security check to prevent directory traversal
        $filename = basename($input['filename']);
        
        // Write file
        if (file_put_contents($filename, $input['content'])) {
            $response['success'] = true;
            $response['message'] = 'File created successfully';
            echo json_encode($response);
            exit;
        } else {
            $response['message'] = 'Failed to create file';
            echo json_encode($response);
            exit;
        }
    }
    
    // Handle regular config updates
    if (!isset($input['siteTitle'])) {
        $response['message'] = 'Site title is required';
        echo json_encode($response);
        exit;
    }
    
    // Load existing config or create new one
    $configFile = 'config.json';
    $config = [];
    
    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);
    }
    
    // Update config values
    $config['siteTitle'] = $input['siteTitle'];
    $config['telegramLink'] = $input['telegramLink'];
    
    // Add PayPal client ID if provided
    if (isset($input['paypalClientId'])) {
        $config['paypalClientId'] = $input['paypalClientId'];
    }
    
    // Add Telegram username if provided
    if (isset($input['telegramUsername'])) {
        $config['telegramUsername'] = $input['telegramUsername'];
    }
    
    // Update password if provided
    if (!empty($input['adminPassword'])) {
        // In a real application, you should hash the password
        // For simplicity, we're storing it as plain text here
        $config['adminPassword'] = $input['adminPassword'];
    }
    
    // Save config
    if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT))) {
        $response['success'] = true;
        $response['message'] = 'Configuration saved successfully';
    } else {
        $response['message'] = 'Failed to save configuration';
    }
    
    // Return response
    echo json_encode($response);
    exit;
}

// Handle GET request to retrieve config
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $configFile = 'config.json';
    
    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);
        
        // Remove sensitive data
        if (isset($config['adminPassword'])) {
            unset($config['adminPassword']);
        }
        
        echo json_encode($config);
    } else {
        echo json_encode([
            'siteTitle' => 'ADULTFLIX',
            'telegramLink' => 'https://t.me/SUPORT_FOLDER',
            'paypalClientId' => '',
            'telegramUsername' => ''
        ]);
    }
    exit;
}
?> 