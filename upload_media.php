<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Carregar configuração do Appwrite
$appwrite = require 'config.php';

$response = ['success' => false, 'message' => 'Unknown error'];

if (isset($_FILES['file']) && isset($_POST['type'])) {
    $type = $_POST['type'];
    
    // Definir o bucket ID baseado no tipo de arquivo
    $bucketId = ($type === 'images') ? $appwrite['thumbnailBucketId'] : $appwrite['videoBucketId'];
    
    try {
        $file = $_FILES['file'];
        $fileId = uniqid();
        $fileName = $file['name'];
        $fileTmpPath = $file['tmp_name'];
        $fileSize = $file['size'];
        
        // Configurar cURL para upload para o Appwrite
        $curl = curl_init();
        
        $url = $appwrite['endpoint'] . '/storage/buckets/' . $bucketId . '/files';
        
        // Criar os limites da requisição multipart
        $boundary = uniqid();
        $delimiter = '-------------' . $boundary;
        
        $postData = '';
        
        // ID do arquivo (opcional)
        $postData .= "--" . $delimiter . "\r\n";
        $postData .= 'Content-Disposition: form-data; name="fileId"' . "\r\n\r\n";
        $postData .= $fileId . "\r\n";
        
        // Permissões - permitir leitura para qualquer pessoa
        $postData .= "--" . $delimiter . "\r\n";
        $postData .= 'Content-Disposition: form-data; name="permissions[]"' . "\r\n\r\n";
        $postData .= 'read("any")' . "\r\n";
        
        // Arquivo
        $postData .= "--" . $delimiter . "\r\n";
        $postData .= 'Content-Disposition: form-data; name="file"; filename="' . $fileName . '"' . "\r\n";
        $postData .= 'Content-Type: ' . mime_content_type($fileTmpPath) . "\r\n\r\n";
        $postData .= file_get_contents($fileTmpPath) . "\r\n";
        $postData .= "--" . $delimiter . "--\r\n";
        
        $headers = [
            'Content-Type: multipart/form-data; boundary=' . $delimiter,
            'Content-Length: ' . strlen($postData),
            'X-Appwrite-Project: ' . $appwrite['projectId'],
            'X-Appwrite-Key: ' . $appwrite['apiKey']
        ];
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
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
            $fileData = json_decode($curlResponse, true);
            
            $response = [
                'success' => true,
                'message' => 'File uploaded successfully',
                'file' => [
                    'id' => $fileData['$id'],
                    'name' => $fileName,
                    'size' => $fileSize,
                    'bucketId' => $bucketId,
                    'type' => $type
                ]
            ];
        } else {
            $errorData = json_decode($curlResponse, true);
            $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'Unknown error';
            throw new Exception('API Error: ' . $errorMessage . ' (Code: ' . $httpCode . ')');
        }
    } catch (Exception $e) {
        $response['message'] = 'Failed to upload file: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'No file uploaded or invalid type';
}

echo json_encode($response);
?> 