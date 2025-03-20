<?php
session_start();
// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to profile page if already logged in
    header("Location: profile.php");
    exit();
}

// Initialize error message variable
$error_message = "";

// Process login form if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Here you would connect to your database and verify the user
    // This is a placeholder for the actual login logic
    
    // Example validation
    if (empty($_POST['email']) || empty($_POST['password'])) {
        $error_message = "Email and password are required";
    } else {
        // Placeholder for database connection and verification
        // In a real application, you would:
        // 1. Connect to database
        // 2. Sanitize inputs
        // 3. Query for user with email
        // 4. Verify password hash
        // 5. Set session variables if successful
        
        // For now, we'll just redirect to simulate a successful login
        // Replace this with actual authentication logic
        $_SESSION['user_id'] = 1; // Example user ID
        $_SESSION['username'] = "User"; // Example username
        header("Location: profile.php");
        exit();
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
                <form id="signin-form" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" novalidate>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required
                               pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
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