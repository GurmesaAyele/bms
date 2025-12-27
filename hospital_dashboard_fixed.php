<?php
session_start();
require_once 'backend/config/database.php';

// Check if user is logged in and is a hospital
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'hospital') {
    header('Location: frontend/auth/login.php');
    exit();
}

// Get user and hospital data
try {
    $stmt = $conn->prepare("
        SELECT u.*, h.id as hospital_db_id, h.hospital_name, h.hospital_type, h.license_number,
               h.is_verified, h.is_active
        FROM users u 
        LEFT JOIN hospitals h ON u.id = h.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch();
    
    if (!$user_data) {
        header('Location: frontend/auth/login.php');
        exit();
    }
} catch (PDOException $e) {
    die("Error loading user data: " . $e->getMessage());
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
                WHERE id = ? AND assigned_hospital_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $notes, $request_id, $user_data['hospital_db_id']]);
            $success_message = "Blood request approved successfully!";
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("
                UPDATE blood_requests 
                SET status = 'rejected', rejected_by_user_id = ?, rejected_at = NOW(), rejection_reason = ?, rejection_notes = ?
                WHERE id = ? AND assigned_hospital_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $notes, $notes, $request_id, $user_data['hospital_db_id']]);
            $success_message = "Blood request rejected.";
        } elseif ($action === 'complete') {
            $stmt = $conn->prepare("
                UPDATE blood_requests 
                SET status = 'completed', completed_at = NOW(), rejection_notes = ?
                WHERE id = ? AND assigned_hospital_id = ? AND status = 'approved'
            ");
            $stmt->execute([$notes, $request_id, $user_data['hospital_db_id']]);
            $success_message = "Blood request marked as completed!";
        }
    } catch (PDOException $e) {
        $error_message = "Failed to update request status: " . $e->getMessage();
    }
}

// Handle blood offer acceptance/rejection
if ($_POST && isset($_POST['offer_action']) && isset($_POST['offer_id'])) {
    $offer_id = (int)$_POST['offer_id'];
    $action = $_POST['offer_action'];
    $feedback_notes = trim($_POST['feedback_notes'] ?? '');
    
    try {
        if ($action === 'accept') {
            $stmt = $conn->prepare("
                UPDATE donation_offers 
                SET status = 'accepted', accepted_by_hospital_id = ?, accepted_by_user_id = ?, accepted_at = NOW(), notes = ?
                WHERE id = ? AND assigned_hospital_id = ?
            ");
            $stmt->execute([$user_data['hospital_db_id'], $_SESSION['user_id'], $feedback_notes, $offer_id, $user_data['hospital_db_id']]);
            $success_message = "Donation offer accepted successfully!";
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("
                UPDATE donation_offers 
                SET status = 'rejected', rejected_by_hospital_id = ?, rejected_by_user_id = ?, rejected_at = NOW(), rejection_reason = ?, rejection_notes = ?
                WHERE id = ? AND assigned_hospital_id = ?
            ");
            $stmt->execute([$user_data['hospital_db_id'], $_SESSION['user_id'], $feedback_notes, $feedback_notes, $offer_id, $user_data['hospital_db_id']]);
            $success_message = "Donation offer rejected.";
        } elseif ($action === 'complete') {
            $stmt = $conn->prepare("
                UPDATE donation_offers 
                SET status = 'completed', completed_at = NOW(), notes = ?
                WHERE id = ? AND assigned_hospital_id = ?
            ");
            $stmt->execute([$feedback_notes, $offer_id, $user_data['hospital_db_id']]);
            $success_message = "Donation completed successfully!";
        }
    } catch (PDOException $e) {
        $error_message = "Failed to update offer status: " . $e->getMessage();
    }
}

// Get hospital statistics
$stats = [
    'total_requests' => 0,
    'pending_requests' => 0,
    'approved_requests' => 0,
    'completed_requests' => 0,
    'total_offers' => 0,
    'pending_offers' => 0
];

if ($user_data['hospital_db_id']) {
    try {
        // Blood requests stats
        $stats_stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests
            FROM blood_requests 
            WHERE assigned_hospital_id = ?
        ");
        $stats_stmt->execute([$user_data['hospital_db_id']]);
        $request_stats = $stats_stmt->fetch();
        
        // Blood offers stats
        $offers_stats_stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_offers,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_offers
            FROM donation_offers 
            WHERE assigned_hospital_id = ?
        ");
        $offers_stats_stmt->execute([$user_data['hospital_db_id']]);
        $offer_stats = $offers_stats_stmt->fetch();
        
        $stats = array_merge($request_stats ?: [], $offer_stats ?: []);
        
    } catch (PDOException $e) {
        // Keep default stats
    }
}

// Get recent blood requests
$blood_requests = [];
if ($user_data['hospital_db_id']) {
    try {
        $requests_stmt = $conn->prepare("
            SELECT br.*, u.first_name, u.last_name, u.email, u.phone
            FROM blood_requests br
            LEFT JOIN users u ON br.requested_by_user_id = u.id
            WHERE br.assigned_hospital_id = ?
            ORDER BY br.created_at DESC
            LIMIT 10
        ");
        $requests_stmt->execute([$user_data['hospital_db_id']]);
        $blood_requests = $requests_stmt->fetchAll();
    } catch (PDOException $e) {
        $blood_requests = [];
    }
}

// Get recent donation offers
$donation_offers = [];
if ($user_data['hospital_db_id']) {
    try {
        $offers_stmt = $conn->prepare("
            SELECT do.*, u.first_name, u.last_name, u.email, u.phone
            FROM donation_offers do
            LEFT JOIN users u ON do.offered_by_user_id = u.id
            WHERE do.assigned_hospital_id = ?
            ORDER BY do.created_at DESC
            LIMIT 10
        ");
        $offers_stmt->execute([$user_data['hospital_db_id']]);
        $donation_offers = $offers_stmt->fetchAll();
    } catch (PDOException $e) {
        $donation_offers = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Dashboard - BloodConnect</title>
    <link rel="stylesheet" href="frontend/css/style.css">
    <link rel="stylesheet" href="frontend/css/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
        .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: #3498db; }
        .stat-label { color: #666; margin-top: 5px; }
        .section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section h2 { margin-top: 0; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .request-card, .offer-card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 8px; background: #f9f9f9; }
        .request-header, .offer-header { display: flex; justify-content: between; align-items: center; margin-bottom: 10px; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold; }
        .status-pending { background: #f39c12; color: white; }
        .status-approved { background: #27ae60; color: white; }
        .status-rejected { background: #e74c3c; color: white; }
        .status-completed { background: #2c3e50; color: white; }
        .btn { padding: 8px 16px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-primary { background: #3498db; color: white; }
        .btn:hover { opacity: 0.8; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .no-data { text-align: center; color: #666; padding: 40px; }
        .actions { margin-top: 10px; }
        .actions input { margin-right: 10px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .detail-row { margin: 5px 0; }
        .detail-row strong { color: #2c3e50; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-hospital"></i> Hospital Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($user_data['hospital_name'] ?? $user_data['first_name'] . ' ' . $user_data['last_name']); ?></p>
            <?php if (!$user_data['is_verified']): ?>
                <div style="background: #f39c12; padding: 10px; border-radius: 4px; margin-top: 10px;">
                    <i class="fas fa-exclamation-triangle"></i> Your hospital account is pending verification.
                </div>
            <?php endif; ?>
        </div>

        <!-- Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_requests'] ?? 0; ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_requests'] ?? 0; ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_offers'] ?? 0; ?></div>
                <div class="stat-label">Pending Offers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['completed_requests'] ?? 0; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>

        <!-- Blood Requests Section -->
        <div class="section">
            <h2><i class="fas fa-hand-holding-medical"></i> Blood Requests</h2>
            
            <?php if (empty($blood_requests)): ?>
                <div class="no-data">
                    <i class="fas fa-inbox" style="font-size: 3em; color: #ccc;"></i>
                    <h3>No Blood Requests</h3>
                    <p>No blood requests have been assigned to your hospital yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($blood_requests as $request): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <div>
                                <h4>Request #<?php echo htmlspecialchars($request['request_id']); ?></h4>
                                <span class="status-badge status-<?php echo $request['status']; ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </div>
                            <div>
                                <small><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></small>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <strong>Patient:</strong> <?php echo htmlspecialchars(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')); ?>
                        </div>
                        <div class="detail-row">
                            <strong>Blood Type:</strong> <?php echo htmlspecialchars($request['blood_type']); ?>
                        </div>
                        <div class="detail-row">
                            <strong>Units Needed:</strong> <?php echo $request['units_requested']; ?>
                        </div>
                        <div class="detail-row">
                            <strong>Priority:</strong> <?php echo ucfirst($request['priority']); ?>
                        </div>
                        <div class="detail-row">
                            <strong>Medical Reason:</strong> <?php echo htmlspecialchars($request['medical_reason']); ?>
                        </div>
                        <?php if ($request['phone']): ?>
                            <div class="detail-row">
                                <strong>Contact:</strong> <?php echo htmlspecialchars($request['phone']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($request['status'] === 'pending' && $user_data['is_verified']): ?>
                            <div class="actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="text" name="notes" placeholder="Add notes (optional)">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="text" name="notes" placeholder="Reason for rejection" required>
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this request?')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </div>
                        <?php elseif ($request['status'] === 'approved' && $user_data['is_verified']): ?>
                            <div class="actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <input type="hidden" name="action" value="complete">
                                    <input type="text" name="notes" placeholder="Completion notes (optional)">
                                    <button type="submit" class="btn btn-primary" onclick="return confirm('Mark this blood request as completed?')">
                                        <i class="fas fa-check-circle"></i> Mark Completed
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($request['rejection_notes']): ?>
                            <div class="detail-row" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;">
                                <strong>Notes:</strong> <?php echo htmlspecialchars($request['rejection_notes']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Blood Offers Section -->
        <div class="section">
            <h2><i class="fas fa-heart"></i> Blood Donation Offers</h2>
            
            <?php if (empty($donation_offers)): ?>
                <div class="no-data">
                    <i class="fas fa-heart" style="font-size: 3em; color: #ccc;"></i>
                    <h3>No Donation Offers</h3>
                    <p>No blood donation offers have been submitted to your hospital yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($donation_offers as $offer): ?>
                    <div class="offer-card">
                        <div class="offer-header">
                            <div>
                                <h4>Offer #<?php echo htmlspecialchars($offer['offer_id']); ?></h4>
                                <span class="status-badge status-<?php echo $offer['status']; ?>">
                                    <?php echo ucfirst($offer['status']); ?>
                                </span>
                            </div>
                            <div>
                                <small><?php echo date('M j, Y g:i A', strtotime($offer['created_at'])); ?></small>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <strong>Donor:</strong> <?php echo htmlspecialchars(($offer['first_name'] ?? '') . ' ' . ($offer['last_name'] ?? '')); ?>
                        </div>
                        <div class="detail-row">
                            <strong>Blood Type:</strong> <?php echo htmlspecialchars($offer['blood_type']); ?>
                        </div>
                        <?php if ($offer['preferred_date']): ?>
                            <div class="detail-row">
                                <strong>Preferred Date:</strong> <?php echo date('M j, Y', strtotime($offer['preferred_date'])); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($offer['preferred_time']): ?>
                            <div class="detail-row">
                                <strong>Preferred Time:</strong> <?php echo date('g:i A', strtotime($offer['preferred_time'])); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($offer['phone']): ?>
                            <div class="detail-row">
                                <strong>Contact:</strong> <?php echo htmlspecialchars($offer['phone']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($offer['status'] === 'pending' && $user_data['is_verified']): ?>
                            <div class="actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                    <input type="hidden" name="offer_action" value="accept">
                                    <input type="text" name="feedback_notes" placeholder="Acceptance message (optional)">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> Accept Offer
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                    <input type="hidden" name="offer_action" value="reject">
                                    <input type="text" name="feedback_notes" placeholder="Reason for rejection" required>
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this donation offer?')">
                                        <i class="fas fa-times"></i> Reject Offer
                                    </button>
                                </form>
                            </div>
                        <?php elseif ($offer['status'] === 'accepted' && $user_data['is_verified']): ?>
                            <div class="actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                    <input type="hidden" name="offer_action" value="complete">
                                    <input type="text" name="feedback_notes" placeholder="Completion notes (optional)">
                                    <button type="submit" class="btn btn-primary" onclick="return confirm('Mark this donation as completed?')">
                                        <i class="fas fa-check-circle"></i> Mark Completed
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($offer['notes']): ?>
                            <div class="detail-row" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;">
                                <strong>Notes:</strong> <?php echo htmlspecialchars($offer['notes']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Debug Info -->
        <div class="section" style="background: #f8f9fa; border-left: 4px solid #17a2b8;">
            <h3>Debug Information</h3>
            <p><strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?></p>
            <p><strong>User Type:</strong> <?php echo $_SESSION['user_type']; ?></p>
            <p><strong>Hospital DB ID:</strong> <?php echo $user_data['hospital_db_id'] ?? 'Not found'; ?></p>
            <p><strong>Hospital Name:</strong> <?php echo $user_data['hospital_name'] ?? 'Not set'; ?></p>
            <p><strong>Verified:</strong> <?php echo $user_data['is_verified'] ? 'Yes' : 'No'; ?></p>
            <p><strong>Blood Requests Found:</strong> <?php echo count($blood_requests); ?></p>
            <p><strong>Blood Offers Found:</strong> <?php echo count($donation_offers); ?></p>
        </div>

        <!-- Navigation -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="frontend/auth/logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</body>
</html>