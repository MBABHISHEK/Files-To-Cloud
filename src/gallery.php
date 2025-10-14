<?php
require_once 'config.php';
startSession();

$db = getMySQLDB();

// Get public images with user information
$stmt = $db->prepare("
    SELECT i.id, i.original_name, i.mime_type, i.file_size, i.uploaded_at, u.username 
    FROM images i 
    JOIN users u ON i.user_id = u.id 
    WHERE i.is_public = 1 
    ORDER BY i.uploaded_at DESC 
    LIMIT 50
");
$stmt->execute();
$publicImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total public image count for stats
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM images WHERE is_public = 1");
$countStmt->execute();
$totalPublicImages = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
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
            <div class="gallery-header">
                <h2>Public Gallery</h2>
                <p>Discover <?php echo $totalPublicImages; ?> images shared by our community</p>
            </div>
            
            <?php if (count($publicImages) > 0): ?>
                <div class="gallery-grid">
                    <?php foreach ($publicImages as $image): ?>
                        <div class="gallery-item">
                            <div class="image-container">
                                <img src="/image_view.php?id=<?php echo $image['id']; ?>" 
                                     alt="<?php echo htmlspecialchars($image['original_name']); ?>"
                                     loading="lazy"
                                     class="gallery-image">
                                <div class="image-overlay">
                                    <a href="/image_view.php?id=<?php echo $image['id']; ?>" target="_blank" class="view-full">
                                        View Full Size
                                    </a>
                                </div>
                            </div>
                            <div class="image-info">
                                <p class="image-name"><?php echo htmlspecialchars($image['original_name']); ?></p>
                                <p class="uploader">By: <?php echo htmlspecialchars($image['username']); ?></p>
                                <p class="upload-time">
                                    <?php echo date('M j, Y g:i A', strtotime($image['uploaded_at'])); ?>
                                </p>
                                <p class="file-size"><?php echo round($image['file_size'] / 1024, 1); ?> KB</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No public images yet</h3>
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