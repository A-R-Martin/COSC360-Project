<?php
// Include database connection if not already included
if (!function_exists('db_log')) {
    require_once 'db_connect.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (isset($page_title)): ?>
    <title><?php echo htmlspecialchars($page_title); ?> - Virtual Library</title>
    <?php else: ?>
    <title>Virtual Library</title>
    <?php endif; ?>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include_once 'nav.php'; ?> 