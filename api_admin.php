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
            
            // Start with base query
            $query = "SELECT user_id, username, email, status, created_at, role FROM users WHERE 1=1";
            $params = [];
            
            // Build the conditions
            if (!empty($search)) {
                $query .= " AND (username LIKE ? OR email LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if (!empty($status)) {
                $query .= " AND status = ?";
                $params[] = $status;
            }
            
            // Order by created_at descending (newest first)
            $query .= " ORDER BY created_at DESC";
            
            // Debug output
            error_log("SQL Query: " . $query);
            error_log("SQL Params: " . json_encode($params));
            
            try {
                $stmt = $conn->prepare($query);
                
                if (!empty($params)) {
                    $stmt->execute($params);
                } else {
                    $stmt->execute();
                }
                
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response = [
                    'status' => 'success',
                    'message' => 'Users retrieved successfully',
                    'data' => $users
                ];
            } catch (PDOException $e) {
                error_log("SQL Error: " . $e->getMessage());
                $response = [
                    'status' => 'error',
                    'message' => 'Database error: ' . $e->getMessage(),
                    'data' => null
                ];
            }
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
            
            // Get active users count
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
            $stmt->execute();
            $activeUsers = $stmt->fetchColumn();
            
            // Get banned users count
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE status = 'banned'");
            $stmt->execute();
            $bannedUsers = $stmt->fetchColumn();
            
            // Get total books count
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM books");
            $stmt->execute();
            $totalBooks = $stmt->fetchColumn();
            
            // Get available books count
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM books WHERE status = 'available'");
            $stmt->execute();
            $availableBooks = $stmt->fetchColumn();
            
            // Get borrowed books count
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM books WHERE status = 'borrowed'");
            $stmt->execute();
            $borrowedBooks = $stmt->fetchColumn();
            
            // Get reserved books count
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM books WHERE status = 'reserved'");
            $stmt->execute();
            $reservedBooks = $stmt->fetchColumn();
            
            // Get overdue books count
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total 
                FROM user_books ub
                WHERE ub.status = 'borrowed' AND ub.return_date < CURRENT_DATE
            ");
            $stmt->execute();
            $overdueBooks = $stmt->fetchColumn();
            
            $response = [
                'status' => 'success',
                'message' => 'Analytics retrieved successfully',
                'data' => [
                    'users' => [
                        'total' => $totalUsers,
                        'active' => $activeUsers,
                        'banned' => $bannedUsers
                    ],
                    'books' => [
                        'total' => $totalBooks,
                        'available' => $availableBooks,
                        'borrowed' => $borrowedBooks,
                        'reserved' => $reservedBooks,
                        'overdue' => $overdueBooks
                    ]
                ]
            ];
            break;
            
        case 'get_books':
            // Get optional query parameters
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'title';
            $sort_order = isset($_GET['sort_order']) ? trim($_GET['sort_order']) : 'asc';
            // Validate sort field to prevent SQL injection
            $allowed_sort_fields = ['title', 'author', 'isbn', 'status'];
            if (!in_array($sort, $allowed_sort_fields)) {
                $sort = 'title'; // Default to title if invalid sort field
            }
            
            // Validate sort order
            $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';
            
            $query = "SELECT b.book_id, b.title, b.author, b.isbn, b.status, b.description, b.cover_image, b.year_published, b.genre, b.rating,
                     bor.username as borrower_name, bor.user_id as borrower_id,
                     res.username as reserver_name, res.user_id as reserver_id,
                     CASE WHEN ub_bor.status = 'borrowed' AND ub_bor.return_date < CURRENT_DATE THEN 1 ELSE 0 END as is_overdue
                     FROM books b
                     LEFT JOIN user_books ub_bor ON b.book_id = ub_bor.book_id AND ub_bor.status = 'borrowed'
                     LEFT JOIN users bor ON ub_bor.user_id = bor.user_id
                     LEFT JOIN user_books ub_res ON b.book_id = ub_res.book_id AND ub_res.status = 'reserved'
                     LEFT JOIN users res ON ub_res.user_id = res.user_id
                     WHERE 1=1";
            $params = [];
            
            if (!empty($search)) {
                // Search all fields
                $query .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            // Add ORDER BY clause based on sort parameters
            $query .= " ORDER BY b.$sort $sort_order";
            
            // Debug output
            error_log("SQL Book Query: " . $query);
            error_log("SQL Book Params: " . json_encode($params));
            
            try {
                $stmt = $conn->prepare($query);
                
                if (!empty($params)) {
                    $stmt->execute($params);
                } else {
                    $stmt->execute();
                }
                
                $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response = [
                    'status' => 'success',
                    'message' => 'Books retrieved successfully',
                    'data' => $books
                ];
            } catch (PDOException $e) {
                error_log("SQL Error: " . $e->getMessage());
                $response = [
                    'status' => 'error',
                    'message' => 'Database error: ' . $e->getMessage(),
                    'data' => null
                ];
            }
            break;
            
        case 'delete_book':
            // Get book ID
            $bookId = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
            
            // Validate inputs
            if ($bookId <= 0) {
                $response['message'] = 'Invalid book ID';
                break;
            }
            
            // Check if book exists
            $stmt = $conn->prepare("SELECT book_id, status FROM books WHERE book_id = ?");
            $stmt->execute([$bookId]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$book) {
                $response['message'] = 'Book not found';
                break;
            }
            
            // Check if book is currently borrowed
            if ($book['status'] === 'borrowed') {
                $response['message'] = 'Cannot delete a book that is currently borrowed';
                break;
            }
            
            $stmt = $conn->prepare("DELETE FROM user_books WHERE book_id = ? AND status = 'reserved'");
            $stmt->execute([$bookId]);
            
            $stmt = $conn->prepare("DELETE FROM books WHERE book_id = ?");
            $stmt->execute([$bookId]);
            
            $response = [
                'status' => 'success',
                'message' => 'Book deleted successfully',
                'data' => [
                    'book_id' => $bookId
                ]
            ];
            break;
            
        case 'export_data':
            $exportType = isset($_GET['type']) ? trim($_GET['type']) : 'all';
            
            $users = [];
            $books = [];
            $analytics = [];
            
            // Get users data if requested
            if ($exportType === 'all' || $exportType === 'users') {
                $stmt = $conn->prepare("SELECT user_id, username, email, status, created_at, role FROM users ORDER BY created_at DESC");
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Get books data if requested
            if ($exportType === 'all' || $exportType === 'books') {
                $query = "SELECT b.book_id, b.title, b.author, b.isbn, b.status, b.description, 
                         b.year_published, b.genre, b.rating,
                         bor.username as borrower_name,
                         res.username as reserver_name,
                         CASE WHEN ub_bor.status = 'borrowed' AND ub_bor.return_date < CURRENT_DATE THEN 1 ELSE 0 END as is_overdue
                         FROM books b
                         LEFT JOIN user_books ub_bor ON b.book_id = ub_bor.book_id AND ub_bor.status = 'borrowed'
                         LEFT JOIN users bor ON ub_bor.user_id = bor.user_id
                         LEFT JOIN user_books ub_res ON b.book_id = ub_res.book_id AND ub_res.status = 'reserved'
                         LEFT JOIN users res ON ub_res.user_id = res.user_id
                         ORDER BY b.title ASC";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            if ($exportType === 'all' || $exportType === 'analytics') {
                // Get total users count
                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users");
                $stmt->execute();
                $totalUsers = $stmt->fetchColumn();
                
                // Get active users count
                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
                $stmt->execute();
                $activeUsers = $stmt->fetchColumn();
                
                // Get banned users count
                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE status = 'banned'");
                $stmt->execute();
                $bannedUsers = $stmt->fetchColumn();
                
                // Get total books count
                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM books");
                $stmt->execute();
                $totalBooks = $stmt->fetchColumn();
                
                // Get available books count
                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM books WHERE status = 'available'");
                $stmt->execute();
                $availableBooks = $stmt->fetchColumn();
                
                // Get borrowed books count
                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM books WHERE status = 'borrowed'");
                $stmt->execute();
                $borrowedBooks = $stmt->fetchColumn();
                
                // Get reserved books count
                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM books WHERE status = 'reserved'");
                $stmt->execute();
                $reservedBooks = $stmt->fetchColumn();
                
                // Get overdue books count
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as total 
                    FROM user_books ub
                    WHERE ub.status = 'borrowed' AND ub.return_date < CURRENT_DATE
                ");
                $stmt->execute();
                $overdueBooks = $stmt->fetchColumn();
                
                $analytics = [
                    'users' => [
                        'total' => $totalUsers,
                        'active' => $activeUsers,
                        'banned' => $bannedUsers
                    ],
                    'books' => [
                        'total' => $totalBooks,
                        'available' => $availableBooks,
                        'borrowed' => $borrowedBooks,
                        'reserved' => $reservedBooks,
                        'overdue' => $overdueBooks
                    ]
                ];
            }
            
            $response = [
                'status' => 'success',
                'message' => 'Data exported successfully',
                'data' => [
                    'users' => $users,
                    'books' => $books,
                    'analytics' => $analytics
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