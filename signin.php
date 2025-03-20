<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db_connect.php';

// Small debug element for development
echo "<div style='background:#f8f8f8; border:1px solid #ccc; padding:5px; margin-bottom:10px; font-size:11px; font-family:monospace;'>";
echo "DB: " . ($conn ? "Connected" : "Failed") . " | ";
echo "POST: " . (empty($_POST) ? "No" : "Yes");
echo "</div>";

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to profile page if already logged in
    header("Location: profile.php");
    exit();
}

// Initialize error message variable
$error_message = "";
$email = "";

// Process login form if submitted
if (!empty($_POST)) {  // This is the important change - check for any POST data
    // Get form inputs
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error_message = "Email and password are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address";
    } else {
        // Sanitize input
        $email = mysqli_real_escape_string($conn, $email);
        
        // Query for user with email
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) == 1) {
            // User found
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Debug successful login (only for development)
                echo "<div style='background:#e8f5e9; border:1px solid #2e7d32; padding:10px; margin:10px 0; font-family:monospace;'>";
                echo "<strong>Login Successful!</strong> User ID: " . $user['user_id'] . " | Username: " . $user['username'];
                echo "</div>";
                
                // Password is correct, set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect to profile page
                header("Location: profile.php");
                exit();
            } else {
                // Password is incorrect
                $error_message = "Invalid email or password";
            }
        } else {
            // User not found
            $error_message = "Invalid email or password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to access your Virtual Library account and explore our full collection of books">
    <meta name="keywords" content="library login, member access, virtual library signin, book borrowing, reader account">
    <title>Sign In - Virtual Library</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav class="main-nav">
            <div class="logo">
                <a href="index.php" class="nav-button">Home</a>
            </div>
            <div class="nav-links">
                <a href="catalog.php" class="nav-button">Guest Catalogue</a>
                <a href="member-catalog.php" class="nav-button">Member Catalog</a>
                <div class="auth-links">
                    <a href="admin.php" class="nav-button">Admin</a>
                    <a href="profile.php" class="nav-button">Profile</a>
                    <a href="signin.php" class="nav-button active">Sign In</a>
                    <a href="signup.php" class="nav-button">Sign Up</a>
                </div>
            </div>
        </nav>
    </header>
    <main>
        <section class="auth-container">
            <div class="auth-form">
                <h1>Sign In</h1>
                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                <form id="signin-form" method="post" action="">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required
                               pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                               value="<?php echo htmlspecialchars($email); ?>"
                               title="Please enter a valid email address">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required
                               title="Please enter your password">
                    </div>
                    <button type="submit" class="btn-primary">Sign In</button>
                </form>
                <p class="auth-links">
                    <span>Don't have an account? <a href="signup.php">Sign Up</a></span>
                </p>
            </div>
        </section>
    </main>
    <script src="scripts.js"></script>
</body>
</html> 