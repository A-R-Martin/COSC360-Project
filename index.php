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
                        const books = data.data.map(book => ({
                            book_id: book.book_id,
                            title: book.title,
                            author: book.author,
                            cover: book.cover || 'sample-image.avif',
                            description: book.description,
                            isbn: book.isbn,
                            rating: parseFloat(book.rating) || 0,
                            status: book.status
                        }));
                        
                        container.innerHTML = ''; // Clear loading indicator
                        
                        // Create book cards
                        books.forEach(book => {
                            const card = createBookCard(book);
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
                
            // Function to create a book card
            function createBookCard(book) {
                const card = document.createElement('div');
                card.className = 'book-card';
                
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
                        <img src="${book.cover}" alt="${book.title}" loading="lazy">
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
        });
    </script>
</body>
</html> 