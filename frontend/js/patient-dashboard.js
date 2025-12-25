// Patient Dashboard Specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializePatientDashboard();
});

function initializePatientDashboard() {
    // Initialize request form
    initializeRequestForm();
    
    // Initialize request tracking
    initializeRequestTracking();
    
    // Initialize hospital information
    initializeHospitalInfo();
    
    // Initialize emergency features
    initializeEmergencyFeatures();
    
    // Initialize blood search
    initializeBloodSearch();
    
    // Initialize request history
    initializeRequestHistory();
    
    // Initialize profile management
    initializeProfileManagement();
}

// Request Form
function initializeRequestForm() {
    const form = document.querySelector('.quick-request-form');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            handleQuickRequest(form);
        });
    }

    // Auto-fill blood type based on patient profile
    const bloodTypeSelect = form.querySelector('select[name="bloodType"]');
    if (bloodTypeSelect) {
        // Get patient's blood type from profile (A+ in this case)
        const patientBloodType = 'A+';
        bloodTypeSelect.value = patientBloodType;
    }
}

function handleQuickRequest(form) {
    const formData = new FormData(form);
    const requestData = {
        bloodType: formData.get('bloodType'),
        units: formData.get('units'),
        priority: formData.get('priority'),
        hospital: formData.get('hospital'),
        reason: formData.get('reason'),
        doctorContact: formData.get('doctorContact')
    };

    // Validate form
    if (!requestData.bloodType || !requestData.units || !requestData.priority || !requestData.hospital || !requestData.reason) {
        showNotification('Please fill in all required fields', 'error');
        return;
    }

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    submitBtn.disabled = true;

    // Simulate API call
    setTimeout(() => {
        // Generate request ID
        const requestId = 'REQ-2024-' + String(Math.floor(Math.random() * 1000)).padStart(3, '0');
        
        // Add new request to timeline
        addNewRequestToTimeline(requestData, requestId);
        
        // Reset form
        form.reset();
        
        // Restore button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        // Show success message
        showNotification(`Blood request submitted successfully! Request ID: ${requestId}`, 'success');
        
        // Update active requests count
        updateActiveRequestsCount(1);
        
    }, 2000);
}

function addNewRequestToTimeline(requestData, requestId) {
    const timeline = document.querySelector('.requests-timeline');
    if (!timeline) return;

    const hospitalNames = {
        'city-general': 'City General Hospital',
        'metro-medical': 'Metro Medical Center',
        'regional-hospital': 'Regional Hospital',
        'community-health': 'Community Health Center'
    };

    const requestItem = document.createElement('div');
    requestItem.className = 'request-item active new-request';
    requestItem.innerHTML = `
        <div class="request-status-indicator pending">
            <i class="fas fa-clock"></i>
        </div>
        <div class="request-details">
            <div class="request-header">
                <h4>${requestData.bloodType} Blood Request</h4>
                <span class="request-id">#${requestId}</span>
            </div>
            <div class="request-info">
                <p><strong>Units:</strong> ${requestData.units} unit${requestData.units > 1 ? 's' : ''}</p>
                <p><strong>Priority:</strong> ${requestData.priority.charAt(0).toUpperCase() + requestData.priority.slice(1)}</p>
                <p><strong>Hospital:</strong> ${hospitalNames[requestData.hospital] || requestData.hospital}</p>
                <p><strong>Submitted:</strong> Just now</p>
            </div>
            <div class="request-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 25%"></div>
                </div>
                <div class="progress-steps">
                    <span class="step completed">Submitted</span>
                    <span class="step active">Under Review</span>
                    <span class="step">Approved</span>
                    <span class="step">Fulfilled</span>
                </div>
            </div>
        </div>
        <div class="request-actions">
            <button class="btn-sm info">View Details</button>
            <button class="btn-sm secondary">Contact Hospital</button>
        </div>
    `;

    // Insert at the beginning
    timeline.insertBefore(requestItem, timeline.firstChild);

    // Animate in
    requestItem.style.opacity = '0';
    requestItem.style.transform = 'translateY(-20px)';
    setTimeout(() => {
        requestItem.style.transition = 'all 0.5s ease';
        requestItem.style.opacity = '1';
        requestItem.style.transform = 'translateY(0)';
    }, 100);

    // Remove new-request class after animation
    setTimeout(() => {
        requestItem.classList.remove('new-request');
    }, 2000);
}

// Request Tracking
function initializeRequestTracking() {
    // Simulate real-time updates
    setInterval(() => {
        updateRequestProgress();
    }, 30000); // Update every 30 seconds

    // Handle request actions
    document.addEventListener('click', function(e) {
        if (e.target.closest('.request-actions .btn-sm')) {
            const btn = e.target.closest('.btn-sm');
            const action = btn.textContent.trim();
            const requestItem = btn.closest('.request-item');
            const requestId = requestItem.querySelector('.request-id').textContent;
            
            handleRequestAction(action, requestId, requestItem);
        }
    });
}

function updateRequestProgress() {
    const activeRequests = document.querySelectorAll('.request-item.active');
    
    activeRequests.forEach(request => {
        const progressFill = request.querySelector('.progress-fill');
        const currentWidth = parseInt(progressFill.style.width);
        
        // Randomly advance progress
        if (currentWidth < 100 && Math.random() < 0.3) {
            const newWidth = Math.min(100, currentWidth + 25);
            progressFill.style.width = newWidth + '%';
            
            // Update steps
            updateProgressSteps(request, newWidth);
            
            // Update status indicator
            updateStatusIndicator(request, newWidth);
            
            // Show notification for major progress
            if (newWidth === 75) {
                const requestId = request.querySelector('.request-id').textContent;
                showNotification(`Request ${requestId} has been approved!`, 'success');
            } else if (newWidth === 100) {
                const requestId = request.querySelector('.request-id').textContent;
                showNotification(`Request ${requestId} has been fulfilled!`, 'success');
                request.classList.remove('active');
                updateActiveRequestsCount(-1);
            }
        }
    });
}

function updateProgressSteps(request, progress) {
    const steps = request.querySelectorAll('.progress-steps .step');
    
    steps.forEach((step, index) => {
        step.classList.remove('active', 'completed');
        
        if ((index + 1) * 25 <= progress) {
            step.classList.add('completed');
        } else if ((index + 1) * 25 === progress + 25) {
            step.classList.add('active');
        }
    });
}

function updateStatusIndicator(request, progress) {
    const indicator = request.querySelector('.request-status-indicator');
    
    if (progress >= 100) {
        indicator.className = 'request-status-indicator fulfilled';
        indicator.innerHTML = '<i class="fas fa-check-circle"></i>';
    } else if (progress >= 75) {
        indicator.className = 'request-status-indicator approved';
        indicator.innerHTML = '<i class="fas fa-check"></i>';
    } else if (progress >= 50) {
        indicator.className = 'request-status-indicator processing';
        indicator.innerHTML = '<i class="fas fa-cog fa-spin"></i>';
    }
}

function handleRequestAction(action, requestId, requestItem) {
    switch (action) {
        case 'View Details':
            showRequestDetailsModal(requestId, requestItem);
            break;
        case 'Contact Hospital':
            showHospitalContactModal(requestItem);
            break;
        case 'Download Receipt':
            downloadReceipt(requestId);
            break;
        case 'Rate Experience':
            showRatingModal(requestId);
            break;
    }
}

function showRequestDetailsModal(requestId, requestItem) {
    const requestInfo = requestItem.querySelector('.request-info');
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Request Details - ${requestId}</h3>
                <button class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="request-details-content">
                    ${requestInfo.innerHTML}
                    <div class="additional-details">
                        <h4>Additional Information</h4>
                        <p><strong>Request Type:</strong> Standard Blood Request</p>
                        <p><strong>Expected Fulfillment:</strong> Within 24 hours</p>
                        <p><strong>Contact Person:</strong> Blood Bank Coordinator</p>
                        <p><strong>Reference Number:</strong> ${requestId}</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-close">Close</button>
            </div>
        </div>
    `;

    showModal(modal);
}

function showHospitalContactModal(requestItem) {
    const hospitalName = requestItem.querySelector('.request-info p:nth-child(3)').textContent.replace('Hospital: ', '');
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Contact ${hospitalName}</h3>
                <button class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <strong>Emergency Line</strong>
                            <p>+1 (555) 123-4567</p>
                        </div>
                        <button class="btn-sm primary">Call</button>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>Blood Bank Email</strong>
                            <p>bloodbank@hospital.com</p>
                        </div>
                        <button class="btn-sm secondary">Email</button>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <strong>Address</strong>
                            <p>123 Medical Center Dr, City</p>
                        </div>
                        <button class="btn-sm info">Directions</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-close">Close</button>
            </div>
        </div>
    `;

    showModal(modal);
}

// Hospital Information
function initializeHospitalInfo() {
    // Handle hospital actions
    document.addEventListener('click', function(e) {
        if (e.target.closest('.hospital-actions .btn-sm')) {
            const btn = e.target.closest('.btn-sm');
            const action = btn.textContent.trim();
            const hospitalCard = btn.closest('.hospital-card');
            const hospitalName = hospitalCard.querySelector('h4').textContent;
            
            handleHospitalAction(action, hospitalName, hospitalCard);
        }
    });

    // Simulate real-time availability updates
    setInterval(() => {
        updateHospitalAvailability();
    }, 45000); // Update every 45 seconds
}

function handleHospitalAction(action, hospitalName, hospitalCard) {
    switch (action) {
        case 'Contact':
            showHospitalContactModal({ querySelector: () => ({ textContent: `Hospital: ${hospitalName}` }) });
            break;
        case 'Directions':
            showNotification(`Opening directions to ${hospitalName}...`, 'info');
            // Would integrate with maps API
            break;
    }
}

function updateHospitalAvailability() {
    const hospitalCards = document.querySelectorAll('.hospital-card');
    
    hospitalCards.forEach(card => {
        const availabilityInfo = card.querySelector('.detail-item:last-child span');
        const currentUnits = parseInt(availabilityInfo.textContent.match(/\d+/)[0]);
        
        // Simulate stock changes
        const change = Math.floor(Math.random() * 6) - 2; // -2 to +3
        const newUnits = Math.max(0, currentUnits + change);
        
        if (change !== 0) {
            availabilityInfo.textContent = availabilityInfo.textContent.replace(/\d+/, newUnits);
            
            // Update status
            const status = card.querySelector('.hospital-status');
            if (newUnits === 0) {
                status.className = 'hospital-status unavailable';
                status.textContent = 'Unavailable';
            } else if (newUnits <= 10) {
                status.className = 'hospital-status limited';
                status.textContent = 'Limited';
            } else {
                status.className = 'hospital-status available';
                status.textContent = 'Available';
            }
        }
    });
}

// Emergency Features
function initializeEmergencyFeatures() {
    // Emergency request button is handled by onclick in HTML
}

// Emergency Reason Toggle
function toggleEmergencyReason() {
    const prioritySelect = document.getElementById('prioritySelect');
    const emergencyReasonGroup = document.getElementById('emergencyReasonGroup');
    const emergencyReason = document.getElementById('emergencyReason');
    
    if (prioritySelect.value === 'emergency') {
        emergencyReasonGroup.style.display = 'block';
        emergencyReason.setAttribute('required', 'required');
    } else {
        emergencyReasonGroup.style.display = 'none';
        emergencyReason.removeAttribute('required');
        emergencyReason.value = '';
    }
}

// Blood Search System
function initializeBloodSearch() {
    // Initialize search functionality
    const searchButton = document.querySelector('.blood-search-section .btn-primary');
    if (searchButton) {
        searchButton.addEventListener('click', searchBloodAvailability);
    }
}

function searchBloodAvailability() {
    const bloodType = document.getElementById('searchBloodType').value;
    const location = document.getElementById('locationPreference').value;
    const radius = document.getElementById('searchRadius').value;
    
    if (!bloodType) {
        showNotification('Please select a blood type to search', 'warning');
        return;
    }
    
    // Show loading state
    const resultsContainer = document.getElementById('searchResults');
    resultsContainer.innerHTML = `
        <div class="search-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Searching for ${bloodType} blood in nearby hospitals...</p>
        </div>
    `;
    
    // Simulate search results
    setTimeout(() => {
        displaySearchResults(bloodType, location, radius);
    }, 2000);
}

function displaySearchResults(bloodType, location, radius) {
    const resultsContainer = document.getElementById('searchResults');
    
    // Mock search results based on search parameters
    const searchResults = [
        {
            hospital: 'City General Hospital',
            distance: '2.3 km',
            availability: 45,
            status: 'available',
            phone: '+1 (555) 123-4567',
            address: '123 Medical Center Dr'
        },
        {
            hospital: 'Metro Medical Center',
            distance: '4.1 km',
            availability: 12,
            status: 'low',
            phone: '+1 (555) 987-6543',
            address: '456 Health Ave'
        },
        {
            hospital: 'Regional Hospital',
            distance: '6.8 km',
            availability: 0,
            status: 'unavailable',
            phone: '+1 (555) 456-7890',
            address: '789 Care Blvd'
        }
    ];
    
    resultsContainer.innerHTML = `
        <div class="search-results-header">
            <h4>Search Results for ${bloodType} Blood</h4>
            <p>Found ${searchResults.filter(r => r.availability > 0).length} hospitals with available blood</p>
        </div>
        <div class="results-list">
            ${searchResults.map(result => `
                <div class="result-item ${result.status}">
                    <div class="result-header">
                        <div class="hospital-icon">
                            <i class="fas fa-hospital"></i>
                        </div>
                        <div class="hospital-info">
                            <h5>${result.hospital}</h5>
                            <p>${result.address}</p>
                            <span class="distance">
                                <i class="fas fa-map-marker-alt"></i>
                                ${result.distance}
                            </span>
                        </div>
                        <div class="availability-info">
                            <div class="availability-count ${result.status}">
                                ${result.availability} units
                            </div>
                            <div class="availability-status ${result.status}">
                                ${result.status === 'available' ? 'Available' : 
                                  result.status === 'low' ? 'Low Stock' : 'Unavailable'}
                            </div>
                        </div>
                    </div>
                    <div class="result-actions">
                        ${result.availability > 0 ? `
                            <button class="btn-sm primary" onclick="requestFromHospital('${result.hospital}', '${bloodType}')">
                                <i class="fas fa-hand-holding-medical"></i>
                                Request Blood
                            </button>
                        ` : ''}
                        <button class="btn-sm secondary" onclick="callHospital('${result.phone}')">
                            <i class="fas fa-phone"></i>
                            Call Hospital
                        </button>
                        <button class="btn-sm info" onclick="getDirections('${result.address}')">
                            <i class="fas fa-directions"></i>
                            Directions
                        </button>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}

function requestFromHospital(hospitalName, bloodType) {
    // Pre-fill the quick request form
    document.querySelector('select[name="bloodType"]').value = bloodType;
    document.querySelector('select[name="hospital"]').value = hospitalName.toLowerCase().replace(/\s+/g, '-');
    
    // Scroll to request form
    document.querySelector('.quick-request-section').scrollIntoView({ behavior: 'smooth' });
    
    showNotification(`Pre-filled request form for ${bloodType} blood at ${hospitalName}`, 'info');
}

function callHospital(phone) {
    if (navigator.userAgent.match(/(iPhone|iPod|Android|BlackBerry)/)) {
        window.location.href = `tel:${phone}`;
    } else {
        showNotification(`Hospital phone: ${phone}`, 'info');
    }
}

function getDirections(address) {
    const encodedAddress = encodeURIComponent(address);
    window.open(`https://maps.google.com/maps?q=${encodedAddress}`, '_blank');
}

// Request History Management
function initializeRequestHistory() {
    // Initialize re-submit functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.history-actions .btn-sm')) {
            const btn = e.target.closest('.btn-sm');
            if (btn.textContent.includes('Re-submit')) {
                const requestId = btn.getAttribute('onclick').match(/'([^']+)'/)[1];
                reSubmitRequest(requestId);
            }
        }
    });
}

function reSubmitRequest(requestId) {
    // Get historical request data (mock data)
    const historicalRequests = {
        'REQ-2024-003': {
            bloodType: 'A+',
            units: '3',
            hospital: 'city-general',
            priority: 'emergency',
            reason: 'Emergency surgery requirements',
            emergencyReason: 'surgery'
        },
        'REQ-2024-001': {
            bloodType: 'A+',
            units: '2',
            hospital: 'metro-medical',
            priority: 'routine',
            reason: 'Scheduled surgery preparation'
        },
        'REQ-2024-002': {
            bloodType: 'A+',
            units: '1',
            hospital: 'regional-hospital',
            priority: 'routine',
            reason: 'Routine transfusion'
        }
    };
    
    const requestData = historicalRequests[requestId];
    if (requestData) {
        // Pre-fill the form with historical data
        document.querySelector('select[name="bloodType"]').value = requestData.bloodType;
        document.querySelector('select[name="units"]').value = requestData.units;
        document.querySelector('select[name="hospital"]').value = requestData.hospital;
        document.querySelector('select[name="priority"]').value = requestData.priority;
        document.querySelector('textarea[name="reason"]').value = requestData.reason;
        
        if (requestData.priority === 'emergency' && requestData.emergencyReason) {
            toggleEmergencyReason();
            document.getElementById('emergencyReason').value = requestData.emergencyReason;
        }
        
        // Scroll to form
        document.querySelector('.quick-request-section').scrollIntoView({ behavior: 'smooth' });
        
        showNotification(`Form pre-filled with data from ${requestId}`, 'success');
    }
}

// Request Cancellation
function cancelRequest(requestId) {
    showConfirmationModal(
        'Cancel Blood Request',
        'Are you sure you want to cancel this blood request? This action cannot be undone.',
        () => {
            // Update request status
            const requestItem = document.querySelector('.request-item.active');
            if (requestItem) {
                const statusIndicator = requestItem.querySelector('.request-status-indicator');
                statusIndicator.className = 'request-status-indicator cancelled';
                statusIndicator.innerHTML = '<i class="fas fa-times-circle"></i>';
                
                const progressSteps = requestItem.querySelectorAll('.progress-steps .step');
                progressSteps.forEach(step => {
                    step.classList.remove('active', 'completed');
                    step.classList.add('cancelled');
                });
                
                const progressFill = requestItem.querySelector('.progress-fill');
                progressFill.style.background = '#ef4444';
                
                // Update actions
                const actions = requestItem.querySelector('.request-actions');
                actions.innerHTML = `
                    <button class="btn-sm secondary" disabled>
                        <i class="fas fa-times-circle"></i>
                        Cancelled
                    </button>
                    <button class="btn-sm success" onclick="reSubmitRequest('${requestId}')">
                        <i class="fas fa-redo"></i>
                        Re-submit
                    </button>
                `;
                
                requestItem.classList.remove('active');
                requestItem.classList.add('cancelled');
            }
            
            showNotification('Blood request cancelled successfully', 'info');
            updateActiveRequestsCount(-1);
        }
    );
}

// Profile Management
function initializeProfileManagement() {
    // Profile management is handled by modal functions
}

function showEditProfileModal() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Edit Profile</h3>
                <button class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form class="edit-profile-form">
                    <div class="form-section">
                        <h4>Personal Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" class="form-control" name="firstName" value="Jane" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" class="form-control" name="lastName" value="Smith" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" class="form-control" name="dateOfBirth" value="1985-03-15" required>
                            </div>
                            <div class="form-group">
                                <label>Gender</label>
                                <select class="form-control" name="gender" required>
                                    <option value="female" selected>Female</option>
                                    <option value="male">Male</option>
                                    <option value="other">Other</option>
                                    <option value="prefer-not-to-say">Prefer not to say</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Blood Type</label>
                            <select class="form-control" name="bloodType" required>
                                <option value="A+" selected>A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Contact Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" name="email" value="jane.smith@email.com" required>
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="tel" class="form-control" name="phone" value="+1 (555) 123-4567" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <textarea class="form-control" name="address" rows="3" required>123 Main St, City Center</textarea>
                        </div>
                        <div class="form-group">
                            <label>Emergency Contact</label>
                            <input type="text" class="form-control" name="emergencyContact" value="John Smith - +1 (555) 987-6543" required>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Medical Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Medical ID</label>
                                <input type="text" class="form-control" name="medicalId" value="MED-2024-001">
                            </div>
                            <div class="form-group">
                                <label>Insurance Provider</label>
                                <input type="text" class="form-control" name="insurance" value="HealthCare Plus">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Known Allergies</label>
                            <textarea class="form-control" name="allergies" rows="2" placeholder="List any known allergies...">None reported</textarea>
                        </div>
                        <div class="form-group">
                            <label>Medical Conditions</label>
                            <textarea class="form-control" name="conditions" rows="2" placeholder="List any medical conditions...">None reported</textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-close">Cancel</button>
                <button class="btn btn-primary" onclick="saveProfile()">
                    <i class="fas fa-save"></i>
                    Save Changes
                </button>
            </div>
        </div>
    `;
    
    showModal(modal);
}

function saveProfile() {
    const form = document.querySelector('.edit-profile-form');
    const formData = new FormData(form);
    
    // Update profile display (in a real app, this would be an API call)
    const profileSections = document.querySelectorAll('.profile-section');
    
    // Update personal information
    const personalSection = profileSections[0];
    personalSection.querySelector('.profile-item:nth-child(1) .value').textContent = 
        `${formData.get('firstName')} ${formData.get('lastName')}`;
    personalSection.querySelector('.profile-item:nth-child(2) .value').textContent = 
        formData.get('bloodType');
    
    // Update contact information
    const contactSection = profileSections[1];
    contactSection.querySelector('.profile-item:nth-child(1) .value').textContent = 
        formData.get('email');
    contactSection.querySelector('.profile-item:nth-child(2) .value').textContent = 
        formData.get('phone');
    contactSection.querySelector('.profile-item:nth-child(3) .value').textContent = 
        formData.get('address');
    contactSection.querySelector('.profile-item:nth-child(4) .value').textContent = 
        formData.get('emergencyContact');
    
    closeModal();
    showNotification('Profile updated successfully!', 'success');
}

function showConfirmationModal(title, message, onConfirm) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content small">
            <div class="modal-header">
                <h3>${title}</h3>
                <button class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>${message}</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-close">Cancel</button>
                <button class="btn btn-danger confirm-action">Confirm</button>
            </div>
        </div>
    `;

    modal.addEventListener('click', function(e) {
        if (e.target.classList.contains('confirm-action')) {
            onConfirm();
            closeModal();
        }
    });

    showModal(modal);
}

function showEmergencyRequestModal() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay emergency-modal';
    modal.innerHTML = `
        <div class="modal-content emergency-content">
            <div class="modal-header emergency-header">
                <div class="emergency-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div>
                    <h3>Emergency Blood Request</h3>
                    <p>This will be marked as highest priority</p>
                </div>
                <button class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="emergency-warning">
                    <i class="fas fa-info-circle"></i>
                    <p>Emergency requests are processed immediately and will notify all nearby hospitals and blood banks.</p>
                </div>
                <form class="emergency-request-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Blood Type Needed</label>
                            <select class="form-control" name="bloodType" required>
                                <option value="A+">A+ (Your Type)</option>
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
                            <label>Units Required</label>
                            <select class="form-control" name="units" required>
                                <option value="1">1 Unit</option>
                                <option value="2">2 Units</option>
                                <option value="3">3 Units</option>
                                <option value="4">4 Units</option>
                                <option value="5">5+ Units</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Current Location</label>
                        <input type="text" class="form-control" name="location" placeholder="Hospital name or address" required>
                    </div>
                    <div class="form-group">
                        <label>Emergency Details</label>
                        <textarea class="form-control" name="details" rows="4" placeholder="Describe the emergency situation..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="tel" class="form-control" name="contact" placeholder="Emergency contact number" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-cancel">Cancel</button>
                <button class="btn btn-danger btn-lg emergency-submit">
                    <i class="fas fa-exclamation-triangle"></i>
                    Send Emergency Request
                </button>
            </div>
        </div>
    `;

    showModal(modal, function() {
        const form = modal.querySelector('.emergency-request-form');
        const formData = new FormData(form);
        
        // Show emergency processing
        showNotification('Emergency request sent! Notifying all nearby hospitals...', 'warning');
        
        setTimeout(() => {
            showNotification('Emergency request confirmed! Hospitals have been alerted.', 'success');
            
            // Add emergency request to timeline
            const emergencyData = {
                bloodType: formData.get('bloodType'),
                units: formData.get('units'),
                priority: 'emergency',
                hospital: 'Multiple Hospitals',
                reason: 'Emergency: ' + formData.get('details')
            };
            
            const requestId = 'EMRG-2024-' + String(Math.floor(Math.random() * 1000)).padStart(3, '0');
            addNewRequestToTimeline(emergencyData, requestId);
            updateActiveRequestsCount(1);
        }, 3000);
    });
}

// Utility Functions
function updateActiveRequestsCount(change) {
    const badge = document.querySelector('.request-status-section .badge');
    if (badge) {
        const currentCount = parseInt(badge.textContent.match(/\d+/)[0]);
        const newCount = Math.max(0, currentCount + change);
        badge.textContent = `${newCount} active request${newCount !== 1 ? 's' : ''}`;
    }
}

function downloadReceipt(requestId) {
    showNotification(`Downloading receipt for ${requestId}...`, 'info');
    // Would generate and download PDF receipt
}

function showRatingModal(requestId) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Rate Your Experience</h3>
                <button class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="rating-section">
                    <h4>How was your experience with ${requestId}?</h4>
                    <div class="star-rating">
                        <i class="fas fa-star" data-rating="1"></i>
                        <i class="fas fa-star" data-rating="2"></i>
                        <i class="fas fa-star" data-rating="3"></i>
                        <i class="fas fa-star" data-rating="4"></i>
                        <i class="fas fa-star" data-rating="5"></i>
                    </div>
                    <textarea class="form-control" placeholder="Share your feedback..." rows="4"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-close">Cancel</button>
                <button class="btn btn-primary">Submit Rating</button>
            </div>
        </div>
    `;

    // Handle star rating
    const stars = modal.querySelectorAll('.star-rating i');
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });
    });

    showModal(modal, function() {
        showNotification('Thank you for your feedback!', 'success');
    });
}

function showModal(modal, onConfirm = null) {
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
        if (e.target === modal || e.target.classList.contains('modal-close') || e.target.classList.contains('modal-cancel')) {
            closeModal();
        } else if (e.target.classList.contains('emergency-submit') || (e.target.closest('.modal-footer .btn-primary') && onConfirm)) {
            if (onConfirm) onConfirm();
            closeModal();
        }
    });

    function closeModal() {
        modal.style.opacity = '0';
        setTimeout(() => modal.remove(), 300);
    }
}

// Add patient-specific styles
const patientStyles = `
    .patient-profile-card {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 20px 40px rgba(59, 130, 246, 0.2);
    }

    .profile-header {
        display: flex;
        align-items: center;
        gap: 2rem;
    }

    .profile-avatar {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
    }

    .profile-info h2 {
        margin-bottom: 0.5rem;
        font-size: 1.75rem;
    }

    .blood-group-highlight {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255, 255, 255, 0.2);
        padding: 0.5rem 1rem;
        border-radius: 25px;
        margin-top: 1rem;
        font-weight: 600;
    }

    .emergency-button {
        margin-left: auto;
    }

    .btn-lg {
        padding: 1rem 2rem;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .quick-request-section, .request-status-section, .hospital-info-section, .blood-info-section {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        margin-bottom: 2rem;
        border: 1px solid #e5e7eb;
    }

    .request-form-container {
        padding: 2rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #374151;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
    }

    .requests-timeline {
        padding: 2rem;
    }

    .request-item {
        display: flex;
        gap: 1.5rem;
        padding: 1.5rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }

    .request-item:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .request-item.new-request {
        border-color: #3b82f6;
        background: rgba(59, 130, 246, 0.02);
    }

    .request-status-indicator {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .request-status-indicator.pending {
        background: #f59e0b;
    }

    .request-status-indicator.processing {
        background: #3b82f6;
    }

    .request-status-indicator.approved {
        background: #10b981;
    }

    .request-status-indicator.fulfilled {
        background: #059669;
    }

    .request-details {
        flex: 1;
    }

    .request-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .request-header h4 {
        margin: 0;
        color: #1f2937;
    }

    .request-id {
        font-size: 0.875rem;
        color: #6b7280;
        font-weight: 600;
    }

    .request-info p {
        margin-bottom: 0.5rem;
        color: #4b5563;
        font-size: 0.875rem;
    }

    .request-progress {
        margin-top: 1rem;
    }

    .progress-bar {
        width: 100%;
        height: 8px;
        background: #e5e7eb;
        border-radius: 4px;
        margin-bottom: 0.5rem;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #3b82f6, #1d4ed8);
        border-radius: 4px;
        transition: width 0.5s ease;
    }

    .progress-steps {
        display: flex;
        justify-content: space-between;
        font-size: 0.75rem;
    }

    .progress-steps .step {
        color: #9ca3af;
        font-weight: 500;
    }

    .progress-steps .step.completed {
        color: #10b981;
        font-weight: 600;
    }

    .progress-steps .step.active {
        color: #3b82f6;
        font-weight: 600;
    }

    .request-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-end;
    }

    .hospitals-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 1.5rem;
        padding: 2rem;
    }

    .hospital-card {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.5rem;
        transition: all 0.3s ease;
    }

    .hospital-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .hospital-header {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        align-items: flex-start;
    }

    .hospital-icon {
        width: 50px;
        height: 50px;
        background: #3b82f6;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .hospital-info {
        flex: 1;
    }

    .hospital-info h4 {
        margin-bottom: 0.25rem;
        color: #1f2937;
    }

    .hospital-info p {
        color: #6b7280;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }

    .hospital-distance {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #9ca3af;
        font-size: 0.875rem;
    }

    .hospital-status {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .hospital-status.available {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }

    .hospital-status.limited {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
    }

    .hospital-status.unavailable {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }

    .hospital-details {
        margin-bottom: 1rem;
    }

    .detail-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
        color: #4b5563;
    }

    .detail-item i {
        color: #6b7280;
        width: 16px;
    }

    .hospital-actions {
        display: flex;
        gap: 0.5rem;
    }

    .compatibility-info {
        padding: 2rem;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    .blood-type-card {
        text-align: center;
        padding: 2rem;
        border-radius: 12px;
        border: 2px solid #e5e7eb;
    }

    .blood-type-card.highlight {
        border-color: #3b82f6;
        background: rgba(59, 130, 246, 0.02);
    }

    .blood-type-icon {
        width: 80px;
        height: 80px;
        background: #dc2626;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.5rem;
        margin: 0 auto 1rem;
    }

    .compatible-types {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 1rem;
    }

    .blood-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .blood-badge.compatible {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }

    .blood-badge.incompatible {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }

    .compatibility-chart {
        padding: 1rem;
    }

    .chart-info {
        margin-top: 1rem;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .emergency-modal .modal-content {
        border: 3px solid #ef4444;
    }

    .emergency-header {
        background: rgba(239, 68, 68, 0.05);
        border-bottom: 1px solid #fecaca;
    }

    .emergency-header .emergency-icon {
        width: 50px;
        height: 50px;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        animation: pulse 2s infinite;
    }

    .emergency-warning {
        background: rgba(245, 158, 11, 0.1);
        border: 1px solid #fbbf24;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .emergency-warning i {
        color: #f59e0b;
        font-size: 1.25rem;
    }

    .contact-info {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .contact-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
    }

    .contact-item i {
        color: #3b82f6;
        font-size: 1.25rem;
        width: 20px;
    }

    .contact-item div {
        flex: 1;
    }

    .star-rating {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
        margin: 1rem 0;
        font-size: 2rem;
    }

    .star-rating i {
        color: #d1d5db;
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .star-rating i:hover,
    .star-rating i.active {
        color: #fbbf24;
    }

    @media (max-width: 1024px) {
        .compatibility-info {
            grid-template-columns: 1fr;
        }

        .hospitals-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Blood Search Section */
    .blood-search-section {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        margin-bottom: 2rem;
        border: 1px solid #e5e7eb;
    }

    .search-container {
        padding: 2rem;
    }

    .search-form .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr auto;
        gap: 1rem;
        align-items: end;
    }

    .search-results {
        margin-top: 2rem;
    }

    .search-loading {
        text-align: center;
        padding: 2rem;
        color: #6b7280;
    }

    .search-loading i {
        font-size: 2rem;
        margin-bottom: 1rem;
        color: #3b82f6;
    }

    .search-results-header {
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .search-results-header h4 {
        margin-bottom: 0.5rem;
        color: #1f2937;
    }

    .results-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .result-item {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.5rem;
        transition: all 0.3s ease;
    }

    .result-item:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .result-item.available {
        border-color: #10b981;
        background: rgba(16, 185, 129, 0.02);
    }

    .result-item.low {
        border-color: #f59e0b;
        background: rgba(245, 158, 11, 0.02);
    }

    .result-item.unavailable {
        border-color: #ef4444;
        background: rgba(239, 68, 68, 0.02);
        opacity: 0.7;
    }

    .result-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .result-header .hospital-icon {
        width: 50px;
        height: 50px;
        background: #3b82f6;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .result-header .hospital-info {
        flex: 1;
    }

    .result-header .hospital-info h5 {
        margin-bottom: 0.25rem;
        color: #1f2937;
    }

    .result-header .hospital-info p {
        color: #6b7280;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }

    .distance {
        color: #9ca3af;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .availability-info {
        text-align: right;
    }

    .availability-count {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }

    .availability-count.available {
        color: #10b981;
    }

    .availability-count.low {
        color: #f59e0b;
    }

    .availability-count.unavailable {
        color: #ef4444;
    }

    .availability-status {
        font-size: 0.875rem;
        font-weight: 600;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
    }

    .availability-status.available {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }

    .availability-status.low {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
    }

    .availability-status.unavailable {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }

    .result-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    /* Emergency Reason Field */
    .emergency-warning {
        color: #f59e0b;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }

    /* Request History Section */
    .request-history-section, .profile-management-section {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        margin-bottom: 2rem;
        border: 1px solid #e5e7eb;
    }

    .history-timeline {
        padding: 2rem;
    }

    .history-item {
        display: flex;
        gap: 1rem;
        padding: 1.5rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .history-item:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .history-item.completed {
        border-color: #10b981;
        background: rgba(16, 185, 129, 0.02);
    }

    .history-item.cancelled {
        border-color: #ef4444;
        background: rgba(239, 68, 68, 0.02);
    }

    .history-status-indicator {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: white;
        flex-shrink: 0;
    }

    .history-item.completed .history-status-indicator {
        background: #10b981;
    }

    .history-item.cancelled .history-status-indicator {
        background: #ef4444;
    }

    .history-details {
        flex: 1;
    }

    .history-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .history-header h4 {
        margin: 0;
        color: #1f2937;
    }

    .history-id {
        font-size: 0.875rem;
        color: #6b7280;
        font-weight: 600;
    }

    .history-info p {
        margin-bottom: 0.25rem;
        color: #4b5563;
        font-size: 0.875rem;
    }

    .history-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-end;
    }

    /* Profile Management Section */
    .profile-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        padding: 2rem;
    }

    .profile-section h4 {
        margin-bottom: 1rem;
        color: #1f2937;
        font-weight: 600;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 0.5rem;
    }

    .profile-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        padding: 0.5rem 0;
    }

    .profile-item .label {
        color: #6b7280;
        font-weight: 500;
    }

    .profile-item .value {
        color: #1f2937;
        font-weight: 500;
        text-align: right;
        max-width: 60%;
        word-break: break-word;
    }

    /* Form Sections in Modals */
    .form-section {
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .form-section h4 {
        margin-bottom: 1rem;
        color: #1f2937;
        font-weight: 600;
    }

    /* Request Status Updates */
    .request-item.cancelled {
        opacity: 0.8;
    }

    .request-item.cancelled .request-status-indicator {
        background: #ef4444;
    }

    .request-item.cancelled .progress-steps .step {
        color: #ef4444;
    }

    .request-item.cancelled .progress-steps .step.cancelled {
        color: #ef4444;
        text-decoration: line-through;
    }

    @media (max-width: 1024px) {
        .search-form .form-row {
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .search-form .form-row .form-group:last-child {
            grid-column: 1 / -1;
        }

        .profile-info-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .profile-header {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }

        .emergency-button {
            margin-left: 0;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .search-form .form-row {
            grid-template-columns: 1fr;
        }

        .request-item, .history-item {
            flex-direction: column;
            gap: 1rem;
        }

        .request-actions, .history-actions {
            flex-direction: row;
            justify-content: center;
        }

        .hospital-header, .result-header {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .hospital-status, .availability-info {
            margin-top: 0.5rem;
        }

        .result-actions {
            justify-content: center;
        }
    }
`;

// Add styles to head
const styleSheet = document.createElement('style');
styleSheet.textContent = patientStyles;
document.head.appendChild(styleSheet);