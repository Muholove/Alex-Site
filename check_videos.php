<?php
header('Content-Type: application/json');

// Function to fix video data
function fixVideoData($video) {
    return [
        'id' => $video['id'] ?? uniqid(),
        'title' => $video['title'] ?? '',
        'price' => floatval($video['price'] ?? 0),
        'description' => $video['description'] ?? '',
        'duration' => $video['duration'] ?? '00:00',
        'status' => $video['status'] ?? 'Active',
        'videoLink' => $video['videoLink'] ?? '',
        'image' => $video['image'] ?? '',
        'videoUrl' => $video['videoUrl'] ?? '',
        'created_at' => $video['created_at'] ?? date('Y-m-d H:i:s'),
        'updated_at' => $video['updated_at'] ?? date('Y-m-d H:i:s')
    ];
}

// Load and fix videos.json
if (file_exists('videos.json')) {
    $videos = json_decode(file_get_contents('videos.json'), true) ?? [];
    
    // Fix each video's data
    $fixedVideos = array_map('fixVideoData', $videos);
    
    // Sort by created_at
    usort($fixedVideos, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Save fixed data
    file_put_contents('videos.json', json_encode($fixedVideos, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'message' => 'Videos data fixed successfully',
        'videos' => $fixedVideos
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'videos.json not found'
    ]);
}
?> 