<?php
session_start();
require_once '../../backend/config/database.php';

// Check if user is logged in and is a hospital
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'hospital') {
    header('Location: ../auth/login.php');
    exit();
}

// Get user and hospital data
try {
    $stmt = $conn->prepare("
        SELECT u.*, h.hospital_id, h.hospital_name, h.hospital_type, h.license_number,
               h.accreditation_level, h.bed_capacity, h.has_blood_bank, h.emergency_services,
               h.trauma_center_level, h.is_24_7, h.website, h.emergency_phone, h.blood_bank_phone,
               h.is_verified, h.is_active
        FROM users u 
        JOIN hospitals h ON u.id = h.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch();
    
    if (!$user_data) {
        header('Location: ../auth/login.php');
        exit();
    }
} catch (PDOException $e) {
    die("Error loading user data: " . $e->getMessage());
}

// Check if hospital is verified
if (!$user_data['is_verified']) {
    $verification_message = "Your hospital account is pending admin approval. You will have limited access until verified.";
}

// Handle blood request approval/rejection
if ($_POST && isset($_POST['action']) && isset($_POST['request_id'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    $notes = trim($_POST['notes'] ?? '');
    
    try {
        if ($action === 'approve') {
            $stmt = $conn->prepare("
                UPDATE blood_requests 
                SET status = 'approved', approved_by_user_id = ?, approved_at = NOW(), rejection_notes = ?
                WHERE id = ? AND assigned_hospital_id = (SELECT id FROM hospitals WHERE user_id = ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $notes, $request_id, $_SESSION['user_id']]);
            $success_message = "Blood request approved successfully!";
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("
                UPDATE blood_requests 
                SET status = 'rejected', rejected_by_user_id = ?, rejected_at = NOW(), rejection_reason = ?, rejection_notes = ?
                WHERE id = ? AND assigned_hospital_id = (SELECT id FROM hospitals WHERE user_id = ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $notes, $notes, $request_id, $_SESSION['user_id']]);
            $success_message = "Blood request rejected.";
        } elseif ($action === 'complete') {
            $stmt = $conn->prepare("
                UPDATE blood_requests 
                SET status = 'completed', completed_at = NOW(), rejection_notes = ?
                WHERE id = ? AND assigned_hospital_id = (SELECT id FROM hospitals WHERE user_id = ?) AND status = 'approved'
            ");
            $stmt->execute([$notes, $request_id, $_SESSION['user_id']]);
            $success_message = "Blood request marked as completed!";
        }
    } catch (PDOException $e) {
        $error_message = "Failed to update request status: " . $e->getMessage();
    }
}

// Handle donation offer acceptance/rejection
if ($_POST && isset($_POST['offer_action']) && isset($_POST['offer_id'])) {
    $offer_id = (int)$_POST['offer_id'];
    $action = $_POST['offer_action'];
    $feedback_notes = trim($_POST['feedback_notes'] ?? '');
    
    try {
        if ($action === 'accept') {
            // Use only the columns that exist in your table
            $stmt = $conn->prepare("
                UPDATE donation_offers 
                SET status = 'accepted', accepted_by = ?, accepted_at = NOW(), notes = ?
                WHERE id = ? AND hospital_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $feedback_notes, $offer_id, $hospital_db_id]);
            $success_message = "Donation offer accepted successfully!";
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("
                UPDATE donation_offers 
                SET status = 'rejected', notes = ?
                WHERE id = ? AND hospital_id = ?
            ");
            $stmt->execute([$feedback_notes, $offer_id, $hospital_db_id]);
            $success_message = "Donation offer rejected.";
        } elseif ($action === 'complete') {
            $stmt = $conn->prepare("
                UPDATE donation_offers 
                SET status = 'completed', completed_at = NOW(), notes = ?
                WHERE id = ? AND hospital_id = ?
            ");
            $stmt->execute([$feedback_notes, $offer_id, $hospital_db_id]);
            $success_message = "Donation completed successfully!";
        } elseif ($action === 'delete') {
            // Allow hospitals to delete completed donations from their history
            $stmt = $conn->prepare("
                DELETE FROM donation_offers 
                WHERE id = ? AND hospital_id = ? AND status = 'completed'
            ");
            $result = $stmt->execute([$offer_id, $hospital_db_id]);
            
            if ($result && $stmt->rowCount() > 0) {
                $success_message = "Completed donation deleted from history.";
            } else {
                $error_message = "Cannot delete this donation. Only completed donations can be deleted.";
            }
        }
    } catch (PDOException $e) {
        $error_message = "Failed to update donation offer status: " . $e->getMessage();
    }
}

// Get hospital statistics
try {
    $hospital_id_stmt = $conn->prepare("SELECT id FROM hospitals WHERE user_id = ?");
    $hospital_id_stmt->execute([$_SESSION['user_id']]);
    $hospital_record = $hospital_id_stmt->fetch();
    $hospital_db_id = $hospital_record['id'];
    
    $stats_stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM blood_requests WHERE assigned_hospital_id = ?) as total_requests,
            (SELECT COUNT(*) FROM blood_requests WHERE assigned_hospital_id = ? AND status = 'pending') as pending_requests,
            (SELECT COUNT(*) FROM blood_requests WHERE assigned_hospital_id = ? AND status = 'approved') as approved_requests,
            (SELECT COUNT(*) FROM blood_requests WHERE assigned_hospital_id = ? AND status = 'completed') as completed_requests,
            (SELECT COUNT(*) FROM donation_offers WHERE hospital_id = ?) as total_offers,
            (SELECT COUNT(*) FROM donation_offers WHERE hospital_id = ? AND status = 'pending') as pending_offers
    ");
    $stats_stmt->execute([$hospital_db_id, $hospital_db_id, $hospital_db_id, $hospital_db_id, $hospital_db_id, $hospital_db_id]);
    $stats = $stats_stmt->fetch();
} catch (PDOException $e) {
    $stats = [
        'total_requests' => 0,
        'pending_requests' => 0,
        'approved_requests' => 0,
        'completed_requests' => 0,
        'total_offers' => 0,
        'pending_offers' => 0
    ];
    $hospital_db_id = null;
}

// Get recent blood requests
try {
    if ($hospital_db_id) {
        $requests_stmt = $conn->prepare("
            SELECT br.*, u.first_name, u.last_name, u.email, u.phone, p.patient_id, p.blood_type as patient_blood_type
            FROM blood_requests br
            JOIN patients p ON br.patient_id = p.id
            JOIN users u ON p.user_id = u.id
            WHERE br.assigned_hospital_id = ?
            ORDER BY br.created_at DESC
            LIMIT 10
        ");
        $requests_stmt->execute([$hospital_db_id]);
        $blood_requests = $requests_stmt->fetchAll();
    } else {
        $blood_requests = [];
    }
} catch (PDOException $e) {
    $blood_requests = [];
}

// Get recent donation offers
try {
    if ($hospital_db_id) {
        // Use the correct query for your donation_offers table structure
        $offers_stmt = $conn->prepare("
            SELECT do.*, u.first_name, u.last_name, u.email, u.phone
            FROM donation_offers do
            LEFT JOIN users u ON do.donor_id = u.id
            WHERE do.hospital_id = ?
            ORDER BY do.created_at DESC
            LIMIT 10
        ");
        $offers_stmt->execute([$hospital_db_id]);
        $donation_offers = $offers_stmt->fetchAll();
    } else {
        $donation_offers = [];
    }
} catch (PDOException $e) {
    $donation_offers = [];
}

// Get blood inventory
try {
    if ($hospital_db_id) {
        $inventory_stmt = $conn->prepare("
            SELECT * FROM blood_inventory 
            WHERE hospital_id = ?
            ORDER BY blood_type
        ");
        $inventory_stmt->execute([$hospital_db_id]);
        $blood_inventory = $inventory_stmt->fetchAll();
    } else {
        $blood_inventory = [];
    }
} catch (PDOException $e) {
    $blood_inventory = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Dashboard - BloodConnect</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">
    <!-- Dashboard Navigation -->
    <nav class="dashboard-nav">
        <div class="nav-brand">
            <i class="fas fa-tint"></i>
            <span>BloodConnect</span>
        </div>
        <div class="nav-user">
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($user_data['hospital_name']); ?></span>
                <span class="user-role">Hospital</span>
            </div>
            <div class="user-avatar">
                <i class="fas fa-hospital"></i>
            </div>
            <div class="nav-actions">
                <button class="btn-icon" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge"><?php echo $stats['pending_requests'] + $stats['pending_offers']; ?></span>
                </button>
                <button class="btn-icon" title="Settings">
                    <i class="fas fa-cog"></i>
                </button>
                <a href="../auth/logout.php" class="btn-icon" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="sidebar-menu">
                <div class="menu-section">
                    <h3>Dashboard</h3>
                    <a href="#overview" class="menu-item active">
                        <i class="fas fa-chart-pie"></i>
                        <span>Overview</span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <h3>Blood Management</h3>
                    <a href="#requests" class="menu-item">
                        <i class="fas fa-hand-holding-medical"></i>
                        <span>Blood Requests</span>
                    </a>
                    <a href="#offers" class="menu-item">
                        <i class="fas fa-heart"></i>
                        <span>Donation Offers</span>
                    </a>
                    <a href="#inventory" class="menu-item">
                        <i class="fas fa-boxes"></i>
                        <span>Blood Inventory</span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <h3>Hospital</h3>
                    <a href="#profile" class="menu-item">
                        <i class="fas fa-hospital"></i>
                        <span>Hospital Profile</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <?php if (isset($verification_message)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($verification_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Hospital Profile Card -->
            <div class="hospital-profile-card" id="overview">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-hospital"></i>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($user_data['hospital_name']); ?></h2>
                        <p>Hospital ID: <span class="hospital-id"><?php echo htmlspecialchars($user_data['hospital_id']); ?></span></p>
                        <div class="hospital-type-highlight">
                            <i class="fas fa-building"></i>
                            <span>Type: <span class="hospital-type"><?php echo ucfirst($user_data['hospital_type']); ?></span></span>
                        </div>
                    </div>
                    <div class="verification-status">
                        <?php if ($user_data['is_verified']): ?>
                            <div class="status-badge verified">
                                <i class="fas fa-check-circle"></i>
                                Verified
                            </div>
                        <?php else: ?>
                            <div class="status-badge pending">
                                <i class="fas fa-clock"></i>
                                Pending Approval
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon requests">
                            <i class="fas fa-hand-holding-medical"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['pending_requests']; ?></div>
                            <div class="stat-label">Pending Requests</div>
                            <div class="stat-sub"><?php echo $stats['total_requests']; ?> total</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon offers">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['pending_offers']; ?></div>
                            <div class="stat-label">Donation Offers</div>
                            <div class="stat-sub"><?php echo $stats['total_offers']; ?> total</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon completed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['completed_requests']; ?></div>
                            <div class="stat-label">Completed Requests</div>
                            <div class="stat-sub"><?php echo $stats['approved_requests']; ?> approved</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon inventory">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo count($blood_inventory); ?></div>
                            <div class="stat-label">Blood Types</div>
                            <div class="stat-sub">In inventory</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Blood Requests Management -->
            <div class="requests-management-section" id="requests">
                <div class="card-header">
                    <h3>Blood Requests</h3>
                    <span class="badge warning"><?php echo $stats['pending_requests']; ?> pending</span>
                </div>
                
                <?php if (empty($blood_requests)): ?>
                    <div class="no-requests">
                        <i class="fas fa-hand-holding-medical"></i>
                        <h4>No Blood Requests</h4>
                        <p>No blood requests have been submitted to your hospital yet.</p>
                    </div>
                <?php else: ?>
                    <div class="requests-list">
                        <?php foreach ($blood_requests as $request): ?>
                            <div class="request-card <?php echo $request['status']; ?>">
                                <div class="request-header">
                                    <div class="request-info">
                                        <h4><?php echo htmlspecialchars($request['blood_type']); ?> Blood Request</h4>
                                        <p><strong>Patient:</strong> <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></p>
                                        <p><strong>Request ID:</strong> <?php echo htmlspecialchars($request['request_id']); ?></p>
                                    </div>
                                    <div class="request-status <?php echo $request['status']; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </div>
                                </div>
                                
                                <div class="request-details">
                                    <div class="detail-row">
                                        <span><strong>Units Needed:</strong> <?php echo $request['units_requested']; ?></span>
                                        <span><strong>Priority:</strong> <?php echo ucfirst($request['priority']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span><strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></span>
                                        <span><strong>Contact:</strong> <?php echo htmlspecialchars($request['phone'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="medical-reason">
                                        <strong>Medical Reason:</strong> <?php echo htmlspecialchars($request['medical_reason']); ?>
                                    </div>
                                    <?php if ($request['rejection_notes']): ?>
                                        <div class="notes">
                                            <strong>Hospital Notes:</strong> <?php echo htmlspecialchars($request['rejection_notes']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($request['status'] === 'pending' && $user_data['is_verified']): ?>
                                    <div class="request-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="text" name="notes" placeholder="Add notes (optional)" style="margin-right: 10px; padding: 5px;">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="text" name="notes" placeholder="Reason for rejection" style="margin-right: 10px; padding: 5px;">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject this request?')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                    </div>
                                <?php elseif ($request['status'] === 'approved' && $user_data['is_verified']): ?>
                                    <div class="request-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <input type="hidden" name="action" value="complete">
                                            <input type="text" name="notes" placeholder="Completion notes (optional)" style="margin-right: 10px; padding: 5px;">
                                            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Mark this blood request as completed?')">
                                                <i class="fas fa-check-circle"></i> Mark Completed
                                            </button>
                                        </form>
                                    </div>
                                <?php elseif ($request['status'] === 'completed'): ?>
                                    <div class="completion-info">
                                        <i class="fas fa-check-circle text-success"></i>
                                        <span>Request completed on <?php echo date('M j, Y', strtotime($request['completed_at'])); ?></span>
                                    </div>
                                <?php elseif ($request['status'] === 'rejected'): ?>
                                    <div class="rejection-info">
                                        <i class="fas fa-times-circle text-danger"></i>
                                        <span>Request rejected</span>
                                        <?php if ($request['rejection_reason']): ?>
                                            <br><small>Reason: <?php echo htmlspecialchars($request['rejection_reason']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Donation Offers -->
            <div class="offers-management-section" id="offers">
                <div class="card-header">
                    <h3>Donation Offers</h3>
                    <span class="badge info"><?php echo $stats['pending_offers']; ?> pending</span>
                </div>
                
                <?php if (empty($donation_offers)): ?>
                    <div class="no-offers">
                        <i class="fas fa-heart"></i>
                        <h4>No Donation Offers</h4>
                        <p>No donation offers have been submitted to your hospital yet.</p>
                    </div>
                <?php else: ?>
                    <div class="offers-list">
                        <?php foreach ($donation_offers as $offer): ?>
                            <div class="offer-card <?php echo $offer['status']; ?>">
                                <div class="offer-header">
                                    <div class="offer-info">
                                        <h4><?php echo htmlspecialchars($offer['blood_type'] ?? 'Unknown'); ?> Blood Donation</h4>
                                        <p><strong>Donor:</strong> <?php echo htmlspecialchars($offer['first_name'] . ' ' . $offer['last_name']); ?></p>
                                        <p><strong>Offer ID:</strong> <?php echo htmlspecialchars($offer['offer_id']); ?></p>
                                    </div>
                                    <div class="offer-status <?php echo $offer['status']; ?>">
                                        <?php echo ucfirst($offer['status']); ?>
                                    </div>
                                </div>
                                
                                <div class="offer-details">
                                    <div class="detail-row">
                                        <span><strong>Preferred Date:</strong> <?php echo date('M j, Y', strtotime($offer['preferred_date'])); ?></span>
                                        <span><strong>Preferred Time:</strong> <?php echo date('g:i A', strtotime($offer['preferred_time'])); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span><strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($offer['created_at'])); ?></span>
                                        <span><strong>Contact:</strong> <?php echo htmlspecialchars($offer['phone'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span><strong>Email:</strong> <?php echo htmlspecialchars($offer['email'] ?? 'N/A'); ?></span>
                                        <span><strong>Blood Type:</strong> <span class="blood-type-badge"><?php echo htmlspecialchars($offer['blood_type'] ?? 'Unknown'); ?></span></span>
                                    </div>
                                    <?php if ($offer['notes']): ?>
                                        <div class="notes">
                                            <strong>Donor Notes:</strong> <?php echo htmlspecialchars($offer['notes']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($offer['accepted_at']): ?>
                                        <div class="hospital-feedback">
                                            <strong>Hospital Response:</strong> <?php echo date('M j, Y g:i A', strtotime($offer['accepted_at'])); ?>
                                            <?php if ($offer['notes']): ?>
                                                <br><strong>Feedback:</strong> <?php echo htmlspecialchars($offer['notes']); ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($user_data['is_verified']): ?>
                                    <div class="offer-actions">
                                        <?php if ($offer['status'] === 'pending'): ?>
                                            <!-- Accept Offer -->
                                            <form method="POST" style="display: inline-block; margin-right: 10px;">
                                                <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                                <input type="hidden" name="offer_action" value="accept">
                                                <div style="margin-bottom: 5px;">
                                                    <input type="text" name="feedback_notes" placeholder="Acceptance message (optional)" 
                                                           style="padding: 5px; width: 250px; border: 1px solid #ddd; border-radius: 3px;">
                                                </div>
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i> Accept Offer
                                                </button>
                                            </form>
                                            
                                            <!-- Reject Offer -->
                                            <form method="POST" style="display: inline-block;">
                                                <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                                <input type="hidden" name="offer_action" value="reject">
                                                <div style="margin-bottom: 5px;">
                                                    <input type="text" name="feedback_notes" placeholder="Reason for rejection" 
                                                           style="padding: 5px; width: 250px; border: 1px solid #ddd; border-radius: 3px;">
                                                </div>
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Are you sure you want to reject this donation offer?')">
                                                    <i class="fas fa-times"></i> Reject Offer
                                                </button>
                                            </form>
                                            
                                        <?php elseif ($offer['status'] === 'accepted'): ?>
                                            <!-- Mark as Completed -->
                                            <form method="POST" style="display: inline-block;">
                                                <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                                <input type="hidden" name="offer_action" value="complete">
                                                <div style="margin-bottom: 5px;">
                                                    <input type="text" name="feedback_notes" placeholder="Completion notes (optional)" 
                                                           style="padding: 5px; width: 250px; border: 1px solid #ddd; border-radius: 3px;">
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm" 
                                                        onclick="return confirm('Mark this donation as completed? This will update the donor records.')">
                                                    <i class="fas fa-check-circle"></i> Mark Completed
                                                </button>
                                            </form>
                                            
                                        <?php elseif ($offer['status'] === 'completed'): ?>
                                            <div class="completion-info">
                                                <i class="fas fa-check-circle text-success"></i>
                                                <span>Donation completed on <?php echo date('M j, Y', strtotime($offer['completed_at'])); ?></span>
                                            </div>
                                            
                                            <!-- Delete Completed Donation -->
                                            <form method="POST" style="display: inline-block; margin-top: 10px;">
                                                <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                                <input type="hidden" name="offer_action" value="delete">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm" 
                                                        onclick="return confirm('Are you sure you want to delete this completed donation from your records? This cannot be undone.')">
                                                    <i class="fas fa-trash"></i> Delete from History
                                                </button>
                                            </form>
                                            
                                        <?php elseif ($offer['status'] === 'rejected'): ?>
                                            <div class="rejection-info">
                                                <i class="fas fa-times-circle text-danger"></i>
                                                <span>Offer rejected</span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Contact Donor -->
                                        <?php if ($offer['phone']): ?>
                                            <a href="tel:<?php echo $offer['phone']; ?>" class="btn btn-secondary btn-sm" style="margin-left: 10px;">
                                                <i class="fas fa-phone"></i> Call Donor
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($offer['email']): ?>
                                            <a href="mailto:<?php echo $offer['email']; ?>" class="btn btn-secondary btn-sm" style="margin-left: 5px;">
                                                <i class="fas fa-envelope"></i> Email Donor
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="verification-required">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Hospital verification required to manage donation offers.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Blood Inventory -->
            <div class="inventory-section" id="inventory">
                <div class="card-header">
                    <h3>Blood Inventory</h3>
                    <p>Current blood stock levels</p>
                </div>
                
                <?php if (empty($blood_inventory)): ?>
                    <div class="no-inventory">
                        <i class="fas fa-boxes"></i>
                        <h4>No Inventory Data</h4>
                        <p>Blood inventory will be initialized when your hospital is verified.</p>
                    </div>
                <?php else: ?>
                    <div class="inventory-grid">
                        <?php foreach ($blood_inventory as $item): ?>
                            <div class="inventory-card">
                                <div class="blood-type-icon">
                                    <?php echo htmlspecialchars($item['blood_type']); ?>
                                </div>
                                <div class="inventory-info">
                                    <div class="units-available"><?php echo $item['units_available']; ?> units</div>
                                    <div class="stock-status <?php echo ($item['units_available'] <= $item['critical_stock_threshold']) ? 'critical' : (($item['units_available'] <= $item['low_stock_threshold']) ? 'low' : 'good'); ?>">
                                        <?php 
                                        if ($item['units_available'] <= $item['critical_stock_threshold']) {
                                            echo 'Critical';
                                        } elseif ($item['units_available'] <= $item['low_stock_threshold']) {
                                            echo 'Low Stock';
                                        } else {
                                            echo 'Good Stock';
                                        }
                                        ?>
                                    </div>
                                    <div class="last-updated">
                                        Updated: <?php echo date('M j, g:i A', strtotime($item['last_updated'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Hospital Profile -->
            <div class="hospital-profile-section" id="profile">
                <div class="card-header">
                    <h3>Hospital Information</h3>
                </div>
                <div class="profile-info-grid">
                    <div class="profile-section">
                        <h4>Basic Information</h4>
                        <div class="profile-item">
                            <span class="label">Hospital Name:</span>
                            <span class="value"><?php echo htmlspecialchars($user_data['hospital_name']); ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Hospital ID:</span>
                            <span class="value"><?php echo htmlspecialchars($user_data['hospital_id']); ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Type:</span>
                            <span class="value"><?php echo ucfirst($user_data['hospital_type']); ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="label">License Number:</span>
                            <span class="value"><?php echo htmlspecialchars($user_data['license_number']); ?></span>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h4>Contact Information</h4>
                        <div class="profile-item">
                            <span class="label">Email:</span>
                            <span class="value"><?php echo htmlspecialchars($user_data['email']); ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Phone:</span>
                            <span class="value"><?php echo htmlspecialchars($user_data['phone'] ?? 'Not provided'); ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Emergency Phone:</span>
                            <span class="value"><?php echo htmlspecialchars($user_data['emergency_phone'] ?? 'Not provided'); ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Address:</span>
                            <span class="value"><?php echo htmlspecialchars(($user_data['address'] ?? '') . ', ' . ($user_data['city'] ?? '') . ', ' . ($user_data['state'] ?? '')); ?></span>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h4>Facilities & Services</h4>
                        <div class="profile-item">
                            <span class="label">Bed Capacity:</span>
                            <span class="value"><?php echo htmlspecialchars($user_data['bed_capacity'] ?? 'Not specified'); ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Blood Bank:</span>
                            <span class="value"><?php echo $user_data['has_blood_bank'] ? 'Yes' : 'No'; ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Emergency Services:</span>
                            <span class="value"><?php echo $user_data['emergency_services'] ? 'Yes' : 'No'; ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="label">24/7 Operations:</span>
                            <span class="value"><?php echo $user_data['is_24_7'] ? 'Yes' : 'No'; ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Trauma Center:</span>
                            <span class="value"><?php echo ucfirst(str_replace('_', ' ', $user_data['trauma_center_level'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Simple navigation
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all items
                document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                
                // Add active class to clicked item
                this.classList.add('active');
                
                // Get target section
                const target = this.getAttribute('href');
                if (target && target.startsWith('#')) {
                    const section = document.querySelector(target);
                    if (section) {
                        section.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        });
    </script>
</body>
</html>