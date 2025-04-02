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
                                    <th>Unique Users</th>
                                    <th>Sessions</th>
                                    <th>Last Viewed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="5" class="loading-cell">Loading data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="table-container">
                    <h2>API Usage Details</h2>
                    <div class="table-scroll">
                        <table class="analytics-table" id="api-usage-table">
                            <thead>
                                <tr>
                                    <th>Endpoint</th>
                                    <th>Method</th>
                                    <th>Call Count</th>
                                    <th>Avg Response Time</th>
                                    <th>Unique Users</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="5" class="loading-cell">Loading data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="analytics-row">
                <div class="table-container">
                    <h2>Most Active Users</h2>
                    <div class="table-scroll">
                        <table class="analytics-table" id="active-users-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Books Viewed</th>
                                    <th>Books Borrowed</th>
                                    <th>Books Returned</th>
                                    <th>Comments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="5" class="loading-cell">Loading data...</td></tr>
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
            
            let globalChartData = {
                pageViews: null,
                apiUsage: null,
                userActivity: null,
                bookStatus: null
            };
            
            /**
             * Load all dashboard data
             */
            function loadDashboardData() {
                const period = document.getElementById('time-period').value;
                
                // Show loading states for all charts
                document.getElementById('page-views-loading').style.display = 'flex';
                document.getElementById('api-usage-loading').style.display = 'flex';
                document.getElementById('user-activity-loading').style.display = 'flex';
                document.getElementById('book-status-loading').style.display = 'flex';
                
                const canvases = ['page-views-chart', 'api-usage-chart', 'user-activity-chart', 'book-status-chart'];
                canvases.forEach(id => {
                    const canvas = document.getElementById(id);
                    if (canvas && canvas.getContext) {
                        const ctx = canvas.getContext('2d');
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                    }
                });
                
                loadOverviewData()
                    .then(() => loadPageViewsData(period))
                    .then(() => loadApiUsageData(period))
                    .then(() => loadUserActivityData(period))
                    .then(() => loadBookStatusData())
                    .then(() => {
                        setTimeout(() => {
                            if (globalChartData.pageViews) {
                                drawBarChart('page-views-chart', globalChartData.pageViews, 'View Count');
                            }
                            
                            if (globalChartData.apiUsage) {
                                drawPieChart('api-usage-chart', globalChartData.apiUsage, 'API Calls');
                            }
                            
                            if (globalChartData.userActivity && globalChartData.userActivity.originalData) {
                                updateUserActivityChart(globalChartData.userActivity.originalData);
                            }
                            
                            if (globalChartData.bookStatus) {
                                drawPieChart('book-status-chart', globalChartData.bookStatus, 'Book Status');
                            }
                        }, 300);
                    })
                    .catch(error => console.error('Error loading dashboard data:', error));
            }
            
            /**
             * Load overview metrics
             */
            function loadOverviewData() {
                return fetch(`api_tracking.php?action=get_analytics_dashboard`)
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
            }
            
            /**
             * Load page views data
             */
            function loadPageViewsData(period) {
                return fetch(`api_tracking.php?action=get_page_views&period=${period}`)
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
            }
            
            /**
             * Load API usage data
             */
            function loadApiUsageData(period) {
                return fetch(`api_tracking.php?action=get_api_usage&period=${period}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            updateApiUsageChart(data.data);
                            document.getElementById('api-usage-loading').style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading API usage:', error);
                        document.getElementById('api-usage-loading').style.display = 'none';
                    });
            }
            
            /**
             * Load user activity data
             */
            function loadUserActivityData(period) {
                return fetch(`api_tracking.php?action=get_user_activity&period=${period}`)
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
            }
            
            /**
             * Load book status data
             */
            function loadBookStatusData() {
                return fetch(`api_tracking.php?action=get_book_status`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            updateBookStatusChart(data.data);
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
                
                globalChartData.pageViews = chartData;
                
                drawBarChart('page-views-chart', chartData, 'View Count');
            }
            
            /**
             * Update API usage chart
             */
            function updateApiUsageChart(apiUsageData) {
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
                
                // Prepare data for the chart
                const labels = [];
                const callCounts = [];
                const backgroundColors = [
                    '#3498db', '#2ecc71', '#e67e22', '#9b59b6', 
                    '#f1c40f', '#1abc9c', '#e74c3c', '#95a5a6'
                ];
                
                apiUsageData.forEach((item, index) => {
                    const label = `${item.endpoint} (${item.method})`;
                    labels.push(label);
                    callCounts.push(parseInt(item.call_count) || 0);
                });
                
                const chartData = {
                    labels: labels,
                    datasets: [{
                        data: callCounts,
                        backgroundColor: backgroundColors
                    }]
                };
                
                globalChartData.apiUsage = chartData;
                
                // Draw pie chart
                drawPieChart('api-usage-chart', chartData, 'API Calls');
                
                // Update the table
                const tableBody = document.querySelector('#api-usage-table tbody');
                if (tableBody) {
                    let tableHTML = '';
                    
                    apiUsageData.forEach(item => {
                        tableHTML += `<tr>
                            <td>${item.endpoint}</td>
                            <td>${item.method}</td>
                            <td>${item.call_count}</td>
                            <td>${Math.round(item.avg_response_time || 0)} ms</td>
                            <td>${item.unique_users}</td>
                        </tr>`;
                    });
                    
                    tableBody.innerHTML = tableHTML || '<tr><td colspan="5" class="empty-cell">No data available</td></tr>';
                }
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
                
                // Prepare chart data
                const chartLabels = [];
                const booksViewed = [];
                const booksBorrowed = [];
                const booksReturned = [];
                const commentsAdded = [];
                const chartColors = {
                    viewed: '#3498db',
                    borrowed: '#2ecc71',
                    returned: '#e74c3c',
                    comments: '#f39c12'
                };
                
                // Limit to top 5 users
                const topUsers = userData.slice(0, 5);
                
                topUsers.forEach(user => {
                    chartLabels.push(user.username);
                    booksViewed.push(parseInt(user.books_viewed) || 0);
                    booksBorrowed.push(parseInt(user.books_borrowed) || 0);
                    booksReturned.push(parseInt(user.books_returned) || 0);
                    commentsAdded.push(parseInt(user.comments_added) || 0);
                });
                
                const chartData = {
                    labels: chartLabels,
                    datasets: [
                        {
                            label: 'Books Viewed',
                            data: booksViewed,
                            backgroundColor: chartColors.viewed
                        },
                        {
                            label: 'Books Borrowed',
                            data: booksBorrowed,
                            backgroundColor: chartColors.borrowed
                        },
                        {
                            label: 'Books Returned',
                            data: booksReturned,
                            backgroundColor: chartColors.returned
                        },
                        {
                            label: 'Comments',
                            data: commentsAdded,
                            backgroundColor: chartColors.comments
                        }
                    ],
                    originalData: userData
                };
                
                globalChartData.userActivity = chartData;
                
                window.updateUserActivityChart = updateUserActivityChart;
                
                // Clear previous chart
                const canvas = document.getElementById('user-activity-chart');
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                // Draw stacked bar chart
                const barWidth = Math.min(30, (canvas.width - 120) / chartLabels.length / 4);
                const chartHeight = canvas.height - 90;
                const chartBottom = canvas.height - 40;
                const chartLeft = 60;
                
                // Calculate maximum value for scaling
                const maxValue = Math.max(
                    ...booksViewed,
                    ...booksBorrowed,
                    ...booksReturned,
                    ...commentsAdded
                ) * 1.1;
                
                // Draw y-axis
                ctx.beginPath();
                ctx.moveTo(chartLeft, 30);
                ctx.lineTo(chartLeft, chartBottom);
                ctx.strokeStyle = '#ddd';
                ctx.stroke();
                
                // Draw x-axis
                ctx.beginPath();
                ctx.moveTo(chartLeft, chartBottom);
                ctx.lineTo(canvas.width - 20, chartBottom);
                ctx.strokeStyle = '#ddd';
                ctx.stroke();
                
                // Draw y-axis labels
                ctx.textAlign = 'right';
                ctx.fillStyle = '#7f8c8d';
                ctx.font = '10px Arial, sans-serif';
                
                const ySteps = 5;
                for (let i = 0; i <= ySteps; i++) {
                    const y = chartBottom - (i * chartHeight / ySteps);
                    const value = Math.round(maxValue * i / ySteps);
                    
                    ctx.beginPath();
                    ctx.moveTo(chartLeft - 5, y);
                    ctx.lineTo(chartLeft, y);
                    ctx.strokeStyle = '#ddd';
                    ctx.stroke();
                    
                    ctx.fillText(value, chartLeft - 8, y + 3);
                }
                
                // Draw bars and legend
                let currentBarX = chartLeft + 20;
                
                // Draw legend
                const legendY = 20;
                const legendSpacing = Math.min(75, (canvas.width - 100) / 4); 
                let legendX = chartLeft;
                
                const legendFontSize = canvas.width < 500 ? '8px' : '10px';
                ctx.font = `${legendFontSize} Arial, sans-serif`;
                
                // Books viewed legend
                ctx.fillStyle = chartColors.viewed;
                ctx.fillRect(legendX, legendY, 10, 10);
                ctx.fillStyle = '#333';
                ctx.textAlign = 'left';
                ctx.fillText('Books Viewed', legendX + 15, legendY + 8);
                
                // Books borrowed legend
                legendX += legendSpacing;
                ctx.fillStyle = chartColors.borrowed;
                ctx.fillRect(legendX, legendY, 10, 10);
                ctx.fillStyle = '#333';
                ctx.fillText('Books Borrowed', legendX + 15, legendY + 8);
                
                // Books returned legend
                legendX += legendSpacing;
                ctx.fillStyle = chartColors.returned;
                ctx.fillRect(legendX, legendY, 10, 10);
                ctx.fillStyle = '#333';
                ctx.fillText('Books Returned', legendX + 15, legendY + 8);
                
                // Comments legend
                legendX += legendSpacing;
                ctx.fillStyle = chartColors.comments;
                ctx.fillRect(legendX, legendY, 10, 10);
                ctx.fillStyle = '#333';
                ctx.fillText('Comments', legendX + 15, legendY + 8);
                
                // Draw bars for each user
                chartLabels.forEach((username, index) => {
                    const barGroupWidth = barWidth * 4 + 10;
                    
                    // Books viewed bar
                    const booksHeight = (booksViewed[index] / maxValue) * chartHeight;
                    ctx.fillStyle = chartColors.viewed;
                    ctx.fillRect(currentBarX, chartBottom - booksHeight, barWidth, booksHeight);
                    
                    // Books borrowed bar
                    const borrowedHeight = (booksBorrowed[index] / maxValue) * chartHeight;
                    ctx.fillStyle = chartColors.borrowed;
                    ctx.fillRect(currentBarX + barWidth, chartBottom - borrowedHeight, barWidth, borrowedHeight);
                    
                    // Books returned bar
                    const returnedHeight = (booksReturned[index] / maxValue) * chartHeight;
                    ctx.fillStyle = chartColors.returned;
                    ctx.fillRect(currentBarX + barWidth * 2, chartBottom - returnedHeight, barWidth, returnedHeight);
                    
                    // Comments bar
                    const commentsHeight = (commentsAdded[index] / maxValue) * chartHeight;
                    ctx.fillStyle = chartColors.comments;
                    ctx.fillRect(currentBarX + barWidth * 3, chartBottom - commentsHeight, barWidth, commentsHeight);
                    
                    // Draw username label
                    ctx.fillStyle = '#333';
                    ctx.textAlign = 'center';
                    ctx.fillText(username, currentBarX + barWidth * 1.5, chartBottom + 15);
                    
                    currentBarX += barGroupWidth + 10;
                });
            }
            
            /**
             * Update book status chart
             */
            function updateBookStatusChart(bookData) {
                if (!bookData || bookData.length === 0) {
                    const canvas = document.getElementById('book-status-chart');
                    const ctx = canvas.getContext('2d');
                    ctx.font = '14px Arial, sans-serif';
                    ctx.fillStyle = '#7f8c8d';
                    ctx.textAlign = 'center';
                    ctx.fillText('No data available', canvas.width / 2, canvas.height / 2);
                    return;
                }
                
                // Extract data for chart
                const labels = bookData.map(item => item.label);
                const values = bookData.map(item => item.value);
                const colors = bookData.map(item => item.color);
                
                // Create chart data
                const chartData = {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors
                    }]
                };
                
                globalChartData.bookStatus = chartData;
                
                drawPieChart('book-status-chart', chartData, 'Book Status');
            }
            
            /**
             * Update top pages table
             */
            function updateTopPagesTable(pageViewsData) {
                const tableBody = document.querySelector('#top-pages-table tbody');
                
                if (!pageViewsData || pageViewsData.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="5" class="empty-cell">No data available for the selected period</td></tr>';
                    return;
                }
                
                let html = '';
                pageViewsData.forEach(item => {
                    html += `
                        <tr>
                            <td>${item.page}</td>
                            <td>${item.view_count}</td>
                            <td>${item.unique_users || '0'}</td>
                            <td>${item.unique_sessions || '0'}</td>
                            <td>${item.last_viewed || 'N/A'}</td>
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
                    tableBody.innerHTML = '<tr><td colspan="5" class="empty-cell">No data available for the selected period</td></tr>';
                    return;
                }
                
                let html = '';
                userData.forEach(user => {
                    html += `
                        <tr>
                            <td>${user.username}</td>
                            <td>${user.books_viewed || '0'}</td>
                            <td>${user.books_borrowed || '0'}</td>
                            <td>${user.books_returned || '0'}</td>
                            <td>${user.comments_added || '0'}</td>
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
                
                if (exportType === 'all_analytics') {
                    downloadCSVForType('page_views', period);
                    downloadCSVForType('api_usage', period);
                    downloadCSVForType('user_activity', period);
                    
                    messageElement.textContent = 'All exports completed!';
                    messageElement.className = 'export-message success';
                } else {
                    downloadCSVForType(exportType, period);
                    
                    messageElement.textContent = 'Export completed!';
                    messageElement.className = 'export-message success';
                }
            }
            
            function downloadCSVForType(exportType, period) {
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
                    default:
                        console.error('Invalid export type');
                        return;
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
                        } else {
                            console.error('Error fetching data:', data.message || 'Unknown error');
                        }
                    })
                    .catch(error => {
                        console.error('Error during fetch:', error);
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