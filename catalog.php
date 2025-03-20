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
<body>
    <?php include 'nav.php'; ?>
    <main>
        <section class="catalog-header">
            <div class="container">
                <h1>Browse Items</h1>
                <div class="catalog-filters">
                    <input type="search" placeholder="Search items..." class="search-input">
                    <select class="category-select">
                        <option value="">All Categories</option>
                        <option value="title">Book Title</option>
                        <option value="author">Author</option>
                        <option value="isbn">ISBN</option>
                    </select>
                </div>
            </div>
        </section>
        <section class="catalog-grid">
            <!-- TODO: Add book 'cards' here later once the js files setup -->
            <div id="catalog-books-container" class="books-container">
            </div>
        </section>
    </main>
    <footer>
    </footer>
    <script src="scripts.js"></script>
</body>
</html> 