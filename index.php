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
                    <a href="signup.php" class="btn btn-primary">Get Started</a>
                    <a href="signin.php" class="btn btn-secondary">Sign In</a>
                </div>
            </div>
        </section>
        
        <section class="featured-books">
            <div class="container">
                <h2>Featured Books</h2>
                <div id="featured-books-container" class="books-container">
                    <!-- Book cards will be inserted here by renderBookCards() in scripts.js -->
                </div>
            </div>
        </section>
    </main>
    <script src="scripts.js"></script>
</body>
</html> 