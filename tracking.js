const VirtualLibraryTracking = {
    /**
     * Initialize tracking
     */
    init: function() {
        this.trackPageView();
        
        this.setupAPITracking();
        
        this.setupEventListeners();
        
        this.lastEventTime = {};
        this.minTimeBetweenEvents = 2000; // 2 seconds minimum between same events
        
        console.log('Tracking initialized');
    },
    
    /**
     * Log an event to the tracking API
     * @param {string} eventType - Type of event
     * @param {object} eventData - Event data
     */
    logEvent: function(eventType, eventData = {}) {
        if (eventType === 'api_call' && eventData.endpoint && 
            (eventData.endpoint.includes('api_tracking.php') || 
             eventData.endpoint.includes('log_event'))) {
            return;
        }
        
        const now = Date.now();
        if (this.lastEventTime[eventType] && 
            now - this.lastEventTime[eventType] < this.minTimeBetweenEvents) {
            return;
        }
        
        this.lastEventTime[eventType] = now;
        
        const data = {
            event_type: eventType,
            event_data: JSON.stringify(eventData)
        };
        
        fetch('api_tracking.php?action=log_event', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .catch(error => {
            console.error('Tracking error:', error);
        });
    },
    
    /**
     * Track page view
     */
    trackPageView: function() {
        if (window.location.pathname.includes('analytics.php')) {
            console.log('Page view tracking disabled on analytics page');
            return;
        }
        
        const sessionKey = 'vl_page_' + window.location.pathname;
        if (sessionStorage.getItem(sessionKey)) {
            return;
        }
        
        sessionStorage.setItem(sessionKey, 'viewed');
        
        const currentPath = window.location.pathname;
        let currentPage = currentPath.split('/').pop() || 'index.php';
        
        const urlParams = new URLSearchParams(window.location.search);
        
        if (currentPage === 'book_detail.php') {
            const bookId = urlParams.get('id');
            if (bookId) {
                currentPage = 'book_detail.php';
            }
        }
        
        const pageTitle = document.title;
        
        let pageType = 'general';
        
        // Check for book detail pages
        if (currentPage === 'book_detail.php') {
            pageType = 'book_detail';
        }
        
        // Check for user profile pages
        if (currentPath.includes('profile') || document.querySelector('.profile-header')) {
            pageType = 'profile';
        }
        
        // Check for search results
        if (currentPath.includes('search') || document.querySelector('.search-results')) {
            pageType = 'search';
        }
        
        const referrer = document.referrer;
        
        const eventData = {
            page: currentPage,
            page_type: pageType,
            page_title: pageTitle,
            referrer: referrer,
            full_path: currentPath,
            screen_size: `${window.innerWidth}x${window.innerHeight}`
        };
        
        if (currentPage === 'book_detail.php') {
            const bookId = urlParams.get('id');
            if (bookId) {
                eventData.book_id = bookId;
            }
        }
        
        this.logEvent('page_view', eventData);
    },
    
    /**
     * Track book view
     * @param {number} bookId - Book ID
     * @param {string} bookTitle - Book title
     */
    trackBookView: function(bookId, bookTitle) {
        this.logEvent('book_view', {
            book_id: bookId,
            title: bookTitle
        });
    },
    
    /**
     * Track search query
     * @param {string} query - Search query
     * @param {number} resultsCount - Number of results
     */
    trackSearch: function(query, resultsCount) {
        this.logEvent('book_search', {
            query: query,
            results_count: resultsCount
        });
    },
    
    /**
     * Track user login
     * @param {string} username - Username of the logged in user
     */
    trackLogin: function(username) {
        this.logEvent('login', {
            username: username,
            timestamp: new Date().toISOString()
        });
    },
    
    /**
     * Set up tracking for API calls
     */
    setupAPITracking: function() {
        const originalFetch = window.fetch;
        
        if (window.location.pathname.includes('analytics.php')) {
            console.log('API tracking disabled on analytics page');
            return;
        }
        
        // Override fetch to track API calls
        window.fetch = (...args) => {
            const url = args[0];
            let method = 'GET';
            
            if (args.length > 1 && args[1] && args[1].method) {
                method = args[1].method;
            }
            
            // Only track API calls to our endpoints
            if (typeof url === 'string' && 
                url.includes('api_') && 
                !url.includes('api_tracking.php') &&
                !url.includes('log_event')) {
                const apiEndpoint = url.split('?')[0];
                const startTime = performance.now();
                
                return originalFetch(...args).then(response => {
                    const endTime = performance.now();
                    const responseTime = Math.round(endTime - startTime);
                    
                    this.logEvent('api_call', {
                        endpoint: apiEndpoint,
                        method: method,
                        response_time: responseTime,
                        status: response.status
                    });
                    
                    return response;
                });
            }
            
            return originalFetch(...args);
        };
    },
    
    /**
     * Set up event listeners for user interactions
     */
    setupEventListeners: function() {
        // Track button clicks
        document.addEventListener('click', event => {
            const target = event.target;
            
            // Track button clicks
            if (target.tagName === 'BUTTON' || target.classList.contains('btn') || 
                (target.tagName === 'A' && target.classList.contains('btn'))) {
                const buttonText = target.textContent.trim();
                const buttonId = target.id || '';
                
                this.logEvent('button_click', {
                    button_text: buttonText,
                    button_id: buttonId,
                    page: window.location.pathname.split('/').pop()
                });
            }
            
            // Track book card clicks
            if (target.closest('.book-card')) {
                const bookCard = target.closest('.book-card');
                const bookId = bookCard.dataset.bookId;
                const bookTitle = bookCard.querySelector('.book-title')?.textContent.trim() || '';
                
                if (bookId) {
                    this.trackBookView(bookId, bookTitle);
                }
            }
        });
        
        // Track search form submissions
        const searchForms = document.querySelectorAll('form.search-form, .catalog-search form');
        searchForms.forEach(form => {
            form.addEventListener('submit', event => {
                const searchInput = form.querySelector('input[type="search"], input[name="search"]');
                if (searchInput) {
                    const query = searchInput.value.trim();
                    
                    this.trackSearch(query, 0);
                }
            });
        });
    }
};

// Initialize tracking when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    VirtualLibraryTracking.init();
}); 