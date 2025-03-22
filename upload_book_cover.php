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
            
            // Create directory structure if it doesn't exist
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    $response['message'] = 'Failed to create upload directory. Please contact administrator.';
                    echo json_encode($response);
                    exit;
                }
            }
            
            // Check if directory is writable
            if (!is_writable($uploadDir)) {
                $response['message'] = 'Upload directory is not writable. Please contact administrator.';
                echo json_encode($response);
                exit;
            }
            
            $uploadPath = $uploadDir . $newFileName;
            
            // Move uploaded file
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                // Validate the uploaded image
                $imageInfo = getimagesize($uploadPath);
                if ($imageInfo === false) {
                    // Not a valid image, delete the file
                    unlink($uploadPath);
                    $response['message'] = 'The uploaded file is not a valid image';
                } else {
                    $response = [
                        'status' => 'success',
                        'message' => 'File uploaded successfully',
                        'data' => [
                            'file_path' => $uploadPath
                        ]
                    ];
                }
            } else {
                $response['message'] = 'Error uploading file. Please try again.';
            }
        } else {
            $response['message'] = 'File size exceeds the limit (2MB)';
        }
    } else {
        $response['message'] = 'Invalid file type. Only JPG, PNG and GIF are allowed';
    }
} else if (isset($_FILES['book_cover'])) {
    // Detailed error messages for different upload errors
    switch ($_FILES['book_cover']['error']) {
        case UPLOAD_ERR_INI_SIZE:
            $response['message'] = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            break;
        case UPLOAD_ERR_FORM_SIZE:
            $response['message'] = 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
            break;
        case UPLOAD_ERR_PARTIAL:
            $response['message'] = 'The uploaded file was only partially uploaded';
            break;
        case UPLOAD_ERR_NO_FILE:
            $response['message'] = 'No file was uploaded';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $response['message'] = 'Missing temporary folder';
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $response['message'] = 'Failed to write file to disk';
            break;
        case UPLOAD_ERR_EXTENSION:
            $response['message'] = 'File upload stopped by extension';
            break;
        default:
            $response['message'] = 'Unknown upload error';
            break;
    }
}

echo json_encode($response);
exit;
?> 