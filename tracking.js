
const VirtualLibraryTracking = {
    /**
     * Initialize tracking
     */
    init: function() {
        this.trackPageView();
        
        this.setupAPITracking();
        
        this.setupEventListeners();
        
        console.log('Tracking initialized');
    },
    
    /**
     * Log an event to the tracking API
     * @param {string} eventType - Type of event
     * @param {object} eventData - Event data
     */
    logEvent: function(eventType, eventData = {}) {
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
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        const referrer = document.referrer;
        
        this.logEvent('page_view', {
            page: currentPage,
            referrer: referrer,
            title: document.title,
            screen_size: `${window.innerWidth}x${window.innerHeight}`
        });
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
     * Set up tracking for API calls
     */
    setupAPITracking: function() {
        const originalFetch = window.fetch;
        
        // Override fetch to track API calls
        window.fetch = (...args) => {
            const url = args[0];
            
            // Only track API calls to our endpoints
            if (typeof url === 'string' && url.includes('api_')) {
                const apiEndpoint = url.split('?')[0];
                const startTime = performance.now();
                
                return originalFetch(...args).then(response => {
                    const endTime = performance.now();
                    const responseTime = Math.round(endTime - startTime);
                    
                    this.logEvent('api_call', {
                        endpoint: apiEndpoint,
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