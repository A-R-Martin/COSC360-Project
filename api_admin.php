<?php
// API endpoint for admin functionality
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        'status' => 'error',
        'message' => 'You must be an admin to perform this action',
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
        case 'get_users':
            // Get optional query parameters
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $status = isset($_GET['status']) ? trim($_GET['status']) : '';
            
            // Build the query
            $query = "SELECT user_id, username, email, status, created_at, role FROM users WHERE 1=1";
            $params = [];
            
            // Add search condition if provided
            if (!empty($search)) {
                $query .= " AND (username LIKE :search OR email LIKE :search)";
                $params['search'] = "%$search%";
            }
            
            // Add status filter if provided
            if (!empty($status)) {
                $query .= " AND status = :status";
                $params['status'] = $status;
            }
            
            // Order by created_at descending (newest first)
            $query .= " ORDER BY created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'status' => 'success',
                'message' => 'Users retrieved successfully',
                'data' => $users
            ];
            break;
            
        case 'update_user_status':
            // Get user ID and new status
            $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            $newStatus = isset($_POST['status']) ? trim($_POST['status']) : '';
            
            // Validate inputs
            if ($userId <= 0) {
                $response['message'] = 'Invalid user ID';
                break;
            }
            
            if (!in_array($newStatus, ['active', 'banned'])) {
                $response['message'] = 'Invalid status value';
                break;
            }
            
            // Check if user exists
            $stmt = $conn->prepare("SELECT user_id, role FROM users WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $response['message'] = 'User not found';
                break;
            }
            
            // Prevent banning other admin accounts
            if ($user['role'] === 'admin' && $newStatus === 'banned' && $userId !== $_SESSION['user_id']) {
                $response['message'] = 'Cannot ban another admin account';
                break;
            }
            
            // Update user status
            $stmt = $conn->prepare("UPDATE users SET status = :status WHERE user_id = :user_id");
            $stmt->execute([
                'status' => $newStatus,
                'user_id' => $userId
            ]);
            
            $response = [
                'status' => 'success',
                'message' => 'User status updated successfully',
                'data' => [
                    'user_id' => $userId,
                    'status' => $newStatus
                ]
            ];
            break;
            
        case 'get_user_details':
            // Get user ID
            $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
            
            // Validate inputs
            if ($userId <= 0) {
                $response['message'] = 'Invalid user ID';
                break;
            }
            
            // Get user details
            $stmt = $conn->prepare("
                SELECT u.user_id, u.username, u.email, u.status, u.created_at, u.bio, u.profile_image, u.role,
                       COUNT(b.book_id) as book_count
                FROM users u
                LEFT JOIN user_books b ON u.user_id = b.user_id
                WHERE u.user_id = :user_id
                GROUP BY u.user_id
            ");
            $stmt->execute(['user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $response['message'] = 'User not found';
                break;
            }
            
            $response = [
                'status' => 'success',
                'message' => 'User details retrieved successfully',
                'data' => $user
            ];
            break;
            
        case 'get_analytics':
            // Get total users count
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users");
            $stmt->execute();
            $totalUsers = $stmt->fetchColumn();
            
            // Get active books count
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM books WHERE status = 'available'");
            $stmt->execute();
            $activeBooks = $stmt->fetchColumn();
            
            // Get borrowed books count
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM books WHERE status = 'borrowed'");
            $stmt->execute();
            $borrowedBooks = $stmt->fetchColumn();
            
            // Get overdue books count
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total 
                FROM books b
                JOIN book_loans bl ON b.book_id = bl.book_id
                WHERE bl.return_date < CURRENT_DATE AND bl.returned = 0
            ");
            $stmt->execute();
            $overdueBooks = $stmt->fetchColumn();
            
            $response = [
                'status' => 'success',
                'message' => 'Analytics retrieved successfully',
                'data' => [
                    'total_users' => $totalUsers,
                    'active_books' => $activeBooks,
                    'borrowed_books' => $borrowedBooks,
                    'overdue_books' => $overdueBooks
                ]
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