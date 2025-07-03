<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Enable full error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Carregar configuração do Appwrite
$appwrite = require 'config.php';

// Log for debugging
error_log("==================== NEW REQUEST ====================");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data: " . json_encode($_POST));
    error_log("FILES data: " . json_encode($_FILES));
}

$response = ['success' => false, 'message' => 'Unknown error'];

// Handle OPTIONS request (for CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Handle DELETE request
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    error_log("Processing DELETE request");
    $input = json_decode(file_get_contents('php://input'), true);
    error_log("DELETE data: " . json_encode($input));
    
    if (isset($input['id'])) {
        $id = $input['id'];
        error_log("Deleting video with ID: " . $id);
        
        try {
            // Configurar cURL para excluir documento no Appwrite
            $curl = curl_init();
            
            $url = $appwrite['endpoint'] . '/databases/' . $appwrite['databaseId'] . '/collections/' . $appwrite['videoCollectionId'] . '/documents/' . $id;
            
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
                error_log("Video deleted successfully");
                $response = ['success' => true, 'message' => 'Video deleted successfully'];
            } else {
                $errorData = json_decode($curlResponse, true);
                $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'Unknown error';
                throw new Exception('API Error: ' . $errorMessage . ' (Code: ' . $httpCode . ')');
            }
        } catch (Exception $e) {
            error_log("Failed to delete video: " . $e->getMessage());
            $response = ['success' => false, 'message' => 'Failed to delete video: ' . $e->getMessage()];
        }
    } else {
        error_log("No video ID specified for deletion");
        $response = ['success' => false, 'message' => 'No video ID specified'];
    }
    
    echo json_encode($response);
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if this is a delete request via POST
        if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
            error_log("Processing DELETE via POST");
            $videoId = $_POST['id'];
            error_log("Deleting video with ID: " . $videoId);
            
            try {
                // Configurar cURL para excluir documento no Appwrite
                $curl = curl_init();
                
                $url = $appwrite['endpoint'] . '/databases/' . $appwrite['databaseId'] . '/collections/' . $appwrite['videoCollectionId'] . '/documents/' . $videoId;
                
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
                    $response = ['success' => true, 'message' => 'Video deleted successfully'];
                } else {
                    $errorData = json_decode($curlResponse, true);
                    $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'Unknown error';
                    throw new Exception('API Error: ' . $errorMessage . ' (Code: ' . $httpCode . ')');
                }
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'Failed to delete video: ' . $e->getMessage()];
            }
            
            echo json_encode($response);
            exit;
        }
        
        // Handle video upload/edit
        error_log("Processing video upload/edit");
        
        // Fix for edit vs new video - check for video-id field
        if (isset($_POST['video-id']) && !empty($_POST['video-id'])) {
            $id = trim($_POST['video-id']);
            error_log("Editing existing video with ID: '$id'");
            
            // Primeiro, buscar o documento existente
            $curl = curl_init();
            
            $url = $appwrite['endpoint'] . '/databases/' . $appwrite['databaseId'] . '/collections/' . $appwrite['videoCollectionId'] . '/documents/' . $id;
            
            $headers = [
                'Content-Type: application/json',
                'X-Appwrite-Project: ' . $appwrite['projectId'],
                'X-Appwrite-Key: ' . $appwrite['apiKey']
            ];
            
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers
            ]);
            
            $curlResponse = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            
            curl_close($curl);
            
            if ($error) {
                throw new Exception('cURL Error while fetching video: ' . $error);
            }
            
            if ($httpCode === 404) {
                // Documento não encontrado, tratar como novo
                $id = 'unique()'; // Appwrite vai gerar um ID
                $isNewVideo = true;
                $existingVideo = null;
                error_log("Video ID not found, creating new video");
            } elseif ($httpCode >= 200 && $httpCode < 300) {
                // Documento encontrado
                $existingVideo = json_decode($curlResponse, true);
                $isNewVideo = false;
                error_log("Found existing video document");
            } else {
                $errorData = json_decode($curlResponse, true);
                $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'Unknown error';
                throw new Exception('API Error while fetching video: ' . $errorMessage . ' (Code: ' . $httpCode . ')');
            }
        } else {
            $id = 'unique()'; // Appwrite vai gerar um ID
            $isNewVideo = true;
            $existingVideo = null;
            error_log("Creating new video with generated ID");
        }
        
        // Preparar dados para salvar
        $videoData = [];
        
        if ($existingVideo) {
            // Se for atualização, começar com os dados existentes
            $videoData = $existingVideo;
        }
        
        // Atualizar campos do formulário
        if (isset($_POST['title']) && !empty($_POST['title'])) {
            $videoData['title'] = $_POST['title'];
            error_log("Title: " . $_POST['title']);
        } else if ($isNewVideo) {
            $videoData['title'] = 'Untitled Video';
        }
        
        if (isset($_POST['price'])) {
            $videoData['price'] = floatval($_POST['price']);
            error_log("Price: " . $_POST['price']);
        } else if ($isNewVideo) {
            $videoData['price'] = 0.0;
        }
        
        if (isset($_POST['description'])) {
            $videoData['description'] = $_POST['description'];
            error_log("Description added");
        } else if ($isNewVideo) {
            $videoData['description'] = '';
        }
        
        if (isset($_POST['duration'])) {
            $videoData['duration'] = $_POST['duration'];
            error_log("Duration: " . $_POST['duration']);
        } else if ($isNewVideo) {
            $videoData['duration'] = '00:00';
        }
        
        if (isset($_POST['status'])) {
            $videoData['is_active'] = ($_POST['status'] === 'active');
            error_log("Status: " . $_POST['status']);
        } else if ($isNewVideo) {
            $videoData['is_active'] = true;
        }
        
        if (isset($_POST['video-link'])) {
            $videoData['product_link'] = $_POST['video-link'];
            error_log("Video link: " . $_POST['video-link']);
        } else if ($isNewVideo) {
            $videoData['product_link'] = '';
        }
        
        if (isset($_POST['views'])) {
            $videoData['views'] = intval($_POST['views']);
            error_log("Views: " . $_POST['views']);
        } else if ($isNewVideo) {
            $videoData['views'] = rand(1000, 10000);
        }
        
        // Processar upload de thumbnail
        if (isset($_FILES['thumbnail-upload']) && $_FILES['thumbnail-upload']['error'] === UPLOAD_ERR_OK) {
            error_log("Processing thumbnail upload");
            
            $file = $_FILES['thumbnail-upload'];
            $fileTmpPath = $file['tmp_name'];
            $fileName = $file['name'];
            $fileSize = $file['size'];
            
            // Gerar ID único para o arquivo
            $fileId = uniqid();
            
            // Configurar cURL para upload para o Appwrite
            $curl = curl_init();
            
            $url = $appwrite['endpoint'] . '/storage/buckets/' . $appwrite['thumbnailBucketId'] . '/files';
            
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
            
            $thumbnailResponse = curl_exec($curl);
            $thumbnailHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $thumbnailError = curl_error($curl);
            
            curl_close($curl);
            
            if ($thumbnailError) {
                throw new Exception('cURL Error during thumbnail upload: ' . $thumbnailError);
            }
            
            if ($thumbnailHttpCode >= 200 && $thumbnailHttpCode < 300) {
                $thumbnailData = json_decode($thumbnailResponse, true);
                
                // Atualizar ID da thumbnail no documento do vídeo
                $videoData['thumbnail_id'] = $thumbnailData['$id'];
                error_log("Thumbnail uploaded successfully with ID: " . $thumbnailData['$id']);
            } else {
                $errorData = json_decode($thumbnailResponse, true);
                $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'Unknown error';
                throw new Exception('API Error during thumbnail upload: ' . $errorMessage . ' (Code: ' . $thumbnailHttpCode . ')');
            }
        }
        
        // Processar upload de vídeo
        if (isset($_FILES['video-upload']) && $_FILES['video-upload']['error'] === UPLOAD_ERR_OK) {
            error_log("Processing video file upload");
            
            $file = $_FILES['video-upload'];
            $fileTmpPath = $file['tmp_name'];
            $fileName = $file['name'];
            $fileSize = $file['size'];
            
            // Gerar ID único para o arquivo
            $fileId = uniqid();
            
            // Configurar cURL para upload para o Appwrite
            $curl = curl_init();
            
            $url = $appwrite['endpoint'] . '/storage/buckets/' . $appwrite['videoBucketId'] . '/files';
            
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
            
            $videoFileResponse = curl_exec($curl);
            $videoFileHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $videoFileError = curl_error($curl);
            
            curl_close($curl);
            
            if ($videoFileError) {
                throw new Exception('cURL Error during video upload: ' . $videoFileError);
            }
            
            if ($videoFileHttpCode >= 200 && $videoFileHttpCode < 300) {
                $videoFileData = json_decode($videoFileResponse, true);
                
                // Atualizar ID do vídeo no documento
                $videoData['video_id'] = $videoFileData['$id'];
                error_log("Video file uploaded successfully with ID: " . $videoFileData['$id']);
            } else {
                $errorData = json_decode($videoFileResponse, true);
                $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'Unknown error';
                throw new Exception('API Error during video upload: ' . $errorMessage . ' (Code: ' . $videoFileHttpCode . ')');
            }
        }
        
        // Adicionar data de criação para novos vídeos
        if ($isNewVideo) {
            $videoData['created_at'] = date('c'); // ISO 8601 format
        }
        
        // Salvar documento no Appwrite
        $curl = curl_init();
        
        if ($isNewVideo) {
            // Criar novo documento
            $url = $appwrite['endpoint'] . '/databases/' . $appwrite['databaseId'] . '/collections/' . $appwrite['videoCollectionId'] . '/documents';
            $method = 'POST';
        } else {
            // Atualizar documento existente
            $url = $appwrite['endpoint'] . '/databases/' . $appwrite['databaseId'] . '/collections/' . $appwrite['videoCollectionId'] . '/documents/' . $id;
            $method = 'PATCH';
        }
        
        $headers = [
            'Content-Type: application/json',
            'X-Appwrite-Project: ' . $appwrite['projectId'],
            'X-Appwrite-Key: ' . $appwrite['apiKey']
        ];
        
        $postFields = json_encode([
            'documentId' => $isNewVideo ? $id : null,
            'data' => $videoData,
            'permissions' => ['read("any")']
        ]);
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => $headers
        ]);
        
        $documentResponse = curl_exec($curl);
        $documentHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $documentError = curl_error($curl);
        
        curl_close($curl);
        
        if ($documentError) {
            throw new Exception('cURL Error while saving document: ' . $documentError);
        }
        
        if ($documentHttpCode >= 200 && $documentHttpCode < 300) {
            $documentData = json_decode($documentResponse, true);
            
            $response = [
                'success' => true,
                'message' => $isNewVideo ? 'Video created successfully' : 'Video updated successfully',
                'video' => $documentData
            ];
        } else {
            $errorData = json_decode($documentResponse, true);
            $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'Unknown error';
            throw new Exception('API Error while saving document: ' . $errorMessage . ' (Code: ' . $documentHttpCode . ')');
        }
        
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
        error_log("Error: " . $e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

// Método não suportado
$response = [
    'success' => false,
    'message' => 'Unsupported request method'
];
echo json_encode($response);
?> 