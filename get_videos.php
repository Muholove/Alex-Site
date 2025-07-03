<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Debug mode
$debug = true;
$debug_info = [];

// Appwrite configuration
$appwrite = [
    'projectId' => '6852ab51002ca9bf6bd4',
    'databaseId' => '681f818100229727cfc0',
    'videoCollectionId' => '681f81a4001d1281896e',
    'thumbnailBucketId' => '681f82280005e6182fdd',
    'videoBucketId' => '681f820d00319f2aa58b',
    'apiKey' => 'standard_f291de00b8dc2241d3248c9786faa23a9e22f81d3ad95842dc9d955f42464bc179f70721cd83c483dcd5b2d596f529f4a77d67afb37f80040941bb7235a4a5f6e16ad4df4ba8299352e8ec59344efbd8a59da626fd684db28ec14221428d21c57c566b52ad84c6dde8055f3c189e93d347200a4778a065dae271c14c4105db0e',
    'endpoint' => 'https://cloud.appwrite.io/v1'
];

// Use environment variables if available
if(getenv('APPWRITE_ENDPOINT')) {
    $appwrite['endpoint'] = getenv('APPWRITE_ENDPOINT');
    $debug_info['endpoint_source'] = 'env';
}
if(getenv('APPWRITE_PROJECT_ID')) {
    $appwrite['projectId'] = getenv('APPWRITE_PROJECT_ID');
    $debug_info['project_id_source'] = 'env';
}
if(getenv('APPWRITE_API_KEY')) {
    $appwrite['apiKey'] = getenv('APPWRITE_API_KEY');
    $debug_info['api_key_source'] = 'env';
}

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