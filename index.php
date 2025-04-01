<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Welcome to Virtual Library - Your digital gateway to a world of books and literature">
    <meta name="keywords" content="library, digital library, books, ebooks, reading, literature, virtual library">
    <title>Home - Virtual Library</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'nav.php'; ?>
    <main>
        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="error-banner">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <section class="hero">
            <div class="container">
                <h1>Welcome to Virtual Library</h1>
                <p>Discover, borrow, and share books with readers around the world!</p>
                <div class="hero-buttons">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="signup.php" class="btn btn-primary">Get Started</a>
                        <a href="signin.php" class="btn btn-secondary">Sign In</a>
                    <?php else: ?>
                        <a href="catalog.php" class="btn btn-primary">Browse Books</a>
                        <a href="profile.php" class="btn btn-secondary">My Profile</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        
        <section class="featured-books">
            <div class="container">
                <h2>Featured Books</h2>
                <div id="featured-books-container" class="books-container">
                    <div class="loading-indicator">Loading featured books...</div>
                </div>
            </div>
        </section>
        
        <!-- Hot Books Section -->
        <section class="hot-books">
            <div class="container">
                <h2>Trending Now</h2>
                
                <!-- Most Discussed Books -->
                <div class="hot-section">
                    <h3>Most Discussed Books</h3>
                    <div id="most-discussed-container" class="books-container">
                        <div class="loading-indicator">Loading most discussed books...</div>
                    </div>
                </div>
                
                <!-- Most Borrowed Books -->
                <div class="hot-section">
                    <h3>Most Popular Books</h3>
                    <div id="most-borrowed-container" class="books-container">
                        <div class="loading-indicator">Loading most borrowed books...</div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <script src="scripts.js"></script>
    <script>
        // Load featured books from the database
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('featured-books-container');
            
            // Fetch 3 random available books 
            fetch('api_books.php?featured=true&random=true&count=3')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.data && data.data.length > 0) {
                        container.innerHTML = ''; // Clear loading indicator
                        
                        data.data.forEach(book => {
                            const processedBook = {
                                book_id: book.book_id,
                                title: book.title,
                                author: book.author,
                                cover: book.cover || null,
                                description: book.description || 'No description available',
                                isbn: book.isbn || '',
                                rating: parseFloat(book.rating) || 0,
                                status: book.status || 'unknown'
                            };
                            
                            const card = createBookCard(processedBook);
                            container.appendChild(card);
                        });
                    } else {
                        container.innerHTML = '<div class="no-results">No featured books available</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML = '<div class="error-message">Error loading featured books. Please try again later.</div>';
                });
            
            loadHotBooks();
                
            // Function to create a book card
            function createBookCard(book) {
                const card = document.createElement('div');
                card.className = 'book-card';
                
                let coverImage = 'sample-image.avif';
                if (book.cover && book.cover !== 'null' && book.cover !== 'undefined') {
                    coverImage = book.cover;
                }
                
                // Create star rating
                let stars = '';
                // Ensure rating is a number
                const rating = parseFloat(book.rating) || 0;
                const roundedRating = Math.round(rating);
                
                // Add full stars
                for (let i = 0; i < roundedRating; i++) {
                    stars += '★';
                }
                
                // Add empty stars
                for (let i = 0; i < 5 - roundedRating; i++) {
                    stars += '☆';
                }
                
                // Create shortened description (first 100 characters)
                const shortDescription = book.description 
                    ? (book.description.length > 100 ? book.description.substring(0, 100) + '...' : book.description)
                    : 'No description available';
                
                card.innerHTML = `
                    <div class="book-card-cover">
                        <img src="${coverImage}" alt="${book.title}" loading="lazy" onerror="this.src='sample-image.avif'; this.onerror=null;">
                    </div>
                    <div class="book-card-content">
                        <div class="book-card-top">
                            <h3 class="book-title">${book.title}</h3>
                            <p class="book-author">By ${book.author}</p>
                            <div class="book-rating">${stars} <span class="rating-number">(${rating.toFixed(1)})</span></div>
                            <p class="book-description">${shortDescription}</p>
                        </div>
                        <div class="book-card-bottom">
                            <button class="btn-details" data-book-id="${book.book_id}">View Details</button>
                        </div>
                    </div>
                `;
                
                // Add event listener for the details button
                card.querySelector('.btn-details').addEventListener('click', function() {
                    window.location.href = 'book_detail.php?id=' + book.book_id;
                });
                
                return card;
            }
            
            function loadHotBooks() {
                const discussedContainer = document.getElementById('most-discussed-container');
                const borrowedContainer = document.getElementById('most-borrowed-container');
                
                // Fetch hot books
                fetch('api_books.php?action=get_hot_books&limit=3')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Handle most discussed books
                            if (data.data.most_commented && data.data.most_commented.length > 0) {
                                discussedContainer.innerHTML = ''; // Clear loading indicator
                                
                                // Create book cards for most discussed
                                data.data.most_commented.forEach(book => {
                                    const processedBook = {
                                        book_id: book.book_id,
                                        title: book.title,
                                        author: book.author,
                                        cover: book.cover || null,
                                        description: book.description || 'No description available',
                                        isbn: book.isbn || '',
                                        rating: parseFloat(book.rating) || 0,
                                        status: book.status || 'unknown',
                                        comment_count: parseInt(book.comment_count) || 0,
                                        last_borrowed_date: book.last_borrowed_date || null,
                                        expected_return_date: book.expected_return_date || null,
                                        last_reserved_date: book.last_reserved_date || null
                                    };
                                    
                                    const discussedCard = createBookCard(processedBook);
                                    
                                    if (processedBook.comment_count > 0) {
                                        const commentBadge = document.createElement('div');
                                        commentBadge.className = 'hot-badge';
                                        commentBadge.innerHTML = `<span>${processedBook.comment_count}</span> comments`;
                                        discussedCard.querySelector('.book-card-top').appendChild(commentBadge);
                                    }
                                    
                                    if (processedBook.status !== 'available') {
                                        addActivityInfo(discussedCard, processedBook);
                                    }
                                    
                                    discussedContainer.appendChild(discussedCard);
                                });
                            } else {
                                discussedContainer.innerHTML = '<div class="no-results">No discussed books available</div>';
                            }
                            
                            if (data.data.most_borrowed && data.data.most_borrowed.length > 0) {
                                borrowedContainer.innerHTML = ''; // Clear loading indicator
                                
                                // Create book cards for most borrowed
                                data.data.most_borrowed.forEach(book => {
                                    const processedBook = {
                                        book_id: book.book_id,
                                        title: book.title,
                                        author: book.author,
                                        cover: book.cover || null,
                                        description: book.description || 'No description available',
                                        isbn: book.isbn || '',
                                        rating: parseFloat(book.rating) || 0,
                                        status: book.status || 'unknown',
                                        borrow_count: parseInt(book.borrow_count) || 0,
                                        last_borrowed_date: book.last_borrowed_date || null,
                                        expected_return_date: book.expected_return_date || null,
                                        last_reserved_date: book.last_reserved_date || null
                                    };
                                    
                                    const borrowedCard = createBookCard(processedBook);
                                    
                                    if (processedBook.borrow_count > 0) {
                                        const borrowBadge = document.createElement('div');
                                        borrowBadge.className = 'hot-badge';
                                        borrowBadge.innerHTML = `<span>${processedBook.borrow_count}</span> borrows`;
                                        borrowedCard.querySelector('.book-card-top').appendChild(borrowBadge);
                                    }
                                    
                                    if (processedBook.status !== 'available') {
                                        addActivityInfo(borrowedCard, processedBook);
                                    }
                                    
                                    borrowedContainer.appendChild(borrowedCard);
                                });
                            } else {
                                borrowedContainer.innerHTML = '<div class="no-results">No borrowed books available</div>';
                            }
                        } else {
                            discussedContainer.innerHTML = '<div class="error-message">Error loading hot books</div>';
                            borrowedContainer.innerHTML = '<div class="error-message">Error loading hot books</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        discussedContainer.innerHTML = '<div class="error-message">Error loading hot books</div>';
                        borrowedContainer.innerHTML = '<div class="error-message">Error loading hot books</div>';
                    });
            }
            
            // Function to add activity date information to a book card
            function addActivityInfo(card, book) {
                const activityInfo = document.createElement('div');
                activityInfo.className = 'activity-info';
                
                // Format dates
                const formatDate = (dateString) => {
                    if (!dateString) return 'N/A';
                    const date = new Date(dateString);
                    return date.toLocaleDateString();
                };
                
                const borrowDate = book.last_borrowed_date || book.borrow_date || null;
                const returnDate = book.expected_return_date || book.return_date || null;
                const reserveDate = book.last_reserved_date || book.reserve_date || null;
                
                let activityHTML = '';
                
                if (book.status === 'borrowed') {
                    activityHTML = `
                        <div class="activity-date borrowed-info">
                            Borrowed: ${formatDate(borrowDate)}<br>
                            Expected Return: ${formatDate(returnDate)}
                        </div>
                    `;
                } else if (book.status === 'reserved') {
                    activityHTML = `
                        <div class="activity-date reserved-info">
                            Reserved: ${formatDate(reserveDate)}
                        </div>
                    `;
                }
                
                if (activityHTML) {
                    activityInfo.innerHTML = activityHTML;
                    card.querySelector('.book-card-top').appendChild(activityInfo);
                }
            }
        });
    </script>
</body>
</html> 