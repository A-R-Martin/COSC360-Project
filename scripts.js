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