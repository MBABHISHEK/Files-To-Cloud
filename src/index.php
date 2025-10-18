<?php
require_once 'config.php';
startSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudCanvas - Share Your Images</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo">CloudCanvas</h1>
            <div class="nav-links">
                <a href="/">Home</a>
                <a href="/gallery.php">Gallery</a>
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
        <div class="hero">
            <div class="hero-content">
                <h2>Share Your Creativity with the World</h2>
                <p>Upload, manage, and share your images with the CloudCanvas community. Join thousands of creators today.</p>
                <div class="hero-buttons">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/upload.php" class="btn btn-primary">Upload Image</a>
                        <a href="/dashboard.php" class="btn btn-secondary">My Dashboard</a>
                    <?php else: ?>
                        <a href="/auth.php?action=register" class="btn btn-primary">Get Started</a>
                        <a href="/gallery.php" class="btn btn-secondary">View Gallery</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="features">
            <h2 style="text-align: center; margin-bottom: 2rem;">Why Choose CloudCanvas?</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <h3>🚀 Easy Upload</h3>
                    <p>Upload your images in seconds with our intuitive interface.</p>
                </div>
                <div class="feature-card">
                    <h3>🌍 Share Globally</h3>
                    <p>Share your creations with the world or keep them private.</p>
                </div>
                <div class="feature-card">
                    <h3>💾 Secure Storage</h3>
                    <p>Your images are safely stored in our secure database.</p>
                </div>
                <div class="feature-card">
                    <h3>📱 Responsive Design</h3>
                    <p>Access your images from any device, anywhere.</p>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; 2024 CloudCanvas. All rights reserved.</p>
    </footer>
</body>
</html