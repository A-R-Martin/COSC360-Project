<?php
session_start();

// Turn on all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db_connect.php';

// Debug info directly on page
/*
echo "<div style='background:#f8f8f8; border:1px solid #ccc; padding:10px; margin-bottom:20px; font-family:monospace;'>";
echo "<strong>Debug Info:</strong><br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Time: " . date('Y-m-d H:i:s') . "<br>";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "POST data received: " . (empty($_POST) ? "No" : "Yes") . "<br>";
echo "Database connection: " . ($conn ? "OK" : "FAILED") . "<br>";

// Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
echo "Users table exists: " . ($result->rowCount() > 0 ? "Yes" : "No") . "<br>";
echo "</div>";
*/

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
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Check if profile image was uploaded
    $profile_image_uploaded = isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK;
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required";
    } elseif (!$profile_image_uploaded) {
        $error_message = "Profile picture is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match";
    } else {
        // Check if username exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ($stmt->rowCount() > 0) {
            $error_message = "Username already exists";
        } else {
            // Check if email exists
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->rowCount() > 0) {
                $error_message = "Email already exists";
            } else {
                // Process profile image upload
                $profile_image_path = null;
                if ($profile_image_uploaded) {
                    $file = $_FILES['profile_image'];
                    $fileName = $file['name'];
                    $fileTmpName = $file['tmp_name'];
                    $fileSize = $file['size'];
                    $fileError = $file['error'];
                    
                    // Get file extension
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    
                    // Allowed extensions
                    $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    // Check if extension is allowed
                    if (in_array($fileExt, $allowedExts)) {
                        // Check file size (max 2MB)
                        if ($fileSize <= 2097152) {
                            // Create unique filename
                            $newFileName = 'profile_' . uniqid() . '.' . $fileExt;
                            $uploadDir = 'uploads/profiles/';
                            
                            if (substr($uploadDir, -1) !== '/') {
                                $uploadDir .= '/';
                            }
                            
                            // Create directory structure if it doesn't exist
                            if (!file_exists($uploadDir)) {
                                if (!mkdir($uploadDir, 0755, true)) {
                                    $error_message = 'Failed to create upload directory. Please contact administrator.';
                                    echo "<div class='alert alert-danger'>";
                                    echo "<strong>Error:</strong> " . $error_message;
                                    echo "</div>";
                                }
                            }
                            
                            $uploadPath = $uploadDir . $newFileName;
                            
                            // Move uploaded file
                            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                                $profile_image_path = $uploadPath;
                            } else {
                                $error_message = "Error uploading profile image";
                                echo "<div class='alert alert-danger'>";
                                echo "<strong>Error:</strong> " . $error_message;
                                echo "</div>";
                            }
                        } else {
                            $error_message = "Profile image size exceeds the limit (2MB)";
                        }
                    } else {
                        $error_message = "Invalid profile image type. Only JPG, PNG and GIF are allowed";
                    }
                }
                
                if (empty($error_message) && $profile_image_path) {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert new user
                    $insert_query = "INSERT INTO users (username, email, password, profile_image, role) VALUES (:username, :email, :password, :profile_image, 'user')";
                    try {
                        $stmt = $conn->prepare($insert_query);
                        $stmt->execute([
                            'username' => $username,
                            'email' => $email,
                            'password' => $hashed_password,
                            'profile_image' => $profile_image_path
                        ]);
                        
                        echo "<div class='alert alert-success'>";
                        echo "<strong>Success!</strong> User created with ID: " . $conn->lastInsertId();
                        echo "</div>";
                        
                        // Redirect to signin page
                        header("Location: signin.php");
                        exit();
                    } catch (PDOException $e) {
                        $error_message = "Error: " . $e->getMessage();
                        echo "<div class='alert alert-danger'>";
                        echo "<strong>DB Error:</strong> " . $e->getMessage();
                        echo "</div>";
                    }
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
        
        .profile-upload-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 2px solid #ddd;
            background-color: #ffffff;
        }
        
        .profile-image-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .profile-image-actions {
            margin-top: 10px;
        }
        
        .upload-btn-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        
        .upload-btn-wrapper input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
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
                
                <form id="signup-form" method="post" action="" enctype="multipart/form-data">
                    <div class="profile-image-container">
                        <img id="signup-profile-preview" class="profile-upload-preview" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Profile Image">
                        <div class="profile-image-actions">
                            <div class="upload-btn-wrapper">
                                <button type="button" class="btn-primary">Choose Profile Picture</button>
                                <input type="file" id="profile_image" name="profile_image" 
                                       accept="image/jpeg,image/png,image/gif" required
                                       title="Please select a profile picture (JPG, PNG, or GIF)">
                            </div>
                            <p class="form-help-text">* Profile picture is required</p>
                        </div>
                    </div>
                
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
                        <input type="password" id="confirm-password" name="confirm_password" required>
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
            
            // Profile image preview
            const profileImageInput = document.getElementById('profile_image');
            const profilePreview = document.getElementById('signup-profile-preview');
            
            if (profileImageInput) {
                profileImageInput.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (file) {
                        // Show image preview
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            profilePreview.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html> 