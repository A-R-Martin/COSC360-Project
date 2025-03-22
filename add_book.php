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
                    <label for="rating">Rating</label>
                    <div class="rating-input-container">
                        <input type="number" id="rating" name="rating" 
                               min="0" max="5" step="0.1" 
                               placeholder="0.0 - 5.0"
                               title="Book rating from 0 to 5 stars">
                        <small class="form-text">Enter a rating between 0 and 5 (e.g., 4.5)</small>
                    </div>
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
                    <button type="submit" class="btn-primary" id="submit-add-book" onclick="console.log('Submit button clicked directly')">Add Book</button>
                </div>
            </form>
            
            <div id="form-response-message" class="alert" style="display: none;"></div>
            
            <!-- Debugging Tools -->
            <div class="debug-tools" style="margin-top: 2rem; padding: 1rem; background: #f8f8f8; border-radius: 4px;">
                <h3>Debugging Tools</h3>
                <p>If you're having trouble submitting the form, you can use these debugging tools:</p>
                <button type="button" id="debug-submit-btn" class="btn-secondary">Debug Form Data</button>
                <button type="button" id="manual-submit-btn" class="btn-secondary" style="margin-left: 1rem">Manual API Submit</button>
                <div id="debug-output" style="margin-top: 1rem; padding: 1rem; background: #efefef; white-space: pre-wrap; font-family: monospace;"></div>
            </div>
        </section>
    </main>
    
    <script src="scripts.js"></script>
    <script>
        // Additional debugging script
        document.getElementById('debug-submit-btn')?.addEventListener('click', function() {
            const debugOutput = document.getElementById('debug-output');
            
            try {
                // Collect form data
                const titleInput = document.getElementById('title');
                const authorInput = document.getElementById('author');
                const descriptionInput = document.getElementById('description');
                const isbnInput = document.getElementById('isbn');
                const yearInput = document.getElementById('year');
                const genreInput = document.getElementById('genre');
                const ratingInput = document.getElementById('rating');
                const bookCoverInput = document.getElementById('book-cover');
                
                const debugData = {
                    title: titleInput?.value?.trim() || 'Not found',
                    author: authorInput?.value?.trim() || 'Not found',
                    description: descriptionInput?.value?.trim() || 'Not found',
                    isbn: isbnInput?.value?.trim() || 'Not found',
                    year: yearInput?.value || 'Not found',
                    genre: genreInput?.value || 'Not found',
                    rating: ratingInput?.value || 'Not found',
                    hasCoverFile: bookCoverInput?.files?.length > 0 ? 'Yes' : 'No',
                    fileInfo: bookCoverInput?.files?.length > 0 ? {
                        name: bookCoverInput.files[0].name,
                        type: bookCoverInput.files[0].type,
                        size: bookCoverInput.files[0].size + ' bytes'
                    } : 'N/A',
                    formId: document.getElementById('add-book-form')?.id || 'Not found',
                    sessionStatus: '<?php echo isset($_SESSION["user_id"]) ? "Logged in (ID: ".$_SESSION["user_id"].")" : "Not logged in"; ?>'
                };
                
                debugOutput.textContent = "Form Data Debug:\n" + JSON.stringify(debugData, null, 2);
                
                // Test if fetch is working
                debugOutput.textContent += "\n\nTesting API connection...";
                
                fetch('api_books.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({action: 'test'})
                })
                .then(response => {
                    debugOutput.textContent += "\nAPI Response Status: " + response.status;
                    return response.text();
                })
                .then(text => {
                    debugOutput.textContent += "\nAPI Response Text: " + text;
                })
                .catch(error => {
                    debugOutput.textContent += "\nAPI Error: " + error.message;
                });
                
            } catch (error) {
                debugOutput.textContent = "Error collecting debug data: " + error.message;
            }
        });
        
        // Manual submit button
        document.getElementById('manual-submit-btn')?.addEventListener('click', async function() {
            const debugOutput = document.getElementById('debug-output');
            debugOutput.textContent = "Attempting manual submission...";
            
            try {
                // Collect form data
                const titleInput = document.getElementById('title');
                const authorInput = document.getElementById('author');
                const descriptionInput = document.getElementById('description');
                const isbnInput = document.getElementById('isbn');
                const yearInput = document.getElementById('year');
                const genreInput = document.getElementById('genre');
                const ratingInput = document.getElementById('rating');
                
                // Create book data object
                const bookData = {
                    action: 'add',
                    title: titleInput.value.trim(),
                    author: authorInput.value.trim(),
                    description: descriptionInput.value.trim(),
                    isbn: isbnInput.value.trim(),
                    year: yearInput.value,
                    genre: genreInput.value,
                    rating: ratingInput.value ? parseFloat(ratingInput.value) : null
                };
                
                debugOutput.textContent += "\nPrepared book data:\n" + JSON.stringify(bookData, null, 2);
                
                // Submit directly to API
                debugOutput.textContent += "\n\nSubmitting to API...";
                
                const response = await fetch('api_books.php', {
                    method: 'POST', 
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(bookData)
                });
                
                debugOutput.textContent += "\nAPI Response Status: " + response.status;
                
                const result = await response.json();
                debugOutput.textContent += "\nAPI Response Data:\n" + JSON.stringify(result, null, 2);
                
                if (result.status === 'success') {
                    debugOutput.textContent += "\n\nBook added successfully! Book ID: " + result.data.book_id;
                } else {
                    debugOutput.textContent += "\n\nError adding book: " + result.message;
                }
                
            } catch (error) {
                debugOutput.textContent += "\n\nError in manual submission: " + error.message;
                console.error('Manual submission error:', error);
            }
        });
    </script>
</body>
</html> 