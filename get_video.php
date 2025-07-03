<?php
// Enable error logging
error_log("get_video.php accessed with path: " . ($_GET['path'] ?? 'none') . ", id: " . ($_GET['id'] ?? 'none'));

// Load configuration from config.php
$appwrite = require 'config.php';

// If an ID is provided, fetch the video from Appwrite
if (isset($_GET['id'])) {
    // Set headers for JSON response
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    
    try {
        $videoId = $_GET['id'];
        
        // Set up cURL to fetch from Appwrite API
        $curl = curl_init();
        
        $url = $appwrite['endpoint'] . '/databases/' . $appwrite['databaseId'] . 
              '/collections/' . $appwrite['videoCollectionId'] . 
              '/documents/' . $videoId;
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Appwrite-Project: ' . $appwrite['projectId'],
                'X-Appwrite-Key: ' . $appwrite['apiKey']
            ]
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception('cURL Error: ' . $err);
        }
        
        $video = json_decode($response, true);
        
        if (isset($video['code']) && $video['code'] >= 400) {
            throw new Exception($video['message'] ?? 'Error fetching video');
        }
        
        // Format the video for frontend compatibility
        $thumbnailUrl = $appwrite['endpoint'] . '/storage/buckets/' . $appwrite['thumbnailBucketId'] . 
                      '/files/' . $video['thumbnail_id'] . '/preview?width=400&project=' . $appwrite['projectId'];
        
        $videoUrl = $appwrite['endpoint'] . '/storage/buckets/' . $appwrite['videoBucketId'] . 
                   '/files/' . $video['video_id'] . '/view?project=' . $appwrite['projectId'];
        
        $formattedVideo = [
            'id' => $video['$id'],
            'title' => $video['title'] ?? 'Untitled',
            'description' => $video['description'] ?? '',
            'price' => $video['price'] ?? 0,
            'duration' => $video['duration'] ?? '00:00',
            'views' => $video['views'] ?? rand(1000, 100000),
            'status' => isset($video['is_active']) && $video['is_active'] ? 'Active' : 'Inactive',
            'image' => $thumbnailUrl,
            'videoUrl' => $videoUrl,
            'videoLink' => $video['product_link'] ?? '',
            'created_at' => isset($video['created_at']) ? date('Y-m-d H:i:s', strtotime($video['created_at'])) : date('Y-m-d H:i:s')
        ];
        
        echo json_encode([
            'success' => true,
            'video' => $formattedVideo
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error loading video: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Get the video path from the request
$path = isset($_GET['path']) ? $_GET['path'] : '';

// Check if this is an Appwrite resource request
if (strpos($path, 'appwrite://') === 0) {
    // Parse the Appwrite URI: appwrite://bucket/fileId
    $parts = explode('/', str_replace('appwrite://', '', $path));
    
    if (count($parts) >= 2) {
        $bucketType = $parts[0]; // 'video' or 'thumbnail'
        $fileId = $parts[1];
        
        // Determine the bucket ID based on the bucket type
        $bucketId = ($bucketType === 'video') ? $appwrite['videoBucketId'] : $appwrite['thumbnailBucketId'];
        
        // Build the Appwrite file view URL
        $fileUrl = $appwrite['endpoint'] . '/storage/buckets/' . $bucketId . 
                  '/files/' . $fileId . '/view?project=' . $appwrite['projectId'];
        
        // Redirect to the Appwrite file
        header('Location: ' . $fileUrl);
        exit;
    } else {
        error_log("Invalid Appwrite path format: $path");
        header('HTTP/1.1 400 Bad Request');
        exit('Invalid Appwrite path format');
    }
}

// If not an Appwrite resource, handle as a local file (legacy support)
if (empty($path) || (strpos($path, 'videos/') !== 0 && strpos($path, 'imagens/') !== 0)) {
    error_log("Access denied for path: $path");
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

// Check if file exists
if (!file_exists($path)) {
    error_log("File not found: $path");
    header('HTTP/1.1 404 Not Found');
    exit('File not found');
}

// Get file info
$fileInfo = pathinfo($path);
$extension = strtolower($fileInfo['extension'] ?? '');

// Set appropriate content type
$contentTypes = [
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'mov' => 'video/quicktime',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif'
];

if (isset($contentTypes[$extension])) {
    header('Content-Type: ' . $contentTypes[$extension]);
} else {
    header('Content-Type: application/octet-stream');
}

// Set cache headers
header('Cache-Control: public, max-age=31536000');
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000));

// Output file
readfile($path);
?> 