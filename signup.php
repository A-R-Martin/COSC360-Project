<?php
session_start();
// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to profile page if already logged in
    header("Location: profile.php");
    exit();
}

// Initialize variables
$error_message = "";
$success_message = "";

// Process registration form if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Here you would connect to your database and register the user
    // This is a placeholder for the actual registration logic
    
    // Example validation
    if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['confirm-password'])) {
        $error_message = "All fields are required";
    } elseif ($_POST['password'] !== $_POST['confirm-password']) {
        $error_message = "Passwords do not match";
    } else {
        // Placeholder for database connection and user creation
        // In a real application, you would:
        // 1. Connect to database
        // 2. Sanitize inputs
        // 3. Hash password
        // 4. Check if username/email already exists
        // 5. Insert new user record
        // 6. Set session variables if successful
        
        // For now, we'll just set a success message
        $success_message = "Account created successfully! You can now sign in.";
        
        // Redirect to sign in page after successful registration
        // Uncomment this in a real application:
        // header("Location: signin.php");
        // exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Join Virtual Library to unlock full access to our digital collection and exclusive member features">
    <meta name="keywords" content="library membership, create account, virtual library registration, new reader, join library">
    <title>Sign Up - Virtual Library</title>
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
                    <a href="signin.php" class="nav-button">Sign In</a>
                    <a href="signup.php" class="nav-button active">Sign Up</a>
                </div>
            </div>
        </nav>
    </header>
    <main>
        <section class="auth-container">
            <div class="auth-form">
                <h1>Create Account</h1>
                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="success-message">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                <form id="signup-form" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" novalidate>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required 
                               minlength="3" maxlength="30" 
                               pattern="^[a-zA-Z0-9_-]+$"
                               title="Username must be between 3-30 characters and can only contain letters, numbers, underscores and hyphens">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required
                               pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                               title="Please enter a valid email address">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required
                               minlength="8"
                               pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                               title="Password must be at least 8 characters long and include uppercase, lowercase, number and special character">
                        <ul class="password-requirements">
                            <li>At least 8 characters long</li>
                            <li>At least one uppercase letter</li>
                            <li>At least one lowercase letter</li>
                            <li>At least one number</li>
                            <li>At least one special character (@$!%*?&)</li>
                        </ul>
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Confirm Password</label>
                        <input type="password" id="confirm-password" name="confirm-password" required>
                    </div>
                    <button type="submit" class="btn-primary">Create Account</button>
                </form>
                <p class="auth-links">
                    Already have an account? <a href="signin.php">Sign In</a>
                </p>
            </div>
        </section>
    </main>
    <script src="scripts.js"></script>
</body>
</html> 