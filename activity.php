<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Book activity timeline - Virtual Library">
    <meta name="keywords" content="library, book activity, borrowing, reservations, timeline">
    <title>Activity Timeline - Virtual Library</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="activity-page">
    <?php include 'nav.php'; ?>
    <main>
        <section class="catalog-header">
            <div class="container">
                <h1>Book Activity Timeline</h1>
                <p class="subtitle">See what's happening with our books, when they were borrowed or reserved, and when they'll become available</p>
                
                <div class="catalog-filters">
                    <div class="filter-group">
                        <select id="activity-filter" class="filter-select">
                            <option value="all">All Activity</option>
                            <option value="borrowed">Borrowed Books</option>
                            <option value="reserved">Reserved Books</option>
                        </select>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="activity-container container">
            <div id="activity-timeline" class="timeline">
                <div class="loading-indicator">Loading activity timeline...</div>
            </div>
        </section>
    </main>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const timelineContainer = document.getElementById('activity-timeline');
            const activityFilter = document.getElementById('activity-filter');
            
            loadActivityData();
            
            activityFilter.addEventListener('change', function() {
                loadActivityData();
            });
            
            function loadActivityData() {
                const activityType = activityFilter.value;
                timelineContainer.innerHTML = '<div class="loading-indicator">Loading activity timeline...</div>';
                
                fetch(`api_books.php?action=get_activity&type=${activityType}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success' && data.data && data.data.length > 0) {
                            renderActivityTimeline(data.data);
                        } else {
                            timelineContainer.innerHTML = '<p class="no-results">No activity found</p>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        timelineContainer.innerHTML = '<p class="error-message">Error loading activity data</p>';
                    });
            }
            
            function renderActivityTimeline(activities) {
                timelineContainer.innerHTML = '';
                
                // Group activities by date (year/month)
                const groupedActivities = {};
                
                activities.forEach(activity => {
                    let dateKey;
                    let activityDate;
                    
                    if (activity.user_book_status === 'borrowed' && activity.borrow_date) {
                        dateKey = activity.borrow_date.substring(0, 7); // YYYY-MM
                        activityDate = activity.borrow_date;
                    } else if (activity.user_book_status === 'reserved' && activity.reserve_date) {
                        dateKey = activity.reserve_date.substring(0, 7); // YYYY-MM
                        activityDate = activity.reserve_date;
                    } else {
                        return;
                    }
                    
                    if (!groupedActivities[dateKey]) {
                        groupedActivities[dateKey] = {
                            date: new Date(dateKey + '-01'),
                            items: []
                        };
                    }
                    
                    groupedActivities[dateKey].items.push(activity);
                });
                
                const sortedGroups = Object.values(groupedActivities).sort((a, b) => b.date - a.date);
                
                sortedGroups.forEach(group => {
                    const monthYear = group.date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
                    
                    const monthSection = document.createElement('div');
                    monthSection.className = 'timeline-month';
                    monthSection.innerHTML = `<h2>${monthYear}</h2>`;
                    
                    const itemsList = document.createElement('div');
                    itemsList.className = 'timeline-items';
                    
                    group.items.forEach(activity => {
                        const activityCard = createActivityCard(activity);
                        itemsList.appendChild(activityCard);
                    });
                    
                    monthSection.appendChild(itemsList);
                    timelineContainer.appendChild(monthSection);
                });
            }
            
            function createActivityCard(activity) {
                const card = document.createElement('div');
                card.className = 'activity-card';
                
                const activityType = activity.user_book_status;
                let activityDate = '';
                
                const borrowDate = activity.last_borrowed_date || activity.borrow_date || null;
                const returnDate = activity.expected_return_date || activity.return_date || null;
                const reserveDate = activity.last_reserved_date || activity.reserve_date || null;
                
                if (activityType === 'borrowed' && borrowDate) {
                    activityDate = new Date(borrowDate).toLocaleDateString();
                } else if (activityType === 'reserved' && reserveDate) {
                    activityDate = new Date(reserveDate).toLocaleDateString();
                }
                
                card.innerHTML = `
                    <div class="activity-card-header">
                        <span class="activity-badge ${activityType}">${activityType.toUpperCase()}</span>
                        <span class="activity-date">${activityDate}</span>
                    </div>
                    <div class="activity-card-content">
                        <div class="activity-book-info">
                            <div class="activity-book-cover">
                                <img src="${activity.cover}" alt="${activity.title}" loading="lazy" 
                                     onerror="this.src='sample-image.avif'; this.onerror=null;">
                            </div>
                            <div class="activity-book-details">
                                <h3 class="activity-book-title">${activity.title}</h3>
                                <p class="activity-book-author">by ${activity.author}</p>
                                ${returnDate ? `<p class="activity-return-info">Return Date: ${new Date(returnDate).toLocaleDateString()}</p>` : ''}
                                <p class="activity-user">User: ${activity.borrower_name}</p>
                            </div>
                        </div>
                    </div>
                    <a href="book_detail.php?id=${activity.book_id}" class="btn-details">View Book</a>
                `;
                
                return card;
            }
        });
    </script>
    <script src="scripts.js"></script>
    <?php include 'footer.php'; ?>
</body>
</html> 