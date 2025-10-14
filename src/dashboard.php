<?php
require_once 'config.php';
startSession();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth.php');
    exit;
}

$db = getMySQLDB();
$user_id = $_SESSION['user_id'];

// Get user's images
$stmt = $db->prepare("SELECT id, filename, original_name, mime_type, file_size, uploaded_at, is_public FROM images WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->execute();
$userImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get image count
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM images WHERE user_id = ?");
$countStmt->bindParam(1, $user_id, PDO::PARAM_INT);
$countStmt->execute();
$imageCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Handle image deletion
if (isset($_POST['delete_image'])) {
    $imageId = $_POST['image_id'];
    
    // Verify the image belongs to the current user
    $verifyStmt = $db->prepare("SELECT id FROM images WHERE id = ? AND user_id = ?");
    $verifyStmt->bindParam(1, $imageId, PDO::PARAM_INT);
    $verifyStmt->bindParam(2, $user_id, PDO::PARAM_INT);
    $verifyStmt->execute();
    $image = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($image) {
        // Delete from database
        $deleteStmt = $db->prepare("DELETE FROM images WHERE id = ? AND user_id = ?");
        $deleteStmt->bindParam(1, $imageId, PDO::PARAM_INT);
        $deleteStmt->bindParam(2, $user_id, PDO::PARAM_INT);
        
        if ($deleteStmt->execute()) {
            header('Location: /dashboard.php');
            exit;
        } else {
            $error = "Failed to delete image";
        }
    } else {
        $error = "Image not found or you don't have permission to delete it";
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
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>Total Images</h3>
                    <p class="stat-number"><?php echo htmlspecialchars($imageCount); ?></p>
                </div>
                
                <?php
                // Get public image count
                $publicStmt = $db->prepare("SELECT COUNT(*) as public_count FROM images WHERE user_id = ? AND is_public = 1");
                $publicStmt->bindParam(1, $user_id, PDO::PARAM_INT);
                $publicStmt->execute();
                $publicCount = $publicStmt->fetch(PDO::FETCH_ASSOC)['public_count'];
                ?>
                <div class="stat-card">
                    <h3>Public Images</h3>
                    <p class="stat-number"><?php echo htmlspecialchars($publicCount); ?></p>
                </div>
                
                <?php
                // Get total storage used
                $storageStmt = $db->prepare("SELECT SUM(file_size) as total_storage FROM images WHERE user_id = ?");
                $storageStmt->bindParam(1, $user_id, PDO::PARAM_INT);
                $storageStmt->execute();
                $totalStorage = $storageStmt->fetch(PDO::FETCH_ASSOC)['total_storage'] ?? 0;
                ?>
                <div class="stat-card">
                    <h3>Storage Used</h3>
                    <p class="stat-number"><?php echo round($totalStorage / (1024 * 1024), 2); ?> MB</p>
                </div>
            </div>

            <div class="user-images">
                <h3>My Images</h3>
                
                <?php if (count($userImages) > 0): ?>
                    <div class="image-grid">
                        <?php foreach ($userImages as $image): ?>
                            <div class="image-card">
                                <img src="/image_view.php?id=<?php echo $image['id']; ?>" 
                                     alt="<?php echo htmlspecialchars($image['original_name']); ?>"
                                     loading="lazy">
                                <div class="image-details">
                                    <p class="image-name"><?php echo htmlspecialchars($image['original_name']); ?></p>
                                    <p class="image-meta">
                                        Uploaded: <?php echo date('M j, Y g:i A', strtotime($image['uploaded_at'])); ?>
                                    </p>
                                    <p class="image-meta">
                                        Size: <?php echo round($image['file_size'] / 1024, 1); ?> KB
                                    </p>
                                    <p class="image-visibility <?php echo $image['is_public'] ? 'public' : 'private'; ?>">
                                        <?php echo $image['is_public'] ? 'Public' : 'Private'; ?>
                                    </p>
                                    <form method="POST" class="delete-form">
                                        <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
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