<?php
session_start();

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
// No redirections anymore - just show the appropriate view
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Browse our extensive collection of books available in our library">
    <meta name="keywords" content="library catalog, book collection, books, browse books">
    <title>Book Catalog - Virtual Library</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="catalog-page">
    <?php include 'nav.php'; ?>
    <main>
        <section class="catalog-header">
            <div class="container">
                <h1>Browse Items</h1>
                <div class="catalog-filters">
                    <div class="filter-group">
                        <input type="text" id="catalog-search" class="search-input" placeholder="Search items...">
                        <select id="catalog-filter" class="filter-select">
                            <option value="title">Sort by Title</option>
                            <option value="rating">Sort by Rating</option>
                        </select>
                    </div>

                    <?php if($is_logged_in): ?>
                    <!-- Member-specific filters -->
                    <div class="filter-buttons">
                        <button class="filter-btn active" data-filter="all">All Books</button>
                        <button class="filter-btn" data-filter="borrowed">Borrowed</button>
                        <button class="filter-btn" data-filter="reserved">Reserved</button>
                        <button class="filter-btn" data-filter="history">History</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        
        <section class="catalog-grid">
            <div id="catalog-books-container" class="books-container">
                <div class="loading-indicator">Loading books...</div>
            </div>
            
            <div class="pagination-controls" style="display: none;">
                <button id="prev-page" class="pagination-arrow" disabled>&#8592;</button>
                <span id="page-indicator">Page 1 of 1</span>
                <button id="next-page" class="pagination-arrow">&#8594;</button>
            </div>
        </section>
    </main>
    
    <script src="scripts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Track current state
            let currentPage = 1;
            let totalPages = 1;
            let booksPerPage = 9;
            let currentSearchTerm = '';
            let currentSort = 'title';
            let currentView = 'all';
            const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
            
            // Search functionality
            const searchInput = document.getElementById('catalog-search');
            searchInput.addEventListener('keyup', function(event) {
                if (event.key === 'Enter') {
                    currentSearchTerm = this.value.trim();
                    currentPage = 1; // Reset to first page on new search
                    loadBooks();
                }
            });
            
            // Sort functionality
            const sortSelect = document.getElementById('catalog-filter');
            sortSelect.addEventListener('change', function() {
                currentSort = this.value;
                currentPage = 1; // Reset to first page on new sort
                loadBooks();
            });
            
            // Member-specific filter buttons
            if (isLoggedIn) {
                const filterButtons = document.querySelectorAll('.filter-btn');
                filterButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        // Remove active class from all buttons
                        filterButtons.forEach(btn => btn.classList.remove('active'));
                        
                        // Add active class to clicked button
                        this.classList.add('active');
                        
                        // Set current view and load books
                        currentView = this.getAttribute('data-filter');
                        currentPage = 1; // Reset to first page on view change
                        loadBooks();
                    });
                });
            }
            
            // Pagination controls
            const prevButton = document.getElementById('prev-page');
            const nextButton = document.getElementById('next-page');
            const pageIndicator = document.getElementById('page-indicator');
            
            prevButton.addEventListener('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    loadBooks();
                }
            });
            
            nextButton.addEventListener('click', function() {
                if (currentPage < totalPages) {
                    currentPage++;
                    loadBooks();
                }
            });
            
            // Function to load books based on current state
            function loadBooks() {
                const container = document.getElementById('catalog-books-container');
                container.innerHTML = '<div class="loading-indicator">Loading books...</div>';
                
                let url = `api_books.php?page=${currentPage}&limit=${booksPerPage}`;
                
                // Add search term if present
                if (currentSearchTerm) {
                    url += `&search=${encodeURIComponent(currentSearchTerm)}`;
                }
                
                // Add sort option
                url += `&sort=${encodeURIComponent(currentSort)}`;
                
                // Add view filter for logged-in users
                if (isLoggedIn) {
                    if (currentView === 'borrowed') {
                        url += '&status=borrowed';
                    } else if (currentView === 'reserved') {
                        url += '&status=reserved';
                    } else if (currentView === 'history') {
                        url += '&status=history';
                    }
                }
                
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Update pagination
                            totalPages = data.total_pages || 1;
                            updatePagination();
                            
                            // Clear container
                            container.innerHTML = '';
                            
                            if (data.data && data.data.length > 0) {
                                // Create book cards
                                data.data.forEach(book => {
                                    const card = createBookCard(book);
                                    container.appendChild(card);
                                });
                            } else {
                                container.innerHTML = '<div class="no-results">No books found</div>';
                            }
                        } else {
                            container.innerHTML = '<div class="error-message">Error loading books: ' + data.message + '</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        container.innerHTML = '<div class="error-message">Error loading books. Please try again later.</div>';
                    });
            }
            
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
                
                // Add additional info for member views (borrowed, reserved, history)
                let additionalInfo = '';
                if (isLoggedIn) {
                    if (currentView === 'borrowed' && book.return_date) {
                        const returnDate = new Date(book.return_date);
                        additionalInfo = `<p class="return-date">Return by: ${returnDate.toLocaleDateString()}</p>`;
                    } else if (currentView === 'reserved' && book.reserve_date) {
                        const reserveDate = new Date(book.reserve_date);
                        additionalInfo = `<p class="reserve-date">Reserved on: ${reserveDate.toLocaleDateString()}</p>`;
                    } else if (currentView === 'history' && book.borrow_date && book.return_date) {
                        const borrowDate = new Date(book.borrow_date);
                        const returnDate = new Date(book.return_date);
                        additionalInfo = `
                            <p class="borrow-date">Borrowed: ${borrowDate.toLocaleDateString()}</p>
                            <p class="return-date">Returned: ${returnDate.toLocaleDateString()}</p>
                        `;
                    }
                }
                
                let displayStatus = book.status;
                if (currentView === 'reserved' || book.has_reservation) {
                    displayStatus = 'reserved';
                }

                card.innerHTML = `
                    <div class="book-card-cover">
                        <img src="${book.cover}" alt="${book.title}" loading="lazy">
                    </div>
                    <div class="book-card-content">
                        <div class="book-card-top">
                            <h3 class="book-title">${book.title}</h3>
                            <p class="book-author">by ${book.author}</p>
                            <div class="book-rating">${stars} <span class="rating-number">(${rating.toFixed(1)})</span></div>
                            <span class="book-status ${displayStatus}">${displayStatus.toUpperCase()}</span>
                            ${additionalInfo}
                        </div>
                        <div class="book-card-bottom">
                            <p class="book-description">${shortDescription}</p>
                            <a href="book_detail.php?id=${book.book_id}" class="btn-details">View Details</a>
                        </div>
                    </div>
                `;
                
                // Add event listener for the details button
                card.querySelector('.btn-details').addEventListener('click', function() {
                    window.location.href = 'book_detail.php?id=' + book.book_id;
                });
                
                return card;
            }
            
            // Function to update pagination controls
            function updatePagination() {
                pageIndicator.textContent = `Page ${currentPage} of ${totalPages}`;
                prevButton.disabled = currentPage <= 1;
                nextButton.disabled = currentPage >= totalPages;
                document.querySelector('.pagination-controls').style.display = totalPages > 1 ? 'flex' : 'none';
            }
            
            // Load books on page load
            loadBooks();
        });
    </script>
</body>
</html> 