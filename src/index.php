<?php
require_once 'config.php';
startSession();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudCanvas - Image Sharing Platform</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo">CloudCanvas</h1>
            <div class="nav-links">
                <?php if ($isLoggedIn): ?>
                    <span>Welcome, <?php echo htmlspecialchars($username); ?></span>
                    <a href="/dashboard.php">Dashboard</a>
                    <a href="/gallery.php">Gallery</a>
                    <a href="/upload.php">Upload</a>
                    <a href="/auth.php?action=logout">Logout</a>
                <?php else: ?>
                    <a href="/auth.php">Login</a>
                    <a href="/auth.php?action=register">Register</a>
                    <a href="/gallery.php">Gallery</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <section class="hero">
            <div class="hero-content">
                <h2>Share Your World Through Images</h2>
                <p>A scalable, cloud-native platform for image sharing and management</p>
                <?php if (!$isLoggedIn): ?>
                    <div class="hero-buttons">
                        <a href="/auth.php?action=register" class="btn btn-primary">Get Started</a>
                        <a href="/gallery.php" class="btn btn-secondary">View Gallery</a>
                    </div>
                <?php else: ?>
                    <div class="hero-buttons">
                        <a href="/upload.php" class="btn btn-primary">Upload Image</a>
                        <a href="/dashboard.php" class="btn btn-secondary">My Dashboard</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="features">
            <div class="feature-grid">
                <div class="feature-card">
                    <h3>Scalable</h3>
                    <p>Built on cloud-native technologies that scale automatically with demand</p>
                </div>
                <div class="feature-card">
                    <h3>Secure</h3>
                    <p>Your images are securely stored with proper access controls</p>
                </div>
                <div class="feature-card">
                    <h3>Fast</h3>
                    <p>Optimized for performance with global content delivery</p>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <p>&copy; 2024 CloudCanvas. Built with PHP, Docker, and Kubernetes.</p>
    </footer>
</body>
</html>