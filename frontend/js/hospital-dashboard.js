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