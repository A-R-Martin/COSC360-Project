<?php
session_start();
// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect to login page if not logged in or not admin
    header("Location: signin.php");
    exit();
}

require_once 'db_connect.php';

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Redirect if no user ID provided
if (!$user_id) {
    header('Location: admin.php');
    exit;
}

// Get user details from the database
try {
    $sql = "SELECT * FROM users WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // User not found
        header('Location: admin.php');
        exit;
    }

    // Get user's borrowed books
    $borrowedSql = "SELECT b.book_id, b.title, b.author, ub.borrow_date, ub.return_date, 
                    DATEDIFF(ub.return_date, CURRENT_DATE()) as days_remaining,
                    CASE WHEN CURRENT_DATE() > ub.return_date THEN 1 ELSE 0 END as is_overdue
                    FROM user_books ub
                    JOIN books b ON ub.book_id = b.book_id
                    WHERE ub.user_id = :user_id AND ub.status = 'borrowed'
                    ORDER BY ub.borrow_date DESC";
    $borrowedStmt = $conn->prepare($borrowedSql);
    $borrowedStmt->execute(['user_id' => $user_id]);
    $borrowedBooks = $borrowedStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's reserved books
    $reservedSql = "SELECT b.book_id, b.title, b.author, ub.reserve_date
                    FROM user_books ub
                    JOIN books b ON ub.book_id = b.book_id
                    WHERE ub.user_id = :user_id AND ub.status = 'reserved'
                    ORDER BY ub.reserve_date DESC";
    $reservedStmt = $conn->prepare($reservedSql);
    $reservedStmt->execute(['user_id' => $user_id]);
    $reservedBooks = $reservedStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Database error
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="User profile management in the Virtual Library admin system">
    <meta name="keywords" content="admin, user profile, user management, library admin">
    <title>User Profile - Admin - Virtual Library</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include 'nav.php'; ?>
    <main>
        <section class="admin-container">
            <div class="admin-header">
                <h1>User Profile: <?php echo htmlspecialchars($user['username']); ?></h1>
                <div class="admin-actions">
                    <a href="admin.php" class="btn-secondary">Back to Admin Dashboard</a>
                </div>
            </div>
            
            <div id="form-message-container" class="form-message-container"></div>
            
            <div class="profile-content">
                <div class="profile-section">
                    <h2>User Information</h2>
                    <form id="admin-user-form" novalidate data-custom-submit="true">
                        <input type="hidden" id="user-id" name="user-id" value="<?php echo $user_id; ?>">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required
                                   minlength="3" maxlength="30" 
                                   pattern="^[a-zA-Z0-9_-]+$"
                                   value="<?php echo htmlspecialchars($user['username']); ?>"
                                   title="Username must be between 3-30 characters and can only contain letters, numbers, underscores and hyphens">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required
                                   pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                                   value="<?php echo htmlspecialchars($user['email']); ?>"
                                   title="Please enter a valid email address">
                        </div>
                        <div class="form-group">
                            <label for="status">Account Status</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="banned" <?php echo $user['status'] === 'banned' ? 'selected' : ''; ?>>Banned</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role">
                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio" rows="4" maxlength="500"
                                    title="Bio can be up to 500 characters long"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary" id="update-user">Update User</button>
                        </div>
                    </form>
                </div>

                <div class="profile-section">
                    <h2>Borrowed Books</h2>
                    <div class="data-table">
                        <table id="borrowed-books">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Author</th>
                                    <th>Borrowed Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($borrowedBooks)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No borrowed books</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($borrowedBooks as $book): ?>
                                <tr>
                                    <td><a href="book_detail.php?id=<?php echo $book['book_id']; ?>"><?php echo htmlspecialchars($book['title']); ?></a></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($book['borrow_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($book['return_date'])); ?></td>
                                    <td>
                                        <?php if ($book['is_overdue']): ?>
                                        <span class="status-badge status-overdue">Overdue</span>
                                        <?php else: ?>
                                        <span class="status-badge status-borrowed">
                                            <?php echo $book['days_remaining'] . ' ' . ($book['days_remaining'] == 1 ? 'day' : 'days'); ?> left
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="profile-section">
                    <h2>Reserved Books</h2>
                    <div class="data-table">
                        <table id="reserved-books">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Author</th>
                                    <th>Reserved Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reservedBooks)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">No reserved books</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($reservedBooks as $book): ?>
                                <tr>
                                    <td><a href="book_detail.php?id=<?php echo $book['book_id']; ?>"><?php echo htmlspecialchars($book['title']); ?></a></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($book['reserve_date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <script src="scripts.js"></script>
</body>

</html> 