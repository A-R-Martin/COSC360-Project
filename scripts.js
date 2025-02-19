// Form validation patterns
const VALIDATION_PATTERNS = {
    email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
    password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/,
    username: /^[a-zA-Z0-9_-]{3,30}$/
};

const VALIDATION_MESSAGES = {
    required: 'This field is required',
    email: 'Please enter a valid email address',
    password: 'Password must be at least 8 characters long and include uppercase, lowercase, number and special character',
    username: 'Username must be between 3-30 characters and can only contain letters, numbers, underscores and hyphens',
    passwordMatch: 'Passwords do not match',
};

document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('form[novalidate]');
    
    forms.forEach(form => {
        // Handle input validation on typing
        form.querySelectorAll('input, textarea, select').forEach(input => {
            input.addEventListener('input', () => {
                if (form.classList.contains('was-validated')) {
                    removeError(input);
                    form.classList.remove('form-valid');
                    validateField(input);
                }
            });
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
                if (!VALIDATION_PATTERNS.password.test(input.value)) {
                    input.setCustomValidity(VALIDATION_MESSAGES.password);
                }
                break;
                
            case input.id === 'confirm-password':
                if (input.value !== input.form.querySelector('#password').value) {
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
    return Array.from(form.querySelectorAll('input, textarea, select'))
        .every(input => validateField(input));
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
    const formActions = {
        'signin-form': () => console.log('Sign in form submitted'),
        'signup-form': () => console.log('Sign up form submitted'),
        'profile-form': () => console.log('Profile form submitted')
    };

    (formActions[form.id] || (() => console.log('Form submitted:', form.id)))();
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
        // TODO: Implement book details view once clicked, maybe a full screen card popup to borrow the book? not sure yet
        console.log(`Viewing details for book: ${this.isbn}`);
    }
}

// Helper function to render book cards in a container
function renderBookCards(books, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = ''; // Clear existing content
    
    books.forEach(bookData => {
        const bookCard = new BookCard(bookData);
        container.appendChild(bookCard.createCard());
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
    const featuredContainer = document.getElementById('featured-books-container');
    if (featuredContainer) {
        renderBookCards(featuredBooks, 'featured-books-container');
    }
});