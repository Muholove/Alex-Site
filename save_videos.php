<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Enable full error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log for debugging
error_log("==================== NEW REQUEST ====================");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data: " . json_encode($_POST));
    error_log("FILES data: " . json_encode($_FILES));
}

$response = ['success' => false, 'message' => 'Unknown error'];

// Create directories if they don't exist
if (!file_exists('videos')) {
    mkdir('videos', 0777, true);
}
if (!file_exists('imagens')) {
    mkdir('imagens', 0777, true);
}
    
    // Load existing videos
    $videos = [];
    if (file_exists('videos.json')) {
    $jsonContent = file_get_contents('videos.json');
    error_log("Raw JSON content: " . substr($jsonContent, 0, 200) . "...");
    
    $videos = json_decode($jsonContent, true);
    if ($videos === null) {
        error_log("JSON decode error: " . json_last_error_msg());
        $videos = [];
    } else if (!is_array($videos)) {
        error_log("JSON decoded but not an array, type: " . gettype($videos));
        $videos = [];
    }
    error_log("Loaded " . count($videos) . " videos from videos.json");
    
    // Debug: Print all video IDs
    $videoIds = array_map(function($video) {
        return $video['id'] ?? 'unknown';
    }, $videos);
    error_log("Existing video IDs: " . implode(', ', $videoIds));
}

// Handle DELETE request
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    error_log("Processing DELETE request");
    $input = json_decode(file_get_contents('php://input'), true);
    error_log("DELETE data: " . json_encode($input));
    
    if (isset($input['id'])) {
        $id = $input['id'];
        error_log("Deleting video with ID: " . $id);
        
        // Count before deletion
        $countBefore = count($videos);
        error_log("Videos before deletion: " . $countBefore);
        
        // Filter out the video to delete
        $videosFiltered = array_filter($videos, function($video) use ($id) {
            return $video['id'] !== $id;
        });
        
        // Re-index array to ensure it's sequential
        $videosFiltered = array_values($videosFiltered);
        
        // Count after deletion
        $countAfter = count($videosFiltered);
        error_log("Videos after deletion: " . $countAfter);
        
        if ($countBefore === $countAfter) {
            error_log("No video found with ID: " . $id);
            $response = ['success' => false, 'message' => 'No video found with that ID'];
        } else if (file_put_contents('videos.json', json_encode($videosFiltered, JSON_PRETTY_PRINT))) {
            error_log("Video deleted successfully");
            $response = ['success' => true, 'message' => 'Video deleted successfully'];
        } else {
            error_log("Failed to delete video: Error writing to file");
            $response = ['success' => false, 'message' => 'Failed to delete video: Error writing to file'];
        }
    } else {
        error_log("No video ID specified for deletion");
        $response = ['success' => false, 'message' => 'No video ID specified'];
    }
    
    echo json_encode($response);
    exit;
}

// Handle OPTIONS request (for CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
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
            
            // Count before deletion
            $countBefore = count($videos);
            
            // Filter out the video to delete
            $videosFiltered = array_filter($videos, function($video) use ($videoId) {
                return $video['id'] !== $videoId;
            });
            
            // Re-index array to ensure it's sequential
            $videosFiltered = array_values($videosFiltered);
            
            // Count after deletion
            $countAfter = count($videosFiltered);
            
            if ($countBefore === $countAfter) {
                $response = ['success' => false, 'message' => 'No video found with that ID'];
            } else if (file_put_contents('videos.json', json_encode($videosFiltered, JSON_PRETTY_PRINT))) {
                $response = ['success' => true, 'message' => 'Video deleted successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete video'];
            }
            
            echo json_encode($response);
        exit;
    }
    
        // Handle video upload/edit
        error_log("Processing video upload/edit");
        
        // Get the form field names from the request
        error_log("Available POST fields: " . implode(", ", array_keys($_POST)));
        
        // Fix for edit vs new video - check for video-id field
        if (isset($_POST['video-id']) && !empty($_POST['video-id'])) {
            $id = trim($_POST['video-id']);
            error_log("Editing existing video with ID: '$id'");
            
            // Check if the ID actually exists in the videos array
            $videoExists = false;
            foreach ($videos as $video) {
                if (isset($video['id'])) {
                    // Convert both to strings for comparison
                    $videoId = (string)$video['id'];
                    $searchId = (string)$id;
                    
                    if ($videoId === $searchId) {
                        $videoExists = true;
                        error_log("Found match: Video ID '$videoId' matches search ID '$searchId'");
                        break;
                    }
                }
            }
            
            if (!$videoExists) {
                error_log("Warning: Video ID '$id' not found in videos.json");
                error_log("All video IDs in database: " . implode(', ', array_column($videos, 'id')));
                error_log("Type comparison check:");
                foreach ($videos as $index => $video) {
                    if (isset($video['id'])) {
                        $videoId = $video['id'];
                        error_log("Video $index - ID: '$videoId' (type: " . gettype($videoId) . ") vs '$id' (type: " . gettype($id) . ")");
                        error_log("String comparison: " . ((string)$videoId === (string)$id ? 'true' : 'false'));
                    }
                }
            }
        } else {
            $id = uniqid();
            error_log("Creating new video with generated ID: $id");
        }
        
        // Get existing video data if editing
        $existingVideo = [];
        $existingIndex = -1;
        foreach ($videos as $index => $video) {
            if (isset($video['id'])) {
                // Convert both to strings for comparison
                $videoId = (string)$video['id'];
                $searchId = (string)$id;
                
                if ($videoId === $searchId) {
                    $existingVideo = $video;
                    $existingIndex = $index;
                    error_log("Found existing video with ID: '$id' at index $index");
                    break;
                }
            }
        }
        
        // Different handling for new vs existing videos
        if ($existingIndex >= 0) {
            // EDITING EXISTING VIDEO
            // Start with existing data and only update what was submitted
            $videoData = $existingVideo;
            error_log("Editing existing video - keeping original data as base");
            
            // Only update fields that were submitted
            if (isset($_POST['title']) && !empty($_POST['title'])) {
                $videoData['title'] = $_POST['title'];
                error_log("Updated title: " . $_POST['title']);
            }
            
            if (isset($_POST['price']) && $_POST['price'] !== '') {
                $videoData['price'] = floatval($_POST['price']);
                error_log("Updated price: " . $_POST['price']);
            }
            
            if (isset($_POST['description']) && !empty($_POST['description'])) {
                $videoData['description'] = $_POST['description'];
                error_log("Updated description");
            }
            
            if (isset($_POST['duration']) && !empty($_POST['duration'])) {
                $videoData['duration'] = $_POST['duration'];
                error_log("Updated duration: " . $_POST['duration']);
            }
            
            if (isset($_POST['status'])) {
                $videoData['status'] = $_POST['status'];
                error_log("Updated status: " . $_POST['status']);
            }
            
            if (isset($_POST['video-link']) && !empty($_POST['video-link'])) {
                $videoData['videoLink'] = $_POST['video-link'];
                error_log("Updated video link: " . $_POST['video-link']);
            }
            
            // Handle thumbnail upload only if a file was actually uploaded
            if (isset($_FILES['thumbnail-upload']) && $_FILES['thumbnail-upload']['error'] === UPLOAD_ERR_OK && !empty($_FILES['thumbnail-upload']['name'])) {
                $imageFile = $_FILES['thumbnail-upload'];
                $imageExt = pathinfo($imageFile['name'], PATHINFO_EXTENSION);
                $imagePath = 'imagens/' . $id . '_thumb.' . $imageExt;
                
                error_log("Uploading new thumbnail to: $imagePath");
                
                if (!move_uploaded_file($imageFile['tmp_name'], $imagePath)) {
                    throw new Exception('Failed to upload thumbnail');
                }
                
                $videoData['image'] = $imagePath;
                error_log("Updated image path: $imagePath");
            } else if (isset($_POST['existing-image']) && !empty($_POST['existing-image'])) {
                // Keep existing image from form
                $videoData['image'] = $_POST['existing-image'];
                error_log("Kept existing image: " . $_POST['existing-image']);
            }
            // else - keep the existing image from database (already in $videoData)
            
            // Handle video upload only if a file was actually uploaded
            if (isset($_FILES['video-upload']) && $_FILES['video-upload']['error'] === UPLOAD_ERR_OK && !empty($_FILES['video-upload']['name'])) {
                $videoFile = $_FILES['video-upload'];
                $videoExt = pathinfo($videoFile['name'], PATHINFO_EXTENSION);
                $videoPath = 'videos/' . $id . '_preview.' . $videoExt;
                
                error_log("Uploading new video to: $videoPath");
                
                if (!move_uploaded_file($videoFile['tmp_name'], $videoPath)) {
                    throw new Exception('Failed to upload video');
                }
                
                $videoData['videoUrl'] = $videoPath;
                error_log("Updated video path: $videoPath");
            } else if (isset($_POST['existing-video']) && !empty($_POST['existing-video'])) {
                // Keep existing video from form
                $videoData['videoUrl'] = $_POST['existing-video'];
                error_log("Kept existing video: " . $_POST['existing-video']);
            }
            // else - keep the existing video from database (already in $videoData)
            
            // Always update the updated_at timestamp
            $videoData['updated_at'] = date('Y-m-d H:i:s');
            
        } else {
            // CREATING NEW VIDEO
            // For new videos, all required fields must be present
            $title = $_POST['title'] ?? '';
            $price = $_POST['price'] ?? 0;
            $description = $_POST['description'] ?? '';
            $duration = $_POST['duration'] ?? '00:00';
            $status = $_POST['status'] ?? 'Active';
            $videoLink = $_POST['video-link'] ?? '';
            
            error_log("Creating new video - ID: $id, Title: $title, Price: $price");
            
            // Check required fields individually
            $missingFields = [];
            if (empty($title)) $missingFields[] = 'title';
            if (empty($price)) $missingFields[] = 'price';
            if (empty($description)) $missingFields[] = 'description';
            if (empty($videoLink)) $missingFields[] = 'video-link';
            
            // Return detailed error if any required fields are missing
            if (!empty($missingFields)) {
                $message = 'Required fields are missing: ' . implode(', ', $missingFields);
                error_log($message);
                throw new Exception($message);
            }
            
            // Handle thumbnail upload
            $imagePath = '';
            if (isset($_FILES['thumbnail-upload']) && $_FILES['thumbnail-upload']['error'] === UPLOAD_ERR_OK && !empty($_FILES['thumbnail-upload']['name'])) {
                $imageFile = $_FILES['thumbnail-upload'];
                $imageExt = pathinfo($imageFile['name'], PATHINFO_EXTENSION);
                $imagePath = 'imagens/' . $id . '_thumb.' . $imageExt;
                
                error_log("Uploading thumbnail to: $imagePath");
                
                if (!move_uploaded_file($imageFile['tmp_name'], $imagePath)) {
                    throw new Exception('Failed to upload thumbnail');
                }
            } else if (isset($_POST['existing-image']) && !empty($_POST['existing-image'])) {
                // Use existing image from form
                $imagePath = $_POST['existing-image'];
                error_log("Using existing image: $imagePath");
            }
            
            // Handle video upload
            $videoPath = '';
            if (isset($_FILES['video-upload']) && $_FILES['video-upload']['error'] === UPLOAD_ERR_OK && !empty($_FILES['video-upload']['name'])) {
                $videoFile = $_FILES['video-upload'];
                $videoExt = pathinfo($videoFile['name'], PATHINFO_EXTENSION);
                $videoPath = 'videos/' . $id . '_preview.' . $videoExt;
                
                error_log("Uploading video to: $videoPath");
                
                if (!move_uploaded_file($videoFile['tmp_name'], $videoPath)) {
                    throw new Exception('Failed to upload video');
                }
            } else if (isset($_POST['existing-video']) && !empty($_POST['existing-video'])) {
                // Use existing video from form
                $videoPath = $_POST['existing-video'];
                error_log("Using existing video: $videoPath");
            }
            
            // Prepare video data for new video
            $videoData = [
            'id' => $id,
            'title' => $title,
                'price' => floatval($price),
            'description' => $description,
            'duration' => $duration,
            'status' => $status,
                'videoLink' => $videoLink,
                'image' => $imagePath,
                'videoUrl' => $videoPath,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }
        
        error_log("Final video data prepared: " . json_encode($videoData));
        
        // Update or add video - direct index update for existing videos
        if ($existingIndex >= 0) {
            $videos[$existingIndex] = $videoData;
            error_log("Updated existing video at index $existingIndex with ID: $id");
        } else {
            $videos[] = $videoData;
            error_log("Added new video with ID: $id");
        }
        
        // Sort videos by created_at
        usort($videos, function($a, $b) {
            return strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0);
        });
        
        // Re-index array to ensure it's sequential
        $videos = array_values($videos);
        
        // Debug: Print all video IDs after update
        $videoIds = array_map(function($video) {
            return $video['id'] ?? 'unknown';
        }, $videos);
        error_log("Video IDs after update: " . implode(', ', $videoIds));
        
        // Save updated video list
        error_log("Saving videos.json with " . count($videos) . " videos");
    if (file_put_contents('videos.json', json_encode($videos, JSON_PRETTY_PRINT))) {
            $response = ['success' => true, 'message' => 'Video saved successfully'];
    } else {
            throw new Exception('Failed to save video data');
        }
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
}

echo json_encode($response);
?> 