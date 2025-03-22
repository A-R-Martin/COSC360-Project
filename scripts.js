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
            event.preventDefault();
            form.classList.remove('was-validated', 'form-valid');
            form.classList.add('was-validated');
            
            if (validateForm(form)) {
                form.classList.add('form-valid');
                handleFormSubmit(form);
            }
        });
    });

    // Profile page buttons
    const saveProfileBtn = document.getElementById('save-profile');
    if (saveProfileBtn) {
        saveProfileBtn.addEventListener('click', () => {
            console.log('Save profile button clicked');
            // TODO: Implement save profile functionality
        });
    }

    const removeProfileImageBtn = document.getElementById('remove-profile-image');
    if (removeProfileImageBtn) {
        removeProfileImageBtn.addEventListener('click', () => {
            console.log('Remove profile image button clicked');
            // TODO: Implement profile image removal
        });
    }

    const updateProfileBtn = document.getElementById('update-profile');
    if (updateProfileBtn) {
        updateProfileBtn.addEventListener('click', (e) => {
            e.preventDefault();
            console.log('Update profile button clicked');
            // TODO: Implement profile update functionality
        });
    }

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
        addBookForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Validate form
            const titleInput = document.getElementById('title');
            const authorInput = document.getElementById('author');
            
            // Check required fields
            if (!titleInput.value.trim()) {
                showFormMessage('Please enter a book title', 'error');
                titleInput.focus();
                return;
            }
            
            if (!authorInput.value.trim()) {
                showFormMessage('Please enter an author name', 'error');
                authorInput.focus();
                return;
            }
            
            // Create form data
            const formData = new FormData();
            const bookData = {
                action: 'add',
                title: titleInput.value.trim(),
                author: authorInput.value.trim(),
                description: document.getElementById('description').value.trim(),
                isbn: document.getElementById('isbn').value.trim(),
                year: document.getElementById('year').value,
                genre: document.getElementById('genre').value
            };
            
            // First, upload the book cover if provided
            let coverPath = null;
            if (bookCoverInput.files.length > 0) {
                const coverFormData = new FormData();
                coverFormData.append('book_cover', bookCoverInput.files[0]);
                
                try {
                    showFormMessage('Uploading cover image...', 'info');
                    const coverResponse = await fetch('upload_book_cover.php', {
                        method: 'POST',
                        body: coverFormData
                    });
                    
                    const coverResult = await coverResponse.json();
                    if (coverResult.status === 'success') {
                        coverPath = coverResult.data.file_path;
                    } else {
                        showFormMessage('Error uploading cover: ' + coverResult.message, 'error');
                        return;
                    }
                } catch (error) {
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
                const response = await fetch('api_books.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(bookData)
                });
                
                const result = await response.json();
                if (result.status === 'success') {
                    showFormMessage('Book added successfully!', 'success');
                    // Reset form
                    addBookForm.reset();
                    coverPreview.style.display = 'none';
                    
                    // Redirect to profile after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'profile.php';
                    }, 2000);
                } else {
                    showFormMessage('Error adding book: ' + result.message, 'error');
                }
            } catch (error) {
                showFormMessage('Error adding book: ' + error.message, 'error');
            }
        });
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
    // TODO: Implement profile pic / book picture validation
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
        this.cover = bookData.cover;
        this.description = bookData.description;
        this.isbn = bookData.isbn;
        // Ensure rating is a number
        this.rating = parseFloat(bookData.rating) || 0;
        this.status = bookData.status || 'available';
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
                <img src="${this.cover}" alt="${this.title}" loading="lazy">
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
    
    books.forEach(bookData => {
        const bookCard = new BookCard(bookData);
        container.appendChild(bookCard.createCard());
    });
    console.log(`Successfully rendered ${books.length} book cards`);
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
    const messageDiv = document.getElementById('form-response-message');
    if (messageDiv) {
        messageDiv.textContent = message;
        messageDiv.className = 'alert';
        messageDiv.classList.add(`alert-${type}`);
        messageDiv.style.display = 'block';
        
        // Scroll to message
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}