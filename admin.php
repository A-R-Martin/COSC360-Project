<?php
session_start();
// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect to login page if not logged in or not admin
    header("Location: signin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Library administration dashboard for managing books, users, and library operations">
    <meta name="keywords" content="library admin, book management, user management, library dashboard, circulation management">
    <title>Admin Dashboard - Virtual Library</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include 'nav.php'; ?>
    <main>
        <section class="admin-container">
            <div class="admin-header">
                <h1>Admin Dashboard</h1>
                <div class="admin-actions">
                    <button class="btn-primary" id="export-data">Export Data</button>
                </div>
            </div>
            
            <div id="form-message-container" class="form-message-container"></div>
            
            <div class="admin-content">
                <div class="admin-section">
                    <h2>User Management</h2>
                    <div class="catalog-filters">
                        <input type="search" id="user-search-input" placeholder="Search users..." class="search-input">
                        <select id="user-status-select" class="category-select">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="banned">Banned</option>
                        </select>
                    </div>
                    <div class="data-table">
                        <table id="users-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Join Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- User data will be dynamically loaded -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="admin-section">
                    <h2>Book Management</h2>
                    <div class="catalog-filters">
                        <input type="search" id="book-search-input" placeholder="Search books..." class="search-input">
                        <select id="book-category-select" class="category-select">
                            <option value="">All Categories</option>
                            <option value="title">Book Title</option>
                            <option value="author">Author</option>
                            <option value="isbn">ISBN</option>
                        </select>
                    </div>
                    <div class="data-table">
                        <table id="books-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>ISBN</th>
                                    <th>Status</th>
                                    <th>Current Borrower</th>
                                    <th>Reserved By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Book data will be dynamically loaded -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="admin-section analytics-section">
                    <h2>Library Analytics</h2>
                    <div class="analytics-categories">
                        <div class="analytics-category">
                            <h3>User Statistics</h3>
                            <div class="analytics-grid">
                                <div class="analytics-card">
                                    <h4>Total Users</h4>
                                    <p class="analytics-value" id="total-users">0</p>
                                </div>
                                <div class="analytics-card">
                                    <h4>Active Users</h4>
                                    <p class="analytics-value" id="active-users">0</p>
                                </div>
                                <div class="analytics-card">
                                    <h4>Banned Users</h4>
                                    <p class="analytics-value" id="banned-users">0</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="analytics-category">
                            <h3>Book Statistics</h3>
                            <div class="analytics-grid">
                                <div class="analytics-card">
                                    <h4>Total Books</h4>
                                    <p class="analytics-value" id="total-books">0</p>
                                </div>
                                <div class="analytics-card">
                                    <h4>Available Books</h4>
                                    <p class="analytics-value" id="available-books">0</p>
                                </div>
                                <div class="analytics-card">
                                    <h4>Borrowed Books</h4>
                                    <p class="analytics-value" id="borrowed-books">0</p>
                                </div>
                                <div class="analytics-card">
                                    <h4>Reserved Books</h4>
                                    <p class="analytics-value" id="reserved-books">0</p>
                                </div>
                                <div class="analytics-card">
                                    <h4>Overdue Books</h4>
                                    <p class="analytics-value overdue" id="overdue-books">0</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <script src="scripts.js"></script>
</body>

</html> 