<?php
// cleanup.php - Script to clean up unnecessary files after migration to Appwrite

// Set time limit to 5 minutes for large operations
set_time_limit(300);

// Create a backup directory
$backupDir = 'backup_' . date('Y-m-d_H-i-s');
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0777, true);
    mkdir("$backupDir/videos", 0777, true);
    mkdir("$backupDir/imagens", 0777, true);
}

// Files to be moved to backup
$filesToBackup = [
    'videos.json',
    'videos (1).json',
    'check_videos.php',
    'upload_media.php',
    'delete_media.php',
    'scan_media.php'
];

// List of backup operations performed
$backupOperations = [];
$errors = [];

// Move specified files to backup directory
foreach ($filesToBackup as $file) {
    if (file_exists($file)) {
        if (copy($file, "$backupDir/$file")) {
            $backupOperations[] = "Backed up $file to $backupDir/$file";
        } else {
            $errors[] = "Failed to backup $file";
            continue;
        }
        
        if (unlink($file)) {
            $backupOperations[] = "Removed original $file";
        } else {
            $errors[] = "Failed to remove original $file";
        }
    } else {
        $backupOperations[] = "File $file does not exist, skipping";
    }
}

// Backup video files
$videoFiles = glob('videos/*.*');
foreach ($videoFiles as $videoFile) {
    $filename = basename($videoFile);
    if (copy($videoFile, "$backupDir/videos/$filename")) {
        $backupOperations[] = "Backed up $videoFile to $backupDir/videos/$filename";
    } else {
        $errors[] = "Failed to backup $videoFile";
        continue;
    }
}

// Backup image files
$imageFiles = glob('imagens/*.*');
foreach ($imageFiles as $imageFile) {
    $filename = basename($imageFile);
    if (copy($imageFile, "$backupDir/imagens/$filename")) {
        $backupOperations[] = "Backed up $imageFile to $backupDir/imagens/$filename";
    } else {
        $errors[] = "Failed to backup $imageFile";
        continue;
    }
}

// Generate report
$report = "# Appwrite Migration Cleanup Report\n\n";
$report .= "Date: " . date('Y-m-d H:i:s') . "\n\n";

$report .= "## Backup Operations\n";
foreach ($backupOperations as $operation) {
    $report .= "- $operation\n";
}

if (!empty($errors)) {
    $report .= "\n## Errors\n";
    foreach ($errors as $error) {
        $report .= "- $error\n";
    }
}

$report .= "\n## Appwrite Configuration\n";
$report .= "- Project ID: 6852ab51002ca9bf6bd4\n";
$report .= "- Database ID: 681f818100229727cfc0\n";
$report .= "- Video Collection ID: 681f81a4001d1281896e\n";
$report .= "- Thumbnail Bucket ID: 681f82280005e6182fdd\n";
$report .= "- Video Bucket ID: 681f820d00319f2aa58b\n";

// Save report
file_put_contents("$backupDir/cleanup_report.md", $report);

// HTML output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Appwrite Migration Cleanup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            line-height: 1.6;
            color: #333;
        }
        
        h1 {
            color: #2196F3;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .success {
            color: #4CAF50;
        }
        
        .error {
            color: #F44336;
        }
        
        pre {
            background: #f5f5f5;
            padding: 15px;
            overflow: auto;
            border-radius: 5px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <h1>Appwrite Migration Cleanup</h1>
    
    <h2>Backup</h2>
    <p>Backup files saved to: <strong><?php echo $backupDir; ?></strong></p>
    
    <?php if (!empty($errors)): ?>
        <h2 class="error">Errors</h2>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li class="error"><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    
    <h2>Operations</h2>
    <ul>
        <?php foreach ($backupOperations as $operation): ?>
            <li class="success"><?php echo $operation; ?></li>
        <?php endforeach; ?>
    </ul>
    
    <h2>Report</h2>
    <p>A detailed report was saved to <strong><?php echo "$backupDir/cleanup_report.md"; ?></strong></p>
    
    <h2>Appwrite Configuration</h2>
    <pre>
Project ID: 6852ab51002ca9bf6bd4
Database ID: 681f818100229727cfc0
Video Collection ID: 681f81a4001d1281896e
Thumbnail Bucket ID: 681f82280005e6182fdd
Video Bucket ID: 681f820d00319f2aa58b
    </pre>
    
    <h2>Next Steps</h2>
    <ol>
        <li>Verify that the website is working correctly with Appwrite</li>
        <li>If everything is working, you can safely delete the backup directory</li>
        <li>If there are any issues, restore from the backup</li>
        <li>Update the admin interface to work with Appwrite</li>
    </ol>
</body>
</html> 