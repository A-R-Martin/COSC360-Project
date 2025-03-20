<?php
session_start();

// Turn on all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db_connect.php';

// Debug info directly on page
echo "<div style='background:#f8f8f8; border:1px solid #ccc; padding:10px; margin-bottom:20px; font-family:monospace;'>";
echo "<strong>Debug Info:</strong><br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Time: " . date('Y-m-d H:i:s') . "<br>";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "POST data received: " . (empty($_POST) ? "No" : "Yes") . "<br>";
echo "Database connection: " . ($conn ? "OK" : "FAILED") . "<br>";

// Check if users table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
echo "Users table exists: " . (mysqli_num_rows($result) > 0 ? "Yes" : "No") . "<br>";
echo "</div>";

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to profile page if already logged in
    header("Location: profile.php");
    exit();
}

// Initialize variables
$error_message = "";
$success_message = "";
$username = "";
$email = "";

// Process registration form if submitted
if (!empty($_POST)) {  // This is the important change - check for any POST data
    // Get form inputs
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm-password']) ? $_POST['confirm-password'] : '';
    
    // Enhanced validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required";
    } elseif (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $username)) {
        $error_message = "Username must be between 3-30 characters and can only contain letters, numbers, underscores and hyphens";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        $error_message = "Password must include uppercase, lowercase, number and special character";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match";
    } else {
        // Sanitize inputs for database insertion
        $username = mysqli_real_escape_string($conn, $username);
        $email = mysqli_real_escape_string($conn, $email);
        
        // Check if username already exists
        $check_username = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
        if (mysqli_num_rows($check_username) > 0) {
            $error_message = "Username already exists";
        } else {
            // Check if email already exists
            $check_email = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
            if (mysqli_num_rows($check_email) > 0) {
                $error_message = "Email already exists";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user with default role of 'user'
                $insert_query = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$hashed_password', 'user')";
                $result = mysqli_query($conn, $insert_query);
                
                if ($result) {
                    // Show successful insertion details
                    echo "<div style='background:#e8f5e9; border:1px solid #2e7d32; padding:10px; margin:10px 0; font-family:monospace;'>";
                    echo "<strong>Success!</strong> User created with ID: " . mysqli_insert_id($conn);
                    echo "</div>";
                    
                    $success_message = "Account created successfully! You can now sign in.";
                    // Clear form data
                    $username = $email = "";
                    
                    // Redirect to signin page after 2 seconds
                    header("refresh:2;url=signin.php");
                } else {
                    // Show error details
                    echo "<div style='background:#ffebee; border:1px solid #c62828; padding:10px; margin:10px 0; font-family:monospace;'>";
                    echo "<strong>DB Error:</strong> " . mysqli_error($conn);
                    echo "</div>";
                    
                    $error_message = "Error: " . mysqli_error($conn);
                }
            }
        }
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
    <style>
        .debug-info {
            background-color: #f8f8f8;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            font-family: monospace;
            white-space: pre-wrap;
            font-size: 14px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
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
                
                <form id="signup-form" method="post" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required 
                               minlength="3" maxlength="30" 
                               pattern="^[a-zA-Z0-9_-]+$"
                               value="<?php echo htmlspecialchars($username); ?>"
                               title="Username must be between 3-30 characters and can only contain letters, numbers, underscores and hyphens">
                    </div>
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
    <script>
        // Password validation script
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const requirements = document.querySelectorAll('.password-requirements li');
            
            // Define validation patterns
            const patterns = [
                { regex: /.{8,}/, index: 0 },                          // At least 8 characters
                { regex: /[A-Z]/, index: 1 },                          // Uppercase letter
                { regex: /[a-z]/, index: 2 },                          // Lowercase letter
                { regex: /[0-9]/, index: 3 },                          // Number
                { regex: /[@$!%*?&#^()_+\-=\[\]{};':"\\|,.<>\/~`]/, index: 4 }  // Special character
            ];
            
            // Add styles for requirements
            const style = document.createElement('style');
            style.textContent = `
                .password-requirements li.met {
                    color: #66bb6a;
                    font-weight: normal;
                }
                .password-requirements li::before {
                    content: "✗ ";
                    color: #cc0000;
                }
                .password-requirements li.met::before {
                    content: "✓ ";
                    color: #66bb6a;
                }
            `;
            document.head.appendChild(style);
            
            // Add event listener for password input
            passwordInput.addEventListener('input', function() {
                const value = passwordInput.value;
                
                // Check each requirement
                patterns.forEach(pattern => {
                    const met = pattern.regex.test(value);
                    if (met) {
                        requirements[pattern.index].classList.add('met');
                    } else {
                        requirements[pattern.index].classList.remove('met');
                    }
                });
            });
        });
    </script>
</body>
</html> 