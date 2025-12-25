// Authentication JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeAuth();
});

function initializeAuth() {
    // Initialize login form
    initializeLoginForm();
    
    // Initialize registration forms
    initializeRegistrationForms();
    
    // Initialize password toggles
    initializePasswordToggles();
    
    // Initialize role selection
    initializeRoleSelection();
    
    // Initialize social login
    initializeSocialLogin();
    
    // Initialize form validation
    initializeAuthValidation();
}

// Login Form
function initializeLoginForm() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleLogin(this);
        });
    }
}

function handleLogin(form) {
    const formData = new FormData(form);
    const loginData = {
        email: formData.get('email'),
        password: formData.get('password'),
        remember: formData.get('remember') === 'yes'
    };

    // Validate login data
    if (!validateLoginForm(loginData)) {
        return;
    }

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
    submitBtn.disabled = true;

    // Simulate API call
    setTimeout(() => {
        // Simulate successful login
        showRoleSelectionModal();
        
        // Restore button (in case modal is closed)
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
    }, 2000);
}

function validateLoginForm(data) {
    const errors = [];

    if (!data.email.trim()) {
        errors.push('Email is required');
    } else if (!isValidEmail(data.email)) {
        errors.push('Please enter a valid email address');
    }

    if (!data.password.trim()) {
        errors.push('Password is required');
    }

    if (errors.length > 0) {
        showNotification(errors.join('<br>'), 'error');
        return false;
    }

    return true;
}

function showRoleSelectionModal() {
    const modal = document.getElementById('roleModal');
    if (modal) {
        modal.style.display = 'flex';
        setTimeout(() => {
            modal.style.opacity = '1';
        }, 10);
    }
}

function selectRole(role) {
    // Hide role modal
    const modal = document.getElementById('roleModal');
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }

    // Show success message
    showNotification('Login successful! Redirecting to dashboard...', 'success');

    // Redirect based on role
    setTimeout(() => {
        switch (role) {
            case 'donor':
                window.location.href = '../dashboard/donor.html';
                break;
            case 'patient':
                window.location.href = '../dashboard/patient.html';
                break;
            case 'hospital':
                window.location.href = '../dashboard/hospital.html';
                break;
            case 'admin':
                window.location.href = '../dashboard/index.html';
                break;
            default:
                window.location.href = '../dashboard/index.html';
        }
    }, 1500);
}

// Registration Forms
function initializeRegistrationForms() {
    const step3Form = document.getElementById('step3Form');
    
    if (step3Form) {
        step3Form.addEventListener('submit', function(e) {
            e.preventDefault();
            handleRegistration();
        });
    }
}

function handleRegistration() {
    // Get all form data from all steps
    const registrationData = collectRegistrationData();
    
    if (!validateRegistrationData(registrationData)) {
        return;
    }

    // Show loading state
    const submitBtn = document.querySelector('#step3Form button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
    submitBtn.disabled = true;

    // Simulate API call
    setTimeout(() => {
        // Show success and redirect
        showRegistrationSuccess(registrationData.role);
        
        // Restore button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
    }, 3000);
}

function collectRegistrationData() {
    const step1Form = document.getElementById('step1Form');
    const step2Form = document.getElementById('step2Form');
    const step3Form = document.getElementById('step3Form');
    
    const step1Data = new FormData(step1Form);
    const step2Data = new FormData(step2Form);
    const step3Data = new FormData(step3Form);
    
    const selectedRole = document.querySelector('.role-option.selected');
    
    return {
        // Step 1 data
        firstName: step1Data.get('firstName'),
        lastName: step1Data.get('lastName'),
        email: step1Data.get('email'),
        password: step1Data.get('password'),
        confirmPassword: step1Data.get('confirmPassword'),
        
        // Step 2 data
        phone: step2Data.get('phone'),
        dateOfBirth: step2Data.get('dateOfBirth'),
        gender: step2Data.get('gender'),
        bloodType: step2Data.get('bloodType'),
        address: step2Data.get('address'),
        emergencyContact: step2Data.get('emergencyContact'),
        
        // Step 3 data
        role: selectedRole ? selectedRole.getAttribute('data-role') : null,
        terms: step3Data.get('terms') === 'on',
        newsletter: step3Data.get('newsletter') === 'on'
    };
}

function validateRegistrationData(data) {
    const errors = [];

    // Step 1 validation
    if (!data.firstName.trim()) errors.push('First name is required');
    if (!data.lastName.trim()) errors.push('Last name is required');
    if (!data.email.trim()) errors.push('Email is required');
    else if (!isValidEmail(data.email)) errors.push('Please enter a valid email address');
    if (!data.password.trim()) errors.push('Password is required');
    else if (data.password.length < 8) errors.push('Password must be at least 8 characters long');
    if (data.password !== data.confirmPassword) errors.push('Passwords do not match');

    // Step 2 validation
    if (!data.phone.trim()) errors.push('Phone number is required');
    if (!data.dateOfBirth) errors.push('Date of birth is required');
    if (!data.gender) errors.push('Gender is required');
    if (!data.address.trim()) errors.push('Address is required');
    if (!data.emergencyContact.trim()) errors.push('Emergency contact is required');

    // Step 3 validation
    if (!data.role) errors.push('Please select a role');
    if (!data.terms) errors.push('You must agree to the terms of service');

    if (errors.length > 0) {
        showNotification(errors.join('<br>'), 'error');
        return false;
    }

    return true;
}

function showRegistrationSuccess(role) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header success-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Welcome to BloodConnect!</h3>
            </div>
            <div class="modal-body">
                <div class="success-content">
                    <p>Your account has been created successfully. You're now part of our life-saving community!</p>
                    <div class="next-steps">
                        <h4>What's next?</h4>
                        <ul>
                            <li>Check your email for a verification link</li>
                            <li>Complete your profile setup</li>
                            <li>Explore your dashboard</li>
                            <li>Start ${role === 'donor' ? 'saving lives' : role === 'patient' ? 'managing your requests' : 'using our platform'}</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="redirectToDashboard('${role}')">
                    Go to Dashboard
                </button>
            </div>
        </div>
    `;

    showModal(modal);
}

function redirectToDashboard(role) {
    switch (role) {
        case 'donor':
            window.location.href = '../dashboard/donor.html';
            break;
        case 'patient':
            window.location.href = '../dashboard/patient.html';
            break;
        case 'hospital':
            window.location.href = '../dashboard/hospital.html';
            break;
        default:
            window.location.href = '../dashboard/index.html';
    }
}

// Multi-step Registration
function nextStep(stepNumber) {
    const currentStep = document.querySelector('.step-form.active');
    const nextStepForm = document.querySelector(`[data-step="${stepNumber}"]`);
    
    // Validate current step
    if (!validateCurrentStep(currentStep)) {
        return;
    }
    
    // Hide current step
    currentStep.classList.remove('active');
    
    // Show next step
    nextStepForm.classList.add('active');
    
    // Update step indicator
    updateStepIndicator(stepNumber);
}

function prevStep(stepNumber) {
    const currentStep = document.querySelector('.step-form.active');
    const prevStepForm = document.querySelector(`[data-step="${stepNumber}"]`);
    
    // Hide current step
    currentStep.classList.remove('active');
    
    // Show previous step
    prevStepForm.classList.add('active');
    
    // Update step indicator
    updateStepIndicator(stepNumber);
}

function updateStepIndicator(activeStep) {
    const steps = document.querySelectorAll('.step');
    
    steps.forEach((step, index) => {
        const stepNumber = index + 1;
        
        if (stepNumber < activeStep) {
            step.classList.add('completed');
            step.classList.remove('active');
        } else if (stepNumber === activeStep) {
            step.classList.add('active');
            step.classList.remove('completed');
        } else {
            step.classList.remove('active', 'completed');
        }
    });
}

function validateCurrentStep(stepForm) {
    const stepNumber = parseInt(stepForm.getAttribute('data-step'));
    
    switch (stepNumber) {
        case 1:
            return validateStep1();
        case 2:
            return validateStep2();
        case 3:
            return validateStep3();
        default:
            return true;
    }
}

function validateStep1() {
    const form = document.getElementById('step1Form');
    const formData = new FormData(form);
    const errors = [];

    const firstName = formData.get('firstName').trim();
    const lastName = formData.get('lastName').trim();
    const email = formData.get('email').trim();
    const password = formData.get('password');
    const confirmPassword = formData.get('confirmPassword');

    if (!firstName) errors.push('First name is required');
    if (!lastName) errors.push('Last name is required');
    if (!email) errors.push('Email is required');
    else if (!isValidEmail(email)) errors.push('Please enter a valid email address');
    if (!password) errors.push('Password is required');
    else if (password.length < 8) errors.push('Password must be at least 8 characters long');
    if (password !== confirmPassword) errors.push('Passwords do not match');

    if (errors.length > 0) {
        showNotification(errors.join('<br>'), 'error');
        return false;
    }

    return true;
}

function validateStep2() {
    const form = document.getElementById('step2Form');
    const formData = new FormData(form);
    const errors = [];

    const phone = formData.get('phone').trim();
    const dateOfBirth = formData.get('dateOfBirth');
    const gender = formData.get('gender');
    const address = formData.get('address').trim();
    const emergencyContact = formData.get('emergencyContact').trim();

    if (!phone) errors.push('Phone number is required');
    if (!dateOfBirth) errors.push('Date of birth is required');
    if (!gender) errors.push('Gender is required');
    if (!address) errors.push('Address is required');
    if (!emergencyContact) errors.push('Emergency contact is required');

    // Age validation
    if (dateOfBirth) {
        const age = calculateAge(new Date(dateOfBirth));
        if (age < 16) {
            errors.push('You must be at least 16 years old to register');
        }
    }

    if (errors.length > 0) {
        showNotification(errors.join('<br>'), 'error');
        return false;
    }

    return true;
}

function validateStep3() {
    const selectedRole = document.querySelector('.role-option.selected');
    const termsCheckbox = document.querySelector('input[name="terms"]');
    const errors = [];

    if (!selectedRole) {
        errors.push('Please select a role');
    }

    if (!termsCheckbox.checked) {
        errors.push('You must agree to the terms of service');
    }

    if (errors.length > 0) {
        showNotification(errors.join('<br>'), 'error');
        return false;
    }

    return true;
}

function calculateAge(birthDate) {
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    
    return age;
}

// Password Toggle
function initializePasswordToggles() {
    const toggleButtons = document.querySelectorAll('.password-toggle');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Role Selection
function initializeRoleSelection() {
    const roleOptions = document.querySelectorAll('.role-option');
    
    roleOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            roleOptions.forEach(opt => opt.classList.remove('selected'));
            
            // Add selected class to clicked option
            this.classList.add('selected');
        });
    });
}

// Social Login
function initializeSocialLogin() {
    const googleBtn = document.querySelector('.btn-google');
    const facebookBtn = document.querySelector('.btn-facebook');
    
    if (googleBtn) {
        googleBtn.addEventListener('click', function() {
            handleSocialLogin('google');
        });
    }
    
    if (facebookBtn) {
        facebookBtn.addEventListener('click', function() {
            handleSocialLogin('facebook');
        });
    }
}

function handleSocialLogin(provider) {
    showNotification(`${provider.charAt(0).toUpperCase() + provider.slice(1)} login integration would be implemented here`, 'info');
    
    // In a real application, this would integrate with OAuth providers
    // For demo purposes, we'll simulate a successful login
    setTimeout(() => {
        showRoleSelectionModal();
    }, 1000);
}

// Form Validation
function initializeAuthValidation() {
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            updatePasswordStrength(this.value);
        });
    }
    
    // Real-time validation
    const inputs = document.querySelectorAll('input[required]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateAuthField(this);
        });
        
        input.addEventListener('input', function() {
            clearAuthFieldError(this);
        });
    });
}

function updatePasswordStrength(password) {
    const strengthBar = document.querySelector('.strength-fill');
    const strengthText = document.querySelector('.strength-text');
    
    if (!strengthBar || !strengthText) return;
    
    let strength = 0;
    let strengthLabel = '';
    
    if (password.length >= 8) strength += 25;
    if (/[a-z]/.test(password)) strength += 25;
    if (/[A-Z]/.test(password)) strength += 25;
    if (/[0-9]/.test(password)) strength += 25;
    
    if (strength === 0) {
        strengthLabel = 'Password strength';
        strengthBar.style.background = '#e5e7eb';
    } else if (strength <= 25) {
        strengthLabel = 'Weak';
        strengthBar.style.background = '#ef4444';
    } else if (strength <= 50) {
        strengthLabel = 'Fair';
        strengthBar.style.background = '#f59e0b';
    } else if (strength <= 75) {
        strengthLabel = 'Good';
        strengthBar.style.background = '#3b82f6';
    } else {
        strengthLabel = 'Strong';
        strengthBar.style.background = '#10b981';
    }
    
    strengthBar.style.width = strength + '%';
    strengthText.textContent = strengthLabel;
}

function validateAuthField(field) {
    const value = field.value.trim();
    const fieldName = field.name;
    let isValid = true;
    let errorMessage = '';

    switch (fieldName) {
        case 'firstName':
        case 'lastName':
            if (!value) {
                isValid = false;
                errorMessage = `${fieldName === 'firstName' ? 'First' : 'Last'} name is required`;
            }
            break;
        case 'email':
            if (!value) {
                isValid = false;
                errorMessage = 'Email is required';
            } else if (!isValidEmail(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
            break;
        case 'password':
            if (!value) {
                isValid = false;
                errorMessage = 'Password is required';
            } else if (value.length < 8) {
                isValid = false;
                errorMessage = 'Password must be at least 8 characters long';
            }
            break;
        case 'confirmPassword':
            const password = document.getElementById('password').value;
            if (!value) {
                isValid = false;
                errorMessage = 'Please confirm your password';
            } else if (value !== password) {
                isValid = false;
                errorMessage = 'Passwords do not match';
            }
            break;
    }

    if (!isValid) {
        showAuthFieldError(field, errorMessage);
    } else {
        clearAuthFieldError(field);
    }

    return isValid;
}

function showAuthFieldError(field, message) {
    clearAuthFieldError(field);
    
    field.classList.add('error');
    
    const errorElement = document.createElement('div');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    
    const inputGroup = field.closest('.input-group');
    if (inputGroup) {
        inputGroup.parentNode.insertBefore(errorElement, inputGroup.nextSibling);
    } else {
        field.parentNode.insertBefore(errorElement, field.nextSibling);
    }
}

function clearAuthFieldError(field) {
    field.classList.remove('error');
    
    const inputGroup = field.closest('.input-group');
    const parent = inputGroup ? inputGroup.parentNode : field.parentNode;
    const errorElement = parent.querySelector('.field-error');
    
    if (errorElement) {
        errorElement.remove();
    }
}

// Utility Functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showModal(modal) {
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;

    document.body.appendChild(modal);

    setTimeout(() => {
        modal.style.opacity = '1';
    }, 10);

    modal.addEventListener('click', function(e) {
        if (e.target === modal || e.target.classList.contains('modal-close')) {
            closeModal();
        }
    });

    function closeModal() {
        modal.style.opacity = '0';
        setTimeout(() => modal.remove(), 300);
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close">
            <i class="fas fa-times"></i>
        </button>
    `;

    // Add notification styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 10001;
        max-width: 400px;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;

    document.body.appendChild(notification);

    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 10);

    // Auto remove after 5 seconds
    setTimeout(() => {
        removeNotification(notification);
    }, 5000);

    // Handle close button
    notification.querySelector('.notification-close').addEventListener('click', () => {
        removeNotification(notification);
    });

    function removeNotification(notif) {
        notif.style.transform = 'translateX(100%)';
        setTimeout(() => notif.remove(), 300);
    }
}
// Multi-step Form Functionality
let currentStep = 1;
let totalSteps = 3;

function nextStep() {
    if (validateCurrentStep()) {
        if (currentStep < totalSteps) {
            currentStep++;
            updateFormStep();
        }
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        updateFormStep();
    }
}

function updateFormStep() {
    // Update progress indicator
    const progressSteps = document.querySelectorAll('.progress-step');
    const formSteps = document.querySelectorAll('.form-step');
    
    progressSteps.forEach((step, index) => {
        if (index + 1 <= currentStep) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });
    
    // Update form steps
    formSteps.forEach((step, index) => {
        if (index + 1 === currentStep) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });
    
    // Scroll to top of form
    const authCard = document.querySelector('.auth-card-modern');
    if (authCard) {
        authCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function validateCurrentStep() {
    const currentFormStep = document.querySelector(`.form-step[data-step="${currentStep}"]`);
    if (!currentFormStep) return false;
    
    const requiredFields = currentFormStep.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
            
            // Remove error class after user starts typing
            field.addEventListener('input', function() {
                this.classList.remove('error');
            }, { once: true });
        } else {
            field.classList.remove('error');
        }
    });
    
    // Additional validation for specific steps
    if (currentStep === 3) {
        // Password confirmation validation
        const password = currentFormStep.querySelector('#password');
        const confirmPassword = currentFormStep.querySelector('#confirmPassword');
        
        if (password && confirmPassword) {
            if (password.value !== confirmPassword.value) {
                confirmPassword.classList.add('error');
                showNotification('Passwords do not match', 'error');
                isValid = false;
            }
        }
        
        // Terms checkbox validation
        const termsCheckbox = currentFormStep.querySelector('input[name="terms"]');
        if (termsCheckbox && !termsCheckbox.checked) {
            showNotification('Please accept the terms and conditions', 'error');
            isValid = false;
        }
    }
    
    if (!isValid) {
        showNotification('Please fill in all required fields', 'error');
    }
    
    return isValid;
}

// Enhanced Password Toggle
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const toggleButton = passwordField.parentElement.querySelector('.password-toggle-modern');
    const toggleIcon = toggleButton.querySelector('i');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Form Submission Handlers
function initializeRegistrationForms() {
    // Donor Registration
    const donorForm = document.getElementById('donorRegistrationForm');
    if (donorForm) {
        donorForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleDonorRegistration(this);
        });
    }
    
    // Patient Registration
    const patientForm = document.getElementById('patientRegistrationForm');
    if (patientForm) {
        patientForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handlePatientRegistration(this);
        });
    }
    
    // Hospital Registration
    const hospitalForm = document.getElementById('hospitalRegistrationForm');
    if (hospitalForm) {
        hospitalForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleHospitalRegistration(this);
        });
    }
}

function handleDonorRegistration(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.classList.add('loading');
    submitButton.disabled = true;
    
    const formData = new FormData(form);
    const donorData = {
        // Personal Info
        firstName: formData.get('firstName'),
        lastName: formData.get('lastName'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        dateOfBirth: formData.get('dateOfBirth'),
        address: formData.get('address'),
        city: formData.get('city'),
        zipCode: formData.get('zipCode'),
        
        // Health Info
        bloodType: formData.get('bloodType'),
        weight: formData.get('weight'),
        previousDonation: formData.get('previousDonation'),
        medicalConditions: formData.get('medicalConditions'),
        medications: formData.get('medications'),
        
        // Account Setup
        password: formData.get('password'),
        emergencyContact: formData.get('emergencyContact'),
        emergencyPhone: formData.get('emergencyPhone'),
        notifications: formData.get('notifications') === 'on',
        emergencyAlerts: formData.get('emergencyAlerts') === 'on'
    };
    
    // Simulate API call
    setTimeout(() => {
        showNotification('Registration successful! Please check your email for verification.', 'success');
        
        // Redirect to login or dashboard
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 2000);
    }, 2000);
}

function handlePatientRegistration(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.classList.add('loading');
    submitButton.disabled = true;
    
    const formData = new FormData(form);
    const patientData = {
        // Personal Info
        firstName: formData.get('firstName'),
        lastName: formData.get('lastName'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        dateOfBirth: formData.get('dateOfBirth'),
        address: formData.get('address'),
        city: formData.get('city'),
        zipCode: formData.get('zipCode'),
        
        // Medical Info
        bloodType: formData.get('bloodType'),
        weight: formData.get('weight'),
        medicalCondition: formData.get('medicalCondition'),
        physician: formData.get('physician'),
        physicianPhone: formData.get('physicianPhone'),
        urgency: formData.get('urgency'),
        
        // Account Setup
        password: formData.get('password'),
        emergencyContact1: formData.get('emergencyContact1'),
        emergencyPhone1: formData.get('emergencyPhone1'),
        emergencyContact2: formData.get('emergencyContact2'),
        emergencyPhone2: formData.get('emergencyPhone2'),
        insuranceProvider: formData.get('insuranceProvider'),
        notifications: formData.get('notifications') === 'on',
        emergencyAlerts: formData.get('emergencyAlerts') === 'on'
    };
    
    // Simulate API call
    setTimeout(() => {
        showNotification('Registration successful! Your account is being reviewed.', 'success');
        
        // Redirect to login
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 2000);
    }, 2000);
}

function handleHospitalRegistration(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.classList.add('loading');
    submitButton.disabled = true;
    
    const formData = new FormData(form);
    const hospitalData = {
        // Hospital Info
        hospitalName: formData.get('hospitalName'),
        hospitalType: formData.get('hospitalType'),
        bedCapacity: formData.get('bedCapacity'),
        address: formData.get('address'),
        city: formData.get('city'),
        state: formData.get('state'),
        zipCode: formData.get('zipCode'),
        phone: formData.get('phone'),
        website: formData.get('website'),
        
        // Admin Setup
        adminFirstName: formData.get('adminFirstName'),
        adminLastName: formData.get('adminLastName'),
        adminEmail: formData.get('adminEmail'),
        adminPhone: formData.get('adminPhone'),
        adminTitle: formData.get('adminTitle'),
        password: formData.get('password'),
        services: formData.getAll('services'),
        
        // Verification
        licenseNumber: formData.get('licenseNumber'),
        accreditation: formData.get('accreditation'),
        taxId: formData.get('taxId'),
        emergencyContact: formData.get('emergencyContact'),
        emergencyPhone: formData.get('emergencyPhone'),
        operatingHours: formData.get('operatingHours'),
        dataSharing: formData.get('dataSharing') === 'on',
        emergencyNetwork: formData.get('emergencyNetwork') === 'on',
        qualityAssurance: formData.get('qualityAssurance') === 'on'
    };
    
    // Simulate API call
    setTimeout(() => {
        showNotification('Registration submitted! Your application will be reviewed within 24-48 hours.', 'success');
        
        // Redirect to login
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 2000);
    }, 2000);
}

// Enhanced Notification System
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close">
            <i class="fas fa-times"></i>
        </button>
    `;

    // Add notification styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${getNotificationColor(type)};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        z-index: 10001;
        max-width: 400px;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    `;

    document.body.appendChild(notification);

    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 10);

    // Auto remove after 5 seconds
    setTimeout(() => {
        removeNotification(notification);
    }, 5000);

    // Handle close button
    notification.querySelector('.notification-close').addEventListener('click', () => {
        removeNotification(notification);
    });

    function removeNotification(notif) {
        notif.style.transform = 'translateX(100%)';
        setTimeout(() => notif.remove(), 300);
    }
}

function getNotificationIcon(type) {
    switch (type) {
        case 'success': return 'check-circle';
        case 'error': return 'exclamation-circle';
        case 'warning': return 'exclamation-triangle';
        default: return 'info-circle';
    }
}

function getNotificationColor(type) {
    switch (type) {
        case 'success': return '#10b981';
        case 'error': return '#ef4444';
        case 'warning': return '#f59e0b';
        default: return '#3b82f6';
    }
}

// Form Validation Styles
const validationStyles = `
    .form-control-modern.error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }
    
    .notification {
        font-family: 'Inter', sans-serif;
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 4px;
        transition: background-color 0.3s ease;
        flex-shrink: 0;
    }
    
    .notification-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    @media (max-width: 768px) {
        .notification {
            top: 10px;
            right: 10px;
            left: 10px;
            max-width: none;
        }
    }
`;

// Add validation styles to head
const styleSheet = document.createElement('style');
styleSheet.textContent = validationStyles;
document.head.appendChild(styleSheet);

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Reset form step on page load
    currentStep = 1;
    updateFormStep();
});