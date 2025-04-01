<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Check if user is an admin
$isAdmin = $isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header>
    <nav class="main-nav">
        <div class="logo">
            <a href="index.php" class="nav-button <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Home</a>
        </div>
        <div class="nav-links">
            <a href="catalog.php" class="nav-button <?php echo ($current_page == 'catalog.php') ? 'active' : ''; ?>">Catalog</a>
            <a href="activity.php" class="nav-button <?php echo ($current_page == 'activity.php') ? 'active' : ''; ?>">Activity</a>
            <div class="auth-links">
                <?php if ($isLoggedIn): ?>
                    <!-- Show these links when logged in -->
                    <?php if ($isAdmin): ?>
                        <a href="admin.php" class="nav-button <?php echo ($current_page == 'admin.php') ? 'active' : ''; ?>">Admin</a>
                    <?php endif; ?>
                    <a href="profile.php" class="nav-button <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">Profile</a>
                    <a href="logout.php" class="nav-button logout-btn">Logout</a>
                <?php else: ?>
                    <!-- Show these links when logged out -->
                    <a href="signin.php" class="nav-button <?php echo ($current_page == 'signin.php') ? 'active' : ''; ?>">Sign In</a>
                    <a href="signup.php" class="nav-button <?php echo ($current_page == 'signup.php') ? 'active' : ''; ?>">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>

<style>
    .logout-btn {
        background-color: #f8d7da;
        color: #721c24;
    }
    .logout-btn:hover {
        background-color: #f5c6cb;
    }
</style> 