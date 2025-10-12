<?php
require_once 'config.php';
startSession();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth.php');
    exit;
}

$db = getMongoDB();
$images = $db->images;

// Get user's images
$userImages = $images->find([
    'user_id' => $_SESSION['user_id']
], [
    'sort' => ['uploaded_at' => -1]
]);

// Handle image deletion
if (isset($_POST['delete_image'])) {
    $imageId = $_POST['image_id'];
    $image = $images->findOne(['_id' => new MongoDB\BSON\ObjectId($imageId)]);
    
    if ($image && $image['user_id'] === $_SESSION['user_id']) {
        $s3 = getS3Client();
        
        // Delete from S3
        try {
            $s3->deleteObject([
                'Bucket' => AWS_BUCKET,
                'Key'    => $image['filename']
            ]);
        } catch (Exception $e) {
            error_log("S3 deletion failed: " . $e->getMessage());
        }
        
        // Delete from MongoDB
        $images->deleteOne(['_id' => new MongoDB\BSON\ObjectId($imageId)]);
        
        header('Location: /dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CloudCanvas</title>
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
        <div class="dashboard-container">
            <h2>My Dashboard</h2>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>Total Images</h3>
                    <p class="stat-number"><?php echo iterator_count($userImages); ?></p>
                </div>
            </div>

            <div class="user-images">
                <h3>My Images</h3>
                
                <?php if (iterator_count($userImages) > 0): ?>
                    <div class="image-grid">
                        <?php foreach ($userImages as $image): ?>
                            <div class="image-card">
                                <img src="<?php echo htmlspecialchars($image['s3_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($image['original_name']); ?>">
                                <div class="image-details">
                                    <p class="image-name"><?php echo htmlspecialchars($image['original_name']); ?></p>
                                    <p class="image-meta">
                                        Uploaded: <?php echo date('M j, Y g:i A', $image['uploaded_at']->toDateTime()->getTimestamp()); ?>
                                    </p>
                                    <p class="image-visibility">
                                        <?php echo $image['is_public'] ? 'Public' : 'Private'; ?>
                                    </p>
                                    <form method="POST" class="delete-form">
                                        <input type="hidden" name="image_id" value="<?php echo (string)$image['_id']; ?>">
                                        <button type="submit" name="delete_image" class="btn btn-danger" 
                                                onclick="return confirm('Are you sure you want to delete this image?')">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No images uploaded yet</h3>
                        <p>Start sharing your images with the community!</p>
                        <a href="/upload.php" class="btn btn-primary">Upload Your First Image</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>