<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connect.php';
header('Content-Type: application/json');

$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => null
];

$method = $_SERVER['REQUEST_METHOD'];

// Handle tracking events
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'log_event':
            // Log user activity or system event
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $event_type = $input['event_type'] ?? '';
                $event_data = $input['event_data'] ?? '';
                $user_id = $_SESSION['user_id'] ?? 0;
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                
                $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, event_type, event_data, ip_address, user_agent, created_at) 
                                        VALUES (:user_id, :event_type, :event_data, :ip_address, :user_agent, NOW())");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':event_type', $event_type);
                $stmt->bindParam(':event_data', $event_data);
                $stmt->bindParam(':ip_address', $ip_address);
                $stmt->bindParam(':user_agent', $user_agent);
                $stmt->execute();
                
                $response = [
                    'status' => 'success',
                    'message' => 'Event logged successfully'
                ];
            }
            break;
            
        case 'get_page_views':
            // Get page view statistics
            $period = $_GET['period'] ?? 'week';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            
            $timeConstraint = '';
            switch ($period) {
                case 'day':
                    $timeConstraint = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)';
                    break;
                case 'week':
                    $timeConstraint = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)';
                    break;
                case 'month':
                    $timeConstraint = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
                    break;
                case 'year':
                    $timeConstraint = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
                    break;
            }
            
            $stmt = $conn->prepare("SELECT SUBSTRING_INDEX(event_data, ':', 1) as page, 
                                    COUNT(*) as view_count 
                                    FROM activity_logs 
                                    WHERE event_type = 'page_view' $timeConstraint
                                    GROUP BY page 
                                    ORDER BY view_count DESC 
                                    LIMIT :limit");
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $pageViews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'status' => 'success',
                'message' => 'Page views retrieved successfully',
                'data' => $pageViews
            ];
            break;
            
        case 'get_user_activity':
            // Get user activity statistics
            $period = $_GET['period'] ?? 'week';
            
            $timeConstraint = '';
            switch ($period) {
                case 'day':
                    $timeConstraint = 'AND al.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)';
                    break;
                case 'week':
                    $timeConstraint = 'AND al.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)';
                    break;
                case 'month':
                    $timeConstraint = 'AND al.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
                    break;
                case 'year':
                    $timeConstraint = 'AND al.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
                    break;
            }
            
            $stmt = $conn->prepare("SELECT u.username, 
                                   COUNT(DISTINCT CASE WHEN al.event_type = 'book_view' THEN SUBSTRING_INDEX(al.event_data, ':', -1) END) as books_viewed,
                                   COUNT(DISTINCT CASE WHEN al.event_type = 'book_search' THEN al.id END) as search_count,
                                   COUNT(DISTINCT CASE WHEN al.event_type = 'login' THEN al.id END) as login_count
                                   FROM users u
                                   LEFT JOIN activity_logs al ON u.user_id = al.user_id
                                   WHERE u.user_id > 0 $timeConstraint
                                   GROUP BY u.user_id
                                   ORDER BY books_viewed DESC, search_count DESC
                                   LIMIT 20");
            $stmt->execute();
            $userActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'status' => 'success',
                'message' => 'User activity retrieved successfully',
                'data' => $userActivity
            ];
            break;
            
        case 'get_api_usage':
            // Get API usage statistics
            $period = $_GET['period'] ?? 'week';
            
            $timeConstraint = '';
            switch ($period) {
                case 'day':
                    $timeConstraint = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)';
                    break;
                case 'week':
                    $timeConstraint = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)';
                    break;
                case 'month':
                    $timeConstraint = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
                    break;
                case 'year':
                    $timeConstraint = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
                    break;
            }
            
            $stmt = $conn->prepare("SELECT SUBSTRING_INDEX(event_data, ':', 1) as api_endpoint,
                                    COUNT(*) as call_count
                                    FROM activity_logs
                                    WHERE event_type = 'api_call' $timeConstraint
                                    GROUP BY api_endpoint
                                    ORDER BY call_count DESC");
            $stmt->execute();
            $apiUsage = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'status' => 'success',
                'message' => 'API usage statistics retrieved successfully',
                'data' => $apiUsage
            ];
            break;
            
        case 'get_analytics_dashboard':
            // Get combined analytics for dashboard
            $stmt = $conn->prepare("SELECT
                                    (SELECT COUNT(*) FROM users) as total_users,
                                    (SELECT COUNT(*) FROM books) as total_books,
                                    (SELECT COUNT(*) FROM user_books WHERE status = 'borrowed') as borrowed_books,
                                    (SELECT COUNT(*) FROM user_books WHERE status = 'reserved') as reserved_books,
                                    (SELECT COUNT(*) FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)) as events_24h,
                                    (SELECT COUNT(DISTINCT user_id) FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)) as active_users_24h");
            $stmt->execute();
            $dashboardStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $response = [
                'status' => 'success',
                'message' => 'Dashboard analytics retrieved successfully',
                'data' => $dashboardStats
            ];
            break;
            
        default:
            $response = [
                'status' => 'error',
                'message' => 'Invalid action'
            ];
            break;
    }
} catch (PDOException $e) {
    $response = [
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ];
}

echo json_encode($response);
?> 