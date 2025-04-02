<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: signin.php');
    exit;
}

require_once 'db_connect.php';

$page_title = "Analytics Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Analytics Dashboard - Virtual Library">
    <meta name="keywords" content="library, analytics, dashboard, tracking, statistics">
    <title><?php echo $page_title; ?> - Virtual Library</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="analytics_styles.css">
</head>
<body class="analytics-page">
    <?php include 'nav.php'; ?>
    
    <main>
        <section class="analytics-header">
            <div class="container">
                <h1>Analytics Dashboard</h1>
                <p class="subtitle">Track usage patterns, user activity, and system performance</p>
                
                <div class="analytics-time-filters">
                    <select id="time-period" class="filter-select">
                        <option value="day">Last 24 Hours</option>
                        <option value="week" selected>Last Week</option>
                        <option value="month">Last Month</option>
                        <option value="year">Last Year</option>
                    </select>
                    <button id="refresh-data" class="btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="refresh-icon"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10"></path><path d="M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                        Refresh Data
                    </button>
                </div>
            </div>
        </section>
        
        <section class="analytics-overview container">
            <h2>Overview</h2>
            <div class="analytics-metrics">
                <div class="metric-card">
                    <h3>Total Users</h3>
                    <div class="metric-value" id="total-users"></div>
                </div>
                <div class="metric-card">
                    <h3>Total Books</h3>
                    <div class="metric-value" id="total-books"></div>
                </div>
                <div class="metric-card">
                    <h3>Active Users (24h)</h3>
                    <div class="metric-value" id="active-users"></div>
                </div>
                <div class="metric-card">
                    <h3>Events (24h)</h3>
                    <div class="metric-value" id="events-24h"></div>
                </div>
                <div class="metric-card">
                    <h3>Borrowed Books</h3>
                    <div class="metric-value" id="borrowed-books"></div>
                </div>
                <div class="metric-card">
                    <h3>Reserved Books</h3>
                    <div class="metric-value" id="reserved-books"></div>
                </div>
            </div>
        </section>
        
        <section class="analytics-charts container">
            <h2>Charts & Visualizations</h2>
            <div class="analytics-row">
                <div class="chart-container">
                    <h2>Page Views</h2>
                    <div class="chart-wrapper">
                        <canvas id="page-views-chart" width="400" height="300"></canvas>
                        <div class="chart-loading" id="page-views-loading">Loading data...</div>
                    </div>
                </div>
                <div class="chart-container">
                    <h2>API Usage</h2>
                    <div class="chart-wrapper">
                        <canvas id="api-usage-chart" width="400" height="300"></canvas>
                        <div class="chart-loading" id="api-usage-loading">Loading data...</div>
                    </div>
                </div>
            </div>
            
            <div class="analytics-row">
                <div class="chart-container">
                    <h2>User Activity</h2>
                    <div class="chart-wrapper">
                        <canvas id="user-activity-chart" width="400" height="300"></canvas>
                        <div class="chart-loading" id="user-activity-loading">Loading data...</div>
                    </div>
                </div>
                <div class="chart-container">
                    <h2>Book Status</h2>
                    <div class="chart-wrapper">
                        <canvas id="book-status-chart" width="400" height="300"></canvas>
                        <div class="chart-loading" id="book-status-loading">Loading data...</div>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="analytics-tables container">
            <h2>Detailed Data</h2>
            <div class="analytics-row">
                <div class="table-container">
                    <h2>Top Pages</h2>
                    <div class="table-scroll">
                        <table class="analytics-table" id="top-pages-table">
                            <thead>
                                <tr>
                                    <th>Page</th>
                                    <th>Views</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="2" class="loading-cell">Loading data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="table-container">
                    <h2>Most Active Users</h2>
                    <div class="table-scroll">
                        <table class="analytics-table" id="active-users-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Books Viewed</th>
                                    <th>Searches</th>
                                    <th>Logins</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="4" class="loading-cell">Loading data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="analytics-export container">
            <h2>Export Data</h2>
            <div class="export-controls">
                <select id="export-type" class="filter-select">
                    <option value="page_views">Page Views</option>
                    <option value="api_usage">API Usage</option>
                    <option value="user_activity">User Activity</option>
                    <option value="all_analytics">All Analytics</option>
                </select>
                <button id="export-data" class="btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="download-icon"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Export CSV
                </button>
            </div>
            <div id="export-message" class="export-message"></div>
        </section>
    </main>
    
    <?php include 'footer.php'; ?>
    
    <script src="tracking.js"></script>
    <script src="analytics_chart_utils.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            
            document.getElementById('time-period').addEventListener('change', loadDashboardData);
            document.getElementById('refresh-data').addEventListener('click', loadDashboardData);
            document.getElementById('export-data').addEventListener('click', exportAnalyticsData);
            
            window.addEventListener('resize', function() {
                const canvasElements = document.querySelectorAll('canvas');
                canvasElements.forEach(canvas => {
                    if (canvas.getContext) {
                        resizeCanvas(canvas);
                    }
                });
            });
            
            window.addEventListener('load', function() {
                setTimeout(() => {
                    const canvasElements = document.querySelectorAll('canvas');
                    canvasElements.forEach(canvas => {
                        if (canvas.getContext) {
                            resizeCanvas(canvas);
                        }
                    });
                }, 100);
            });
            
            /**
             * Load all dashboard data
             */
            function loadDashboardData() {
                const period = document.getElementById('time-period').value;
                
                // Show loading states
                document.getElementById('page-views-loading').style.display = 'flex';
                document.getElementById('api-usage-loading').style.display = 'flex';
                document.getElementById('user-activity-loading').style.display = 'flex';
                document.getElementById('book-status-loading').style.display = 'flex';
                
                // Load overview metrics
                fetch(`api_tracking.php?action=get_analytics_dashboard`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            document.getElementById('total-users').textContent = data.data.total_users;
                            document.getElementById('total-books').textContent = data.data.total_books;
                            document.getElementById('active-users').textContent = data.data.active_users_24h || '0';
                            document.getElementById('events-24h').textContent = data.data.events_24h || '0';
                            document.getElementById('borrowed-books').textContent = data.data.borrowed_books;
                            document.getElementById('reserved-books').textContent = data.data.reserved_books;
                        }
                    })
                    .catch(error => console.error('Error loading analytics dashboard:', error));
                
                // Load page views for chart
                fetch(`api_tracking.php?action=get_page_views&period=${period}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            updatePageViewsChart(data.data);
                            updateTopPagesTable(data.data);
                            document.getElementById('page-views-loading').style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading page views:', error);
                        document.getElementById('page-views-loading').style.display = 'none';
                    });
                
                // Load API usage
                fetch(`api_tracking.php?action=get_api_usage&period=${period}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            updateAPIUsageChart(data.data);
                            document.getElementById('api-usage-loading').style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading API usage:', error);
                        document.getElementById('api-usage-loading').style.display = 'none';
                    });
                
                // Load user activity
                fetch(`api_tracking.php?action=get_user_activity&period=${period}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            updateUserActivityChart(data.data);
                            updateActiveUsersTable(data.data);
                            document.getElementById('user-activity-loading').style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading user activity:', error);
                        document.getElementById('user-activity-loading').style.display = 'none';
                    });
                
                fetch('api_admin.php?action=get_chart_data')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            updateBookStatusChart(data.data.bookStatusData);
                            document.getElementById('book-status-loading').style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading book status:', error);
                        document.getElementById('book-status-loading').style.display = 'none';
                    });
            }
            
            /**
             * Update page views chart
             */
            function updatePageViewsChart(pageViewsData) {
                if (!pageViewsData || pageViewsData.length === 0) {
                    const canvas = document.getElementById('page-views-chart');
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.font = '14px Arial, sans-serif';
                    ctx.fillStyle = '#7f8c8d';
                    ctx.textAlign = 'center';
                    ctx.fillText('No data available for the selected period', canvas.width / 2, canvas.height / 2);
                    return;
                }
                
                // Format data for the chart
                const chartData = {
                    labels: pageViewsData.slice(0, 8).map(item => item.page), // Top 8 pages
                    datasets: [{
                        data: pageViewsData.slice(0, 8).map(item => parseInt(item.view_count)),
                        backgroundColor: [
                            '#3498db', '#2ecc71', '#e67e22', '#f1c40f', 
                            '#9b59b6', '#34495e', '#e74c3c', '#1abc9c'
                        ]
                    }]
                };
                
                window.pageViewsData = chartData;
                
                drawBarChart('page-views-chart', chartData, 'View Count');
            }
            
            /**
             * Update API usage chart
             */
            function updateAPIUsageChart(apiUsageData) {
                if (!apiUsageData || apiUsageData.length === 0) {
                    const canvas = document.getElementById('api-usage-chart');
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.font = '14px Arial, sans-serif';
                    ctx.fillStyle = '#7f8c8d';
                    ctx.textAlign = 'center';
                    ctx.fillText('No data available for the selected period', canvas.width / 2, canvas.height / 2);
                    return;
                }
                
                const chartData = {
                    labels: apiUsageData.map(item => {
                        const endpoint = item.api_endpoint;
                        return endpoint.replace('api_', '').replace('.php', '');
                    }),
                    datasets: [{
                        data: apiUsageData.map(item => parseInt(item.call_count)),
                        backgroundColor: [
                            '#3498db', '#2ecc71', '#e67e22', '#9b59b6', 
                            '#f1c40f', '#1abc9c', '#e74c3c', '#95a5a6'
                        ]
                    }]
                };
                
                window.apiUsageData = chartData;
                
                // Draw pie chart
                drawPieChart('api-usage-chart', chartData, 'API Calls');
            }
            
            /**
             * Update user activity chart
             */
            function updateUserActivityChart(userData) {
                if (!userData || userData.length === 0) {
                    const canvas = document.getElementById('user-activity-chart');
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.font = '14px Arial, sans-serif';
                    ctx.fillStyle = '#7f8c8d';
                    ctx.textAlign = 'center';
                    ctx.fillText('No data available for the selected period', canvas.width / 2, canvas.height / 2);
                    return;
                }
                
                // Use top 5 users for the chart
                const topUsers = userData.slice(0, 5);
                
                // Create combined data for a bar chart
                const allActivities = [];
                
                // Add book views
                topUsers.forEach(user => {
                    allActivities.push({
                        label: `${user.username} (Books)`,
                        value: parseInt(user.books_viewed) || 0
                    });
                });
                
                // Add searches
                topUsers.forEach(user => {
                    allActivities.push({
                        label: `${user.username} (Searches)`,
                        value: parseInt(user.search_count) || 0
                    });
                });
                
                const chartData = {
                    labels: allActivities.map(item => item.label),
                    datasets: [{
                        data: allActivities.map(item => item.value),
                        backgroundColor: [
                            '#3498db', '#3498db', '#3498db', '#3498db', '#3498db',
                            '#2ecc71', '#2ecc71', '#2ecc71', '#2ecc71', '#2ecc71'
                        ]
                    }]
                };
                
                window.userActivityData = chartData;
                
                drawBarChart('user-activity-chart', chartData, 'Count');
            }
            
            /**
             * Update book status chart
             */
            function updateBookStatusChart(bookStatusData) {
                if (!bookStatusData || Object.keys(bookStatusData).length === 0) {
                    const canvas = document.getElementById('book-status-chart');
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.font = '14px Arial, sans-serif';
                    ctx.fillStyle = '#7f8c8d';
                    ctx.textAlign = 'center';
                    ctx.fillText('No data available', canvas.width / 2, canvas.height / 2);
                    return;
                }
                
                const chartData = {
                    labels: Object.keys(bookStatusData),
                    datasets: [{
                        data: Object.values(bookStatusData),
                        backgroundColor: [
                            '#2ecc71', // Available
                            '#e67e22', // Borrowed
                            '#3498db', // Reserved
                            '#95a5a6'  // Other
                        ]
                    }]
                };
                
                window.bookStatusData = chartData;
                
                drawPieChart('book-status-chart', chartData, 'Book Count');
            }
            
            /**
             * Update top pages table
             */
            function updateTopPagesTable(pageViewsData) {
                const tableBody = document.querySelector('#top-pages-table tbody');
                
                if (!pageViewsData || pageViewsData.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="2" class="empty-cell">No data available for the selected period</td></tr>';
                    return;
                }
                
                let html = '';
                pageViewsData.forEach(item => {
                    html += `
                        <tr>
                            <td>${item.page}</td>
                            <td>${item.view_count}</td>
                        </tr>
                    `;
                });
                
                tableBody.innerHTML = html;
            }
            
            /**
             * Update active users table
             */
            function updateActiveUsersTable(userData) {
                const tableBody = document.querySelector('#active-users-table tbody');
                
                if (!userData || userData.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="4" class="empty-cell">No data available for the selected period</td></tr>';
                    return;
                }
                
                let html = '';
                userData.forEach(user => {
                    html += `
                        <tr>
                            <td>${user.username}</td>
                            <td>${user.books_viewed || '0'}</td>
                            <td>${user.search_count || '0'}</td>
                            <td>${user.login_count || '0'}</td>
                        </tr>
                    `;
                });
                
                tableBody.innerHTML = html;
            }
            
            /**
             * Export analytics data as CSV
             */
            function exportAnalyticsData() {
                const exportType = document.getElementById('export-type').value;
                const period = document.getElementById('time-period').value;
                const messageElement = document.getElementById('export-message');
                
                messageElement.textContent = 'Preparing export...';
                messageElement.className = 'export-message info';
                
                let apiEndpoint = '';
                switch (exportType) {
                    case 'page_views':
                        apiEndpoint = `api_tracking.php?action=get_page_views&period=${period}`;
                        break;
                    case 'api_usage':
                        apiEndpoint = `api_tracking.php?action=get_api_usage&period=${period}`;
                        break;
                    case 'user_activity':
                        apiEndpoint = `api_tracking.php?action=get_user_activity&period=${period}`;
                        break;
                    case 'all_analytics':
                        apiEndpoint = `api_tracking.php?action=get_analytics_dashboard`;
                        break;
                }
                
                fetch(apiEndpoint)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success' && data.data) {
                            // Convert data to CSV
                            const csv = convertToCSV(data.data);
                            
                            // Download the CSV file
                            const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
                            downloadCSV(csv, `${exportType}_${timestamp}.csv`);
                            
                            messageElement.textContent = 'Export completed successfully!';
                            messageElement.className = 'export-message success';
                        } else {
                            messageElement.textContent = 'Error exporting data: ' + (data.message || 'Unknown error');
                            messageElement.className = 'export-message error';
                        }
                    })
                    .catch(error => {
                        console.error('Error exporting data:', error);
                        messageElement.textContent = 'Error exporting data: ' + error.message;
                        messageElement.className = 'export-message error';
                    });
            }
            
            /**
             * Convert data to CSV format
             */
            function convertToCSV(data) {
                if (!data || data.length === 0) {
                    if (typeof data === 'object' && !Array.isArray(data)) {
                        const headers = Object.keys(data).join(',');
                        const values = Object.values(data).join(',');
                        return headers + '\n' + values;
                    }
                    return '';
                }
                
                const headers = Object.keys(data[0]).join(',');
                
                const rows = data.map(item => {
                    return Object.values(item).map(value => {
                        // Handle values that contain commas by wrapping in quotes
                        if (typeof value === 'string' && value.includes(',')) {
                            return `"${value}"`;
                        }
                        return value;
                    }).join(',');
                });
                
                return headers + '\n' + rows.join('\n');
            }
            
            /**
             * Download CSV file
             */
            function downloadCSV(csv, filename) {
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        });
    </script>
</body>
</html> 