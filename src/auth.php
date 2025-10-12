<?php
require_once 'config.php';
startSession();

$action = $_GET['action'] ?? 'login';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getMongoDB();
    
    if ($action === 'register') {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($username && $email && $password) {
            $users = $db->users;
            
            // Check if user exists
            $existingUser = $users->findOne(['$or' => [
                ['username' => $username],
                ['email' => $email]
            ]]);
            
            if (!$existingUser) {
                $result = $users->insertOne([
                    'username' => $username,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'created_at' => new MongoDB\BSON\UTCDateTime()
                ]);
                
                if ($result->getInsertedId()) {
                    $success = 'Registration successful! Please login.';
                    $action = 'login';
                }
            } else {
                $error = 'Username or email already exists';
            }
        } else {
            $error = 'Please fill all fields';
        }
    } elseif ($action === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($username && $password) {
            $users = $db->users;
            $user = $users->findOne([
                'username' => $username
            ]);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = (string)$user['_id'];
                $_SESSION['username'] = $user['username'];
                header('Location: /dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Please fill all fields';
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: /');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($action); ?> - CloudCanvas</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo">CloudCanvas</h1>
            <div class="nav-links">
                <a href="/">Home</a>
                <a href="/gallery.php">Gallery</a>
            </div>
        </div>
    </nav>

    <main class="main-content auth-container">
        <div class="auth-form">
            <h2><?php echo ucfirst($action); ?></h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php if ($action === 'register'): ?>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <?php echo ucfirst($action); ?>
                </button>
            </form>
            
            <div class="auth-switch">
                <?php if ($action === 'login'): ?>
                    <p>Don't have an account? <a href="/auth.php?action=register">Register here</a></p>
                <?php else: ?>
                    <p>Already have an account? <a href="/auth.php">Login here</a></p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>