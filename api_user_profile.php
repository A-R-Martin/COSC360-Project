<?php
// API endpoint for user profile management
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'You must be logged in to perform this action',
        'data' => null
    ]);
    exit;
}

// Default response
$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => null
];

// Get the action from GET or POST
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

try {
    switch ($action) {
        case 'get_profile':
            // Get user profile data
            $stmt = $conn->prepare("SELECT username, email, bio, profile_image FROM users WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $response = [
                    'status' => 'success',
                    'message' => 'User profile retrieved successfully',
                    'data' => $user
                ];
            } else {
                $response['message'] = 'User not found';
            }
            break;
            
        case 'update_profile':
            // Validate and sanitize the data
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
            
            // Validate username
            if (empty($username)) {
                $response['message'] = 'Username is required';
                echo json_encode($response);
                exit;
            }
            
            if (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $username)) {
                $response['message'] = 'Username must be between 3-30 characters and can only contain letters, numbers, underscores and hyphens';
                echo json_encode($response);
                exit;
            }
            
            // Validate email
            if (empty($email)) {
                $response['message'] = 'Email is required';
                echo json_encode($response);
                exit;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Please enter a valid email address';
                echo json_encode($response);
                exit;
            }
            
            // Check if username already exists (except for the current user)
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = :username AND user_id != :user_id");
            $stmt->execute([
                'username' => $username,
                'user_id' => $_SESSION['user_id']
            ]);
            
            if ($stmt->rowCount() > 0) {
                $response['message'] = 'Username already exists';
                echo json_encode($response);
                exit;
            }
            
            // Check if email already exists (except for the current user)
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :user_id");
            $stmt->execute([
                'email' => $email,
                'user_id' => $_SESSION['user_id']
            ]);
            
            if ($stmt->rowCount() > 0) {
                $response['message'] = 'Email already exists';
                echo json_encode($response);
                exit;
            }
            
            // Update user profile in database
            $stmt = $conn->prepare("UPDATE users SET username = :username, email = :email, bio = :bio WHERE user_id = :user_id");
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'bio' => $bio,
                'user_id' => $_SESSION['user_id']
            ]);
            
            // Update session username
            $_SESSION['username'] = $username;
            
            $response = [
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => [
                    'username' => $username,
                    'email' => $email,
                    'bio' => $bio
                ]
            ];
            break;
            
        case 'delete_profile_image':
            // Remove profile image
            $stmt = $conn->prepare("SELECT profile_image FROM users WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
                unlink($user['profile_image']);
            }
            
            // Update user profile in database
            $stmt = $conn->prepare("UPDATE users SET profile_image = NULL WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
            
            $response = [
                'status' => 'success',
                'message' => 'Profile image removed successfully',
                'data' => null
            ];
            break;
            
        default:
            $response['message'] = 'Invalid action';
            break;
    }
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
exit; 