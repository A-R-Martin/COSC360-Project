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
            
        case 'update_admin_user':
            // Validate required fields
            if (empty($_POST['user_id']) || empty($_POST['username']) || empty($_POST['email']) || empty($_POST['status']) || empty($_POST['role'])) {
                $response = [
                    'status' => 'error',
                    'message' => 'All required fields must be filled in',
                    'data' => null
                ];
                break;
            }
            
            $userId = (int)$_POST['user_id'];
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $status = trim($_POST['status']);
            $role = trim($_POST['role']);
            $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
            
            // Validate username format
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username) || strlen($username) < 3 || strlen($username) > 30) {
                $response = [
                    'status' => 'error',
                    'message' => 'Username must be between 3-30 characters and can only contain letters, numbers, underscores and hyphens',
                    'data' => null
                ];
                break;
            }
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response = [
                    'status' => 'error',
                    'message' => 'Please enter a valid email address',
                    'data' => null
                ];
                break;
            }
            
            $checkStmt = $conn->prepare("
                SELECT user_id FROM users 
                WHERE (username = :username OR email = :email) 
                AND user_id != :user_id
            ");
            $checkStmt->execute([
                'username' => $username,
                'email' => $email,
                'user_id' => $userId
            ]);
            
            if ($checkStmt->rowCount() > 0) {
                $response = [
                    'status' => 'error',
                    'message' => 'Username or email already exists',
                    'data' => null
                ];
                break;
            }
            
            // Update user data
            $updateStmt = $conn->prepare("
                UPDATE users SET 
                username = :username,
                email = :email,
                status = :status,
                role = :role,
                bio = :bio
                WHERE user_id = :user_id
            ");
            
            $updateSuccess = $updateStmt->execute([
                'username' => $username,
                'email' => $email,
                'status' => $status,
                'role' => $role,
                'bio' => $bio,
                'user_id' => $userId
            ]);
            
            if ($updateSuccess) {
                $response = [
                    'status' => 'success',
                    'message' => 'User profile updated successfully',
                    'data' => null
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to update user profile',
                    'data' => null
                ];
            }
            break;
            
        case 'update_book':
            // Validate required fields
            if (empty($_POST['book_id']) || empty($_POST['title']) || empty($_POST['author'])) {
                $response = [
                    'status' => 'error',
                    'message' => 'Book title and author are required',
                    'data' => null
                ];
                break;
            }
            
            $bookId = (int)$_POST['book_id'];
            $title = trim($_POST['title']);
            $author = trim($_POST['author']);
            $isbn = isset($_POST['isbn']) ? trim($_POST['isbn']) : '';
            $description = isset($_POST['description']) ? trim($_POST['description']) : '';
            $yearPublished = isset($_POST['year_published']) && !empty($_POST['year_published']) ? (int)$_POST['year_published'] : null;
            $genre = isset($_POST['genre']) ? trim($_POST['genre']) : '';
            $rating = isset($_POST['rating']) && !empty($_POST['rating']) ? (float)$_POST['rating'] : 3.0;
            $status = isset($_POST['status']) ? trim($_POST['status']) : 'available';
            
            // Handle file upload for cover image
            $coverImagePath = null;
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
                // Define allowed file types
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                // Check file size and type
                if ($_FILES['cover_image']['size'] > $maxSize) {
                    $response = [
                        'status' => 'error',
                        'message' => 'File size exceeds the maximum limit of 5MB',
                        'data' => null
                    ];
                    break;
                }
                
                if (!in_array($_FILES['cover_image']['type'], $allowedTypes)) {
                    $response = [
                        'status' => 'error',
                        'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed',
                        'data' => null
                    ];
                    break;
                }
                
                // Create uploads directory if it doesn't exist
                $uploadsDir = 'uploads/books/';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }
                
                // Generate unique filename
                $extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('book_' . $bookId . '_') . '.' . $extension;
                $targetPath = $uploadsDir . $filename;
                
                // Move the uploaded file
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $targetPath)) {
                    $coverImagePath = $targetPath;
                } else {
                    $response = [
                        'status' => 'error',
                        'message' => 'Failed to upload the cover image',
                        'data' => null
                    ];
                    break;
                }
            }
            
            // Build update query
            $updateColumns = [
                'title = :title',
                'author = :author',
                'isbn = :isbn',
                'description = :description',
                'year_published = :year_published',
                'genre = :genre',
                'rating = :rating',
                'status = :status'
            ];
            
            $params = [
                'title' => $title,
                'author' => $author,
                'isbn' => $isbn,
                'description' => $description,
                'year_published' => $yearPublished,
                'genre' => $genre,
                'rating' => $rating,
                'status' => $status,
                'book_id' => $bookId
            ];
            
            // Add cover image if uploaded
            if ($coverImagePath) {
                $updateColumns[] = 'cover_image = :cover_image';
                $params['cover_image'] = $coverImagePath;
            }
            
            // Update book data
            $updateQuery = "UPDATE books SET " . implode(', ', $updateColumns) . " WHERE book_id = :book_id";
            $updateStmt = $conn->prepare($updateQuery);
            $updateSuccess = $updateStmt->execute($params);
            
            if ($updateSuccess) {
                $response = [
                    'status' => 'success',
                    'message' => 'Book updated successfully',
                    'data' => null
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to update book',
                    'data' => null
                ];
            }
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