// Form validation patterns
const VALIDATION_PATTERNS = {
    email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
    password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/,
    username: /^[a-zA-Z0-9_-]{3,30}$/
};

const VALIDATION_MESSAGES = {
    required: 'This field is required',
    email: 'Please enter a valid email address',
    password: 'Password must be at least 8 characters long and include uppercase, lowercase, number and special character (@$!%*?&)',
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
    input.setCustomValidity('');
    
    // Required field validation
    if (input.hasAttribute('required') && !input.value.trim()) {
        input.setCustomValidity(VALIDATION_MESSAGES.required);
        showError(input);
        return false;
    }
    
    // Type-specific validation
    if (input.value.trim()) {
        switch(true) {
            case input.type === 'email':
                if (!VALIDATION_PATTERNS.email.test(input.value)) {
                    input.setCustomValidity(VALIDATION_MESSAGES.email);
                }
                break;
                
            case input.id === 'password' && input.form.id === 'signup-form':
            case input.id === 'new-password':
                if (!VALIDATION_PATTERNS.password.test(input.value)) {
                    input.setCustomValidity(VALIDATION_MESSAGES.password);
                }
                break;
                
            case input.id === 'confirm-password':
                const passwordField = input.form.querySelector('#password, #new-password');
                if (passwordField && input.value !== passwordField.value) {
                    input.setCustomValidity(VALIDATION_MESSAGES.passwordMatch);
                }
                break;
                
            case input.id === 'username':
                if (!VALIDATION_PATTERNS.username.test(input.value)) {
                    input.setCustomValidity(VALIDATION_MESSAGES.username);
                }
                break;
                
            case input.type === 'file' && input.files.length > 0:
                validateFile(input);
                break;
        }
    }
    
    if (!input.validity.valid) {
        showError(input);
        return false;
    }
    
    return true;
}

function validateFile(input) {
    // TODO: Implement profile pic / book picture validation
}

function validateForm(form) {
    const isValid = Array.from(form.querySelectorAll('input, textarea, select'))
        .every(input => validateField(input));
        
    if (!isValid) {
        console.log(`Form validation failed for ${form.id}`);
    } else {
        console.log(`Form validation successful for ${form.id}`);
    }
    
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
    console.log(`Form submission started for: ${form.id}`);
    const formActions = {
        'signin-form': () => {
            console.log('Sign in form submitted successfully');
            // TODO: Implement sign in logic
        },
        'signup-form': () => {
            console.log('Sign up form submitted successfully');
            // TODO: Implement sign up logic
        },
        'profile-form': () => {
            console.log('Profile form submitted successfully');
            // TODO: Implement profile update logic
        }
    };

    (formActions[form.id] || (() => console.log(`Form ${form.id} submitted successfully`)))();
}

// Book Card Component
class BookCard {
    constructor(bookData) {
        this.title = bookData.title;
        this.author = bookData.author;
        this.cover = bookData.cover;
        this.price = bookData.price;
        this.isbn = bookData.isbn;
        this.description = bookData.description;
        this.rating = bookData.rating || 0;
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
        card.innerHTML = `
            <div class="book-card-cover">
                <img src="${this.cover}" alt="${this.title}" loading="lazy">
            </div>
            <div class="book-card-content">
                <h3 class="book-title">${this.title}</h3>
                <p class="book-author">By ${this.author}</p>
                ${this.createStarRating()}
                <p class="book-price">$${this.price.toFixed(2)}</p>
                <p class="book-description">${this.description}</p>
                <button class="btn-details" data-isbn="${this.isbn}">View Details</button>
            </div>
        `;

        // Add event listener for the details button
        card.querySelector('.btn-details').addEventListener('click', () => {
            this.handleViewDetails();
        });

        return card;
    }

    handleViewDetails() {
        console.log(`Viewing details for book: ${this.isbn}`);
        console.log(`Title: ${this.title}`);
        console.log(`Author: ${this.author}`);
        console.log(`Price: $${this.price}`);
        // TODO: Implement book details view
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