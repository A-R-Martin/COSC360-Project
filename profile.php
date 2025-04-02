<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: signin.php");
    exit();
}

// Check if the user is banned
require_once 'db_connect.php';
$stmt = $conn->prepare("SELECT status FROM users WHERE user_id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (isset($user['status']) && $user['status'] === 'banned') {
    // Clear session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Redirect with error message
    session_start();
    $_SESSION['error_message'] = "Your account has been banned. Please contact the administrator.";
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage your Virtual Library profile, track your borrowed books, and update your reading preferences">
    <meta name="keywords" content="library profile, reading history, borrowed books, reader preferences, virtual library account">
    <title>My Profile - Virtual Library</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'nav.php'; ?>
    <main>
        <section class="profile-container">
            <div class="profile-header">
                <h1>My Profile</h1>
            </div>
            
            <!-- Add form message container -->
            <div id="form-message-container" class="form-message-container"></div>
            
            <div class="profile-content">
                <div class="profile-section">
                    <h2>Profile Information</h2>
                    <div class="profile-image-container">
                        <img id="current-profile-image" src="placeholder-profile.jpg" alt="Profile Image">
                        <div class="profile-image-actions">
                            <div class="upload-btn-wrapper">
                                <button type="button" class="btn-primary">Choose Image</button>
                                <input type="file" id="profile-image" name="profile-image" 
                                       accept="image/jpeg,image/png,image/gif"
                                       title="Please select an image file (JPG, PNG, or GIF)">
                            </div>
                            <button type="button" class="btn-danger" id="remove-profile-image">Remove Picture</button>
                        </div>
                    </div>
                    <form id="profile-form" novalidate data-custom-submit="true">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required
                                   minlength="3" maxlength="30" 
                                   pattern="^[a-zA-Z0-9_-]+$"
                                   title="Username must be between 3-30 characters and can only contain letters, numbers, underscores and hyphens">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required
                                   pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                                   title="Please enter a valid email address">
                        </div>
                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio" rows="4" maxlength="500"
                                    title="Bio can be up to 500 characters long"></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary" id="update-profile">Update Profile</button>
                        </div>
                    </form>
                </div>

                <div class="profile-section">
                    <h2>Change Password</h2>
                    <form id="password-form" class="password-change-form" novalidate data-custom-submit="true">
                        <div class="form-group">
                            <label for="current-password">Current Password</label>
                            <input type="password" id="current-password" name="current-password" required
                                   title="Please enter your current password">
                        </div>
                        <div class="form-group">
                            <label for="new-password">New Password</label>
                            <input type="password" id="new-password" name="new-password" required
                                   minlength="8"
                                   pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                                   title="Password must be at least 8 characters long and include uppercase, lowercase, number and special character">
                            <ul class="password-requirements">
                                <li>At least 8 characters long</li>
                                <li>At least one uppercase letter</li>
                                <li>At least one lowercase letter</li>
                                <li>At least one number</li>
                                <li>At least one special character (@$!%*?&)</li>
                            </ul>
                        </div>
                        <div class="form-group">
                            <label for="confirm-password">Confirm New Password</label>
                            <input type="password" id="confirm-password" name="confirm-password" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary" id="change-password">Update Password</button>
                        </div>
                    </form>
                </div>

                <div class="profile-section">
                    <h2>My Borrowed Books</h2>
                    <div class="data-table">
                        <table id="borrowed-books">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Owner</th>
                                    <th>Borrowed Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- TODO: Populate with borrowed books from db -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="profile-section">
                    <h2>My Library (Books Available for Borrowing)</h2>
                    <div class="data-table">
                        <table id="owned-books">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Author</th>
                                    <th>Status</th>
                                    <th>Current Borrower</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Will be populated with uploaded books from db -->
                            </tbody>
                        </table>
                        <div class="button-container" style="margin-top: 1rem; display: flex; justify-content: flex-end;">
                            <button class="btn-primary" id="add-book">Add New Book</button>
                        </div>
                    </div>
                </div>

                <div class="profile-section">
                    <h2>Reading History</h2>
                    <div class="data-table">
                        <table id="reading-history">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Author</th>
                                    <th>Borrowed Date</th>
                                    <th>Return Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- TODO: Populate with reading history from db -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="profile-section">
                    <h2>My Comments</h2>
                    <div id="comments-container" class="comments-list">
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="scripts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Load borrowed books
            loadBorrowedBooks();
            
            // Load uploaded books
            loadUploadedBooks();
            
            // Load reading history
            loadReadingHistory();
            
            // Handle add book button
            document.getElementById('add-book').addEventListener('click', function() {
                window.location.href = 'add_book.php'; // Or show a modal
            });
            
            // Function to load borrowed books
            function loadBorrowedBooks() {
                const table = document.getElementById('borrowed-books').getElementsByTagName('tbody')[0];
                table.innerHTML = '<tr><td colspan="6">Loading...</td></tr>';
                
                fetch('api_user_books.php?action=borrowed')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            table.innerHTML = '';
                            if (data.data && data.data.length > 0) {
                                data.data.forEach(book => {
                                    const row = document.createElement('tr');
                                    
                                    // Calculate days remaining
                                    const dueDate = new Date(book.return_date);
                                    const today = new Date();
                                    const daysRemaining = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
                                    
                                    let statusClass = '';
                                    if (daysRemaining <= 3) {
                                        statusClass = 'status-due-soon';
                                    } else if (daysRemaining <= 0) {
                                        statusClass = 'status-overdue';
                                    }
                                    
                                    row.innerHTML = `
                                        <td>${book.title}</td>
                                        <td>Owner Name</td>
                                        <td>${new Date(book.borrow_date).toLocaleDateString()}</td>
                                        <td>${new Date(book.return_date).toLocaleDateString()}</td>
                                        <td class="${statusClass}">${daysRemaining <= 0 ? 'Overdue' : `${daysRemaining} days remaining`}</td>
                                        <td>
                                            <button class="return-book" data-book-id="${book.book_id}">Return</button>
                                        </td>
                                    `;
                                    
                                    table.appendChild(row);
                                });
                                
                                // Add event listeners for return buttons
                                const returnButtons = document.querySelectorAll('.return-book');
                                returnButtons.forEach(button => {
                                    button.addEventListener('click', function() {
                                        const bookId = this.getAttribute('data-book-id');
                                        returnBook(bookId);
                                    });
                                });
                            } else {
                                table.innerHTML = '<tr><td colspan="6">No borrowed books found</td></tr>';
                            }
                        } else {
                            table.innerHTML = `<tr><td colspan="6">Error: ${data.message}</td></tr>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        table.innerHTML = '<tr><td colspan="6">Error loading borrowed books</td></tr>';
                    });
            }
            
            // Function to load uploaded books
            function loadUploadedBooks() {
                const table = document.getElementById('owned-books').getElementsByTagName('tbody')[0];
                table.innerHTML = '<tr><td colspan="5">Loading...</td></tr>';
                
                fetch('api_user_books.php?action=my_uploads')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            table.innerHTML = '';
                            if (data.data && data.data.length > 0) {
                                data.data.forEach(book => {
                                    const row = document.createElement('tr');
                                    
                                    let statusClass = '';
                                    let statusText = book.status;
                                    
                                    if (book.status === 'borrowed') {
                                        statusClass = 'status-borrowed';
                                        statusText = 'Borrowed';
                                    } else if (book.status === 'reserved') {
                                        statusClass = 'status-reserved';
                                        statusText = 'Reserved';
                                    } else {
                                        statusClass = 'status-available';
                                        statusText = 'Available';
                                    }
                                    
                                    row.innerHTML = `
                                        <td>${book.title}</td>
                                        <td>${book.author}</td>
                                        <td class="${statusClass}">${statusText}</td>
                                        <td>${book.borrower_name || '-'}</td>
                                        <td>
                                            <button class="btn-edit-book" data-book-id="${book.book_id}">Edit</button>
                                            <button class="btn-delete-book" data-book-id="${book.book_id}">Delete</button>
                                        </td>
                                    `;
                                    
                                    table.appendChild(row);
                                });
                                
                                // Add event listeners for action buttons
                                /* Edit button is now handled by global event handler in scripts.js
                                const editButtons = document.querySelectorAll('.edit-book');
                                editButtons.forEach(button => {
                                    button.addEventListener('click', function() {
                                        const bookId = this.getAttribute('data-book-id');
                                        window.location.href = `edit_book.php?id=${bookId}`;
                                    });
                                });
                                */
                                
                                const deleteButtons = document.querySelectorAll('.delete-book');
                                deleteButtons.forEach(button => {
                                    button.addEventListener('click', function() {
                                        const bookId = this.getAttribute('data-book-id');
                                        if (confirm('Are you sure you want to delete this book?')) {
                                            deleteBook(bookId);
                                        }
                                    });
                                });
                            } else {
                                table.innerHTML = '<tr><td colspan="5">No uploaded books found</td></tr>';
                            }
                        } else {
                            table.innerHTML = `<tr><td colspan="5">Error: ${data.message}</td></tr>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        table.innerHTML = '<tr><td colspan="5">Error loading uploaded books</td></tr>';
                    });
            }
            
            // Function to load reading history
            function loadReadingHistory() {
                const table = document.getElementById('reading-history').getElementsByTagName('tbody')[0];
                table.innerHTML = '<tr><td colspan="5">Loading...</td></tr>';
                
                fetch('api_user_books.php?action=history')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            table.innerHTML = '';
                            if (data.data && data.data.length > 0) {
                                data.data.forEach(book => {
                                    const row = document.createElement('tr');
                                    row.innerHTML = `
                                        <td>${book.title}</td>
                                        <td>${book.author}</td>
                                        <td>${new Date(book.borrow_date).toLocaleDateString()}</td>
                                        <td>${new Date(book.return_date).toLocaleDateString()}</td>
                                        <td>Returned</td>
                                    `;
                                    table.appendChild(row);
                                });
                            } else {
                                table.innerHTML = '<tr><td colspan="5">No reading history found</td></tr>';
                            }
                        } else {
                            table.innerHTML = `<tr><td colspan="5">Error: ${data.message}</td></tr>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        table.innerHTML = '<tr><td colspan="5">Error loading reading history</td></tr>';
                    });
            }
            
            // Function to return a book
            function returnBook(bookId) {
                fetch('api_user_books.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'return',
                        book_id: bookId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Book returned successfully');
                        loadBorrowedBooks();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error returning book');
                });
            }
            
            // Function to delete a book
            function deleteBook(bookId) {
                fetch('api_books.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        book_id: bookId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Book deleted successfully');
                        loadUploadedBooks();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting book');
                });
            }
        });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html> 