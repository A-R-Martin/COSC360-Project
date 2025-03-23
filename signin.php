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
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Prepare SQL statement
    $sql = "SELECT * FROM users WHERE email = :email";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['email' => $email]);
    
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($password, $user['password'])) {
            // Debug successful login (only for development)
            // Check if the user is banned
            if (isset($user['status']) && $user['status'] === 'banned') {
                $error_message = "Your account has been banned. Please contact the administrator.";
            } else {
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
            }
        } else {
            $error_message = "Invalid email or password";
        }
    } else {
        $error_message = "Invalid email or password";
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
    <?php include 'nav.php'; ?>
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
                        <div class="password-field">
                            <input type="password" id="password" name="password" required
                                   title="Please enter your password">
                            <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                                <span class="show-password">👁️</span>
                            </button>
                        </div>
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
    <script>
        // Password visibility toggle
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('.toggle-password');
            const passwordInput = document.getElementById('password');
            
            // Add styles for password field
            const style = document.createElement('style');
            style.textContent = `
                .password-field {
                    position: relative;
                    display: flex;
                    align-items: center;
                }
                .toggle-password {
                    position: absolute;
                    right: 10px;
                    background: none;
                    border: none;
                    cursor: pointer;
                    color: #777;
                    font-size: 16px;
                    padding: 0;
                    display: flex;
                    align-items: center;
                }
                .toggle-password:hover, .toggle-password:focus {
                    color: #333;
                    outline: none;
                }
            `;
            document.head.appendChild(style);
            
            // Toggle password visibility
            if (togglePassword) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    // Change the icon/text based on password visibility
                    this.querySelector('.show-password').textContent = type === 'password' ? '👁️' : '🔒';
                });
            }
        });
    </script>
</body>
</html> 