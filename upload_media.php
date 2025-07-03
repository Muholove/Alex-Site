<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = ['success' => false, 'message' => 'Unknown error'];

if (isset($_FILES['file']) && isset($_POST['type'])) {
    $type = $_POST['type'];
    $uploadDir = ($type === 'images') ? 'imagens/' : 'videos/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileName = basename($_FILES['file']['name']);
    $targetPath = $uploadDir . $fileName;
    
    // Check if file already exists
    if (file_exists($targetPath)) {
        $fileName = time() . '_' . $fileName;
        $targetPath = $uploadDir . $fileName;
    }
    
    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        $response = [
            'success' => true,
            'message' => 'File uploaded successfully',
            'file' => [
                'name' => $fileName,
                'path' => $targetPath,
                'size' => filesize($targetPath)
            ]
        ];
    } else {
        $response['message'] = 'Failed to upload file';
    }
} else {
    $response['message'] = 'No file uploaded or invalid type';
}

echo json_encode($response);
?> 