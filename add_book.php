<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: signin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Add a new book to your Virtual Library collection">
    <meta name="keywords" content="add book, library management, virtual library, book catalog">
    <title>Add New Book - Virtual Library</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'nav.php'; ?>
    <main>
        <section class="form-container">
            <div class="form-header">
                <h1>Add New Book</h1>
                <a href="profile.php" class="btn-secondary">Back to Profile</a>
            </div>
            
            <form id="add-book-form" class="styled-form" enctype="multipart/form-data" novalidate>
                <div class="form-group">
                    <label for="title">Book Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required
                           maxlength="255"
                           title="Please enter the book title">
                </div>
                
                <div class="form-group">
                    <label for="author">Author <span class="required">*</span></label>
                    <input type="text" id="author" name="author" required
                           maxlength="255"
                           title="Please enter the author's name">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"
                              title="Please enter a brief description of the book"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="isbn">ISBN</label>
                    <input type="text" id="isbn" name="isbn"
                           pattern="^(?:\d{10}|\d{13})$"
                           title="ISBN must be 10 or 13 digits">
                    <small class="form-text">Enter a valid 10 or 13 digit ISBN number</small>
                </div>
                
                <div class="form-group">
                    <label for="year">Year Published</label>
                    <input type="number" id="year" name="year"
                           min="1000" max="<?php echo date('Y'); ?>"
                           title="Please enter a valid year">
                </div>
                
                <div class="form-group">
                    <label for="genre">Genre</label>
                    <select id="genre" name="genre">
                        <option value="">Select a genre</option>
                        <option value="Fiction">Fiction</option>
                        <option value="Non-Fiction">Non-Fiction</option>
                        <option value="Science Fiction">Science Fiction</option>
                        <option value="Fantasy">Fantasy</option>
                        <option value="Mystery">Mystery</option>
                        <option value="Romance">Romance</option>
                        <option value="Horror">Horror</option>
                        <option value="Biography">Biography</option>
                        <option value="History">History</option>
                        <option value="Self-Help">Self-Help</option>
                        <option value="Business">Business</option>
                        <option value="Children">Children</option>
                        <option value="Young Adult">Young Adult</option>
                        <option value="Science">Science</option>
                        <option value="Technology">Technology</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="book-cover">Book Cover Image</label>
                    <div class="file-input-container">
                        <input type="file" id="book-cover" name="book_cover" 
                               accept="image/jpeg,image/png,image/gif"
                               title="Please select an image file (JPG, PNG, or GIF)">
                        <div class="file-input-preview">
                            <img id="cover-preview" src="#" alt="Cover preview" style="display: none; max-width: 200px; max-height: 300px;">
                        </div>
                    </div>
                    <small class="form-text">Upload a cover image (optional). Max file size: 2MB</small>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" id="cancel-add-book">Cancel</button>
                    <button type="submit" class="btn-primary" id="submit-add-book">Add Book</button>
                </div>
            </form>
            
            <div id="form-response-message" class="alert" style="display: none;"></div>
        </section>
    </main>
    
    <script src="scripts.js"></script>
</body>
</html> 