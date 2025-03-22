<?php
// Handle book cover image uploads with validation
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'You must be logged in to upload images',
        'data' => null
    ]);
    exit;
}

// Default response
$response = [
    'status' => 'error',
    'message' => 'No file was uploaded',
    'data' => null
];

// Check if file was uploaded
if (isset($_FILES['book_cover']) && $_FILES['book_cover']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['book_cover'];
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
            $newFileName = uniqid('cover_', true) . '.' . $fileExt;
            $uploadDir = 'uploads/covers/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $uploadPath = $uploadDir . $newFileName;
            
            // Move uploaded file
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                $response = [
                    'status' => 'success',
                    'message' => 'File uploaded successfully',
                    'data' => [
                        'file_path' => $uploadPath
                    ]
                ];
            } else {
                $response['message'] = 'Error uploading file';
            }
        } else {
            $response['message'] = 'File size exceeds the limit (2MB)';
        }
    } else {
        $response['message'] = 'Invalid file type. Only JPG, PNG and GIF are allowed';
    }
}

echo json_encode($response);
exit;
?> 