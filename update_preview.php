<?php
// Read the preview.html file
$filePath = 'preview.html';
$content = file_get_contents($filePath);

// Check if the config-loader.js script is already included
if (strpos($content, 'config-loader.js') === false) {
    // Add the script after the favicon line
    $updatedContent = str_replace(
        '<link rel="shortcut icon" href="fav.png" type="image/png">',
        '<link rel="shortcut icon" href="fav.png" type="image/png">' . PHP_EOL . '  <script src="config-loader.js"></script>',
        $content
    );
    
    // Write the updated content back to the file
    file_put_contents($filePath, $updatedContent);
    echo "Successfully updated preview.html\n";
} else {
    echo "config-loader.js is already included in preview.html\n";
}
?> 