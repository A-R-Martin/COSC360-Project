<?php
session_start();

// Redirect to member catalog if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: member-catalog.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Browse our extensive collection of books available to all visitors">
    <meta name="keywords" content="library catalog, book collection, public books, book search, browse books">
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
                            <option value="">All Categories</option>
                        </select>
                    </div>
                </div>
            </div>
        </section>
        <section class="catalog-grid">
            <div class="container">
                <div id="catalog-books-container" class="books-container">
                    <div class="loading-indicator">Loading books...</div>
                </div>
                <div class="pagination-controls">
                    <button id="prev-page" class="btn btn-secondary" disabled>Previous</button>
                    <span id="page-indicator">Page 1</span>
                    <button id="next-page" class="btn btn-secondary">Next</button>
                </div>
            </div>
        </section>
    </main>
    <footer>
    </footer>
    <script src="scripts.js"></script>
    <script>
        // Catalog specific JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize variables for pagination
            let currentPage = 1;
            let totalPages = 1;
            const booksPerPage = 6;
            let currentSearchTerm = '';
            let currentFilter = '';
            
            // Initial load of books
            loadBooks();
            
            // Search functionality
            const searchInput = document.getElementById('catalog-search');
            searchInput.addEventListener('input', function() {
                currentSearchTerm = this.value.trim();
                currentPage = 1; // Reset to first page on new search
                loadBooks();
            });
            
            // Filter functionality
            const filterSelect = document.getElementById('catalog-filter');
            filterSelect.addEventListener('change', function() {
                currentFilter = this.value;
                currentPage = 1; // Reset to first page on new filter
                loadBooks();
            });
            
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
            
            // Function to load books from the API
            function loadBooks() {
                const container = document.getElementById('catalog-books-container');
                container.innerHTML = '<div class="loading-indicator">Loading books...</div>';
                
                let url = `api_books.php?page=${currentPage}&limit=${booksPerPage}`;
                
                // Add search term if present
                if (currentSearchTerm) {
                    url += `&search=${encodeURIComponent(currentSearchTerm)}`;
                }
                
                // Add filter if present
                if (currentFilter) {
                    url += `&category=${encodeURIComponent(currentFilter)}`;
                }
                
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            container.innerHTML = '';
                            
                            if (data.data && data.data.length > 0) {
                                // Format the data for rendering
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
                                
                                // Render the books
                                books.forEach(book => {
                                    const card = createBookCard(book);
                                    container.appendChild(card);
                                });
                                
                                // Update pagination
                                totalPages = data.total_pages || 1;
                                updatePagination();
                            } else {
                                container.innerHTML = '<div class="no-results">No books found</div>';
                                document.querySelector('.pagination-controls').style.display = 'none';
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
                const roundedRating = Math.round(book.rating);
                
                // Add full stars
                for (let i = 0; i < roundedRating; i++) {
                    stars += '★';
                }
                
                // Add empty stars
                for (let i = 0; i < 5 - roundedRating; i++) {
                    stars += '☆';
                }
                
                card.innerHTML = `
                    <div class="book-card-cover">
                        <img src="${book.cover}" alt="${book.title}" loading="lazy">
                    </div>
                    <div class="book-card-content">
                        <div class="book-card-top">
                            <h3 class="book-title">${book.title}</h3>
                            <p class="book-author">By ${book.author}</p>
                            <div class="book-rating">${stars} <span class="rating-number">(${book.rating.toFixed(1)})</span></div>
                            <p class="book-status ${book.status}">${book.status.toUpperCase()}</p>
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
            
            // Function to update pagination controls
            function updatePagination() {
                pageIndicator.textContent = `Page ${currentPage} of ${totalPages}`;
                prevButton.disabled = currentPage <= 1;
                nextButton.disabled = currentPage >= totalPages;
                document.querySelector('.pagination-controls').style.display = 'flex';
            }
        });
    </script>
</body>
</html> 