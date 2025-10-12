<?php
require_once 'config.php';
startSession();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed';
    } elseif ($file['size'] > UPLOAD_MAX_SIZE) {
        $error = 'File too large (max 5MB)';
    } elseif (!in_array($file['type'], ALLOWED_TYPES)) {
        $error = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
    } else {
        $db = getMongoDB();
        $s3 = getS3Client();
        
        try {
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . $_SESSION['user_id'] . '.' . $extension;
            
            // Upload to S3
            $result = $s3->putObject([
                'Bucket' => AWS_BUCKET,
                'Key'    => $filename,
                'Body'   => fopen($file['tmp_name'], 'rb'),
                'ContentType' => $file['type'],
                'ACL'    => 'public-read'
            ]);
            
            // Store metadata in MongoDB
            $images = $db->images;
            $images->insertOne([
                'user_id' => $_SESSION['user_id'],
                'filename' => $filename,
                'original_name' => $file['name'],
                's3_url' => $result['ObjectURL'],
                'uploaded_at' => new MongoDB\BSON\UTCDateTime(),
                'is_public' => isset($_POST['is_public']) ? true : false
            ]);
            
            $success = 'Image uploaded successfully!';
            
        } catch (Exception $e) {
            $error = 'Upload failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload - CloudCanvas</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo">CloudCanvas</h1>
            <div class="nav-links">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="/dashboard.php">Dashboard</a>
                <a href="/gallery.php">Gallery</a>
                <a href="/upload.php">Upload</a>
                <a href="/auth.php?action=logout">Logout</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="upload-container">
            <h2>Upload Image</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <div class="form-group">
                    <label for="image">Select Image (Max 5MB):</label>
                    <input type="file" id="image" name="image" accept="image/*" required>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_public" checked>
                        Make image public (visible in gallery)
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">Upload Image</button>
            </form>
            
            <div class="upload-info">
                <h3>Supported Formats:</h3>
                <ul>
                    <li>JPEG (.jpg, .jpeg)</li>
                    <li>PNG (.png)</li>
                    <li>GIF (.gif)</li>
                </ul>
                <p>Maximum file size: 5MB</p>
            </div>
        </div>
    </main>
</body>
</html>