<?php
// User Books API Endpoints (borrow, reserve, return operations)
header('Content-Type: application/json');
require_once 'db_connect.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Authentication required',
        'data' => null
    ]);
    exit;
}

// Process request based on method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

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
 * Handle GET requests for user books
 */
function handleGetRequest($action, $conn, &$response) {
    $userId = $_SESSION['user_id'];
    
    switch ($action) {
        case 'borrowed':
            try {
                $query = "SELECT b.*, ub.borrow_date, ub.return_date 
                         FROM books b 
                         JOIN user_books ub ON b.book_id = ub.book_id 
                         WHERE ub.user_id = :userId AND ub.status = 'borrowed'
                         ORDER BY ub.borrow_date DESC";
                
                $stmt = $conn->prepare($query);
                $stmt->execute(['userId' => $userId]);
                $books = [];
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $books[] = [
                        'book_id' => $row['book_id'],
                        'title' => $row['title'],
                        'author' => $row['author'],
                        'cover' => $row['cover_image'] ?? 'sample-image.avif',
                        'borrow_date' => $row['borrow_date'],
                        'return_date' => $row['return_date']
                    ];
                }
                
                $response['status'] = 'success';
                $response['message'] = count($books) . ' borrowed books found';
                $response['data'] = $books;
                
            } catch (PDOException $e) {
                $response['message'] = 'Database error: ' . $e->getMessage();
            }
            break;
            
        case 'reserved':
            try {
                $query = "SELECT b.*, ub.reserve_date 
                         FROM books b 
                         JOIN user_books ub ON b.book_id = ub.book_id 
                         WHERE ub.user_id = :userId AND ub.status = 'reserved'
                         ORDER BY ub.reserve_date DESC";
                
                $stmt = $conn->prepare($query);
                $stmt->execute(['userId' => $userId]);
                $books = [];
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $books[] = [
                        'book_id' => $row['book_id'],
                        'title' => $row['title'],
                        'author' => $row['author'],
                        'cover' => $row['cover_image'] ?? 'sample-image.avif',
                        'reserve_date' => $row['reserve_date']
                    ];
                }
                
                $response['status'] = 'success';
                $response['message'] = count($books) . ' reserved books found';
                $response['data'] = $books;
                
            } catch (PDOException $e) {
                $response['message'] = 'Database error: ' . $e->getMessage();
            }
            break;
            
        case 'history':
            try {
                $query = "SELECT b.*, ub.borrow_date, ub.return_date 
                         FROM books b 
                         JOIN user_books ub ON b.book_id = ub.book_id 
                         WHERE ub.user_id = :userId AND ub.status = 'returned'
                         ORDER BY ub.return_date DESC";
                
                $stmt = $conn->prepare($query);
                $stmt->execute(['userId' => $userId]);
                $books = [];
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $books[] = [
                        'book_id' => $row['book_id'],
                        'title' => $row['title'],
                        'author' => $row['author'],
                        'cover' => $row['cover_image'] ?? 'sample-image.avif',
                        'borrow_date' => $row['borrow_date'],
                        'return_date' => $row['return_date'],
                        'status' => $row['status']
                    ];
                }
                
                $response['status'] = 'success';
                $response['message'] = count($books) . ' books in history';
                $response['data'] = $books;
                
            } catch (PDOException $e) {
                $response['message'] = 'Database error: ' . $e->getMessage();
            }
            break;
            
        default:
            $response['message'] = 'Invalid action';
    }
}

/**
 * Handle POST requests for user books
 */
function handlePostRequest($action, $conn, &$response) {
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['book_id']) || empty($data['book_id'])) {
        $response['message'] = 'Book ID is required';
        return;
    }
    
    $bookId = intval($data['book_id']);
    
    try {
        // Check if book exists
        $checkQuery = "SELECT book_id, status FROM books WHERE book_id = :bookId";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute(['bookId' => $bookId]);
        
        if ($checkStmt->rowCount() === 0) {
            $response['message'] = 'Book not found';
            return;
        }
        
        $book = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        switch ($action) {
            case 'borrow':
                if ($book['status'] !== 'available') {
                    $response['message'] = 'Book is not available for borrowing';
                    return;
                }
                
                // Check borrowing limit
                $countQuery = "SELECT COUNT(*) as count FROM user_books 
                              WHERE user_id = :userId AND status = 'borrowed'";
                $countStmt = $conn->prepare($countQuery);
                $countStmt->execute(['userId' => $userId]);
                $borrowedCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($borrowedCount >= 5) {
                    $response['message'] = 'You have reached the maximum limit of 5 borrowed books';
                    return;
                }
                
                // Start transaction
                $conn->beginTransaction();
                
                try {
                    // Update book status
                    $updateBookQuery = "UPDATE books SET status = 'borrowed' WHERE book_id = :bookId";
                    $updateBookStmt = $conn->prepare($updateBookQuery);
                    $updateBookStmt->execute(['bookId' => $bookId]);
                    
                    // Calculate dates
                    $borrowDate = date('Y-m-d H:i:s');
                    $returnDate = date('Y-m-d H:i:s', strtotime('+14 days'));
                    
                    // Insert into user_books
                    $insertQuery = "INSERT INTO user_books (user_id, book_id, status, borrow_date, return_date) 
                                  VALUES (:userId, :bookId, 'borrowed', :borrowDate, :returnDate)";
                    $insertStmt = $conn->prepare($insertQuery);
                    $insertStmt->execute([
                        'userId' => $userId,
                        'bookId' => $bookId,
                        'borrowDate' => $borrowDate,
                        'returnDate' => $returnDate
                    ]);
                    
                    $conn->commit();
                    $response['status'] = 'success';
                    $response['message'] = 'Book borrowed successfully';
                    $response['data'] = [
                        'book_id' => $bookId,
                        'borrow_date' => $borrowDate,
                        'return_date' => $returnDate
                    ];
                } catch (Exception $e) {
                    $conn->rollBack();
                    $response['message'] = 'Failed to borrow book: ' . $e->getMessage();
                }
                break;
                
            case 'reserve':
                if ($book['status'] === 'reserved') {
                    $response['message'] = 'Book is already reserved';
                    return;
                }
                
                // Check reservation limit
                $countQuery = "SELECT COUNT(*) as count FROM user_books 
                              WHERE user_id = :userId AND status = 'reserved'";
                $countStmt = $conn->prepare($countQuery);
                $countStmt->execute(['userId' => $userId]);
                $reservedCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($reservedCount >= 3) {
                    $response['message'] = 'You have reached the maximum limit of 3 reserved books';
                    return;
                }
                
                // Check if already borrowed
                $checkBorrowedQuery = "SELECT id FROM user_books 
                                     WHERE user_id = :userId AND book_id = :bookId AND status = 'borrowed'";
                $checkBorrowedStmt = $conn->prepare($checkBorrowedQuery);
                $checkBorrowedStmt->execute([
                    'userId' => $userId,
                    'bookId' => $bookId
                ]);
                
                if ($checkBorrowedStmt->rowCount() > 0) {
                    $response['message'] = 'You have already borrowed this book';
                    return;
                }
                
                // Start transaction
                $conn->beginTransaction();
                
                try {
                    // Update book status
                    $updateBookQuery = "UPDATE books SET status = 'reserved' WHERE book_id = :bookId";
                    $updateBookStmt = $conn->prepare($updateBookQuery);
                    $updateBookStmt->execute(['bookId' => $bookId]);
                    
                    // Calculate reserve date
                    $reserveDate = date('Y-m-d H:i:s');
                    
                    // Insert into user_books
                    $insertQuery = "INSERT INTO user_books (user_id, book_id, status, reserve_date) 
                                  VALUES (:userId, :bookId, 'reserved', :reserveDate)";
                    $insertStmt = $conn->prepare($insertQuery);
                    $insertStmt->execute([
                        'userId' => $userId,
                        'bookId' => $bookId,
                        'reserveDate' => $reserveDate
                    ]);
                    
                    $conn->commit();
                    $response['status'] = 'success';
                    $response['message'] = 'Book reserved successfully';
                    $response['data'] = [
                        'book_id' => $bookId,
                        'reserve_date' => $reserveDate
                    ];
                } catch (Exception $e) {
                    $conn->rollBack();
                    $response['message'] = 'Failed to reserve book: ' . $e->getMessage();
                }
                break;
                
            case 'return':
                // Check if user has borrowed this book
                $checkBorrowedQuery = "SELECT id FROM user_books 
                                     WHERE user_id = ? AND book_id = ? AND status = 'borrowed'";
                $checkBorrowedStmt = $conn->prepare($checkBorrowedQuery);
                $checkBorrowedStmt->execute([$userId, $bookId]);
                
                if ($checkBorrowedStmt->rowCount() === 0) {
                    $response['message'] = 'You have not borrowed this book';
                    return;
                }
                
                $borrowRecord = $checkBorrowedStmt->fetch(PDO::FETCH_ASSOC);
                $borrowId = $borrowRecord['id'];
                
                // Start transaction
                $conn->beginTransaction();
                
                try {
                    // Update book status
                    $updateBookQuery = "UPDATE books SET status = 'available' WHERE book_id = :bookId";
                    $updateBookStmt = $conn->prepare($updateBookQuery);
                    $updateBookStmt->execute(['bookId' => $bookId]);
                    
                    // Update user_books record to history
                    $returnDate = date('Y-m-d H:i:s');
                    
                    $updateQuery = "UPDATE user_books SET status = 'returned', return_date = :returnDate 
                                   WHERE id = :borrowId";
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->execute([
                        'returnDate' => $returnDate,
                        'borrowId' => $borrowId
                    ]);
                    
                    $conn->commit();
                    $response['status'] = 'success';
                    $response['message'] = 'Book returned successfully';
                    $response['data'] = [
                        'book_id' => $bookId,
                        'return_date' => $returnDate
                    ];
                } catch (Exception $e) {
                    $conn->rollBack();
                    $response['message'] = 'Failed to return book: ' . $e->getMessage();
                }
                break;
                
            case 'cancel_reservation':
                // Check if user has reserved this book
                $checkReservedQuery = "SELECT id FROM user_books 
                                      WHERE user_id = ? AND book_id = ? AND status = 'reserved'";
                $checkReservedStmt = $conn->prepare($checkReservedQuery);
                $checkReservedStmt->execute([$userId, $bookId]);
                
                if ($checkReservedStmt->rowCount() === 0) {
                    $response['message'] = 'You have not reserved this book';
                    return;
                }
                
                $reserveRecord = $checkReservedStmt->fetch(PDO::FETCH_ASSOC);
                $reserveId = $reserveRecord['id'];
                
                // Start transaction
                $conn->beginTransaction();
                
                try {
                    // Update book status
                    $updateBookQuery = "UPDATE books SET status = 'available' WHERE book_id = :bookId";
                    $updateBookStmt = $conn->prepare($updateBookQuery);
                    $updateBookStmt->execute(['bookId' => $bookId]);
                    
                    // Delete the reservation record
                    $deleteQuery = "DELETE FROM user_books WHERE id = :reserveId";
                    $deleteStmt = $conn->prepare($deleteQuery);
                    $deleteStmt->execute(['reserveId' => $reserveId]);
                    
                    $conn->commit();
                    $response['status'] = 'success';
                    $response['message'] = 'Reservation cancelled successfully';
                    $response['data'] = [
                        'book_id' => $bookId
                    ];
                } catch (Exception $e) {
                    $conn->rollBack();
                    $response['message'] = 'Failed to cancel reservation: ' . $e->getMessage();
                }
                break;
                
            default:
                $response['message'] = 'Invalid action';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}
?> 