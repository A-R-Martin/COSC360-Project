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
    <meta name="description" content="Exclusive member access to our complete library collection with advanced features">
    <meta name="keywords" content="member library, exclusive books, digital collection, member benefits, advanced catalog">
    <title>Member Catalog - Virtual Library</title>
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
            <div id="member-catalog-books-container" class="books-container">
            </div>
        </section>
    </main>
    <script src="scripts.js"></script>
</body>
</html> 