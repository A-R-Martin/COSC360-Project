// Form validation patterns
const VALIDATION_PATTERNS = {
    email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
    password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/,
    username: /^[a-zA-Z0-9_-]{3,30}$/
};

const VALIDATION_MESSAGES = {
    required: 'This field is required',
    email: 'Please enter a valid email address',
    password: 'Please fill all password requirements',
    username: 'Username must be between 3-30 characters and can only contain letters, numbers, underscores and hyphens',
    passwordMatch: 'Passwords do not match',
};

document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('form[novalidate]');
    
    forms.forEach(form => {
        // Handle input validation on typing
        form.querySelectorAll('input, textarea, select').forEach(input => {
            if (input.id === 'password' || input.id === 'new-password') {
                // Add real-time password requirement checking
                input.addEventListener('input', () => {
                    checkPasswordRequirements(input);
                    if (form.classList.contains('was-validated')) {
                        removeError(input);
                        form.classList.remove('form-valid');
                        validateField(input);
                    }
                });
            } else {
                input.addEventListener('input', () => {
                    if (form.classList.contains('was-validated')) {
                        removeError(input);
                        form.classList.remove('form-valid');
                        validateField(input);
                    }
                });
            }
        });

        // Handle form submission
        form.addEventListener('submit', (event) => {
            if (form.hasAttribute('data-custom-submit')) {
                console.log(`Skipping generic handler for form ${form.id} - has custom handler`);
                return;
            }
            
            event.preventDefault();
            console.log('Generic form submit handler triggered for form:', form.id);
            form.classList.remove('was-validated', 'form-valid');
            form.classList.add('was-validated');
            
            const isValid = validateForm(form);
            console.log(`Form ${form.id} validation result:`, isValid);
            
            if (isValid) {
                form.classList.add('form-valid');
                console.log(`Calling handleFormSubmit for form ${form.id}`);
                handleFormSubmit(form);
            } else {
                console.log(`Form ${form.id} validation failed`);
            }
        });
    });

    // Profile image upload
    const profileImageInput = document.getElementById('profile-image');
    const currentProfileImage = document.getElementById('current-profile-image');
    
    if (profileImageInput) {
        profileImageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                // Show image preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    currentProfileImage.src = e.target.result;
                };
                reader.readAsDataURL(file);
                
                // Upload the image
                uploadProfileImage(file);
            }
        });
    }

    const removeProfileImageBtn = document.getElementById('remove-profile-image');
    if (removeProfileImageBtn) {
        removeProfileImageBtn.addEventListener('click', () => {
            console.log('Remove profile image button clicked');
            removeProfileImage();
        });
    }

    const updateProfileBtn = document.getElementById('update-profile');
    if (updateProfileBtn) {
        updateProfileBtn.addEventListener('click', (e) => {
            e.preventDefault();
            console.log('Update profile button clicked');
            updateUserProfile();
        });
    }

    // Load user profile data when on profile page
    if (document.querySelector('.profile-container')) {
        loadUserProfile();
    }

    // Profile page buttons
    const addBookBtn = document.getElementById('add-book');
    if (addBookBtn) {
        addBookBtn.addEventListener('click', () => {
            console.log('Add book button clicked');
            window.location.href = 'add_book.php';
        });
    }

    // Add book form handlers
    const addBookForm = document.getElementById('add-book-form');
    const bookCoverInput = document.getElementById('book-cover');
    const coverPreview = document.getElementById('cover-preview');
    const cancelAddBookBtn = document.getElementById('cancel-add-book');
    
    // Book cover preview
    if (bookCoverInput) {
        bookCoverInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    coverPreview.src = e.target.result;
                    coverPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                coverPreview.src = '#';
                coverPreview.style.display = 'none';
            }
        });
    }
    
    // Cancel button
    if (cancelAddBookBtn) {
        cancelAddBookBtn.addEventListener('click', () => {
            window.location.href = 'profile.php';
        });
    }
    
    // Form submission
    if (addBookForm) {
        // Log that the form was found
        console.log('Add book form found with ID:', addBookForm.id);
        
        addBookForm.addEventListener('submit', async (e) => {
            console.log('*** ADD BOOK FORM SPECIFIC HANDLER TRIGGERED ***');
            try {
                e.preventDefault();
                console.log('Add book form submitted');
                
                // Validate form
                const titleInput = document.getElementById('title');
                const authorInput = document.getElementById('author');
                const isbnInput = document.getElementById('isbn');
                const ratingInput = document.getElementById('rating');
                
                console.log('Form elements:', {
                    title: titleInput?.value,
                    author: authorInput?.value
                });
                
                // Check required fields
                if (!titleInput || !titleInput.value.trim()) {
                    showFormMessage('Please enter a book title', 'error');
                    if (titleInput) titleInput.focus();
                    return;
                }
                
                if (!authorInput || !authorInput.value.trim()) {
                    showFormMessage('Please enter an author name', 'error');
                    if (authorInput) authorInput.focus();
                    return;
                }
                
                // Validate ISBN if provided (must be 10 or 13 digits)
                const isbnValue = isbnInput ? isbnInput.value.trim() : '';
                if (isbnValue && !/^(\d{10}|\d{13})$/.test(isbnValue)) {
                    showFormMessage('ISBN must be exactly 10 or 13 digits', 'error');
                    isbnInput.focus();
                    return;
                }
                
                // Validate rating if provided
                const ratingValue = ratingInput ? ratingInput.value.trim() : '';
                if (ratingValue) {
                    const rating = parseFloat(ratingValue);
                    if (isNaN(rating) || rating < 0 || rating > 5) {
                        showFormMessage('Rating must be a number between 0 and 5', 'error');
                        ratingInput.focus();
                        return;
                    }
                }
                
                // Create book data object
                const bookData = {
                    action: 'add',
                    title: titleInput.value.trim(),
                    author: authorInput.value.trim(),
                    description: document.getElementById('description')?.value?.trim() || '',
                    isbn: isbnValue,
                    year: document.getElementById('year')?.value || '',
                    genre: document.getElementById('genre')?.value || '',
                    rating: ratingValue ? parseFloat(ratingValue) : null
                };
                
                console.log('Book data to submit:', bookData);
                
                // First, upload the book cover if provided
                let coverPath = null;
                const bookCoverInput = document.getElementById('book-cover');
                const coverPreview = document.getElementById('cover-preview');
                
                if (bookCoverInput && bookCoverInput.files.length > 0) {
                    const coverFormData = new FormData();
                    coverFormData.append('book_cover', bookCoverInput.files[0]);
                    
                    try {
                        showFormMessage('Uploading cover image...', 'info');
                        console.log('Uploading cover image...');
                        const coverResponse = await fetch('upload_book_cover.php', {
                            method: 'POST',
                            body: coverFormData
                        });
                        
                        const coverResult = await coverResponse.json();
                        console.log('Cover upload response:', coverResult);
                        if (coverResult.status === 'success') {
                            coverPath = coverResult.data.file_path;
                        } else {
                            showFormMessage('Error uploading cover: ' + coverResult.message, 'error');
                            return;
                        }
                    } catch (error) {
                        console.error('Error uploading cover:', error);
                        showFormMessage('Error uploading cover: ' + error.message, 'error');
                        return;
                    }
                }
                
                // Add cover path to book data if available
                if (coverPath) {
                    bookData.cover_image = coverPath;
                }
                
                // Submit book data
                try {
                    showFormMessage('Adding book to library...', 'info');
                    console.log('Submitting book data to API:', bookData);
                    const response = await fetch('api_books.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(bookData)
                    });
                    
                    console.log('API response status:', response.status);
                    if (!response.ok) {
                        const errorText = await response.text();
                        throw new Error(`API error (${response.status}): ${errorText}`);
                    }
                    
                    const result = await response.json();
                    console.log('API response data:', result);
                    
                    if (result.status === 'success') {
                        showFormMessage('Book added successfully!', 'success');
                        // Reset form
                        addBookForm.reset();
                        if (coverPreview) coverPreview.style.display = 'none';
                        
                        // Redirect to profile after 2 seconds
                        setTimeout(() => {
                            window.location.href = 'profile.php';
                        }, 2000);
                    } else {
                        showFormMessage('Error adding book: ' + result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error submitting book data:', error);
                    showFormMessage('Error adding book: ' + error.message, 'error');
                }
            } catch (err) {
                console.error('Global form submission error:', err);
                showFormMessage('An unexpected error occurred: ' + err.message, 'error');
            }
        });
    } else {
        console.warn('Add book form not found on this page');
    }

    // Admin page buttons
    const exportDataBtn = document.getElementById('export-data');
    if (exportDataBtn) {
        exportDataBtn.addEventListener('click', () => {
            console.log('Export data button clicked');
            // TODO: Implement data export functionality
        });
    }

    // Book card action buttons (for catalog pages)
    document.addEventListener('click', (e) => {
        if (e.target.matches('.btn-details')) {
            const bookId = e.target.getAttribute('data-book-id');
            console.log('View details clicked for book ID:', bookId);
            // Go to the book detail page
            window.location.href = `book_detail.php?id=${bookId}`;
        } else if (e.target.matches('.btn-borrow')) {
            const bookId = e.target.getAttribute('data-book-id');
            console.log('Borrow clicked for book ID:', bookId);
            // TODO: Implement borrow functionality
        } else if (e.target.matches('.btn-reserve')) {
            const bookId = e.target.getAttribute('data-book-id');
            console.log('Reserve clicked for book ID:', bookId);
            // TODO: Implement reserve functionality
        }
    });

    // Admin table action buttons
    document.addEventListener('click', (e) => {
        if (e.target.matches('.btn-edit-user')) {
            const userId = e.target.dataset.userId;
            console.log(`Edit user clicked for user ID: ${userId}`);
            // TODO: Implement user edit functionality
        }
        
        if (e.target.matches('.btn-suspend-user')) {
            const userId = e.target.dataset.userId;
            console.log(`Suspend user clicked for user ID: ${userId}`);
            // TODO: Implement user suspension
        }
        
        if (e.target.matches('.btn-edit-book')) {
            const isbn = e.target.dataset.isbn;
            console.log(`Edit book clicked for ISBN: ${isbn}`);
            // TODO: Implement book edit functionality
        }
        
        if (e.target.matches('.btn-delete-book')) {
            const isbn = e.target.dataset.isbn;
            console.log(`Delete book clicked for ISBN: ${isbn}`);
            // TODO: Implement book deletion
        }
    });
});

function checkPasswordRequirements(input) {
    const requirements = {
        length: { regex: /.{8,}/, index: 0 },
        uppercase: { regex: /[A-Z]/, index: 1 },
        lowercase: { regex: /[a-z]/, index: 2 },
        number: { regex: /[0-9]/, index: 3 },
        special: { regex: /[@$!%*?&]/, index: 4 }
    };

    const password = input.value;
    const requirementsList = input.parentNode.querySelector('.password-requirements');
    
    if (!requirementsList) return;

    const items = requirementsList.getElementsByTagName('li');
    
    Object.keys(requirements).forEach(req => {
        const isValid = requirements[req].regex.test(password);
        const item = items[requirements[req].index];
        
        if (isValid) {
            item.classList.remove('invalid');
            item.classList.add('valid');
        } else {
            item.classList.remove('valid');
            item.classList.add('invalid');
        }
    });
}

function validateField(input) {
    const errorMessages = [];
    
    // Check required fields
    if (input.required && !input.value.trim()) {
        errorMessages.push(VALIDATION_MESSAGES.required);
    }
    
    // Email validation
    if (input.type === 'email' && input.value.trim() && !VALIDATION_PATTERNS.email.test(input.value)) {
        errorMessages.push(VALIDATION_MESSAGES.email);
    }
    
    // Username validation
    if (input.id === 'username' && input.value.trim() && !VALIDATION_PATTERNS.username.test(input.value)) {
        errorMessages.push(VALIDATION_MESSAGES.username);
    }
    
    // Password validation
    if ((input.id === 'password' || input.id === 'new-password') && input.value.trim() && !VALIDATION_PATTERNS.password.test(input.value)) {
        errorMessages.push(VALIDATION_MESSAGES.password);
    }
    
    // Confirm password validation
    if (input.id === 'confirm-password' || input.id === 'confirm-new-password') {
        const passwordField = input.id === 'confirm-password' 
            ? document.getElementById('password') 
            : document.getElementById('new-password');
        
        if (passwordField && input.value !== passwordField.value) {
            errorMessages.push(VALIDATION_MESSAGES.passwordMatch);
        }
    }
    
    // Add or remove validation classes based on result
    if (errorMessages.length > 0) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        
        // Create or update error messages
        errorMessages.forEach((message) => {
            displayError(input, message);
        });
        
        return false;
    } else if (input.value.trim()) {
        input.classList.add('is-valid');
        input.classList.remove('is-invalid');
        removeError(input);
        return true;
    }
    
    return !input.required;
}

function validateFile(input) {
    // Validate file type and size
    if (input.files.length === 0) {
        return true; // No file selected is valid (might be optional)
    }
    
    const file = input.files[0];
    const fileType = file.type;
    const fileSize = file.size;
    
    // Check file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(fileType)) {
        displayError(input, 'Invalid file type. Only JPG, PNG and GIF are allowed');
        return false;
    }
    
    // Check file size (max 2MB)
    const maxSize = 2 * 1024 * 1024; // 2MB in bytes
    if (fileSize > maxSize) {
        displayError(input, 'File size exceeds the limit (2MB)');
        return false;
    }
    
    return true;
}

function validateForm(form) {
    let isValid = true;
    const formFields = form.querySelectorAll('input, textarea, select');
    
    formFields.forEach((field) => {
        const fieldIsValid = validateField(field);
        isValid = isValid && fieldIsValid;
    });
    
    return isValid;
}

function removeError(input) {
    const errorMessage = input.parentNode.querySelector('.error-message');
    errorMessage?.remove();
}

function displayError(input, message) {
    // Remove any existing error first
    removeError(input);
    
    // Create and add new error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    input.parentNode.appendChild(errorDiv);
}

function showError(input) {
    removeError(input);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = input.validationMessage;
    input.parentNode.appendChild(errorDiv);
}

function handleFormSubmit(form) {
    console.log('Form is valid, submitting...');
    
    // Identify form by ID and handle accordingly
    const formId = form.id;
    
    if (formId === 'signup-form') {
        // Handle signup
        console.log('Signup form submitted');
        form.submit();
    } else if (formId === 'signin-form') {
        // Handle signin
        console.log('Signin form submitted');
        form.submit();
    } else if (formId === 'profile-form') {
        // Handle profile update
        console.log('Profile form submitted');
        form.submit();
    } else if (formId === 'add-book-form') {
        console.log('Add book form submission handled by dedicated event listener');
        return; // Return early to prevent duplicate submission
    } else {
        // Default behavior
        console.log('Unknown form submitted');
        form.submit();
    }
}

// Book Card Component - Generic version for reuse
class BookCard {
    constructor(bookData) {
        this.bookId = bookData.book_id;
        this.title = bookData.title;
        this.author = bookData.author;
        this.cover = bookData.cover || 'sample-image.avif';
        this.description = bookData.description;
        this.isbn = bookData.isbn;
        // Ensure rating is a number
        this.rating = parseFloat(bookData.rating) || 0;
        this.status = bookData.status || 'available';
        
        // Fix the cover path if needed
        this.fixCoverPath();
    }
    
    fixCoverPath() {
        // If cover is null or undefined, set a placeholder
        if (!this.cover) {
            this.cover = 'sample-image.avif';
            return;
        }
        
        // Check if the path already includes http:// or https:// or is an absolute path
        if (this.cover.startsWith('http://') || this.cover.startsWith('https://') || this.cover.startsWith('/')) {
            return; // Path is already correct
        }
        
        if (this.cover === 'null' || this.cover === 'undefined') {
            this.cover = 'sample-image.avif';
            return;
        }
        
        // Log for debugging
        console.log('Original cover path:', this.cover);
        
        if (this.cover.startsWith('./')) {
            this.cover = this.cover.substring(2);
        } else if (this.cover.startsWith('../')) {
            this.cover = this.cover.substring(3);
        }
        
        // Log for debugging
        console.log('Processed cover path:', this.cover);
    }

    createStarRating() {
        const roundedRating = Math.round(this.rating);
        let stars = '';
        
        // Add full stars
        for (let i = 0; i < roundedRating; i++) {
            stars += '★';
        }
        
        // Add empty stars
        for (let i = 0; i < 5 - roundedRating; i++) {
            stars += '☆';
        }
        
        return `<div class="book-rating">${stars} <span class="rating-number">(${this.rating.toFixed(1)})</span></div>`;
    }

    createCard() {
        const card = document.createElement('div');
        card.className = 'book-card';
        
        // Create shortened description (first 100 characters)
        const shortDescription = this.description 
            ? (this.description.length > 100 ? this.description.substring(0, 100) + '...' : this.description)
            : 'No description available';
        
        card.innerHTML = `
            <div class="book-card-cover">
                <img src="${this.cover}" alt="${this.title}" loading="lazy" onerror="this.src='sample-image.avif'; this.onerror=null;" class="book-cover-img">
            </div>
            <div class="book-card-content">
                <div class="book-card-top">
                    <h3 class="book-title">${this.title}</h3>
                    <p class="book-author">By ${this.author}</p>
                    ${this.createStarRating()}
                    <p class="book-description">${shortDescription}</p>
                    <p class="book-status ${this.status}">${this.status.toUpperCase()}</p>
                </div>
                <div class="book-card-bottom">
                    <button class="btn-details" data-book-id="${this.bookId}">View Details</button>
                </div>
            </div>
        `;

        return card;
    }
}

// Helper function to render book cards in a container
function renderBookCards(books, containerId) {
    console.log(`Rendering ${books.length} books in container: ${containerId}`);
    const container = document.getElementById(containerId);
    if (!container) {
        console.log(`Container ${containerId} not found`);
        return;
    }

    container.innerHTML = ''; // Clear existing content
    
    if (books.length === 0) {
        container.innerHTML = '<div class="no-results">No books found</div>';
        return;
    }
    
    // Debug book data
    console.log('Book data sample:', books[0]);
    
    books.forEach(bookData => {
        // Add image path debugging
        if (bookData.cover) {
            console.log(`Book "${bookData.title}" has cover path: ${bookData.cover}`);
        } else {
            console.log(`Book "${bookData.title}" has no cover path`);
        }
        
        const bookCard = new BookCard(bookData);
        container.appendChild(bookCard.createCard());
    });
    console.log(`Successfully rendered ${books.length} book cards`);
    
    // Add image loading error event listeners
    document.querySelectorAll('.book-cover-img').forEach(img => {
        img.addEventListener('error', function() {
            console.log(`Image failed to load: ${this.src}`);
            this.src = 'sample-image.avif';
        });
    });
}

// Sample featured books data with ratings
const featuredBooks = [
    {
        title: "The Great Gatsby",
        author: "F. Scott Fitzgerald",
        cover: "sample-image.avif",
        price: 9.99,
        isbn: "978-0743273565",
        description: "A story of the fabulously wealthy Jay Gatsby and his powerful love for the beautiful Daisy Buchanan.",
        rating: 4.5
    },
    {
        title: "To Kill a Mockingbird",
        author: "Harper Lee",
        cover: "sample-image.avif",
        price: 12.99,
        isbn: "978-0446310789",
        description: "The unforgettable novel of a childhood in a sleepy Southern town and the crisis of conscience that rocked it.",
        rating: 5.0
    },
    {
        title: "1984",
        author: "George Orwell",
        cover: "sample-image.avif",
        price: 10.99,
        isbn: "978-0451524935",
        description: "A dystopian social science fiction novel and cautionary tale about the dangers of totalitarianism.",
        rating: 4.7
    }
];

// Initialize featured books on home page
document.addEventListener('DOMContentLoaded', () => {
    // For featured books on home page
    const featuredContainer = document.getElementById('featured-books-container');
    if (featuredContainer) {
        renderBookCards(featuredBooks, 'featured-books-container');
    }

    // For now both guest and member pages render books the same, need to make a third details page that pulls fromdb
    // and has dynamic button for wheteher or not user is able to grab book or not
    // Guest catalog page render
    const catalogContainer = document.getElementById('catalog-books-container');
    if (catalogContainer) {
        renderBookCards(featuredBooks, 'catalog-books-container');
    }

    // Member catalog page render
    const memberCatalogContainer = document.getElementById('member-catalog-books-container');
    if (memberCatalogContainer) {
        renderBookCards(featuredBooks, 'member-catalog-books-container');
    }

    // Initialize search functionality
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const filteredBooks = featuredBooks.filter(book => 
                book.title.toLowerCase().includes(searchTerm) ||
                book.author.toLowerCase().includes(searchTerm) ||
                book.isbn.includes(searchTerm)
            );
            
            // Determine which container to update
            const container = document.querySelector('.books-container');
            if (container) {
                renderBookCards(filteredBooks, container.id);
            }
        });
    }
});

/**
 * Display form message
 */
function showFormMessage(message, type = 'info') {
    const container = document.getElementById('form-message-container');
    if (!container) return;
    
    // Clear any existing messages
    container.innerHTML = '';
    
    // Create new message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `alert alert-${type}`;
    messageDiv.textContent = message;
    messageDiv.style.display = 'block';
    
    // Add to container
    container.appendChild(messageDiv);
    
    // Scroll to message
    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Auto-hide after 5 seconds for success messages
    if (type === 'success') {
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    }
}

/**
 * Load user profile data
 */
function loadUserProfile() {
    fetch('api_user_profile.php?action=get_profile')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Populate form fields with user data
                const user = data.data;
                document.getElementById('username').value = user.username;
                document.getElementById('email').value = user.email;
                document.getElementById('bio').value = user.bio || '';
                
                // Set profile image
                const profileImage = document.getElementById('current-profile-image');
                if (user.profile_image) {
                    profileImage.src = user.profile_image;
                } else {
                    profileImage.src = 'placeholder-profile.jpg';
                }
            } else {
                showFormMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error loading user profile:', error);
            showFormMessage('Error loading user profile. Please try again later.', 'error');
        });
}

/**
 * Upload profile image
 */
function uploadProfileImage(file) {
    const formData = new FormData();
    formData.append('profile_image', file);
    
    fetch('upload_profile_image.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showFormMessage('Profile image updated successfully', 'success');
        } else {
            showFormMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error uploading profile image:', error);
        showFormMessage('Error uploading profile image. Please try again later.', 'error');
    });
}

/**
 * Remove profile image
 */
function removeProfileImage() {
    fetch('api_user_profile.php?action=delete_profile_image', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Reset profile image to default
            document.getElementById('current-profile-image').src = 'placeholder-profile.jpg';
            showFormMessage('Profile image removed successfully', 'success');
        } else {
            showFormMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error removing profile image:', error);
        showFormMessage('Error removing profile image. Please try again later.', 'error');
    });
}

/**
 * Update user profile data
 */
function updateUserProfile() {
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const bio = document.getElementById('bio').value;
    
    const formData = new FormData();
    formData.append('action', 'update_profile');
    formData.append('username', username);
    formData.append('email', email);
    formData.append('bio', bio);
    
    fetch('api_user_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showFormMessage('Profile updated successfully', 'success');
        } else {
            showFormMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error updating user profile:', error);
        showFormMessage('Error updating profile. Please try again later.', 'error');
    });
}