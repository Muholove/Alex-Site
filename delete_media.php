<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = ['success' => false, 'message' => 'Unknown error'];

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['file'])) {
    $file = $input['file'];
    
    // Security check: make sure file is in imagens/ or videos/ directory
    if (strpos($file, 'imagens/') === 0 || strpos($file, 'videos/') === 0) {
        if (file_exists($file) && unlink($file)) {
            $response = [
                'success' => true,
                'message' => 'File deleted successfully'
            ];
        } else {
            $response['message'] = 'Failed to delete file or file does not exist';
        }
    } else {
        $response['message'] = 'Invalid file path';
    }
} else {
    $response['message'] = 'No file specified';
}

echo json_encode($response);
?> 