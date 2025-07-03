<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function scanDirectory($dir) {
    $files = [];
    if (is_dir($dir)) {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item != "." && $item != "..") {
                $path = $dir . '/' . $item;
                if (!is_dir($path)) {
                    $files[] = [
                        'name' => $item,
                        'path' => $path,
                        'size' => filesize($path),
                        'modified' => date('Y-m-d H:i:s', filemtime($path))
                    ];
                }
            }
        }
    }
    return $files;
}

$result = [
    'images' => scanDirectory('imagens'),
    'videos' => scanDirectory('videos')
];

echo json_encode($result);
?> 