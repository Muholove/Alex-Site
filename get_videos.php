<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Debug mode
$debug = true;
$debug_info = [];

// Load configuration from config.php
$appwrite = require 'config.php';

try {
    // Set up cURL to fetch from Appwrite API
    $curl = curl_init();
    
    $url = $appwrite['endpoint'] . '/databases/' . $appwrite['databaseId'] . '/collections/' . $appwrite['videoCollectionId'] . '/documents?queries[]=is_active=true';
    $debug_info['request_url'] = $url;
    
    $headers = [
        'Content-Type: application/json',
        'X-Appwrite-Project: ' . $appwrite['projectId'],
        'X-Appwrite-Key: ' . $appwrite['apiKey']
    ];
    $debug_info['request_headers'] = $headers;
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_VERBOSE => $debug,
        CURLINFO_HEADER_OUT => true
    ]);
    
    $response = curl_exec($curl);
    $debug_info['curl_info'] = curl_getinfo($curl);
    $err = curl_error($curl);
    
    curl_close($curl);
    
    if ($err) {
        throw new Exception('cURL Error: ' . $err);
    }
    
    // Log the raw response for debugging
    if($debug) {
        $debug_info['raw_response'] = $response;
    }
    
    $result = json_decode($response, true);
    
    if ($result === null) {
        $debug_info['json_error'] = json_last_error_msg();
        throw new Exception('Invalid JSON response from Appwrite API: ' . json_last_error_msg());
    }
    
    if (!isset($result['documents']) && !isset($result['message'])) {
        $debug_info['response_structure'] = array_keys($result);
        throw new Exception('Invalid response structure from Appwrite API');
    }
    
    // Check if there's an error message in the response
    if (isset($result['message'])) {
        throw new Exception('Appwrite API Error: ' . $result['message'] . (isset($result['code']) ? ' (Code: ' . $result['code'] . ')' : ''));
    }
    
    $videos = $result['documents'];
    $debug_info['video_count'] = count($videos);
    
    // Format videos for frontend compatibility
    $formattedVideos = [];
    foreach ($videos as $video) {
        // Only include active videos
        if (isset($video['is_active']) && $video['is_active']) {
            // Format thumbnail and video URLs
            $thumbnailUrl = $appwrite['endpoint'] . '/storage/buckets/' . $appwrite['thumbnailBucketId'] . 
                          '/files/' . $video['thumbnail_id'] . '/preview?width=400&project=' . $appwrite['projectId'];
            
            $videoUrl = $appwrite['endpoint'] . '/storage/buckets/' . $appwrite['videoBucketId'] . 
                       '/files/' . $video['video_id'] . '/view?project=' . $appwrite['projectId'];
            
            $formattedVideos[] = [
                'id' => $video['$id'],
                'title' => $video['title'] ?? 'Untitled',
                'description' => $video['description'] ?? '',
                'price' => $video['price'] ?? 0,
                'duration' => $video['duration'] ?? '00:00',
                'views' => $video['views'] ?? rand(1000, 100000),
                'status' => $video['is_active'] ? 'Active' : 'Inactive',
                'image' => $thumbnailUrl,
                'videoUrl' => $videoUrl,
                'videoLink' => $video['product_link'] ?? '',
                'created_at' => isset($video['created_at']) ? date('Y-m-d H:i:s', strtotime($video['created_at'])) : date('Y-m-d H:i:s')
            ];
        }
    }
    
    // Sort videos by created_at in descending order (newest first)
    usort($formattedVideos, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    $response_data = [
        'success' => true,
        'videos' => $formattedVideos
    ];
    
    // Add debug info if enabled
    if ($debug) {
        $response_data['debug_info'] = $debug_info;
    }
    
    echo json_encode($response_data);
    
} catch (Exception $e) {
    $error_response = [
        'success' => false,
        'message' => 'Error loading videos: ' . $e->getMessage()
    ];
    
    // Add debug info if enabled
    if ($debug) {
        $error_response['debug_info'] = $debug_info;
    }
    
    echo json_encode($error_response);
}
?> 