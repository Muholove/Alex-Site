<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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

try {
    // Set up cURL to fetch from Appwrite API
    $curl = curl_init();
    
    $url = $appwrite['endpoint'] . '/databases/' . $appwrite['databaseId'] . '/collections/' . $appwrite['videoCollectionId'] . '/documents?queries[]=is_active=true';
    
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
    
    $result = json_decode($response, true);
    
    if (!isset($result['documents'])) {
        throw new Exception('Invalid response from Appwrite API');
    }
    
    $videos = $result['documents'];
    
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
    
    echo json_encode([
        'success' => true,
        'videos' => $formattedVideos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading videos: ' . $e->getMessage()
    ]);
}
?> 