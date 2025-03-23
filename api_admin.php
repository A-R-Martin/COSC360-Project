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
            
        case 'get_chart_data':
            // Get the analytics data first (copied from get_analytics)
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

            $bookStatusData = [
                'labels' => ['Available', 'Borrowed', 'Reserved'],
                'datasets' => [
                    [
                        'data' => [$availableBooks, $borrowedBooks, $reservedBooks],
                        'backgroundColor' => ['#2ecc71', '#3498db', '#f39c12']
                    ]
                ]
            ];
            
            // Get genre distribution data
            $stmt = $conn->prepare("
                SELECT genre, COUNT(*) as count 
                FROM books 
                WHERE genre IS NOT NULL AND genre != '' 
                GROUP BY genre 
                ORDER BY count DESC 
                LIMIT 5
            ");
            $stmt->execute();
            $genreData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $genreLabels = [];
            $genreCounts = [];
            $genreColors = ['#e74c3c', '#3498db', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#34495e'];
            
            foreach ($genreData as $index => $genre) {
                $genreLabels[] = $genre['genre'];
                $genreCounts[] = $genre['count'];
            }
            
            $genreDistributionData = [
                'labels' => $genreLabels,
                'datasets' => [
                    [
                        'data' => $genreCounts,
                        'backgroundColor' => array_slice($genreColors, 0, count($genreLabels))
                    ]
                ]
            ];
            
            // Get most popular books data
            $stmt = $conn->prepare("
                SELECT b.title, COUNT(ub.id) as borrow_count 
                FROM books b
                JOIN user_books ub ON b.book_id = ub.book_id
                WHERE ub.status IN ('borrowed', 'returned')
                GROUP BY b.book_id
                ORDER BY borrow_count DESC
                LIMIT 5
            ");
            $stmt->execute();
            $popularBooksData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $bookLabels = [];
            $bookCounts = [];
            
            foreach ($popularBooksData as $book) {
                $bookLabels[] = strlen($book['title']) > 15 ? substr($book['title'], 0, 15) . '...' : $book['title'];
                $bookCounts[] = $book['borrow_count'];
            }
            
            $popularBooksChartData = [
                'labels' => $bookLabels,
                'datasets' => [
                    [
                        'data' => $bookCounts,
                        'backgroundColor' => '#3498db'
                    ]
                ]
            ];
            
            // Get user activity data (users by borrowed books count)
            $stmt = $conn->prepare("
                SELECT 
                    CASE 
                        WHEN count = 0 THEN 'No Activity'
                        WHEN count = 1 THEN '1 Book'
                        WHEN count BETWEEN 2 AND 3 THEN '2-3 Books'
                        WHEN count BETWEEN 4 AND 10 THEN '4-10 Books'
                        ELSE '10+ Books'
                    END as activity_level,
                    COUNT(*) as user_count
                FROM (
                    SELECT u.user_id, COUNT(ub.id) as count
                    FROM users u
                    LEFT JOIN user_books ub ON u.user_id = ub.user_id AND ub.status IN ('borrowed', 'returned')
                    GROUP BY u.user_id
                ) as user_activity
                GROUP BY activity_level
                ORDER BY 
                    CASE 
                        WHEN activity_level = 'No Activity' THEN 1
                        WHEN activity_level = '1 Book' THEN 2
                        WHEN activity_level = '2-3 Books' THEN 3
                        WHEN activity_level = '4-10 Books' THEN 4
                        ELSE 5
                    END
            ");
            $stmt->execute();
            $userActivityData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $activityLabels = [];
            $activityCounts = [];
            
            foreach ($userActivityData as $activity) {
                $activityLabels[] = $activity['activity_level'];
                $activityCounts[] = $activity['user_count'];
            }
            
            $userActivityChartData = [
                'labels' => $activityLabels,
                'datasets' => [
                    [
                        'data' => $activityCounts,
                        'backgroundColor' => ['#ecf0f1', '#3498db', '#2ecc71', '#f39c12', '#e74c3c']
                    ]
                ]
            ];
            
            $response = [
                'status' => 'success',
                'message' => 'Chart data retrieved successfully',
                'data' => [
                    'bookStatus' => $bookStatusData,
                    'genreDistribution' => $genreDistributionData,
                    'popularBooks' => $popularBooksChartData,
                    'userActivity' => $userActivityChartData
                ]
            ];
            break;
            
        case 'get_books':
            // Get optional query parameters
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $category = isset($_GET['category']) ? trim($_GET['category']) : '';
            
            $query = "SELECT b.book_id, b.title, b.author, b.isbn, b.status, 
                     bor.username as borrower_name, bor.user_id as borrower_id,
                     res.username as reserver_name, res.user_id as reserver_id,
                     ub_bor.return_date,
                     CASE WHEN ub_bor.status = 'borrowed' AND ub_bor.return_date < CURRENT_DATE THEN 1 ELSE 0 END as is_overdue
                     FROM books b
                     LEFT JOIN user_books ub_bor ON b.book_id = ub_bor.book_id AND ub_bor.status = 'borrowed'
                     LEFT JOIN users bor ON ub_bor.user_id = bor.user_id
                     LEFT JOIN user_books ub_res ON b.book_id = ub_res.book_id AND ub_res.status = 'reserved'
                     LEFT JOIN users res ON ub_res.user_id = res.user_id
                     WHERE 1=1";
            $params = [];
            
            if (!empty($search)) {
                if ($category == 'title') {
                    $query .= " AND b.title LIKE ?";
                    $params[] = "%$search%";
                } else if ($category == 'author') {
                    $query .= " AND b.author LIKE ?";
                    $params[] = "%$search%";
                } else if ($category == 'isbn') {
                    $query .= " AND b.isbn LIKE ?";
                    $params[] = "%$search%";
                } else {
                    // Search all fields if no category specified
                    $query .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }
            }
            
            $query .= " ORDER BY b.title ASC";
            
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
            
        default:
            $response['message'] = 'Invalid action';
            break;
    }
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
exit; 