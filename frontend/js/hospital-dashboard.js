// Hospital Dashboard Specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeHospitalDashboard();
});

function initializeHospitalDashboard() {
    // Initialize real-time stock monitoring
    initializeStockMonitoring();
    
    // Initialize approval system
    initializeApprovalSystem();
    
    // Initialize alert system
    initializeAlertSystem();
    
    // Initialize inventory management
    initializeInventoryManagement();
    
    // Initialize blood request system
    initializeBloodRequestSystem();
    
    // Initialize tab system
    initializeTabSystem();
}

// Stock Monitoring
function initializeStockMonitoring() {
    // Simulate real-time stock updates
    setInterval(() => {
        updateStockLevels();
    }, 30000); // Update every 30 seconds

    // Initialize stock level animations
    animateStockCards();
}

function updateStockLevels() {
    const stockCards = document.querySelectorAll('.blood-inventory-card');
    
    stockCards.forEach(card => {
        const stockNumber = card.querySelector('.stock-number');
        const currentStock = parseInt(stockNumber.textContent);
        
        // Simulate stock changes (±1-3 units)
        const change = Math.floor(Math.random() * 7) - 3; // -3 to +3
        const newStock = Math.max(0, currentStock + change);
        
        if (change !== 0) {
            // Animate the change
            stockNumber.style.transform = 'scale(1.1)';
            stockNumber.style.color = change > 0 ? '#10b981' : '#ef4444';
            
            setTimeout(() => {
                stockNumber.textContent = newStock;
                updateStockStatus(card, newStock);
                
                setTimeout(() => {
                    stockNumber.style.transform = 'scale(1)';
                    stockNumber.style.color = '';
                }, 300);
            }, 200);
        }
    });
}

function updateStockStatus(card, stockLevel) {
    const statusElement = card.querySelector('.stock-status');
    const bloodType = card.getAttribute('data-type');
    
    // Update status based on stock level
    if (stockLevel <= 5) {
        statusElement.className = 'stock-status critical';
        statusElement.textContent = 'Critical';
        
        // Create alert for critical stock
        createStockAlert(bloodType, stockLevel, 'critical');
    } else if (stockLevel <= 15) {
        statusElement.className = 'stock-status low';
        statusElement.textContent = 'Low Stock';
        
        if (stockLevel <= 10) {
            createStockAlert(bloodType, stockLevel, 'warning');
        }
    } else {
        statusElement.className = 'stock-status good';
        statusElement.textContent = 'Good Stock';
    }
}

function animateStockCards() {
    const cards = document.querySelectorAll('.blood-inventory-card');
    
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// Approval System
function initializeApprovalSystem() {
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-sm')) {
            const action = e.target.textContent.trim();
            const item = e.target.closest('.donation-item, .request-item');
            
            if (action === 'Approve') {
                handleDonationApproval(item, true);
            } else if (action === 'Reject') {
                handleDonationApproval(item, false);
            } else if (action === 'Fulfill') {
                handleBloodRequest(item, 'fulfill');
            } else if (action === 'Transfer') {
                handleBloodRequest(item, 'transfer');
            }
        }
    });
}

function handleDonationApproval(item, approved) {
    const donorName = item.querySelector('h4').textContent;
    const bloodType = item.querySelector('p').textContent.split('•')[0].trim();
    
    // Animate item out
    item.style.transition = 'all 0.3s ease';
    item.style.opacity = '0.5';
    item.style.transform = 'translateX(20px)';
    
    setTimeout(() => {
        if (approved) {
            showNotification(`${donorName}'s ${bloodType} donation approved!`, 'success');
            // Update stock levels
            updateStockAfterDonation(bloodType, 1);
        } else {
            showNotification(`${donorName}'s donation rejected.`, 'info');
        }
        
        // Remove item from list
        item.remove();
        
        // Update pending count
        updatePendingCount('.pending-donations .badge', -1);
    }, 300);
}

function handleBloodRequest(item, action) {
    const requestType = item.querySelector('h4').textContent;
    const bloodInfo = item.querySelector('p').textContent;
    
    item.style.transition = 'all 0.3s ease';
    item.style.opacity = '0.5';
    
    setTimeout(() => {
        if (action === 'fulfill') {
            showNotification(`Blood request fulfilled: ${bloodInfo}`, 'success');
            // Update stock levels (decrease)
            const bloodType = bloodInfo.split('•')[0].trim();
            const units = parseInt(bloodInfo.split('•')[1].trim().split(' ')[0]);
            updateStockAfterRequest(bloodType, units);
        } else {
            showNotification(`Transfer initiated for: ${bloodInfo}`, 'info');
        }
        
        item.remove();
        updatePendingCount('.blood-requests .badge', -1);
    }, 300);
}

function updateStockAfterDonation(bloodType, units) {
    const card = document.querySelector(`[data-type="${bloodType}"]`);
    if (card) {
        const stockNumber = card.querySelector('.stock-number');
        const currentStock = parseInt(stockNumber.textContent);
        const newStock = currentStock + units;
        
        stockNumber.textContent = newStock;
        updateStockStatus(card, newStock);
        
        // Animate positive change
        stockNumber.style.color = '#10b981';
        stockNumber.style.transform = 'scale(1.1)';
        setTimeout(() => {
            stockNumber.style.color = '';
            stockNumber.style.transform = 'scale(1)';
        }, 500);
    }
}

function updateStockAfterRequest(bloodType, units) {
    const card = document.querySelector(`[data-type="${bloodType}"]`);
    if (card) {
        const stockNumber = card.querySelector('.stock-number');
        const currentStock = parseInt(stockNumber.textContent);
        const newStock = Math.max(0, currentStock - units);
        
        stockNumber.textContent = newStock;
        updateStockStatus(card, newStock);
        
        // Animate negative change
        stockNumber.style.color = '#ef4444';
        stockNumber.style.transform = 'scale(1.1)';
        setTimeout(() => {
            stockNumber.style.color = '';
            stockNumber.style.transform = 'scale(1)';
        }, 500);
    }
}

function updatePendingCount(selector, change) {
    const badge = document.querySelector(selector);
    if (badge) {
        const currentCount = parseInt(badge.textContent.match(/\d+/)[0]);
        const newCount = Math.max(0, currentCount + change);
        badge.textContent = badge.textContent.replace(/\d+/, newCount);
    }
}

// Alert System
function initializeAlertSystem() {
    // Handle alert actions
    document.addEventListener('click', function(e) {
        if (e.target.closest('.alert-actions .btn-sm')) {
            const btn = e.target.closest('.btn-sm');
            const action = btn.textContent.trim();
            const alertItem = btn.closest('.alert-item');
            
            if (action === 'Dismiss') {
                dismissAlert(alertItem);
            } else if (action === 'Request Blood') {
                showBloodRequestModal();
            } else if (action === 'Use First') {
                showNotification('Marked for priority use', 'info');
                dismissAlert(alertItem);
            }
        }
    });

    // Simulate new alerts
    setInterval(() => {
        if (Math.random() < 0.2) { // 20% chance every minute
            addNewAlert();
        }
    }, 60000);
}

function dismissAlert(alertItem) {
    alertItem.style.transition = 'all 0.3s ease';
    alertItem.style.opacity = '0';
    alertItem.style.transform = 'translateX(100%)';
    
    setTimeout(() => {
        alertItem.remove();
    }, 300);
}

function createStockAlert(bloodType, stockLevel, severity) {
    const alertsList = document.querySelector('.alerts-list');
    if (!alertsList) return;

    // Check if alert already exists for this blood type
    const existingAlert = alertsList.querySelector(`[data-blood-type="${bloodType}"]`);
    if (existingAlert) return;

    const alertItem = document.createElement('div');
    alertItem.className = `alert-item ${severity}`;
    alertItem.setAttribute('data-blood-type', bloodType);
    
    alertItem.innerHTML = `
        <div class="alert-icon">
            <i class="fas fa-${severity === 'critical' ? 'exclamation-circle' : 'exclamation-triangle'}"></i>
        </div>
        <div class="alert-content">
            <h4>${severity === 'critical' ? 'Critical' : 'Low'} Stock Level</h4>
            <p>${bloodType} blood type has only ${stockLevel} units remaining</p>
            <span class="alert-time">Just now</span>
        </div>
        <div class="alert-actions">
            <button class="btn-sm danger">Request Blood</button>
            <button class="btn-sm secondary">Dismiss</button>
        </div>
    `;

    // Insert at the beginning
    alertsList.insertBefore(alertItem, alertsList.firstChild);

    // Animate in
    alertItem.style.opacity = '0';
    alertItem.style.transform = 'translateX(-100%)';
    setTimeout(() => {
        alertItem.style.transition = 'all 0.5s ease';
        alertItem.style.opacity = '1';
        alertItem.style.transform = 'translateX(0)';
    }, 100);
}

function addNewAlert() {
    const alerts = [
        {
            type: 'info',
            icon: 'info-circle',
            title: 'Donation Scheduled',
            message: 'New donation appointment scheduled for tomorrow',
            time: 'Just now'
        },
        {
            type: 'warning',
            icon: 'clock',
            title: 'Maintenance Reminder',
            message: 'Blood storage unit maintenance due next week',
            time: 'Just now'
        }
    ];

    const randomAlert = alerts[Math.floor(Math.random() * alerts.length)];
    const alertsList = document.querySelector('.alerts-list');
    
    const alertItem = document.createElement('div');
    alertItem.className = `alert-item ${randomAlert.type}`;
    
    alertItem.innerHTML = `
        <div class="alert-icon">
            <i class="fas fa-${randomAlert.icon}"></i>
        </div>
        <div class="alert-content">
            <h4>${randomAlert.title}</h4>
            <p>${randomAlert.message}</p>
            <span class="alert-time">${randomAlert.time}</span>
        </div>
        <div class="alert-actions">
            <button class="btn-sm secondary">View Details</button>
        </div>
    `;

    alertsList.insertBefore(alertItem, alertsList.firstChild);

    // Animate in
    alertItem.style.opacity = '0';
    alertItem.style.transform = 'translateX(-100%)';
    setTimeout(() => {
        alertItem.style.transition = 'all 0.5s ease';
        alertItem.style.opacity = '1';
        alertItem.style.transform = 'translateX(0)';
    }, 100);

    // Remove oldest alerts if more than 5
    const items = alertsList.querySelectorAll('.alert-item');
    if (items.length > 5) {
        items[items.length - 1].remove();
    }
}

// Inventory Management
function initializeInventoryManagement() {
    // Handle inventory actions
    document.addEventListener('click', function(e) {
        if (e.target.closest('.stock-actions .btn-sm')) {
            const btn = e.target.closest('.btn-sm');
            const action = btn.textContent.trim();
            const card = btn.closest('.blood-inventory-card');
            const bloodType = card.getAttribute('data-type');
            
            if (action === 'Update') {
                showUpdateStockModal(bloodType);
            } else if (action === 'History') {
                showStockHistory(bloodType);
            } else if (action === 'Request') {
                showBloodRequestModal(bloodType);
            }
        }
    });
}

// Add Blood Modal
function showAddBloodModal() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Blood Units</h3>
                <button class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form class="add-blood-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Blood Type</label>
                            <select class="form-control" name="bloodType">
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
                            <label>Number of Units</label>
                            <input type="number" class="form-control" name="units" min="1" max="50" value="1">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Collection Date</label>
                            <input type="date" class="form-control" name="collectionDate" value="${new Date().toISOString().split('T')[0]}">
                        </div>
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="date" class="form-control" name="expiryDate">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Donor ID (Optional)</label>
                        <input type="text" class="form-control" name="donorId" placeholder="Enter donor ID">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Additional notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-cancel">Cancel</button>
                <button class="btn btn-primary modal-confirm">Add Blood Units</button>
            </div>
        </div>
    `;

    showModal(modal, function() {
        const form = modal.querySelector('.add-blood-form');
        const formData = new FormData(form);
        const bloodType = formData.get('bloodType');
        const units = parseInt(formData.get('units'));
        
        // Update stock
        updateStockAfterDonation(bloodType, units);
        showNotification(`Added ${units} units of ${bloodType} blood to inventory`, 'success');
    });
}

function showUpdateStockModal(bloodType) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update ${bloodType} Stock</h3>
                <button class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form class="update-stock-form">
                    <div class="form-group">
                        <label>Action</label>
                        <select class="form-control" name="action">
                            <option value="add">Add Units</option>
                            <option value="remove">Remove Units</option>
                            <option value="set">Set Total</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Number of Units</label>
                        <input type="number" class="form-control" name="units" min="1" value="1">
                    </div>
                    <div class="form-group">
                        <label>Reason</label>
                        <select class="form-control" name="reason">
                            <option value="donation">New Donation</option>
                            <option value="usage">Used for Patient</option>
                            <option value="expired">Expired Units</option>
                            <option value="transfer">Transfer</option>
                            <option value="correction">Inventory Correction</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Additional notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-cancel">Cancel</button>
                <button class="btn btn-primary modal-confirm">Update Stock</button>
            </div>
        </div>
    `;

    showModal(modal, function() {
        const form = modal.querySelector('.update-stock-form');
        const formData = new FormData(form);
        const action = formData.get('action');
        const units = parseInt(formData.get('units'));
        const reason = formData.get('reason');
        
        const card = document.querySelector(`[data-type="${bloodType}"]`);
        const stockNumber = card.querySelector('.stock-number');
        const currentStock = parseInt(stockNumber.textContent);
        
        let newStock;
        if (action === 'add') {
            newStock = currentStock + units;
        } else if (action === 'remove') {
            newStock = Math.max(0, currentStock - units);
        } else {
            newStock = units;
        }
        
        stockNumber.textContent = newStock;
        updateStockStatus(card, newStock);
        
        showNotification(`${bloodType} stock updated: ${reason}`, 'success');
    });
}

function showBloodRequestModal(bloodType = '') {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Request Blood from Network</h3>
                <button class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form class="request-blood-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Blood Type</label>
                            <select class="form-control" name="bloodType">
                                <option value="A+" ${bloodType === 'A+' ? 'selected' : ''}>A+</option>
                                <option value="A-" ${bloodType === 'A-' ? 'selected' : ''}>A-</option>
                                <option value="B+" ${bloodType === 'B+' ? 'selected' : ''}>B+</option>
                                <option value="B-" ${bloodType === 'B-' ? 'selected' : ''}>B-</option>
                                <option value="AB+" ${bloodType === 'AB+' ? 'selected' : ''}>AB+</option>
                                <option value="AB-" ${bloodType === 'AB-' ? 'selected' : ''}>AB-</option>
                                <option value="O+" ${bloodType === 'O+' ? 'selected' : ''}>O+</option>
                                <option value="O-" ${bloodType === 'O-' ? 'selected' : ''}>O-</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Units Needed</label>
                            <input type="number" class="form-control" name="units" min="1" max="20" value="1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Priority Level</label>
                        <select class="form-control" name="priority">
                            <option value="routine">Routine</option>
                            <option value="urgent">Urgent</option>
                            <option value="emergency">Emergency</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reason for Request</label>
                        <textarea class="form-control" name="reason" rows="3" placeholder="Describe the medical need..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Expected Delivery</label>
                        <select class="form-control" name="delivery">
                            <option value="immediate">Immediate (Emergency)</option>
                            <option value="today">Within Today</option>
                            <option value="tomorrow">Tomorrow</option>
                            <option value="week">Within a Week</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-cancel">Cancel</button>
                <button class="btn btn-danger modal-confirm">Send Request</button>
            </div>
        </div>
    `;

    showModal(modal, function() {
        const form = modal.querySelector('.request-blood-form');
        const formData = new FormData(form);
        const requestBloodType = formData.get('bloodType');
        const units = formData.get('units');
        const priority = formData.get('priority');
        
        showNotification(`Blood request sent: ${units} units of ${requestBloodType} (${priority})`, 'success');
    });
}

function showModal(modal, onConfirm) {
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
        } else if (e.target.classList.contains('modal-confirm')) {
            onConfirm();
            closeModal();
        }
    });

    function closeModal() {
        modal.style.opacity = '0';
        setTimeout(() => modal.remove(), 300);
    }
}

// Add hospital-specific styles
const hospitalStyles = `
    .hospital-info-card {
        background: linear-gradient(135deg, #1f2937, #374151);
        color: white;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 20px 40px rgba(31, 41, 55, 0.2);
    }

    .hospital-header {
        display: flex;
        align-items: center;
        gap: 2rem;
    }

    .hospital-avatar {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
    }

    .hospital-details h2 {
        margin-bottom: 0.5rem;
        font-size: 1.75rem;
    }

    .hospital-status {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #10b981;
        animation: pulse 2s infinite;
    }

    .hospital-actions {
        margin-left: auto;
    }

    .inventory-section, .approvals-requests-grid, .alerts-section {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        margin-bottom: 2rem;
        border: 1px solid #e5e7eb;
    }

    .section-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .section-actions {
        display: flex;
        gap: 1rem;
    }

    .inventory-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        padding: 1.5rem;
    }

    .blood-inventory-card {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.5rem;
        transition: all 0.3s ease;
    }

    .blood-inventory-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .blood-type-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .blood-type-icon {
        width: 50px;
        height: 50px;
        background: #dc2626;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
    }

    .stock-status {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .stock-status.good {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }

    .stock-status.low {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
    }

    .stock-status.critical {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }

    .stock-details {
        text-align: center;
        margin-bottom: 1rem;
    }

    .stock-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1f2937;
        transition: all 0.3s ease;
    }

    .stock-label {
        color: #6b7280;
        font-size: 0.875rem;
    }

    .stock-info {
        margin-bottom: 1rem;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }

    .info-item .warning {
        color: #f59e0b;
        font-weight: 600;
    }

    .info-item .critical {
        color: #ef4444;
        font-weight: 600;
    }

    .stock-actions {
        display: flex;
        gap: 0.5rem;
    }

    .approvals-requests-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
    }

    .pending-donations, .blood-requests {
        padding: 0;
    }

    .pending-donations {
        border-right: 1px solid #e5e7eb;
    }

    .donations-list, .requests-list {
        padding: 1.5rem;
        max-height: 400px;
        overflow-y: auto;
    }

    .donation-item, .request-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .donation-item:hover, .request-item:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .request-item.urgent {
        border-color: #ef4444;
        background: rgba(239, 68, 68, 0.02);
    }

    .donor-info, .request-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .donor-avatar {
        width: 40px;
        height: 40px;
        background: #dc2626;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .request-icon {
        width: 40px;
        height: 40px;
        background: #fee2e2;
        color: #dc2626;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .request-item.urgent .request-icon {
        background: #fef2f2;
        color: #ef4444;
        animation: pulse 2s infinite;
    }

    .donor-details h4, .request-details h4 {
        margin-bottom: 0.25rem;
        font-size: 1rem;
    }

    .donor-details p, .request-details p {
        color: #6b7280;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }

    .time {
        font-size: 0.75rem;
        color: #9ca3af;
    }

    .donation-actions, .request-actions {
        display: flex;
        gap: 0.5rem;
    }

    .alerts-list {
        padding: 1.5rem;
        max-height: 400px;
        overflow-y: auto;
    }

    .alert-item {
        display: flex;
        gap: 1rem;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        border: 1px solid;
        transition: all 0.3s ease;
    }

    .alert-item.critical {
        border-color: #ef4444;
        background: rgba(239, 68, 68, 0.02);
    }

    .alert-item.warning {
        border-color: #f59e0b;
        background: rgba(245, 158, 11, 0.02);
    }

    .alert-item.info {
        border-color: #3b82f6;
        background: rgba(59, 130, 246, 0.02);
    }

    .alert-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .alert-item.critical .alert-icon {
        background: #fef2f2;
        color: #ef4444;
    }

    .alert-item.warning .alert-icon {
        background: #fffbeb;
        color: #f59e0b;
    }

    .alert-item.info .alert-icon {
        background: #eff6ff;
        color: #3b82f6;
    }

    .alert-content {
        flex: 1;
    }

    .alert-content h4 {
        margin-bottom: 0.5rem;
        font-size: 1rem;
    }

    .alert-time {
        font-size: 0.75rem;
        color: #9ca3af;
    }

    .alert-actions {
        display: flex;
        gap: 0.5rem;
        align-items: flex-start;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    @media (max-width: 1024px) {
        .inventory-grid {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }

        .approvals-requests-grid {
            grid-template-columns: 1fr;
        }

        .pending-donations {
            border-right: none;
            border-bottom: 1px solid #e5e7eb;
        }
    }

    @media (max-width: 768px) {
        .hospital-header {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }

        .hospital-actions {
            margin-left: 0;
        }

        .section-header {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }

        .section-actions {
            justify-content: center;
        }

        .inventory-grid {
            grid-template-columns: 1fr;
        }

        .donation-item, .request-item {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }

        .donation-actions, .request-actions {
            justify-content: center;
        }

        .form-row {
            grid-template-columns: 1fr;
        }
    }
`;

// Add styles to head
const styleSheet = document.createElement('style');
styleSheet.textContent = hospitalStyles;
document.head.appendChild(styleSheet);

// Blood Request System
function initializeBloodRequestSystem() {
    // Handle blood request actions
    document.addEventListener('click', function(e) {
        if (e.target.closest('.stock-actions .btn-sm')) {
            const btn = e.target.closest('.btn-sm');
            const action = btn.textContent.trim();
            
            if (action === 'Request More') {
                const card = btn.closest('.blood-inventory-card');
                const bloodType = card.getAttribute('data-type');
                showBloodRequestModal(bloodType);
            }
        }
    });

    // Simulate incoming requests
    setInterval(() => {
        if (Math.random() < 0.2) { // 20% chance every 2 minutes
            addNewIncomingRequest();
        }
    }, 120000);
}

// Tab System
function initializeTabSystem() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            document.getElementById(targetTab + '-tab').classList.add('active');
        });
    });
}

// Blood Request Modal
function showBloodRequestModal(bloodType = '') {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Request Blood from Network</h3>
                <button class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="request-form-container">
                    <div class="form-section">
                        <h4>Blood Requirements</h4>
                        <form class="blood-request-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Blood Type Needed</label>
                                    <select class="form-control" name="bloodType" required>
                                        <option value="">Select Blood Type</option>
                                        <option value="A+" ${bloodType === 'A+' ? 'selected' : ''}>A+</option>
                                        <option value="A-" ${bloodType === 'A-' ? 'selected' : ''}>A-</option>
                                        <option value="B+" ${bloodType === 'B+' ? 'selected' : ''}>B+</option>
                                        <option value="B-" ${bloodType === 'B-' ? 'selected' : ''}>B-</option>
                                        <option value="AB+" ${bloodType === 'AB+' ? 'selected' : ''}>AB+</option>
                                        <option value="AB-" ${bloodType === 'AB-' ? 'selected' : ''}>AB-</option>
                                        <option value="O+" ${bloodType === 'O+' ? 'selected' : ''}>O+</option>
                                        <option value="O-" ${bloodType === 'O-' ? 'selected' : ''}>O-</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Units Required</label>
                                    <input type="number" class="form-control" name="units" min="1" max="20" value="1" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Priority Level</label>
                                    <select class="form-control" name="priority" required>
                                        <option value="normal">Normal</option>
                                        <option value="urgent">Urgent</option>
                                        <option value="emergency">Emergency</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Preferred Source</label>
                                    <select class="form-control" name="source">
                                        <option value="any">Any Available Hospital</option>
                                        <option value="central">Central Blood Bank</option>
                                        <option value="metro">Metro Medical Center</option>
                                        <option value="regional">Regional Hospital</option>
                                        <option value="community">Community Health Center</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Medical Reason</label>
                                <textarea class="form-control" name="reason" rows="3" placeholder="Describe the medical need for this blood request..." required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Expected Delivery Time</label>
                                <select class="form-control" name="delivery">
                                    <option value="immediate">Immediate (Emergency)</option>
                                    <option value="2hours">Within 2 Hours</option>
                                    <option value="6hours">Within 6 Hours</option>
                                    <option value="24hours">Within 24 Hours</option>
                                    <option value="48hours">Within 48 Hours</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Contact Person</label>
                                <input type="text" class="form-control" name="contact" placeholder="Doctor/Staff name and contact number">
                            </div>
                        </form>
                    </div>
                    
                    <div class="availability-section">
                        <h4>Network Availability</h4>
                        <div class="availability-list">
                            <div class="availability-item">
                                <div class="hospital-info">
                                    <i class="fas fa-hospital"></i>
                                    <span>Central Blood Bank</span>
                                </div>
                                <div class="blood-availability">
                                    <span class="available">A+: 25</span>
                                    <span class="available">O+: 18</span>
                                    <span class="low">AB-: 3</span>
                                </div>
                                <div class="distance">2.1 km</div>
                            </div>
                            <div class="availability-item">
                                <div class="hospital-info">
                                    <i class="fas fa-hospital"></i>
                                    <span>Metro Medical Center</span>
                                </div>
                                <div class="blood-availability">
                                    <span class="available">A+: 12</span>
                                    <span class="available">O+: 8</span>
                                    <span class="unavailable">AB-: 0</span>
                                </div>
                                <div class="distance">4.5 km</div>
                            </div>
                            <div class="availability-item">
                                <div class="hospital-info">
                                    <i class="fas fa-hospital"></i>
                                    <span>Regional Hospital</span>
                                </div>
                                <div class="blood-availability">
                                    <span class="available">A+: 8</span>
                                    <span class="low">O+: 4</span>
                                    <span class="unavailable">AB-: 0</span>
                                </div>
                                <div class="distance">6.8 km</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-cancel">Cancel</button>
                <button class="btn btn-danger btn-lg" onclick="submitBloodRequest()">
                    <i class="fas fa-paper-plane"></i>
                    Submit Blood Request
                </button>
            </div>
        </div>
    `;

    showModal(modal);
}

function submitBloodRequest() {
    const form = document.querySelector('.blood-request-form');
    const formData = new FormData(form);
    
    // Validate form
    if (!formData.get('bloodType') || !formData.get('units') || !formData.get('reason')) {
        showNotification('Please fill in all required fields', 'error');
        return;
    }

    const requestData = {
        id: 'REQ-' + Date.now(),
        bloodType: formData.get('bloodType'),
        units: parseInt(formData.get('units')),
        priority: formData.get('priority'),
        source: formData.get('source'),
        reason: formData.get('reason'),
        delivery: formData.get('delivery'),
        contact: formData.get('contact'),
        timestamp: new Date().toISOString(),
        status: 'pending'
    };

    // Add to outgoing requests
    addOutgoingRequest(requestData);
    
    // Close modal
    closeModal();
    
    // Show success notification
    showNotification(`Blood request submitted successfully! Request ID: ${requestData.id}`, 'success');
    
    // Simulate network notification to other hospitals
    setTimeout(() => {
        showNotification('Request sent to network hospitals and blood banks', 'info');
    }, 2000);
}

function addOutgoingRequest(requestData) {
    const outgoingTab = document.getElementById('outgoing-tab');
    const requestList = outgoingTab.querySelector('.request-list');
    
    const requestItem = document.createElement('div');
    requestItem.className = 'request-item outgoing';
    requestItem.innerHTML = `
        <div class="request-info">
            <div class="request-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="request-details">
                <h4>${requestData.bloodType} Blood Request</h4>
                <p><strong>To: ${getSourceName(requestData.source)} • ${requestData.units} units</strong></p>
                <p>${requestData.reason}</p>
                <span class="request-time">Just now</span>
            </div>
        </div>
        <div class="request-status">
            <span class="status-badge pending">Pending Approval</span>
        </div>
        <div class="request-actions">
            <button class="btn-sm info" onclick="trackRequest('${requestData.id}')">
                <i class="fas fa-search"></i>
                Track
            </button>
            <button class="btn-sm warning" onclick="editRequest('${requestData.id}')">
                <i class="fas fa-edit"></i>
                Edit
            </button>
            <button class="btn-sm danger" onclick="cancelRequest('${requestData.id}')">
                <i class="fas fa-times"></i>
                Cancel
            </button>
        </div>
    `;
    
    // Insert at the beginning
    requestList.insertBefore(requestItem, requestList.firstChild);
    
    // Update tab badge
    updateTabBadge('outgoing', 1);
}

function getSourceName(source) {
    const sourceNames = {
        'any': 'Network Hospitals',
        'central': 'Central Blood Bank',
        'metro': 'Metro Medical Center',
        'regional': 'Regional Hospital',
        'community': 'Community Health Center'
    };
    return sourceNames[source] || 'Network Hospitals';
}

// Incoming Request Handlers
function approveBloodRequest(requestId, hospitalName, bloodType, units) {
    showConfirmationModal(
        'Approve Blood Transfer',
        `Are you sure you want to approve and transfer ${units} units of ${bloodType} blood to ${hospitalName}?`,
        () => {
            // Update stock
            updateStockAfterRequest(bloodType, units);
            
            // Remove from incoming requests
            removeIncomingRequest(requestId);
            
            // Show success notification
            showNotification(`Blood transfer approved! ${units} units of ${bloodType} sent to ${hospitalName}`, 'success');
            
            // Update tab badge
            updateTabBadge('incoming', -1);
        }
    );
}

function rejectBloodRequest(requestId) {
    showConfirmationModal(
        'Reject Blood Request',
        'Are you sure you want to reject this blood request?',
        () => {
            removeIncomingRequest(requestId);
            showNotification('Blood request rejected', 'info');
            updateTabBadge('incoming', -1);
        }
    );
}

function removeIncomingRequest(requestId) {
    const requestItems = document.querySelectorAll('.request-item.incoming');
    requestItems.forEach(item => {
        // In a real app, you'd match by actual request ID
        // For demo, we'll remove the first matching item
        if (item.querySelector('.request-actions button').getAttribute('onclick').includes(requestId)) {
            item.remove();
            return;
        }
    });
}

function viewRequestDetails(requestId) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Blood Request Details - ${requestId}</h3>
                <button class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="request-details-grid">
                    <div class="detail-section">
                        <h4>Request Information</h4>
                        <div class="detail-item">
                            <label>Request ID:</label>
                            <span>${requestId}</span>
                        </div>
                        <div class="detail-item">
                            <label>Blood Type:</label>
                            <span class="blood-type-badge">O-</span>
                        </div>
                        <div class="detail-item">
                            <label>Units Required:</label>
                            <span>5 units</span>
                        </div>
                        <div class="detail-item">
                            <label>Priority:</label>
                            <span class="priority-badge emergency">Emergency</span>
                        </div>
                        <div class="detail-item">
                            <label>Requested:</label>
                            <span>15 minutes ago</span>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Hospital Information</h4>
                        <div class="detail-item">
                            <label>Hospital:</label>
                            <span>Metro Medical Center</span>
                        </div>
                        <div class="detail-item">
                            <label>Contact Person:</label>
                            <span>Dr. Sarah Johnson</span>
                        </div>
                        <div class="detail-item">
                            <label>Phone:</label>
                            <span>+1 (555) 987-6543</span>
                        </div>
                        <div class="detail-item">
                            <label>Distance:</label>
                            <span>4.5 km away</span>
                        </div>
                    </div>
                </div>
                
                <div class="medical-reason">
                    <h4>Medical Reason</h4>
                    <p>Multiple trauma patients from vehicle accident. Critical need for O- blood for immediate transfusion. Patients are in critical condition and require immediate blood supply.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-close">Close</button>
                <button class="btn btn-success" onclick="approveBloodRequest('${requestId}', 'Metro Medical Center', 'O-', 5); closeModal();">
                    <i class="fas fa-check"></i>
                    Approve Transfer
                </button>
            </div>
        </div>
    `;
    
    showModal(modal);
}

// Outgoing Request Handlers
function trackRequest(requestId) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Track Request - ${requestId}</h3>
                <button class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="tracking-timeline">
                    <div class="timeline-item completed">
                        <div class="timeline-marker">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="timeline-content">
                            <h4>Request Submitted</h4>
                            <p>Blood request sent to network hospitals</p>
                            <span class="timeline-time">30 minutes ago</span>
                        </div>
                    </div>
                    
                    <div class="timeline-item completed">
                        <div class="timeline-marker">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="timeline-content">
                            <h4>Availability Checked</h4>
                            <p>System found matching blood type at Central Blood Bank</p>
                            <span class="timeline-time">25 minutes ago</span>
                        </div>
                    </div>
                    
                    <div class="timeline-item active">
                        <div class="timeline-marker">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="timeline-content">
                            <h4>Pending Approval</h4>
                            <p>Waiting for approval from Central Blood Bank</p>
                            <span class="timeline-time">Current status</span>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-marker">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="timeline-content">
                            <h4>In Transit</h4>
                            <p>Blood units being transported</p>
                            <span class="timeline-time">Pending</span>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-marker">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="timeline-content">
                            <h4>Delivered</h4>
                            <p>Blood units received and verified</p>
                            <span class="timeline-time">Pending</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-close">Close</button>
                <button class="btn btn-primary">
                    <i class="fas fa-phone"></i>
                    Contact Supplier
                </button>
            </div>
        </div>
    `;
    
    showModal(modal);
}

function editRequest(requestId) {
    showNotification('Request editing functionality would be implemented here', 'info');
}

function cancelRequest(requestId) {
    showConfirmationModal(
        'Cancel Blood Request',
        'Are you sure you want to cancel this blood request?',
        () => {
            // Remove from outgoing requests
            const requestItems = document.querySelectorAll('.request-item.outgoing');
            requestItems.forEach(item => {
                if (item.querySelector('.request-actions button').getAttribute('onclick').includes(requestId)) {
                    item.remove();
                    return;
                }
            });
            
            showNotification('Blood request cancelled', 'info');
            updateTabBadge('outgoing', -1);
        }
    );
}

function confirmReceived(requestId) {
    showConfirmationModal(
        'Confirm Blood Received',
        'Confirm that you have received the blood units and they have been added to your inventory?',
        () => {
            // Update the request status
            const requestItem = document.querySelector(`[onclick*="${requestId}"]`).closest('.request-item');
            const statusBadge = requestItem.querySelector('.status-badge');
            statusBadge.className = 'status-badge completed';
            statusBadge.textContent = 'Completed';
            
            // Update the icon
            const icon = requestItem.querySelector('.request-icon');
            icon.className = 'request-icon completed';
            icon.innerHTML = '<i class="fas fa-check-circle"></i>';
            
            // Update actions
            const actions = requestItem.querySelector('.request-actions');
            actions.innerHTML = `
                <button class="btn-sm success" disabled>
                    <i class="fas fa-check-circle"></i>
                    Received
                </button>
                <button class="btn-sm info" onclick="downloadReceipt('${requestId}')">
                    <i class="fas fa-download"></i>
                    Receipt
                </button>
            `;
            
            showNotification('Blood units confirmed received and added to inventory', 'success');
        }
    );
}

function downloadReceipt(requestId) {
    showNotification(`Downloading receipt for ${requestId}...`, 'info');
    // Would generate and download PDF receipt
}

// Utility Functions
function addNewIncomingRequest() {
    const incomingTab = document.getElementById('incoming-tab');
    const requestList = incomingTab.querySelector('.request-list');
    
    const hospitals = ['Regional Hospital', 'Community Health Center', 'Metro Medical Center'];
    const bloodTypes = ['A+', 'B+', 'O+', 'AB+', 'A-', 'B-', 'O-', 'AB-'];
    const priorities = ['normal', 'urgent', 'emergency'];
    const reasons = [
        'Scheduled surgery preparation',
        'Emergency patient treatment',
        'Stock replenishment needed',
        'Critical patient care'
    ];
    
    const randomHospital = hospitals[Math.floor(Math.random() * hospitals.length)];
    const randomBloodType = bloodTypes[Math.floor(Math.random() * bloodTypes.length)];
    const randomPriority = priorities[Math.floor(Math.random() * priorities.length)];
    const randomReason = reasons[Math.floor(Math.random() * reasons.length)];
    const randomUnits = Math.floor(Math.random() * 5) + 1;
    const requestId = 'REQ-' + Date.now();
    
    const requestItem = document.createElement('div');
    requestItem.className = `request-item incoming ${randomPriority === 'emergency' ? 'urgent' : ''}`;
    requestItem.innerHTML = `
        <div class="request-info">
            <div class="hospital-icon">
                <i class="fas fa-hospital"></i>
            </div>
            <div class="request-details">
                <h4>${randomHospital}</h4>
                <p><strong>${randomBloodType} Blood • ${randomUnits} units</strong></p>
                <p>${randomReason}</p>
                <span class="request-time">Just now</span>
            </div>
        </div>
        <div class="request-priority">
            <span class="priority-badge ${randomPriority}">${randomPriority.charAt(0).toUpperCase() + randomPriority.slice(1)}</span>
        </div>
        <div class="request-actions">
            <button class="btn-sm success" onclick="approveBloodRequest('${requestId}', '${randomHospital}', '${randomBloodType}', ${randomUnits})">
                <i class="fas fa-check"></i>
                Approve & Transfer
            </button>
            <button class="btn-sm danger" onclick="rejectBloodRequest('${requestId}')">
                <i class="fas fa-times"></i>
                Reject
            </button>
            <button class="btn-sm info" onclick="viewRequestDetails('${requestId}')">
                <i class="fas fa-eye"></i>
                Details
            </button>
        </div>
    `;
    
    // Insert at the beginning
    requestList.insertBefore(requestItem, requestList.firstChild);
    
    // Animate in
    requestItem.style.opacity = '0';
    requestItem.style.transform = 'translateY(-20px)';
    setTimeout(() => {
        requestItem.style.transition = 'all 0.5s ease';
        requestItem.style.opacity = '1';
        requestItem.style.transform = 'translateY(0)';
    }, 100);
    
    // Update tab badge
    updateTabBadge('incoming', 1);
    
    // Show notification
    showNotification(`New ${randomPriority} blood request from ${randomHospital}`, randomPriority === 'emergency' ? 'error' : 'info');
}

function updateTabBadge(tabType, change) {
    const tab = document.querySelector(`[data-tab="${tabType}"]`);
    const badge = tab.querySelector('.tab-badge');
    const currentCount = parseInt(badge.textContent);
    const newCount = Math.max(0, currentCount + change);
    badge.textContent = newCount;
    
    if (newCount === 0) {
        badge.style.display = 'none';
    } else {
        badge.style.display = 'inline';
    }
}

// Add hospital blood request styles
const hospitalBloodRequestStyles = `
    .blood-request-management {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        margin-bottom: 2rem;
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    .request-tabs {
        display: flex;
        border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
    }

    .tab-btn {
        flex: 1;
        padding: 1rem 1.5rem;
        border: none;
        background: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-weight: 500;
        color: #6b7280;
        transition: all 0.3s ease;
        position: relative;
    }

    .tab-btn.active {
        color: #dc2626;
        background: white;
        border-bottom: 2px solid #dc2626;
    }

    .tab-btn:hover {
        background: rgba(220, 38, 38, 0.05);
        color: #dc2626;
    }

    .tab-badge {
        background: #dc2626;
        color: white;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 10px;
        font-weight: 600;
        min-width: 20px;
        text-align: center;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .request-list {
        padding: 1.5rem;
        max-height: 600px;
        overflow-y: auto;
    }

    .request-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.5rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
        background: white;
    }

    .request-item:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .request-item.urgent {
        border-color: #ef4444;
        background: rgba(239, 68, 68, 0.02);
    }

    .request-item.incoming .hospital-icon {
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

    .request-item.outgoing .request-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: white;
    }

    .request-icon.pending {
        background: #f59e0b;
    }

    .request-icon.approved {
        background: #10b981;
    }

    .request-icon.completed {
        background: #059669;
    }

    .request-info {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex: 1;
    }

    .request-details h4 {
        margin-bottom: 0.25rem;
        color: #1f2937;
        font-weight: 600;
    }

    .request-details p {
        margin-bottom: 0.25rem;
        color: #4b5563;
        font-size: 0.875rem;
    }

    .request-time {
        font-size: 0.75rem;
        color: #9ca3af;
    }

    .request-priority, .request-status {
        margin-right: 1rem;
    }

    .priority-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .priority-badge.normal {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
    }

    .priority-badge.urgent {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
    }

    .priority-badge.emergency {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        animation: pulse 2s infinite;
    }

    .status-badge.pending {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
    }

    .status-badge.approved {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }

    .status-badge.completed {
        background: rgba(5, 150, 105, 0.1);
        color: #059669;
    }

    .request-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .request-form-container {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
    }

    .form-section h4, .availability-section h4 {
        margin-bottom: 1rem;
        color: #1f2937;
        font-weight: 600;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 0.5rem;
    }

    .availability-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .availability-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #f9fafb;
    }

    .hospital-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
    }

    .hospital-info i {
        color: #3b82f6;
    }

    .blood-availability {
        display: flex;
        gap: 0.5rem;
        font-size: 0.875rem;
    }

    .blood-availability .available {
        color: #10b981;
        font-weight: 600;
    }

    .blood-availability .low {
        color: #f59e0b;
        font-weight: 600;
    }

    .blood-availability .unavailable {
        color: #ef4444;
        font-weight: 600;
    }

    .distance {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .request-details-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .medical-reason {
        background: #f9fafb;
        padding: 1rem;
        border-radius: 8px;
        border-left: 4px solid #dc2626;
    }

    .medical-reason h4 {
        margin-bottom: 0.5rem;
        color: #1f2937;
    }

    .tracking-timeline {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .timeline-item {
        display: flex;
        gap: 1rem;
        padding: 1rem 0;
        position: relative;
    }

    .timeline-item:not(:last-child)::after {
        content: '';
        position: absolute;
        left: 24px;
        top: 50px;
        width: 2px;
        height: calc(100% + 1rem);
        background: #e5e7eb;
    }

    .timeline-item.completed::after {
        background: #10b981;
    }

    .timeline-item.active::after {
        background: linear-gradient(to bottom, #10b981 0%, #e5e7eb 50%);
    }

    .timeline-marker {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: white;
        flex-shrink: 0;
        z-index: 1;
    }

    .timeline-item.completed .timeline-marker {
        background: #10b981;
    }

    .timeline-item.active .timeline-marker {
        background: #3b82f6;
        animation: pulse 2s infinite;
    }

    .timeline-item:not(.completed):not(.active) .timeline-marker {
        background: #d1d5db;
        color: #6b7280;
    }

    .timeline-content {
        flex: 1;
        padding-top: 0.5rem;
    }

    .timeline-content h4 {
        margin-bottom: 0.25rem;
        color: #1f2937;
        font-weight: 600;
    }

    .timeline-content p {
        color: #6b7280;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }

    .timeline-time {
        font-size: 0.75rem;
        color: #9ca3af;
    }

    @media (max-width: 1024px) {
        .request-form-container {
            grid-template-columns: 1fr;
        }

        .request-details-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .request-item {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }

        .request-info {
            flex-direction: column;
            align-items: stretch;
        }

        .request-actions {
            justify-content: center;
        }

        .tab-btn {
            flex-direction: column;
            gap: 0.25rem;
            font-size: 0.875rem;
        }
    }
`;

// Add styles to head
const styleSheet = document.createElement('style');
styleSheet.textContent = hospitalBloodRequestStyles;
document.head.appendChild(styleSheet);