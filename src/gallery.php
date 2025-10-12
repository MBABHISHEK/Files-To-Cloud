<?php
require_once 'config.php';
startSession();

$db = getMongoDB();
$images = $db->images;

// Get public images
$publicImages = $images->find([
    'is_public' => true
], [
    'sort' => ['uploaded_at' => -1],
    'limit' => 50
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - CloudCanvas</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo">CloudCanvas</h1>
            <div class="nav-links">
                <a href="/">Home</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="/dashboard.php">Dashboard</a>
                    <a href="/upload.php">Upload</a>
                    <a href="/auth.php?action=logout">Logout</a>
                <?php else: ?>
                    <a href="/auth.php">Login</a>
                    <a href="/auth.php?action=register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="gallery-container">
            <h2>Public Gallery</h2>
            <p>Discover images shared by our community</p>
            
            <div class="gallery-grid">
                <?php foreach ($publicImages as $image): ?>
                    <div class="gallery-item">
                        <img src="<?php echo htmlspecialchars($image['s3_url']); ?>" 
                             alt="<?php echo htmlspecialchars($image['original_name']); ?>"
                             loading="lazy">
                        <div class="image-info">
                            <p class="image-name"><?php echo htmlspecialchars($image['original_name']); ?></p>
                            <p class="upload-time">
                                <?php echo date('M j, Y g:i A', $image['uploaded_at']->toDateTime()->getTimestamp()); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (iterator_count($publicImages) === 0): ?>
                <div class="empty-state">
                    <h3>No images yet</h3>
                    <p>Be the first to upload an image to the gallery!</p>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="/auth.php?action=register" class="btn btn-primary">Join Now</a>
                    <?php else: ?>
                        <a href="/upload.php" class="btn btn-primary">Upload Image</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>