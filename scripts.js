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
    
    // Password change form handler
    const changePasswordBtn = document.getElementById('change-password');
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', (e) => {
            e.preventDefault();
            console.log('Change password button clicked');
            updatePassword();
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
 * Display form response message
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
                    // Black magic fuckery that makes a transparent gif for user profile image if no image is set, actually a really neat idea tbh
                    profileImage.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
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
            // Black magic fuckery again that makes a transparent gif for user profile image if no image is set, actually a really neat idea tbh
            document.getElementById('current-profile-image').src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
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

/**
 * Update user password
 */
function updatePassword() {
    // Get password form
    const passwordForm = document.getElementById('password-form');
    
    // Validate form
    passwordForm.classList.remove('was-validated', 'form-valid');
    passwordForm.classList.add('was-validated');
    
    if (!validateForm(passwordForm)) {
        console.log('Password form validation failed');
        return;
    }
    
    // Get password values
    const currentPassword = document.getElementById('current-password').value;
    const newPassword = document.getElementById('new-password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    
    // Check if passwords match
    if (newPassword !== confirmPassword) {
        showFormMessage('New passwords do not match', 'error');
        return;
    }
    
    // Create form data
    const formData = new FormData();
    formData.append('action', 'update_password');
    formData.append('current_password', currentPassword);
    formData.append('new_password', newPassword);
    formData.append('confirm_password', confirmPassword);
    
    // Send request to update password
    fetch('api_user_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showFormMessage('Password updated successfully', 'success');
            // Reset form
            passwordForm.reset();
            passwordForm.classList.remove('was-validated', 'form-valid');
        } else {
            showFormMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error updating password:', error);
        showFormMessage('Error updating password. Please try again later.', 'error');
    });
}

/**
 * Admin functionality
 */
document.addEventListener('DOMContentLoaded', () => {
    // Load admin data if on admin page
    if (document.querySelector('.admin-container')) {
        loadAdminData();
        setupAdminEventListeners();
    }
});

/**
 * Load admin dashboard data
 */
function loadAdminData() {
    // Load users data
    loadUsers();
    
    // Load books data
    loadBooks();
    
    // Load analytics data
    loadAnalytics();
}

/**
 * Set up admin page event listeners
 */
function setupAdminEventListeners() {
    // User search functionality
    const userSearchInput = document.getElementById('user-search-input');
    if (userSearchInput) {
        userSearchInput.addEventListener('input', debounce(() => {
            loadUsers(userSearchInput.value);
        }, 300));
    }
    
    // User status filter
    const userStatusSelect = document.getElementById('user-status-select');
    if (userStatusSelect) {
        userStatusSelect.addEventListener('change', () => {
            loadUsers(userSearchInput ? userSearchInput.value : '', userStatusSelect.value);
        });
    }
    
    // Book search functionality
    const bookSearchInput = document.getElementById('book-search-input');
    if (bookSearchInput) {
        bookSearchInput.addEventListener('input', debounce(() => {
            loadBooks(bookSearchInput.value);
        }, 300));
    }
    
    const sortableHeaders = document.querySelectorAll('#books-table th.sortable');
    if (sortableHeaders.length > 0) {
        sortableHeaders.forEach(header => {
            header.addEventListener('click', () => {
                sortableHeaders.forEach(h => {
                    h.classList.remove('sort-asc', 'sort-desc');
                });
                
                const sortField = header.getAttribute('data-sort');
                let sortOrder = 'asc';
                
                if (header.getAttribute('data-current-sort') === 'asc') {
                    sortOrder = 'desc';
                    header.classList.add('sort-desc');
                } else {
                    header.classList.add('sort-asc');
                }
                
                header.setAttribute('data-current-sort', sortOrder);
                
                loadBooks(bookSearchInput ? bookSearchInput.value : '', sortField, sortOrder);
            });
        });
    }
    
    // Default sort on first load (by title ascending)
    const titleHeader = document.querySelector('#books-table th[data-sort="title"]');
    if (titleHeader) {
        titleHeader.classList.add('sort-asc');
        titleHeader.setAttribute('data-current-sort', 'asc');
    }
    
    // Delegate event handler for user actions
    document.addEventListener('click', handleAdminUserActions);
    
    document.addEventListener('click', handleAdminBookActions);
}

/**
 * Handle admin user action clicks
 */
function handleAdminUserActions(e) {
    // Edit user
    if (e.target.matches('.btn-edit-user')) {
        const userId = e.target.dataset.userId;
        console.log(`Edit user clicked for user ID: ${userId}`);
        // TODO: Implement edit user modal
    }
    
    // Ban/activate user
    if (e.target.matches('.btn-suspend-user')) {
        const userId = e.target.dataset.userId;
        const currentStatus = e.target.dataset.status;
        const newStatus = currentStatus === 'active' ? 'banned' : 'active';
        const actionText = newStatus === 'active' ? 'activate' : 'ban';
        
        if (confirm(`Are you sure you want to ${actionText} this user?`)) {
            updateUserStatus(userId, newStatus);
        }
    }
}

/**
 * Load users list
 */
function loadUsers(search = '', status = '') {
    console.log(`Loading users with search: "${search}", status: "${status}"`);
    const usersTable = document.getElementById('users-table');
    if (!usersTable) return;
    
    const tableBody = usersTable.querySelector('tbody');
    tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Loading...</td></tr>';
    
    // Build query string
    let queryParams = new URLSearchParams();
    queryParams.append('action', 'get_users');
    if (search) queryParams.append('search', search);
    if (status) queryParams.append('status', status);
    
    fetch(`api_admin.php?${queryParams.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                renderUsersTable(data.data);
            } else {
                console.error('Error loading users:', data.message);
                tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading users:', error);
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading users. Please try again.</td></tr>';
        });
}

/**
 * Render users table with data
 */
function renderUsersTable(users) {
    const tableBody = document.querySelector('#users-table tbody');
    if (!tableBody) return;
    
    if (users.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center">No users found</td></tr>';
        return;
    }
    
    tableBody.innerHTML = '';
    
    users.forEach(user => {
        // Format date
        const joinDate = new Date(user.created_at).toLocaleDateString();
        
        // Make sure status has a default value if it's null or undefined
        const status = user.status || 'active';
        
        // Create status badge class
        const statusClass = status === 'active' ? 'status-active' : 'status-banned';
        
        // Create action button (don't allow banning own account)
        const isCurrentUser = user.username === document.querySelector('.user-name')?.textContent?.trim();
        const actionButton = isCurrentUser ? 
            '' : 
            `<button class="btn-suspend-user" data-user-id="${user.user_id}" data-status="${status}">
                ${status === 'active' ? 'Ban' : 'Activate'}
            </button>`;
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${user.username}${user.role === 'admin' ? ' <span class="admin-badge">Admin</span>' : ''}</td>
            <td>${user.email}</td>
            <td><span class="status-badge ${statusClass}">${status === 'active' ? 'Active' : 'Banned'}</span></td>
            <td>${joinDate}</td>
            <td class="actions">
                <button class="btn-edit-user" data-user-id="${user.user_id}">Details</button>
                ${actionButton}
            </td>
        `;
        
        tableBody.appendChild(row);
    });
}

/**
 * Update user status
 */
function updateUserStatus(userId, newStatus) {
    console.log(`Updating user ${userId} status to ${newStatus}`);
    
    const formData = new FormData();
    formData.append('action', 'update_user_status');
    formData.append('user_id', userId);
    formData.append('status', newStatus);
    
    fetch('api_admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Reload the users table
            loadUsers();
            showFormMessage(`User ${newStatus === 'active' ? 'activated' : 'banned'} successfully`, 'success');
        } else {
            showFormMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error updating user status:', error);
        showFormMessage('Error updating user status. Please try again later.', 'error');
    });
}

/**
 * Load analytics data for admin dashboard
 */
function loadAnalytics() {
    fetch('api_admin.php?action=get_analytics')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                console.log('Analytics data received:', data.data);
                
                // Update user analytics
                document.getElementById('total-users').textContent = data.data.users.total;
                document.getElementById('active-users').textContent = data.data.users.active;
                document.getElementById('banned-users').textContent = data.data.users.banned;
                
                // Update book analytics
                document.getElementById('total-books').textContent = data.data.books.total;
                document.getElementById('available-books').textContent = data.data.books.available;
                document.getElementById('borrowed-books').textContent = data.data.books.borrowed;
                document.getElementById('reserved-books').textContent = data.data.books.reserved;
                document.getElementById('overdue-books').textContent = data.data.books.overdue;
                
                // Add visual indicator if there are overdue books
                const overdueElement = document.getElementById('overdue-books');
                if (data.data.books.overdue > 0) {
                    overdueElement.classList.add('overdue');
                } else {
                    overdueElement.classList.remove('overdue');
                }
            } else {
                console.error('Error loading analytics:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading analytics:', error);
        });
}

/**
 * Debounce function for search inputs
 */
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(context, args);
        }, wait);
    };
}

/**
 * Load books list for admin panel
 */
function loadBooks(search = '', sortField = 'title', sortOrder = 'asc') {
    console.log(`Loading books with search: "${search}", sort: "${sortField}", order: "${sortOrder}"`);
    const booksTable = document.getElementById('books-table');
    if (!booksTable) return;
    
    const tableBody = booksTable.querySelector('tbody');
    tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Loading...</td></tr>';
    
    // Build query string
    let queryParams = new URLSearchParams();
    queryParams.append('action', 'get_books');
    if (search) queryParams.append('search', search);
    if (sortField) queryParams.append('sort', sortField);
    if (sortOrder) queryParams.append('sort_order', sortOrder);
    
    fetch(`api_admin.php?${queryParams.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                renderBooksTable(data.data);
            } else {
                console.error('Error loading books:', data.message);
                tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading books:', error);
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error loading books. Please try again.</td></tr>';
        });
}

/**
 * Render books table with data
 */
function renderBooksTable(books) {
    const tableBody = document.querySelector('#books-table tbody');
    if (!tableBody) return;
    
    if (books.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">No books found</td></tr>';
        return;
    }
    
    tableBody.innerHTML = '';
    
    books.forEach(book => {
        // Create status badge class based on book status
        let statusClass;
        let statusText = book.status;
        
        // Check if the book is overdue
        const isOverdue = book.status === 'borrowed' && book.is_overdue;
        
        if (isOverdue) {
            statusClass = 'status-overdue';
            statusText = 'Overdue';
        } else {
            switch (book.status) {
                case 'available':
                    statusClass = 'status-active';
                    break;
                case 'borrowed':
                    statusClass = 'status-borrowed';
                    break;
                case 'reserved':
                    statusClass = 'status-reserved';
                    break;
                default:
                    statusClass = 'status-banned';
            }
        }
        
        // Create action buttons
        const actionButtons = `
            <button class="btn-edit-book" data-book-id="${book.book_id}">Edit</button>
            <button class="btn-delete-book" data-book-id="${book.book_id}">Delete</button>
        `;
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${book.title}</td>
            <td>${book.author}</td>
            <td>${book.isbn || 'N/A'}</td>
            <td><span class="status-badge ${statusClass}">${statusText}</span></td>
            <td>${book.borrower_name || 'N/A'}</td>
            <td>${book.reserver_name || 'N/A'}</td>
            <td class="actions">
                ${actionButtons}
            </td>
        `;
        
        tableBody.appendChild(row);
    });
}

/**
 * Handle admin book action clicks
 */
function handleAdminBookActions(e) {
    // Edit book
    if (e.target.matches('.btn-edit-book')) {
        const bookId = e.target.dataset.bookId;
        console.log(`Edit book clicked for book ID: ${bookId}`);
        // TODO: Implement book edit functionality
    }
    
    // Delete book
    if (e.target.matches('.btn-delete-book')) {
        const bookId = e.target.dataset.bookId;
        console.log(`Delete book clicked for book ID: ${bookId}`);
        
        if (confirm(`Are you sure you want to delete this book? This action cannot be undone.`)) {
            deleteBook(bookId);
        }
    }
}

/**
 * Delete a book
 */
function deleteBook(bookId) {
    console.log(`Deleting book with ID: ${bookId}`);
    
    const formData = new FormData();
    formData.append('action', 'delete_book');
    formData.append('book_id', bookId);
    
    fetch('api_admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Reload the books table
            loadBooks();
            showFormMessage('Book deleted successfully', 'success');
        } else {
            showFormMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting book:', error);
        showFormMessage('Error deleting book. Please try again later.', 'error');
    });
}