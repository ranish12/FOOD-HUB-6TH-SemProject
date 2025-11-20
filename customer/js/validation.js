// Email validation regex
const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
// Phone number validation regex (allows +, spaces, and numbers)
const phoneRegex = /^\+?[\d\s-]{10,}$/;
// Password validation regex (at least 8 characters, 1 uppercase, 1 lowercase, 1 number)
const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;

// Show error message
function showError(input, message) {
    const formControl = input.parentElement;
    const errorDiv = formControl.querySelector('.error-message') || document.createElement('div');
    errorDiv.className = 'error-message text-danger small mt-1';
    errorDiv.innerText = message;
    if (!formControl.querySelector('.error-message')) {
        formControl.appendChild(errorDiv);
    }
    input.classList.add('is-invalid');
}

// Remove error message
function removeError(input) {
    const formControl = input.parentElement;
    const errorDiv = formControl.querySelector('.error-message');
    if (errorDiv) {
        formControl.removeChild(errorDiv);
    }
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
}

// Validate email
function validateEmail(input) {
    if (!emailRegex.test(input.value.trim())) {
        showError(input, 'Please enter a valid email address');
        return false;
    }
    removeError(input);
    return true;
}

// Validate password
function validatePassword(input) {
    const value = input.value.trim();
    if (value.length < 8) {
        showError(input, 'Password must be at least 8 characters long');
        return false;
    }
    if (!passwordRegex.test(value)) {
        showError(input, 'Password must contain at least one uppercase letter, one lowercase letter, and one number');
        return false;
    }
    removeError(input);
    return true;
}

// Validate phone
function validatePhone(input) {
    const value = input.value.trim();
    if (!value) {
        showError(input, 'Phone number is required');
        return false;
    }
    if (!phoneRegex.test(value)) {
        showError(input, 'Please enter a valid phone number (at least 10 digits)');
        return false;
    }
    removeError(input);
    return true;
}

// Validate confirm password
function validateConfirmPassword(passwordInput, confirmInput) {
    if (passwordInput.value !== confirmInput.value) {
        showError(confirmInput, 'Passwords do not match');
        return false;
    }
    removeError(confirmInput);
    return true;
}

// Initialize login form validation
function initLoginValidation() {
    const form = document.getElementById('loginForm');
    if (!form) return;

    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    // Clear previous validation states when typing
    emailInput.addEventListener('input', () => {
        emailInput.classList.remove('is-invalid', 'is-valid');
        const errorDiv = emailInput.parentElement.querySelector('.error-message');
        if (errorDiv) errorDiv.remove();
    });
    passwordInput.addEventListener('input', () => {
        passwordInput.classList.remove('is-invalid', 'is-valid');
        const errorDiv = passwordInput.parentElement.querySelector('.error-message');
        if (errorDiv) errorDiv.remove();
    });

    // Form submission validation
    form.addEventListener('submit', (e) => {
        e.preventDefault(); // Always prevent default first
        
        let isValid = true;
        // Clear all previous validation states
        [emailInput, passwordInput].forEach(input => {
            input.classList.remove('is-invalid', 'is-valid');
            const errorDiv = input.parentElement.querySelector('.error-message');
            if (errorDiv) errorDiv.remove();
        });

        if (!validateEmail(emailInput)) isValid = false;
        if (!validatePassword(passwordInput)) isValid = false;

        if (isValid) {
            form.submit(); // Manually submit if validation passes
        }
    });
}

// Initialize registration form validation
function initRegisterValidation() {
    const form = document.getElementById('registerForm');
    if (!form) return;

    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const phoneInput = document.getElementById('phone');

    // Clear validation states when typing
    const inputs = [nameInput, emailInput, passwordInput, confirmPasswordInput, phoneInput];
    inputs.forEach(input => {
        if (input) {
            input.addEventListener('input', () => {
                input.classList.remove('is-invalid', 'is-valid');
                const errorDiv = input.parentElement.querySelector('.error-message');
                if (errorDiv) errorDiv.remove();
            });
        }
    });

    // Form submission validation
    form.addEventListener('submit', (e) => {
        e.preventDefault(); // Always prevent default first
        
        let isValid = true;
        // Clear all previous validation states
        inputs.forEach(input => {
            if (input) {
                input.classList.remove('is-invalid', 'is-valid');
                const errorDiv = input.parentElement.querySelector('.error-message');
                if (errorDiv) errorDiv.remove();
            }
        });

        if (!nameInput.value.trim()) {
            showError(nameInput, 'Name is required');
            isValid = false;
        } else {
            removeError(nameInput);
        }

        if (!validateEmail(emailInput)) isValid = false;
        if (!validatePassword(passwordInput)) isValid = false;
        if (!validateConfirmPassword(passwordInput, confirmPasswordInput)) isValid = false;
        if (!validatePhone(phoneInput)) isValid = false;

        if (isValid) {
            form.submit(); // Manually submit if validation passes
        }
    });
}

// Initialize validation on page load
document.addEventListener('DOMContentLoaded', () => {
    initLoginValidation();
    initRegisterValidation();
});
