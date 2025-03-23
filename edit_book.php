<?php
session_start();
// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect to login page if not logged in or not admin
    header("Location: signin.php");
    exit();
}

require_once 'db_connect.php';

// Get book ID from URL
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Redirect if no book ID provided
if (!$book_id) {
    header('Location: admin.php');
    exit;
}

// Get book details from the database
try {
    $sql = "SELECT * FROM books WHERE book_id = :book_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['book_id' => $book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        // Book not found
        header('Location: admin.php');
        exit;
    }
    
} catch (PDOException $e) {
    // Database error
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Edit book details in the Virtual Library admin system">
    <meta name="keywords" content="edit book, admin, book management, library admin">
    <title>Edit Book - Admin - Virtual Library</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include 'nav.php'; ?>
    <main>
        <div class="form-container">
            <div class="form-header">
                <h1>Edit Book</h1>
                <a href="admin.php" class="btn-secondary">Back to Admin Dashboard</a>
            </div>
            
            <div id="form-message-container" class="form-message-container"></div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php else: ?>
                <form id="edit-book-form" class="styled-form" enctype="multipart/form-data" novalidate data-custom-submit="true">
                    <input type="hidden" id="book-id" name="book-id" value="<?php echo $book_id; ?>">

                    <div class="form-group">
                        <label for="title">Book Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" required 
                            value="<?php echo htmlspecialchars($book['title']); ?>"
                            maxlength="255">
                    </div>

                    <div class="form-group">
                        <label for="author">Author <span class="required">*</span></label>
                        <input type="text" id="author" name="author" required
                            value="<?php echo htmlspecialchars($book['author']); ?>"
                            maxlength="255">
                    </div>

                    <div class="form-group">
                        <label for="isbn">ISBN</label>
                        <input type="text" id="isbn" name="isbn"
                            value="<?php echo htmlspecialchars($book['isbn'] ?? ''); ?>"
                            pattern="^(?:\d[- ]?){9}[\dX]$|^(?:\d[- ]?){13}$"
                            title="Please enter a valid 10 or 13 digit ISBN number"
                            maxlength="20">
                        <div class="form-text">ISBN-10 or ISBN-13 format, with or without hyphens (e.g., 978-3-16-148410-0 or 9781234567897)</div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5" maxlength="2000"><?php echo htmlspecialchars($book['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="year_published">Year Published</label>
                        <input type="number" id="year_published" name="year_published" min="1000" max="<?php echo date('Y'); ?>"
                            value="<?php echo htmlspecialchars($book['year_published'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="genre">Genre</label>
                        <input type="text" id="genre" name="genre" maxlength="100"
                            value="<?php echo htmlspecialchars($book['genre'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="rating">Rating (1-5)</label>
                        <input type="number" id="rating" name="rating" min="1" max="5" step="0.1"
                            value="<?php echo htmlspecialchars($book['rating'] ?? '3.0'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="available" <?php echo $book['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="borrowed" <?php echo $book['status'] === 'borrowed' ? 'selected' : ''; ?>>Borrowed</option>
                            <option value="reserved" <?php echo $book['status'] === 'reserved' ? 'selected' : ''; ?>>Reserved</option>
                        </select>
                    </div>

                    <div class="form-group file-input-container">
                        <label for="cover_image">Book Cover Image</label>
                        <input type="file" id="cover_image" name="cover_image" accept="image/*">
                        <div class="form-text">Maximum file size: 5MB. Recommended dimensions: 600x900 pixels.</div>
                        
                        <div class="file-input-preview">
                            <img id="cover-image-preview" src="<?php echo !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : 'Sample-image.avif'; ?>" 
                                alt="Book cover preview" style="max-height: 200px; max-width: 100%;">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Update Book</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>
    <script src="scripts.js"></script>
</body>

</html> 