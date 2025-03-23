<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db_connect.php';
header('Content-Type: application/json');

// Default response structure
$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => null,
    'total_pages' => 0
];

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle GET requests
if ($method === 'GET') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $featured = isset($_GET['featured']) ? (bool)$_GET['featured'] : false;
    $random = isset($_GET['random']) ? (bool)$_GET['random'] : false;
    $count = isset($_GET['count']) ? (int)$_GET['count'] : 3; // Used with random=true
    
    // User ID for user-specific book status (borrowed, reserved)
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    

    $offset = ($page - 1) * $limit;
    
    try {
        // Base query parts
        $select = "SELECT b.book_id, b.title, b.author, b.description, b.cover_image as cover, b.isbn, 
                  b.year_published, b.genre, b.rating, b.status";
        
        // Add user_books relation if user is logged in
        if ($user_id) {
            $select .= ", ub.status as user_status, ub.borrow_date, ub.return_date, ub.reserve_date, ub.cancel_date";
            
            $select .= ", (SELECT COUNT(*) FROM user_books WHERE book_id = b.book_id AND status = 'reserved') as reservation_count";
        }
        
        $from = "FROM books b";
        
        // Join user_books if user is logged in
        if ($user_id) {
            $from .= " LEFT JOIN user_books ub ON b.book_id = ub.book_id AND ub.user_id = :user_id";
            $params['user_id'] = $user_id;
        }
        
        // Start building WHERE clause
        $where = "WHERE 1=1";
        
        // Add search condition if provided
        if (!empty($search)) {
            $where .= " AND (b.title LIKE :search OR b.author LIKE :search OR b.description LIKE :search OR b.isbn LIKE :search)";
            $search_param = "%$search%";
            $params['search'] = $search_param;
        }
        
        // Add category/genre filter if provided
        if (!empty($category) && $category !== 'all') {
            $where .= " AND b.genre = :category";
            $params['category'] = $category;
        }
        
        // Add status filter if provided
        if (!empty($status)) {
            switch ($status) {
                case 'available':
                    $where .= " AND b.status = 'available'";
                    break;
                case 'borrowed':
                    if ($user_id) {
                        $where .= " AND ub.status = 'borrowed'";
                    } else {
                        $where .= " AND b.status = 'borrowed'";
                    }
                    break;
                case 'reserved':
                    if ($user_id) {
                        $where .= " AND ub.status = 'reserved'";
                    } else {
                        $where .= " AND b.status = 'reserved'";
                    }
                    break;
                case 'history':
                    if ($user_id) {
                        $where .= " AND ub.status = 'returned'";
                    }
                    break;
            }
        }
        
        // Order by
        $orderBy = "";
        if ($random) {
            $orderBy = "ORDER BY RAND()";
            $limit = $count; // Override limit with count for random
            $offset = 0; // No offset for random
        } else if ($featured) {
            $orderBy = "ORDER BY b.rating DESC";
            $where .= " AND b.status = 'available'";
        } else {
            $orderBy = "ORDER BY b.title ASC";
        }
        
        // Count total records for pagination
        $countSql = "SELECT COUNT(*) as total $from $where";
        $countStmt = $conn->prepare($countSql);
        
        // Bind parameters for count query
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $countStmt->bindParam($key, $value);
            }
        }
        
        $countStmt->execute();
        $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalRecords / $limit);
        
        // Main query with pagination
        $sql = "$select $from $where $orderBy LIMIT $limit OFFSET $offset";
        $stmt = $conn->prepare($sql);
        
        // Bind parameters for main query
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $stmt->bindParam($key, $value);
            }
        }
        
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process book data to validate cover images
        foreach ($books as &$book) {
            // If cover is set, make sure it exists or set a default
            if (isset($book['cover']) && $book['cover']) {
                // Check if the file exists (if it's a relative path)
                if (!filter_var($book['cover'], FILTER_VALIDATE_URL) && !file_exists($book['cover'])) {
                    // File doesn't exist, set to default
                    $book['cover'] = 'sample-image.avif';
                }
            } else {
                // No cover set, use default
                $book['cover'] = 'sample-image.avif';
            }
            
            // Add has_reservation flag if reservation_count is available
            if (isset($book['reservation_count'])) {
                $book['has_reservation'] = (int)$book['reservation_count'] > 0;
                // Remove the raw count from the response
                unset($book['reservation_count']);
            }
        }
        
        // Success response
        $response = [
            'status' => 'success',
            'message' => 'Books retrieved successfully',
            'data' => $books,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'total_records' => $totalRecords
        ];
        
    } catch (PDOException $e) {
        $response = [
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage(),
            'data' => null,
            'total_pages' => 0
        ];
    }
}

// Handle POST requests (for borrowing, reserving, etc.)
else if ($method === 'POST') {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $response = [
            'status' => 'error',
            'message' => 'You must be logged in to perform this action',
            'data' => null
        ];
        echo json_encode($response);
        exit;
    }
    
    // Get JSON data from request body
    $json = file_get_contents('php://input');
    if (empty($json)) {
        $response = [
            'status' => 'error',
            'message' => 'No data received. Empty request body.',
            'data' => null
        ];
        echo json_encode($response);
        exit;
    }
    
    $data = json_decode($json, true);
    
    if (!$data) {
        $response = [
            'status' => 'error',
            'message' => 'Invalid JSON data: ' . json_last_error_msg(),
            'data' => [
                'received_data' => $json,
                'json_error' => json_last_error(),
                'json_error_msg' => json_last_error_msg()
            ]
        ];
        echo json_encode($response);
        exit;
    }
    
    $action = isset($data['action']) ? $data['action'] : '';
    $book_id = isset($data['book_id']) ? (int)$data['book_id'] : 0;
    $user_id = $_SESSION['user_id'];
    
    // Log the action for debugging
    error_log("API action: $action, user_id: $user_id, data: " . json_encode($data));
    
    if ($action !== 'add' && $action !== 'test' && !$book_id) {
        $response = [
            'status' => 'error',
            'message' => 'Book ID is required',
            'data' => null
        ];
        echo json_encode($response);
        exit;
    }
    
    try {
        switch ($action) {
            case 'add':
                // Required fields
                if (empty($data['title']) || empty($data['author'])) {
                    $response = [
                        'status' => 'error',
                        'message' => 'Title and author are required',
                        'data' => null
                    ];
                    break;
                }
                
                // Check if user exists
                if (!$user_id) {
                    $response = [
                        'status' => 'error',
                        'message' => 'User ID is missing, please log in again',
                        'data' => null
                    ];
                    break;
                }
                
                // Verify user exists in database
                $checkUserSql = "SELECT user_id FROM users WHERE user_id = ?";
                $checkUserStmt = $conn->prepare($checkUserSql);
                $checkUserStmt->execute([$user_id]);
                
                if ($checkUserStmt->rowCount() === 0) {
                    $response = [
                        'status' => 'error',
                        'message' => 'Invalid user account, please log in again',
                        'data' => null
                    ];
                    break;
                }
                
                // Get book data
                $title = $data['title'];
                $author = $data['author'];
                $description = isset($data['description']) ? $data['description'] : null;
                $isbn = isset($data['isbn']) ? $data['isbn'] : null;
                $year_published = isset($data['year']) ? (int)$data['year'] : null;
                $genre = isset($data['genre']) ? $data['genre'] : null;
                $cover_image = isset($data['cover_image']) ? $data['cover_image'] : null;
                $rating = isset($data['rating']) ? floatval($data['rating']) : null;
                
                // Validate rating if provided
                if ($rating !== null && ($rating < 0 || $rating > 5)) {
                    $response = [
                        'status' => 'error',
                        'message' => 'Rating must be between 0 and 5',
                        'data' => null
                    ];
                    break;
                }
                
                // Insert new book
                $insertSql = "INSERT INTO books (title, author, description, isbn, year_published, genre, cover_image, rating, status, owner_id) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'available', ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->execute([$title, $author, $description, $isbn, $year_published, $genre, $cover_image, $rating, $user_id]);
                
                $newBookId = $conn->lastInsertId();
                
                $response = [
                    'status' => 'success',
                    'message' => 'Book added successfully',
                    'data' => [
                        'book_id' => $newBookId,
                        'title' => $title
                    ]
                ];
                break;
                
            case 'borrow':
                // Check if the book is available
                $checkSql = "SELECT status FROM books WHERE book_id = ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->execute([$book_id]);
                $bookStatus = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$bookStatus) {
                    $response = [
                        'status' => 'error',
                        'message' => 'Book not found',
                        'data' => null
                    ];
                    break;
                }
                
                if ($bookStatus['status'] !== 'available') {
                    $response = [
                        'status' => 'error',
                        'message' => 'This book is not available for borrowing',
                        'data' => null
                    ];
                    break;
                }
                
                // Begin transaction
                $conn->beginTransaction();
                
                // Update book status
                $updateBookSql = "UPDATE books SET status = 'borrowed' WHERE book_id = ?";
                $updateBookStmt = $conn->prepare($updateBookSql);
                $updateBookStmt->execute([$book_id]);
                
                // Set borrowing details
                $borrowDate = date('Y-m-d H:i:s');
                $returnDate = date('Y-m-d H:i:s', strtotime('+14 days')); // 2 weeks borrowing period
                
                // Create user_books record
                $insertSql = "INSERT INTO user_books (user_id, book_id, status, borrow_date, return_date) 
                             VALUES (?, ?, 'borrowed', ?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->execute([$user_id, $book_id, $borrowDate, $returnDate]);
                
                $conn->commit();
                
                $response = [
                    'status' => 'success',
                    'message' => 'Book borrowed successfully',
                    'data' => [
                        'borrow_date' => $borrowDate,
                        'return_date' => $returnDate
                    ]
                ];
                break;
                
            case 'reserve':
                // Check if the book is already borrowed (can only reserve borrowed books)
                $checkSql = "SELECT status FROM books WHERE book_id = ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->execute([$book_id]);
                $bookStatus = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$bookStatus) {
                    $response = [
                        'status' => 'error',
                        'message' => 'Book not found',
                        'data' => null
                    ];
                    break;
                }
                
                if ($bookStatus['status'] !== 'borrowed') {
                    $response = [
                        'status' => 'error',
                        'message' => 'This book can only be reserved if it is currently borrowed',
                        'data' => null
                    ];
                    break;
                }
                
                // Check if the book is already reserved
                $checkReservationSql = "SELECT * FROM user_books WHERE book_id = ? AND status = 'reserved'";
                $checkReservationStmt = $conn->prepare($checkReservationSql);
                $checkReservationStmt->execute([$book_id]);
                $existingReservation = $checkReservationStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingReservation) {
                    $response = [
                        'status' => 'error',
                        'message' => 'This book is already reserved',
                        'data' => null
                    ];
                    break;
                }
                
                // Begin transaction
                $conn->beginTransaction();
                
                // Update book status
                $updateBookSql = "UPDATE books SET status = 'reserved' WHERE book_id = ?";
                $updateBookStmt = $conn->prepare($updateBookSql);
                $updateBookStmt->execute([$book_id]);
                
                // Set reservation date
                $reservationDate = date('Y-m-d H:i:s');
                
                // Create user_books record
                $insertSql = "INSERT INTO user_books (user_id, book_id, status, reserve_date) 
                             VALUES (?, ?, 'reserved', ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->execute([$user_id, $book_id, $reservationDate]);
                
                $conn->commit();
                
                $response = [
                    'status' => 'success',
                    'message' => 'Book reserved successfully',
                    'data' => [
                        'reserve_date' => $reservationDate
                    ]
                ];
                break;
                
            case 'return':
                // Check if the user has borrowed this book
                $checkSql = "SELECT * FROM user_books WHERE user_id = ? AND book_id = ? AND status = 'borrowed'";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->execute([$user_id, $book_id]);
                $borrowedBook = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$borrowedBook) {
                    $response = [
                        'status' => 'error',
                        'message' => 'You have not borrowed this book',
                        'data' => null
                    ];
                    break;
                }
                
                // Begin transaction
                $conn->beginTransaction();
                
                // Check if there's a reservation for this book
                $checkReservationSql = "SELECT * FROM user_books WHERE book_id = ? AND status = 'reserved'";
                $checkReservationStmt = $conn->prepare($checkReservationSql);
                $checkReservationStmt->execute([$book_id]);
                $reservation = $checkReservationStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($reservation) {
                    // The book remains 'reserved' in the books table
                    // Update user_books record for the borrower
                    $updateUserBookSql = "UPDATE user_books SET status = 'returned', return_date = ? 
                                        WHERE user_id = ? AND book_id = ? AND status = 'borrowed'";
                    $returnDate = date('Y-m-d H:i:s');
                    $updateUserBookStmt = $conn->prepare($updateUserBookSql);
                    $updateUserBookStmt->execute([$returnDate, $user_id, $book_id]);
                } else {
                    // Update book status to available
                    $updateBookSql = "UPDATE books SET status = 'available' WHERE book_id = ?";
                    $updateBookStmt = $conn->prepare($updateBookSql);
                    $updateBookStmt->execute([$book_id]);
                    
                    // Update user_books record
                    $updateUserBookSql = "UPDATE user_books SET status = 'returned', return_date = ? 
                                        WHERE user_id = ? AND book_id = ? AND status = 'borrowed'";
                    $returnDate = date('Y-m-d H:i:s');
                    $updateUserBookStmt = $conn->prepare($updateUserBookSql);
                    $updateUserBookStmt->execute([$returnDate, $user_id, $book_id]);
                }
                
                $conn->commit();
                
                $response = [
                    'status' => 'success',
                    'message' => 'Book returned successfully',
                    'data' => [
                        'return_date' => $returnDate
                    ]
                ];
                break;
                
            case 'cancel_reservation':
                // Check if the user has reserved this book
                $checkSql = "SELECT * FROM user_books WHERE user_id = ? AND book_id = ? AND status = 'reserved'";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->execute([$user_id, $book_id]);
                $reservedBook = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$reservedBook) {
                    $response = [
                        'status' => 'error',
                        'message' => 'You have not reserved this book',
                        'data' => null
                    ];
                    break;
                }
                
                // Begin transaction
                $conn->beginTransaction();
                
                // Check if the book is currently borrowed
                $checkBorrowedSql = "SELECT * FROM user_books WHERE book_id = ? AND status = 'borrowed'";
                $checkBorrowedStmt = $conn->prepare($checkBorrowedSql);
                $checkBorrowedStmt->execute([$book_id]);
                $borrowedStatus = $checkBorrowedStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($borrowedStatus) {
                    // Book remains borrowed, just cancel the reservation
                    // Update user_books record for the reservation
                    $updateUserBookSql = "UPDATE user_books SET status = 'cancelled', cancel_date = ? 
                                        WHERE user_id = ? AND book_id = ? AND status = 'reserved'";
                    $cancelDate = date('Y-m-d H:i:s');
                    $updateUserBookStmt = $conn->prepare($updateUserBookSql);
                    $updateUserBookStmt->execute([$cancelDate, $user_id, $book_id]);
                } else {
                    // Update book status to available
                    $updateBookSql = "UPDATE books SET status = 'available' WHERE book_id = ?";
                    $updateBookStmt = $conn->prepare($updateBookSql);
                    $updateBookStmt->execute([$book_id]);
                    
                    // Update user_books record
                    $updateUserBookSql = "UPDATE user_books SET status = 'cancelled', cancel_date = ? 
                                        WHERE user_id = ? AND book_id = ? AND status = 'reserved'";
                    $cancelDate = date('Y-m-d H:i:s');
                    $updateUserBookStmt = $conn->prepare($updateUserBookSql);
                    $updateUserBookStmt->execute([$cancelDate, $user_id, $book_id]);
                }
                
                $conn->commit();
                
                $response = [
                    'status' => 'success',
                    'message' => 'Reservation cancelled successfully',
                    'data' => [
                        'cancel_date' => $cancelDate
                    ]
                ];
                break;
                
            case 'delete':
                // Check if the book exists and is owned by this user
                $checkSql = "SELECT * FROM books WHERE book_id = ? AND owner_id = ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->execute([$book_id, $user_id]);
                $book = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$book) {
                    $response = [
                        'status' => 'error',
                        'message' => 'Book not found or you don\'t have permission to delete it',
                        'data' => null
                    ];
                    break;
                }
                
                // Check if the book is currently borrowed
                if ($book['status'] === 'borrowed') {
                    $response = [
                        'status' => 'error',
                        'message' => 'Cannot delete a book that is currently borrowed',
                        'data' => null
                    ];
                    break;
                }
                
                // Begin transaction
                $conn->beginTransaction();
                
                try {
                    // Delete from user_books first due to foreign key constraint
                    $deleteUBSql = "DELETE FROM user_books WHERE book_id = ?";
                    $deleteUBStmt = $conn->prepare($deleteUBSql);
                    $deleteUBStmt->execute([$book_id]);
                    
                    $deleteSql = "DELETE FROM books WHERE book_id = ?";
                    $deleteStmt = $conn->prepare($deleteSql);
                    $deleteStmt->execute([$book_id]);
                    
                    $conn->commit();
                    
                    $response = [
                        'status' => 'success',
                        'message' => 'Book deleted successfully',
                        'data' => null
                    ];
                } catch (Exception $e) {
                    $conn->rollBack();
                    $response = [
                        'status' => 'error',
                        'message' => 'Error deleting book: ' . $e->getMessage(),
                        'data' => null
                    ];
                }
                break;
                
            default:
                $response = [
                    'status' => 'error',
                    'message' => 'Invalid action',
                    'data' => null
                ];
                
                // Special case for test action
                if ($action === 'test') {
                    $response = [
                        'status' => 'success',
                        'message' => 'API connection test successful',
                        'data' => [
                            'received_data' => $data,
                            'user_id' => $user_id,
                            'session_active' => isset($_SESSION['user_id']),
                            'php_version' => PHP_VERSION,
                            'datetime' => date('Y-m-d H:i:s')
                        ]
                    ];
                }
        }
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        $response = [
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage(),
            'data' => null
        ];
    }
}

// Output JSON response
echo json_encode($response); 