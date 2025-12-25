// Request Blood Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeRequestBloodPage();
});

function initializeRequestBloodPage() {
    // Initialize availability checker
    initializeAvailabilityChecker();
    
    // Initialize navigation toggle
    initializeNavigation();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize animations
    initializeAnimations();
}

// Navigation functionality
function initializeNavigation() {
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            navToggle.classList.toggle('active');
        });
    }
}

// Availability Checker
function initializeAvailabilityChecker() {
    const checkButton = document.querySelector('#checkAvailability');
    if (checkButton) {
        checkButton.addEventListener('click', checkAvailability);
    }
}

function checkAvailability() {
    const bloodType = document.getElementById('checkBloodType').value;
    const location = document.getElementById('checkLocation').value;
    const radius = document.getElementById('checkRadius').value;
    const resultsContainer = document.getElementById('availabilityResults');
    
    if (!bloodType || !location) {
        showNotification('Please select blood type and enter location', 'error');
        return;
    }
    
    // Show loading state
    resultsContainer.innerHTML = `
        <div class="loading-state">
            <div class="loading-spinner"></div>
            <p>Checking availability in your area...</p>
        </div>
    `;
    
    // Simulate API call
    setTimeout(() => {
        const mockResults = generateMockAvailabilityResults(bloodType, location, radius);
        displayAvailabilityResults(mockResults);
    }, 2000);
}

function generateMockAvailabilityResults(bloodType, location, radius) {
    const hospitals = [
        { name: 'City General Hospital', distance: '2.3 miles', units: Math.floor(Math.random() * 20) + 5, status: 'available' },
        { name: 'Memorial Medical Center', distance: '4.7 miles', units: Math.floor(Math.random() * 15) + 3, status: 'available' },
        { name: 'Regional Blood Bank', distance: '6.1 miles', units: Math.floor(Math.random() * 30) + 10, status: 'available' },
        { name: 'University Hospital', distance: '8.9 miles', units: Math.floor(Math.random() * 10) + 2, status: 'low' },
        { name: 'Community Health Center', distance: '12.4 miles', units: Math.floor(Math.random() * 5) + 1, status: 'critical' }
    ];
    
    return {
        bloodType,
        location,
        radius,
        totalUnits: hospitals.reduce((sum, h) => sum + h.units, 0),
        hospitals: hospitals.slice(0, Math.floor(Math.random() * 3) + 3)
    };
}

function displayAvailabilityResults(results) {
    const resultsContainer = document.getElementById('availabilityResults');
    
    resultsContainer.innerHTML = `
        <div class="availability-summary">
            <h3>Blood Availability for ${results.bloodType}</h3>
            <p>Found <strong>${results.totalUnits} units</strong> within ${results.radius} miles of ${results.location}</p>
        </div>
        <div class="hospital-results">
            ${results.hospitals.map(hospital => `
                <div class="hospital-result">
                    <div class="hospital-info">
                        <h4>${hospital.name}</h4>
                        <p class="hospital-distance">
                            <i class="fas fa-map-marker-alt"></i>
                            ${hospital.distance}
                        </p>
                    </div>
                    <div class="hospital-availability">
                        <div class="units-available">
                            <span class="units-count">${hospital.units}</span>
                            <span class="units-label">units</span>
                        </div>
                        <div class="availability-status ${hospital.status}">
                            ${hospital.status === 'available' ? 'Available' : 
                              hospital.status === 'low' ? 'Low Stock' : 'Critical'}
                        </div>
                    </div>
                    <div class="hospital-actions">
                        <button class="btn btn-sm btn-primary" onclick="requestFromHospital('${hospital.name}')">
                            Request Blood
                        </button>
                    </div>
                </div>
            `).join('')}
        </div>
        <div class="availability-actions">
            <button class="btn btn-outline-primary" onclick="checkAvailability()">
                <i class="fas fa-refresh"></i>
                Refresh Results
            </button>
        </div>
    `;
}

// Request Form Modal
function showRequestForm(type) {
    const modal = document.getElementById('requestModal');
    const modalTitle = document.getElementById('requestModalTitle');
    const formContainer = document.querySelector('#bloodRequestForm');
    
    // Set modal title based on request type
    const titles = {
        emergency: 'Emergency Blood Request',
        scheduled: 'Scheduled Blood Request',
        ongoing: 'Ongoing Treatment Request'
    };
    
    modalTitle.textContent = titles[type] || 'Blood Request Form';
    
    // Generate form based on type
    formContainer.innerHTML = generateRequestForm(type);
    
    // Show modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Initialize form validation for the new form
    initializeFormValidation();
}

function generateRequestForm(type) {
    const commonFields = `
        <div class="form-section">
            <h4>Patient Information</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="patientName">Patient Full Name *</label>
                    <input type="text" id="patientName" name="patientName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="patientDOB">Date of Birth *</label>
                    <input type="date" id="patientDOB" name="patientDOB" class="form-control" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="patientPhone">Phone Number *</label>
                    <input type="tel" id="patientPhone" name="patientPhone" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="patientEmail">Email Address</label>
                    <input type="email" id="patientEmail" name="patientEmail" class="form-control">
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h4>Medical Information</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="bloodType">Blood Type Needed *</label>
                    <select id="bloodType" name="bloodType" class="form-control" required>
                        <option value="">Select Blood Type</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="unitsNeeded">Units Needed *</label>
                    <input type="number" id="unitsNeeded" name="unitsNeeded" class="form-control" min="1" max="10" required>
                </div>
            </div>
            <div class="form-group">
                <label for="medicalCondition">Medical Condition/Reason *</label>
                <textarea id="medicalCondition" name="medicalCondition" class="form-control" rows="3" required placeholder="Please describe the medical condition or reason for blood transfusion"></textarea>
            </div>
        </div>
        
        <div class="form-section">
            <h4>Hospital Information</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="hospitalName">Hospital/Medical Center *</label>
                    <input type="text" id="hospitalName" name="hospitalName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="doctorName">Attending Physician *</label>
                    <input type="text" id="doctorName" name="doctorName" class="form-control" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="department">Department/Unit</label>
                    <input type="text" id="department" name="department" class="form-control" placeholder="e.g., Emergency, Surgery, ICU">
                </div>
                <div class="form-group">
                    <label for="medicalRecordNumber">Medical Record Number</label>
                    <input type="text" id="medicalRecordNumber" name="medicalRecordNumber" class="form-control">
                </div>
            </div>
        </div>
    `;
    
    let specificFields = '';
    let submitButtonText = 'Submit Request';
    
    if (type === 'emergency') {
        specificFields = `
            <div class="form-section emergency-section">
                <h4>Emergency Details</h4>
                <div class="emergency-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Emergency requests are processed immediately and require medical verification.</p>
                </div>
                <div class="form-group">
                    <label for="emergencyReason">Emergency Justification *</label>
                    <textarea id="emergencyReason" name="emergencyReason" class="form-control" rows="3" required placeholder="Please provide detailed justification for emergency status"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="emergencyContact">Emergency Contact Name *</label>
                        <input type="text" id="emergencyContact" name="emergencyContact" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="emergencyContactPhone">Emergency Contact Phone *</label>
                        <input type="tel" id="emergencyContactPhone" name="emergencyContactPhone" class="form-control" required>
                    </div>
                </div>
            </div>
        `;
        submitButtonText = 'Submit Emergency Request';
    } else if (type === 'scheduled') {
        specificFields = `
            <div class="form-section">
                <h4>Scheduling Information</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="procedureDate">Procedure/Treatment Date *</label>
                        <input type="datetime-local" id="procedureDate" name="procedureDate" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="procedureType">Procedure Type</label>
                        <input type="text" id="procedureType" name="procedureType" class="form-control" placeholder="e.g., Surgery, Chemotherapy">
                    </div>
                </div>
                <div class="form-group">
                    <label for="specialInstructions">Special Instructions</label>
                    <textarea id="specialInstructions" name="specialInstructions" class="form-control" rows="3" placeholder="Any special requirements or instructions"></textarea>
                </div>
            </div>
        `;
    } else if (type === 'ongoing') {
        specificFields = `
            <div class="form-section">
                <h4>Treatment Schedule</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="treatmentFrequency">Treatment Frequency *</label>
                        <select id="treatmentFrequency" name="treatmentFrequency" class="form-control" required>
                            <option value="">Select Frequency</option>
                            <option value="weekly">Weekly</option>
                            <option value="biweekly">Bi-weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="treatmentDuration">Expected Duration</label>
                        <input type="text" id="treatmentDuration" name="treatmentDuration" class="form-control" placeholder="e.g., 6 months, 1 year">
                    </div>
                </div>
                <div class="form-group">
                    <label for="nextAppointment">Next Appointment Date</label>
                    <input type="datetime-local" id="nextAppointment" name="nextAppointment" class="form-control">
                </div>
            </div>
        `;
    }
    
    return `
        ${commonFields}
        ${specificFields}
        <div class="form-section">
            <h4>Additional Information</h4>
            <div class="form-group">
                <label for="additionalNotes">Additional Notes</label>
                <textarea id="additionalNotes" name="additionalNotes" class="form-control" rows="3" placeholder="Any additional information that might be helpful"></textarea>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="consentCheckbox" name="consent" required>
                    <span class="checkmark"></span>
                    I consent to the processing of my medical information for blood request purposes *
                </label>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="accuracyCheckbox" name="accuracy" required>
                    <span class="checkmark"></span>
                    I confirm that all information provided is accurate and complete *
                </label>
            </div>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="closeRequestModal()">Cancel</button>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i>
                ${submitButtonText}
            </button>
        </div>
    `;
}

function closeRequestModal() {
    const modal = document.getElementById('requestModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

function requestFromHospital(hospitalName) {
    // Pre-fill hospital name and show request form
    showRequestForm('emergency');
    
    // Wait for form to load, then pre-fill hospital name
    setTimeout(() => {
        const hospitalNameField = document.getElementById('hospitalName');
        if (hospitalNameField) {
            hospitalNameField.value = hospitalName;
        }
    }, 100);
}

// Form Validation
function initializeFormValidation() {
    const form = document.getElementById('bloodRequestForm');
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
        
        // Add real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
            input.addEventListener('input', clearFieldError);
        });
    }
}

function validateField(event) {
    const field = event.target;
    const value = field.value.trim();
    
    // Remove existing error
    clearFieldError(event);
    
    // Validate required fields
    if (field.hasAttribute('required') && !value) {
        showFieldError(field, 'This field is required');
        return false;
    }
    
    // Validate specific field types
    switch (field.type) {
        case 'email':
            if (value && !isValidEmail(value)) {
                showFieldError(field, 'Please enter a valid email address');
                return false;
            }
            break;
        case 'tel':
            if (value && !isValidPhone(value)) {
                showFieldError(field, 'Please enter a valid phone number');
                return false;
            }
            break;
        case 'number':
            const min = parseInt(field.getAttribute('min'));
            const max = parseInt(field.getAttribute('max'));
            const numValue = parseInt(value);
            if (value && (numValue < min || numValue > max)) {
                showFieldError(field, `Please enter a number between ${min} and ${max}`);
                return false;
            }
            break;
    }
    
    return true;
}

function clearFieldError(event) {
    const field = event.target;
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
        errorElement.remove();
    }
    field.classList.remove('error');
}

function showFieldError(field, message) {
    field.classList.add('error');
    
    const errorElement = document.createElement('div');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    
    field.parentNode.appendChild(errorElement);
}

function handleFormSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Validate all fields
    let isValid = true;
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        if (!validateField({ target: input })) {
            isValid = false;
        }
    });
    
    if (!isValid) {
        showNotification('Please correct the errors in the form', 'error');
        return;
    }
    
    // Show loading state
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    submitButton.disabled = true;
    
    // Simulate form submission
    setTimeout(() => {
        // Reset button
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
        
        // Show success message
        showNotification('Blood request submitted successfully! You will receive confirmation shortly.', 'success');
        
        // Close modal
        closeRequestModal();
        
        // Optionally redirect to dashboard or show next steps
        setTimeout(() => {
            if (confirm('Would you like to create an account to track your request?')) {
                window.location.href = 'auth/register.html';
            }
        }, 2000);
        
    }, 3000);
}

// Utility Functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
    return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 
                           type === 'error' ? 'fa-exclamation-circle' : 
                           'fa-info-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Animation initialization
function initializeAnimations() {
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    const animateElements = document.querySelectorAll('.request-option, .process-step, .story-card, .requirement-category');
    animateElements.forEach(el => observer.observe(el));
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('requestModal');
    if (event.target === modal) {
        closeRequestModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeRequestModal();
    }
});
   