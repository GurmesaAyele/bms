// Donor Dashboard Specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeDonorDashboard();
});

function initializeDonorDashboard() {
    // Initialize availability toggle
    initializeAvailabilityToggle();
    
    // Initialize emergency request notifications
    initializeEmergencyNotifications();
    
    // Initialize achievement animations
    initializeAchievements();
    
    // Initialize timeline animations
    initializeTimeline();
}

// Availability Toggle
function initializeAvailabilityToggle() {
    const toggle = document.querySelector('.toggle-switch input');
    const label = document.querySelector('.toggle-label');
    
    if (toggle) {
        toggle.addEventListener('change', function() {
            if (this.checked) {
                label.textContent = 'Available to Donate';
                label.style.color = '#10b981';
                showNotification('You are now available for donations!', 'success');
            } else {
                label.textContent = 'Not Available';
                label.style.color = '#ef4444';
                showNotification('You are now unavailable for donations.', 'info');
            }
        });
    }
}

// Emergency Notifications
function initializeEmergencyNotifications() {
    // Simulate new emergency requests
    setInterval(() => {
        if (Math.random() < 0.3) { // 30% chance every 2 minutes
            addNewEmergencyRequest();
        }
    }, 120000); // Check every 2 minutes
    
    // Handle emergency action buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.emergency-actions .btn')) {
            const btn = e.target.closest('.btn');
            const action = btn.textContent.trim();
            
            if (action.includes('Call Hospital')) {
                showNotification('Calling hospital...', 'info');
                // Simulate phone call
                setTimeout(() => {
                    showNotification('Connected to hospital emergency line', 'success');
                }, 2000);
            } else if (action.includes('Get Directions')) {
                showNotification('Opening directions...', 'info');
                // Would open maps app
            } else if (action.includes('Schedule Visit')) {
                showScheduleModal();
            }
        }
    });
}

// Add New Emergency Request
function addNewEmergencyRequest() {
    const emergencyList = document.querySelector('.emergency-list');
    if (!emergencyList) return;

    const emergencyTypes = [
        {
            type: 'urgent',
            title: 'Critical: O+ Blood Needed',
            hospital: 'Emergency Medical Center',
            distance: '1.8 km away',
            description: 'Multiple accident victims need immediate blood transfusion',
            time: 'Just now'
        },
        {
            type: 'normal',
            title: 'O+ Blood Stock Critical',
            hospital: 'Regional Blood Bank',
            distance: '3.2 km away',
            description: 'Blood bank critically low on O+ blood supply',
            time: 'Just now'
        }
    ];

    const randomRequest = emergencyTypes[Math.floor(Math.random() * emergencyTypes.length)];
    
    const emergencyItem = document.createElement('div');
    emergencyItem.className = `emergency-item ${randomRequest.type === 'urgent' ? 'urgent' : ''}`;
    emergencyItem.style.opacity = '0';
    emergencyItem.style.transform = 'translateY(-20px)';
    
    emergencyItem.innerHTML = `
        <div class="emergency-icon">
            <i class="fas fa-${randomRequest.type === 'urgent' ? 'exclamation-triangle' : 'hospital'}"></i>
        </div>
        <div class="emergency-content">
            <h4>${randomRequest.title}</h4>
            <p><strong>${randomRequest.hospital}</strong> - ${randomRequest.distance}</p>
            <p>${randomRequest.description}</p>
            <div class="emergency-actions">
                <button class="btn btn-danger btn-sm">
                    <i class="fas fa-phone"></i>
                    Call Hospital
                </button>
                <button class="btn btn-primary btn-sm">
                    <i class="fas fa-directions"></i>
                    Get Directions
                </button>
            </div>
        </div>
        <div class="emergency-time">
            <span>${randomRequest.time}</span>
        </div>
    `;

    // Insert at the beginning
    emergencyList.insertBefore(emergencyItem, emergencyList.firstChild);

    // Animate in
    setTimeout(() => {
        emergencyItem.style.transition = 'all 0.5s ease';
        emergencyItem.style.opacity = '1';
        emergencyItem.style.transform = 'translateY(0)';
    }, 100);

    // Update badge count
    const badge = document.querySelector('.emergency-requests .badge');
    if (badge) {
        const currentCount = parseInt(badge.textContent.match(/\d+/)[0]);
        badge.textContent = `${currentCount + 1} Urgent`;
    }

    // Show notification
    showNotification('New emergency blood request in your area!', 'warning');

    // Remove oldest items if more than 3
    const items = emergencyList.querySelectorAll('.emergency-item');
    if (items.length > 3) {
        items[items.length - 1].remove();
    }
}

// Initialize Achievements
function initializeAchievements() {
    const achievements = document.querySelectorAll('.achievement-item');
    
    achievements.forEach((achievement, index) => {
        // Animate achievements on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, index * 200);
                    observer.unobserve(entry.target);
                }
            });
        });
        
        achievement.style.opacity = '0';
        achievement.style.transform = 'translateY(20px)';
        achievement.style.transition = 'all 0.5s ease';
        observer.observe(achievement);
    });

    // Animate progress bars
    const progressBars = document.querySelectorAll('.progress-fill');
    progressBars.forEach(bar => {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const width = entry.target.style.width;
                    entry.target.style.width = '0%';
                    setTimeout(() => {
                        entry.target.style.transition = 'width 1.5s ease';
                        entry.target.style.width = width;
                    }, 500);
                    observer.unobserve(entry.target);
                }
            });
        });
        observer.observe(bar);
    });
}

// Initialize Timeline
function initializeTimeline() {
    const timelineItems = document.querySelectorAll('.timeline-item');
    
    timelineItems.forEach((item, index) => {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('animate-in');
                    }, index * 200);
                    observer.unobserve(entry.target);
                }
            });
        });
        observer.observe(item);
    });
}

// Schedule Modal
function showScheduleModal() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Schedule Donation</h3>
                <button class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form class="schedule-form">
                    <div class="form-group">
                        <label>Preferred Date</label>
                        <input type="date" class="form-control" min="${new Date().toISOString().split('T')[0]}">
                    </div>
                    <div class="form-group">
                        <label>Preferred Time</label>
                        <select class="form-control">
                            <option>9:00 AM - 10:00 AM</option>
                            <option>10:00 AM - 11:00 AM</option>
                            <option>11:00 AM - 12:00 PM</option>
                            <option>2:00 PM - 3:00 PM</option>
                            <option>3:00 PM - 4:00 PM</option>
                            <option>4:00 PM - 5:00 PM</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <select class="form-control">
                            <option>City General Hospital</option>
                            <option>Metro Blood Bank</option>
                            <option>Community Health Center</option>
                            <option>Regional Medical Center</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Special Notes (Optional)</label>
                        <textarea class="form-control" rows="3" placeholder="Any special requirements or notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-cancel">Cancel</button>
                <button class="btn btn-primary modal-confirm">Schedule Donation</button>
            </div>
        </div>
    `;

    // Add modal styles
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

    // Animate in
    setTimeout(() => {
        modal.style.opacity = '1';
    }, 10);

    // Handle modal actions
    modal.addEventListener('click', function(e) {
        if (e.target === modal || e.target.classList.contains('modal-close') || e.target.classList.contains('modal-cancel')) {
            closeModal();
        } else if (e.target.classList.contains('modal-confirm')) {
            showNotification('Donation scheduled successfully!', 'success');
            closeModal();
        }
    });

    function closeModal() {
        modal.style.opacity = '0';
        setTimeout(() => modal.remove(), 300);
    }
}

// Add custom styles for donor dashboard
const donorStyles = `
    .donor-profile-card {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        color: white;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 20px 40px rgba(220, 38, 38, 0.2);
    }

    .profile-header {
        display: flex;
        align-items: center;
        gap: 2rem;
        margin-bottom: 2rem;
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

    .donation-status {
        margin-left: auto;
    }

    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255, 255, 255, 0.3);
        transition: 0.4s;
        border-radius: 34px;
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: 0.4s;
        border-radius: 50%;
    }

    input:checked + .toggle-slider {
        background-color: #10b981;
    }

    input:checked + .toggle-slider:before {
        transform: translateX(26px);
    }

    .toggle-label {
        display: block;
        margin-top: 0.5rem;
        font-weight: 500;
        color: #10b981;
    }

    .profile-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 2rem;
    }

    .stat-item {
        text-align: center;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 0.875rem;
        opacity: 0.9;
    }

    .eligibility-card, .emergency-requests, .donation-history, .achievements-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        margin-bottom: 2rem;
        border: 1px solid #e5e7eb;
    }

    .eligibility-content {
        padding: 1.5rem;
    }

    .eligibility-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .text-success {
        color: #10b981;
    }

    .next-donation {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e5e7eb;
    }

    .emergency-list {
        padding: 1.5rem;
    }

    .emergency-item {
        display: flex;
        gap: 1rem;
        padding: 1.5rem;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .emergency-item:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .emergency-item.urgent {
        border-color: #ef4444;
        background: rgba(239, 68, 68, 0.02);
    }

    .emergency-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fee2e2;
        color: #dc2626;
        font-size: 1.25rem;
    }

    .emergency-item.urgent .emergency-icon {
        background: #fef2f2;
        color: #ef4444;
        animation: pulse 2s infinite;
    }

    .emergency-content {
        flex: 1;
    }

    .emergency-content h4 {
        margin-bottom: 0.5rem;
        color: #1f2937;
    }

    .emergency-actions {
        margin-top: 1rem;
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .emergency-time {
        text-align: right;
        font-size: 0.75rem;
        color: #6b7280;
    }

    .timeline {
        padding: 1.5rem;
    }

    .timeline-item {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        opacity: 0;
        transform: translateX(-20px);
        transition: all 0.5s ease;
    }

    .timeline-item.animate-in {
        opacity: 1;
        transform: translateX(0);
    }

    .timeline-marker {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1rem;
        position: relative;
    }

    .timeline-marker.success {
        background: #10b981;
    }

    .timeline-marker::after {
        content: '';
        position: absolute;
        top: 40px;
        left: 50%;
        transform: translateX(-50%);
        width: 2px;
        height: 60px;
        background: #e5e7eb;
    }

    .timeline-item:last-child .timeline-marker::after {
        display: none;
    }

    .timeline-content {
        flex: 1;
        padding-top: 0.25rem;
    }

    .timeline-content h4 {
        margin-bottom: 0.5rem;
        color: #1f2937;
    }

    .timeline-date {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .timeline-status {
        padding-top: 0.25rem;
    }

    .achievements-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        padding: 1.5rem;
    }

    .achievement-item {
        text-align: center;
        padding: 1.5rem;
        border-radius: 12px;
        border: 2px solid #e5e7eb;
        transition: all 0.3s ease;
    }

    .achievement-item.earned {
        border-color: #10b981;
        background: rgba(16, 185, 129, 0.05);
    }

    .achievement-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .achievement-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.5rem;
        background: #f3f4f6;
        color: #6b7280;
    }

    .achievement-item.earned .achievement-icon {
        background: #10b981;
        color: white;
    }

    .progress-bar {
        width: 100%;
        height: 8px;
        background: #e5e7eb;
        border-radius: 4px;
        margin: 1rem 0 0.5rem;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #dc2626, #ef4444);
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .progress-text {
        font-size: 0.75rem;
        color: #6b7280;
        font-weight: 600;
    }

    .modal-content {
        background: white;
        border-radius: 16px;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    }

    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.25rem;
        cursor: pointer;
        color: #6b7280;
    }

    .modal-body {
        padding: 1.5rem;
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
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #dc2626;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
    }

    .modal-footer {
        padding: 1.5rem;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    @media (max-width: 768px) {
        .profile-header {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }

        .donation-status {
            margin-left: 0;
        }

        .emergency-item {
            flex-direction: column;
        }

        .emergency-time {
            text-align: left;
        }

        .achievements-grid {
            grid-template-columns: 1fr;
        }
    }
`;

// Add styles to head
const styleSheet = document.createElement('style');
styleSheet.textContent = donorStyles;
document.head.appendChild(styleSheet);