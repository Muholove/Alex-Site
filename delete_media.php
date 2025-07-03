<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Carregar configuração do Appwrite
$appwrite = require 'config.php';

$response = ['success' => false, 'message' => 'Unknown error'];

// Handle OPTIONS request (for CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Receber dados JSON do corpo da requisição
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['fileId']) && isset($input['bucketId'])) {
    $fileId = $input['fileId'];
    $bucketId = $input['bucketId'];
    
    try {
        // Configurar cURL para excluir arquivo no Appwrite
        $curl = curl_init();
        
        $url = $appwrite['endpoint'] . '/storage/buckets/' . $bucketId . '/files/' . $fileId;
        
        $headers = [
            'Content-Type: application/json',
            'X-Appwrite-Project: ' . $appwrite['projectId'],
            'X-Appwrite-Key: ' . $appwrite['apiKey']
        ];
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => $headers
        ]);
        
        $curlResponse = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $response = [
                'success' => true,
                'message' => 'File deleted successfully'
            ];
        } else {
            $errorData = json_decode($curlResponse, true);
            $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'Unknown error';
            throw new Exception('API Error: ' . $errorMessage . ' (Code: ' . $httpCode . ')');
        }
    } catch (Exception $e) {
        $response['message'] = 'Failed to delete file: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'File ID or Bucket ID not specified';
}

echo json_encode($response);
?> 