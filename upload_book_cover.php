<?php
// Handle book cover image uploads with validation
session_start();
require_once 'db_connect.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access',
        'data' => null
    ]);
    exit;
}

// Response array
$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => null
];

// Check if the request is POST and contains a file
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['cover_image'])) {
    $response['message'] = 'No file uploaded or invalid request method';
    echo json_encode($response);
    exit;
}

// Validate file
$file = $_FILES['cover_image'];
$book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : null;

// Create upload directory if it doesn't exist
$upload_dir = 'uploads/book_covers/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Validate book ID if provided
if ($book_id) {
    try {
        $checkQuery = "SELECT * FROM books WHERE book_id = :book_id";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute(['book_id' => $book_id]);
        
        if ($checkStmt->rowCount() === 0) {
            $response['message'] = 'Book not found';
            echo json_encode($response);
            exit;
        }
        
        $file = $_FILES['cover_image'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        
        // File validation
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($fileExt, $allowedExtensions)) {
            $response['message'] = 'Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.';
            echo json_encode($response);
            exit;
        }
        
        if ($fileError !== 0) {
            $response['message'] = 'Error uploading file';
            echo json_encode($response);
            exit;
        }
        
        if ($fileSize > 5000000) { // 5MB max
            $response['message'] = 'File too large. Maximum size is 5MB.';
            echo json_encode($response);
            exit;
        }
        
        // Create unique filename
        $newFileName = uniqid('book_cover_') . '.' . $fileExt;
        $upload_path = $upload_dir . $newFileName;
        
        if (move_uploaded_file($fileTmpName, $upload_path)) {
            // Update database with new cover path
            $query = "UPDATE books SET cover_image = :cover WHERE book_id = :book_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                'cover' => $upload_path,
                'book_id' => $book_id
            ]);
            
            $response['status'] = 'success';
            $response['message'] = 'Cover image uploaded successfully';
            $response['data'] = [
                'book_id' => $book_id,
                'filename' => $newFileName,
                'path' => $upload_path
            ];
        } else {
            $response['message'] = 'Failed to move uploaded file';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Book ID is required';
}

echo json_encode($response);
exit;
?> 