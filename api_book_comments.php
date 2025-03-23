<?php
// Book Comments API Endpoints
header('Content-Type: application/json');
require_once 'db_connect.php';

// Check if user is logged in for operations that require authentication
session_start();

// Process request based on method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($method === 'POST' && empty($action)) {
    $jsonData = json_decode(file_get_contents('php://input'), true);
    $action = isset($jsonData['action']) ? $jsonData['action'] : '';
}

// Response array
$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => null
];

// Handle different request methods
switch ($method) {
    case 'GET':
        handleGetRequest($action, $conn, $response);
        break;
    case 'POST':
        handlePostRequest($action, $conn, $response);
        break;
    default:
        $response['message'] = 'Method not allowed';
}

// Output response
echo json_encode($response);
exit;

/**
 * Handle GET requests for book comments
 */
function handleGetRequest($action, $conn, &$response) {
    if ($action == 'get_comments' || empty($action)) {
        $book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;
        
        if (!$book_id) {
            $response['message'] = 'Book ID is required';
            return;
        }
        
        try {
            // Get comments for the specified book with user information
            $sql = "SELECT c.comment_id, c.comment, c.created_at, c.user_id, 
                           u.username, u.profile_image 
                    FROM book_comments c 
                    JOIN users u ON c.user_id = u.user_id 
                    WHERE c.book_id = :book_id 
                    ORDER BY c.created_at DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute(['book_id' => $book_id]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['status'] = 'success';
            $response['message'] = 'Comments retrieved successfully';
            $response['data'] = $comments;
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid action';
    }
}

/**
 * Handle POST requests for book comments
 */
function handlePostRequest($action, $conn, &$response) {
    // Check authentication for all POST actions
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'Authentication required';
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $jsonData = json_decode(file_get_contents('php://input'), true);
    
    if ($action == 'add_comment') {
        $book_id = isset($jsonData['book_id']) ? (int)$jsonData['book_id'] : 0;
        $comment = isset($jsonData['comment']) ? trim($jsonData['comment']) : '';
        
        if (!$book_id) {
            $response['message'] = 'Book ID is required';
            return;
        }
        
        if (empty($comment)) {
            $response['message'] = 'Comment cannot be empty';
            return;
        }
        
        try {
            // Check if book exists
            $checkSql = "SELECT COUNT(*) as count FROM books WHERE book_id = :book_id";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute(['book_id' => $book_id]);
            $bookExists = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
            
            if (!$bookExists) {
                $response['message'] = 'Invalid book ID';
                return;
            }
            
            // Add the comment
            $sql = "INSERT INTO book_comments (book_id, user_id, comment) 
                    VALUES (:book_id, :user_id, :comment)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'book_id' => $book_id,
                'user_id' => $user_id,
                'comment' => $comment
            ]);
            
            $commentId = $conn->lastInsertId();
            $fetchSql = "SELECT c.comment_id, c.comment, c.created_at, c.user_id, 
                                u.username, u.profile_image 
                         FROM book_comments c 
                         JOIN users u ON c.user_id = u.user_id 
                         WHERE c.comment_id = :comment_id";
            $fetchStmt = $conn->prepare($fetchSql);
            $fetchStmt->execute(['comment_id' => $commentId]);
            $newComment = $fetchStmt->fetch(PDO::FETCH_ASSOC);
            
            $response['status'] = 'success';
            $response['message'] = 'Comment added successfully';
            $response['data'] = $newComment;
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid action';
    }
}
?> 