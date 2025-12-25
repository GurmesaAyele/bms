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

        .request-item {
            flex-direction: column;
            gap: 1rem;
        }

        .request-actions {
            flex-direction: row;
            justify-content: center;
        }

        .hospital-header {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .hospital-status {
            margin-top: 0.5rem;
        }
    }
`;

// Add styles to head
const styleSheet = document.createElement('style');
styleSheet.textContent = patientStyles;
document.head.appendChild(styleSheet);