<?php
session_start();
require_once 'db_connect.php';

// Get book ID from URL
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Redirect if no book ID provided
if (!$book_id) {
    header('Location: catalog.php');
    exit;
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Get book details from the database
try {
    $sql = "SELECT *, cover_image as cover FROM books WHERE book_id = :book_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['book_id' => $book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        // Book not found
        header('Location: catalog.php');
        exit;
    }
    
    // If user is logged in, check if they have a relationship with this book
    $user_book_status = null;
    if ($is_logged_in) {
        $statusSql = "SELECT status, borrow_date, return_date, reserve_date, cancel_date 
                     FROM user_books 
                     WHERE user_id = :user_id AND book_id = :book_id 
                     ORDER BY id DESC LIMIT 1";
        $statusStmt = $conn->prepare($statusSql);
        $statusStmt->execute([
            'user_id' => $user_id,
            'book_id' => $book_id
        ]);
        $user_book = $statusStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_book) {
            $user_book_status = $user_book['status'];
        }
    }
    
} catch (PDOException $e) {
    // Database error
    $error = "Database error: " . $e->getMessage();
}

// Set page title
$page_title = $book ? $book['title'] : "Book Not Found";

// Include header
include 'includes/header.php';
?>

<main class="container">
    <?php if (isset($error)): ?>
        <div class="error-container">
            <p class="error-message"><?php echo $error; ?></p>
            <a href="catalog.php" class="btn btn-primary">Back to Catalog</a>
        </div>
    <?php elseif ($book): ?>
        <div class="book-detail">
            <div class="book-detail-header">
                <a href="catalog.php" class="btn btn-primary">
                    &larr; Back to Catalog
                </a>
                <h1><?php echo htmlspecialchars($book['title']); ?></h1>
            </div>
            
            <div class="book-detail-content">
                <div class="book-detail-cover">
                    <img src="<?php echo htmlspecialchars($book['cover'] ?? 'sample-image.avif'); ?>" 
                         alt="<?php echo htmlspecialchars($book['title']); ?>" 
                         class="book-cover"
                         onerror="this.src='sample-image.avif'; this.onerror=null;">
                </div>
                
                <div class="book-detail-info">
                    <div class="book-meta">
                        <p class="book-author">By <strong><?php echo htmlspecialchars($book['author']); ?></strong></p>
                        <p class="book-isbn">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></p>
                        <p class="book-genre">Genre: <?php echo htmlspecialchars($book['genre']); ?></p>
                        <p class="book-year">Year: <?php echo htmlspecialchars($book['year_published']); ?></p>
                        <div class="book-rating">
                            <?php
                            $rating = (float)$book['rating'];
                            $fullStars = floor($rating);
                            $halfStar = $rating - $fullStars >= 0.5;
                            
                            // Display full stars
                            for ($i = 0; $i < $fullStars; $i++) {
                                echo '<span class="star full">★</span>';
                            }
                            
                            // Display half star if needed
                            if ($halfStar) {
                                echo '<span class="star half">★</span>';
                                $i++;
                            }
                            
                            // Display empty stars
                            for (; $i < 5; $i++) {
                                echo '<span class="star empty">☆</span>';
                            }
                            
                            echo '<span class="rating-value">(' . number_format($rating, 1) . ')</span>';
                            ?>
                        </div>
                    </div>
                    
                    <div class="book-status-container">
                        <?php
                        $display_status = $book['status'];
                        $is_reserved = false;
                        
                        // Check if book is reserved by anyone
                        if ($is_logged_in) {
                            $reserveCheckSql = "SELECT COUNT(*) as count FROM user_books 
                                               WHERE book_id = :book_id AND status = 'reserved'";
                            $reserveCheckStmt = $conn->prepare($reserveCheckSql);
                            $reserveCheckStmt->execute(['book_id' => $book_id]);
                            $is_reserved = $reserveCheckStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
                            
                            // If book is borrowed but also has reservations, show as RESERVED for other users
                            if ($is_reserved && $book['status'] === 'borrowed' && $user_book_status !== 'borrowed') {
                                $display_status = 'reserved';
                            }
                            
                            // If current user has reserved this book, show as RESERVED
                            if ($user_book_status === 'reserved') {
                                $display_status = 'reserved';
                            }
                        }
                        ?>
                        <span class="book-status <?php echo htmlspecialchars($display_status); ?>">
                            <?php echo strtoupper(htmlspecialchars($display_status)); ?>
                        </span>
                        
                        <?php if ($is_logged_in): ?>
                            <div class="book-actions">
                                <?php if ($user_book_status === 'borrowed'): ?>
                                    <button class="btn btn-return" data-book-id="<?php echo $book_id; ?>">Return Book</button>
                                    <?php if ($user_book['borrow_date'] || $user_book['return_date']): ?>
                                        <p class="borrowed-info">
                                            <?php if ($user_book['borrow_date']): ?>
                                                Borrowed on: <?php echo date('M d, Y', strtotime($user_book['borrow_date'])); ?>
                                            <?php endif; ?>
                                            <?php if ($user_book['borrow_date'] && $user_book['return_date']): ?> | <?php endif; ?>
                                            <?php if ($user_book['return_date']): ?>
                                                Due by: <?php echo date('M d, Y', strtotime($user_book['return_date'])); ?>
                                            <?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                <?php elseif ($user_book_status === 'reserved'): ?>
                                    <button class="btn btn-cancel" data-book-id="<?php echo $book_id; ?>">Cancel Reservation</button>
                                    <?php if ($user_book['reserve_date']): ?>
                                        <p class="reserved-info">Reserved on: <?php echo date('M d, Y', strtotime($user_book['reserve_date'])); ?></p>
                                    <?php endif; ?>
                                <?php elseif ($book['status'] === 'available'): ?>
                                    <button class="btn btn-borrow" data-book-id="<?php echo $book_id; ?>">Borrow Book</button>
                                <?php elseif ($book['status'] === 'borrowed' && !$user_book_status): ?>
                                    <button class="btn btn-reserve" data-book-id="<?php echo $book_id; ?>">Reserve Book</button>
                                    <p class="book-note">This book is currently borrowed. You will be notified when it becomes available.</p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="login-prompt">
                                <p>Please <a href="signin.php">sign in</a> to borrow or reserve this book.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="book-description">
                        <h2>Description</h2>
                        <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                    </div>
                    
                    <!-- Book Comments Section -->
                    <div class="book-comments-section">
                        <h2>Comments</h2>
                        
                        <!-- Comment form for logged-in users -->
                        <?php if ($is_logged_in): ?>
                            <div class="comment-form">
                                <textarea id="comment-text" placeholder="Write a comment..." rows="3"></textarea>
                                <button id="post-comment" class="btn btn-primary" data-book-id="<?php echo $book_id; ?>">Post Comment</button>
                            </div>
                        <?php else: ?>
                            <div class="login-prompt">
                                <p>Please <a href="signin.php">sign in</a> to leave a comment.</p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Comments container -->
                        <div id="comments-container" class="comments-container">
                            <div class="loading-comments">Loading comments...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="action-response" class="action-response"></div>
        
        <?php if ($is_logged_in): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Book action buttons
                const borrowBtn = document.querySelector('.btn-borrow');
                const returnBtn = document.querySelector('.btn-return');
                const reserveBtn = document.querySelector('.btn-reserve');
                const cancelBtn = document.querySelector('.btn-cancel');
                const responseDiv = document.getElementById('action-response');
                
                // Borrow book
                if (borrowBtn) {
                    borrowBtn.addEventListener('click', function() {
                        performBookAction('borrow', <?php echo $book_id; ?>);
                    });
                }
                
                // Return book
                if (returnBtn) {
                    returnBtn.addEventListener('click', function() {
                        performBookAction('return', <?php echo $book_id; ?>);
                    });
                }
                
                // Reserve book
                if (reserveBtn) {
                    reserveBtn.addEventListener('click', function() {
                        performBookAction('reserve', <?php echo $book_id; ?>);
                    });
                }
                
                // Cancel reservation
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', function() {
                        performBookAction('cancel', <?php echo $book_id; ?>);
                    });
                }
                
                // Function to perform book actions
                function performBookAction(action, bookId) {
                    // Show loading state
                    responseDiv.innerHTML = '<div class="loading">Processing...</div>';
                    responseDiv.classList.add('visible');
                    
                    const apiAction = action === 'cancel' ? 'cancel_reservation' : action;
                    
                    fetch('api_user_books.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: apiAction,
                            book_id: bookId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Create a form message similar to profile updated
                            const successBanner = document.createElement('div');
                            successBanner.className = 'alert alert-success book-action-success';
                            successBanner.innerHTML = data.message;
                            successBanner.style.display = 'block';
                            
                            // Insert the success banner at the top of the main container, but before the book-detail div
                            const mainContainer = document.querySelector('main.container');
                            const bookDetail = document.querySelector('.book-detail');
                            mainContainer.insertBefore(successBanner, bookDetail);
                            
                            // Hide the response div
                            responseDiv.classList.remove('visible');
                            
                            // Scroll to show the banner
                            successBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            
                            // Reload the page after 2 seconds to show updated state
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            responseDiv.innerHTML = `<div class="error">${data.message}</div>`;
                            setTimeout(() => {
                                responseDiv.classList.remove('visible');
                            }, 5000);
                        }
                    })
                    .catch(error => {
                        responseDiv.innerHTML = `<div class="error">Error: ${error.message}</div>`;
                        setTimeout(() => {
                            responseDiv.classList.remove('visible');
                        }, 5000);
                    });
                }
                
                // Comment functionality
                const commentsContainer = document.getElementById('comments-container');
                const commentText = document.getElementById('comment-text');
                const postCommentBtn = document.getElementById('post-comment');
                
                loadComments(<?php echo $book_id; ?>);
                
                // Post a new comment
                if (postCommentBtn) {
                    postCommentBtn.addEventListener('click', function() {
                        const comment = commentText.value.trim();
                        if (comment) {
                            postComment(<?php echo $book_id; ?>, comment);
                        }
                    });
                }
                
                // Function to load comments
                function loadComments(bookId) {
                    commentsContainer.innerHTML = '<div class="loading-comments">Loading comments...</div>';
                    
                    fetch(`api_book_comments.php?action=get_comments&book_id=${bookId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                renderComments(data.data);
                            } else {
                                commentsContainer.innerHTML = `<div class="error">${data.message}</div>`;
                            }
                        })
                        .catch(error => {
                            commentsContainer.innerHTML = `<div class="error">Error loading comments: ${error.message}</div>`;
                        });
                }
                
                // Function to post a comment
                function postComment(bookId, comment) {
                    postCommentBtn.disabled = true;
                    postCommentBtn.textContent = 'Posting...';
                    
                    fetch('api_book_comments.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'add_comment',
                            book_id: bookId,
                            comment: comment
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Clear comment input
                            commentText.value = '';
                            
                            // Reload comments
                            loadComments(bookId);
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        alert(`Error: ${error.message}`);
                    })
                    .finally(() => {
                        postCommentBtn.disabled = false;
                        postCommentBtn.textContent = 'Post Comment';
                    });
                }
                
                // Function to render comments
                function renderComments(comments) {
                    if (!comments || comments.length === 0) {
                        commentsContainer.innerHTML = '<p class="no-comments">No comments yet. Be the first to comment!</p>';
                        return;
                    }
                    
                    let commentHtml = '';
                    
                    comments.forEach(comment => {
                        const date = new Date(comment.created_at);
                        const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        
                        commentHtml += `
                            <div class="comment">
                                <div class="comment-header">
                                    <div class="comment-user">
                                        <span class="username">${comment.username}</span>
                                    </div>
                                    <span class="comment-date">${formattedDate}</span>
                                </div>
                                <div class="comment-body">
                                    <p>${comment.comment.replace(/\n/g, '<br>')}</p>
                                </div>
                            </div>
                        `;
                    });
                    
                    commentsContainer.innerHTML = commentHtml;
                }
            });
        </script>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?> 