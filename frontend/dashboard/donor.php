<?php
session_start();
require_once '../../backend/config/database.php';

// Check if user is logged in and is a donor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'donor') {
    header('Location: ../auth/login.php');
    exit();
}

// Get user and donor data
try {
    $stmt = $conn->prepare("
        SELECT u.*, d.donor_id, d.blood_type, d.date_of_birth, d.gender, d.weight, d.height,
               d.is_eligible, d.last_donation_date, d.next_eligible_date, d.total_donations,
               d.health_status, d.is_available, d.preferred_donation_time,
               d.emergency_contact_name, d.emergency_contact_phone, d.known_allergies, d.medical_conditions
        FROM users u 
        JOIN donors d ON u.id = d.user_id 
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

// Handle donation offer submission
if ($_POST && isset($_POST['submit_offer'])) {
    $hospital_id = (int)$_POST['hospital'];
    $preferred_date = $_POST['preferredDate'];
    $preferred_time = $_POST['preferredTime'];
    $notes = trim($_POST['notes']);
    
    // Validation
    $validation_errors = [];
    
    if (empty($hospital_id) || $hospital_id <= 0) {
        $validation_errors[] = "Please select a hospital.";
    }
    
    if (empty($preferred_date)) {
        $validation_errors[] = "Please select a preferred date.";
    } elseif (strtotime($preferred_date) < strtotime(date('Y-m-d'))) {
        $validation_errors[] = "Preferred date cannot be in the past.";
    }
    
    if (empty($preferred_time)) {
        $validation_errors[] = "Please select a preferred time.";
    }
    
    if (!$user_data['is_eligible']) {
        $validation_errors[] = "You are not currently eligible to donate blood.";
    }
    
    if (empty($validation_errors)) {
        try {
            // Check if hospital exists and is active
            $hospital_check = $conn->prepare("SELECT id FROM hospitals WHERE id = ? AND is_verified = 1 AND is_active = 1");
            $hospital_check->execute([$hospital_id]);
            if (!$hospital_check->fetch()) {
                throw new Exception("Selected hospital is not available.");
            }
            
            $offer_id = 'OFF-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            
            $stmt = $conn->prepare("
                INSERT INTO donation_offers (offer_id, donor_id, hospital_id, blood_type, preferred_date, 
                                           preferred_time, notes, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $result = $stmt->execute([
                $offer_id,
                $_SESSION['user_id'],
                $hospital_id,
                $user_data['blood_type'],
                $preferred_date,
                $preferred_time,
                $notes
            ]);
            
            if ($result) {
                $success_message = "Donation offer submitted successfully! Offer ID: " . $offer_id;
                // Refresh the page to show updated offers
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=1&offer_id=" . $offer_id);
                exit();
            } else {
                $error_message = "Failed to submit offer. Database error occurred.";
            }
            
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    } else {
        $error_message = implode("<br>", $validation_errors);
    }
}

// Handle donor actions (edit, cancel, delete)
if ($_POST && isset($_POST['donor_action']) && isset($_POST['offer_id'])) {
    $offer_id = (int)$_POST['offer_id'];
    $action = $_POST['donor_action'];
    
    try {
        if ($action === 'cancel') {
            // Only allow canceling pending or accepted offers
            $stmt = $conn->prepare("
                UPDATE donation_offers 
                SET status = 'cancelled', updated_at = NOW()
                WHERE id = ? AND donor_id = ? AND status IN ('pending', 'accepted')
            ");
            $result = $stmt->execute([$offer_id, $_SESSION['user_id']]);
            
            if ($result && $stmt->rowCount() > 0) {
                $success_message = "Donation offer cancelled successfully.";
            } else {
                $error_message = "Cannot cancel this offer. It may have already been processed.";
            }
            
        } elseif ($action === 'delete') {
            // Only allow deleting completed, rejected, or cancelled offers
            $stmt = $conn->prepare("
                DELETE FROM donation_offers 
                WHERE id = ? AND donor_id = ? AND status IN ('completed', 'rejected', 'cancelled')
            ");
            $result = $stmt->execute([$offer_id, $_SESSION['user_id']]);
            
            if ($result && $stmt->rowCount() > 0) {
                $success_message = "Donation offer deleted from history.";
            } else {
                $error_message = "Cannot delete this offer. Only completed, rejected, or cancelled offers can be deleted.";
            }
            
        } elseif ($action === 'edit') {
            // Only allow editing pending offers
            $new_date = $_POST['new_date'];
            $new_time = $_POST['new_time'];
            $new_notes = trim($_POST['new_notes']);
            
            if (empty($new_date) || empty($new_time)) {
                $error_message = "Please provide both date and time for the update.";
            } elseif (strtotime($new_date) < strtotime(date('Y-m-d'))) {
                $error_message = "New date cannot be in the past.";
            } else {
                $stmt = $conn->prepare("
                    UPDATE donation_offers 
                    SET preferred_date = ?, preferred_time = ?, notes = ?, updated_at = NOW()
                    WHERE id = ? AND donor_id = ? AND status = 'pending'
                ");
                $result = $stmt->execute([$new_date, $new_time, $new_notes, $offer_id, $_SESSION['user_id']]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $success_message = "Donation offer updated successfully.";
                } else {
                    $error_message = "Cannot edit this offer. Only pending offers can be modified.";
                }
            }
        }
        
        // Redirect to prevent form resubmission
        if (isset($success_message)) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?action_success=1");
            exit();
        }
        
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Check for action success message
if (isset($_GET['action_success'])) {
    $success_message = "Action completed successfully!";
}

// Check for success message from redirect
if (isset($_GET['success']) && isset($_GET['offer_id'])) {
    $success_message = "Donation offer submitted successfully! Offer ID: " . htmlspecialchars($_GET['offer_id']);
}

// Get donor's donation offers
try {
    $offers_stmt = $conn->prepare("
        SELECT do.*, h.hospital_name, h.emergency_phone as hospital_phone
        FROM donation_offers do
        LEFT JOIN hospitals h ON do.hospital_id = h.id
        WHERE do.donor_id = ?
        ORDER BY do.created_at DESC
        LIMIT 10
    ");
    $offers_stmt->execute([$_SESSION['user_id']]);
    $donation_offers = $offers_stmt->fetchAll();
} catch (PDOException $e) {
    $donation_offers = [];
}

// Get nearby hospitals
try {
    $hospitals_stmt = $conn->prepare("
        SELECT h.id, h.hospital_name, h.hospital_type, h.emergency_services, h.is_24_7,
               u.phone, u.address, u.city
        FROM hospitals h
        JOIN users u ON h.user_id = u.id
        WHERE h.is_verified = 1 AND h.is_active = 1 AND h.has_blood_bank = 1
        ORDER BY h.hospital_name
        LIMIT 10
    ");
    $hospitals_stmt->execute();
    $hospitals = $hospitals_stmt->fetchAll();
} catch (PDOException $e) {
    $hospitals = [];
}

// Calculate next eligible date if last donation exists
$next_eligible_date = null;
if ($user_data['last_donation_date']) {
    $next_eligible_date = date('Y-m-d', strtotime($user_data['last_donation_date'] . ' + 56 days'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard - BloodConnect</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/modern-styles.css">
    <link rel="stylesheet" href="../css/page-specific.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
                <span class="user-name"><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></span>
                <span class="user-role">Donor</span>
            </div>
            <div class="user-avatar">
                <i class="fas fa-heart"></i>
            </div>
            <div class="nav-actions">
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
                    <h3>Donations</h3>
                    <a href="#offer" class="menu-item">
                        <i class="fas fa-plus-circle"></i>
                        <span>New Offer</span>
                    </a>
                    <a href="#offers" class="menu-item">
                        <i class="fas fa-list"></i>
                        <span>My Offers</span>
                    </a>
                    <a href="#history" class="menu-item">
                        <i class="fas fa-history"></i>
                        <span>Donation History</span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <h3>Profile</h3>
                    <a href="#profile" class="menu-item">
                        <i class="fas fa-user"></i>
                        <span>My Profile</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
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

            <!-- Donor Profile Card -->
            <div class="donor-profile-card" id="overview">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h2>
                        <p>Donor ID: <span class="donor-id"><?php echo htmlspecialchars($user_data['donor_id']); ?></span></p>
                        <div class="blood-group-highlight">
                            <i class="fas fa-tint"></i>
                            <span>Blood Type: <span class="blood-type"><?php echo htmlspecialchars($user_data['blood_type']); ?></span></span>
                        </div>
                    </div>
                    <div class="donation-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $user_data['total_donations']; ?></div>
                            <div class="stat-label">Total Donations</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $user_data['is_eligible'] ? 'Yes' : 'No'; ?></div>
                            <div class="stat-label">Eligible</div>
                        </div>
                    </div>
                </div>
                
                <!-- Eligibility Status -->
                <div class="eligibility-status">
                    <?php if ($user_data['is_eligible']): ?>
                        <div class="status-card eligible">
                            <i class="fas fa-check-circle"></i>
                            <h4>Eligible to Donate</h4>
                            <p>You are currently eligible to donate blood.</p>
                            <?php if ($next_eligible_date && $next_eligible_date > date('Y-m-d')): ?>
                                <p><strong>Next eligible date:</strong> <?php echo date('F j, Y', strtotime($next_eligible_date)); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="status-card not-eligible">
                            <i class="fas fa-times-circle"></i>
                            <h4>Not Eligible</h4>
                            <p>You are currently not eligible to donate.</p>
                            <?php if ($next_eligible_date): ?>
                                <p><strong>Next eligible date:</strong> <?php echo date('F j, Y', strtotime($next_eligible_date)); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Donation Offer Form -->
            <div class="donation-offer-section" id="offer">
                <div class="card-header">
                    <h3>Submit Donation Offer</h3>
                    <p>Offer to donate blood at a hospital near you</p>
                </div>
                <div class="offer-form-container">
                    <form class="donation-offer-form" method="POST" id="donationOfferForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Blood Type</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['blood_type']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Preferred Hospital <span style="color: red;">*</span></label>
                                <select class="form-control" name="hospital" required>
                                    <option value="">Select Hospital</option>
                                    <?php foreach ($hospitals as $hospital): ?>
                                        <option value="<?php echo $hospital['id']; ?>">
                                            <?php echo htmlspecialchars($hospital['hospital_name'] . ' - ' . $hospital['city']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($hospitals)): ?>
                                    <small style="color: red;">No hospitals available. Please contact admin.</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Preferred Date <span style="color: red;">*</span></label>
                                <input type="date" class="form-control" name="preferredDate" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Preferred Time <span style="color: red;">*</span></label>
                                <select class="form-control" name="preferredTime" required>
                                    <option value="">Select Time</option>
                                    <option value="09:00:00">9:00 AM</option>
                                    <option value="10:00:00">10:00 AM</option>
                                    <option value="11:00:00">11:00 AM</option>
                                    <option value="14:00:00">2:00 PM</option>
                                    <option value="15:00:00">3:00 PM</option>
                                    <option value="16:00:00">4:00 PM</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Additional Notes</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Any special requirements or notes..."></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="submit_offer" class="btn btn-primary btn-lg" <?php echo !$user_data['is_eligible'] ? 'disabled' : ''; ?>>
                                <i class="fas fa-heart"></i>
                                Submit Donation Offer
                            </button>
                            <?php if (!$user_data['is_eligible']): ?>
                                <small class="form-help">You are currently not eligible to donate.</small>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Donation Offers Status -->
            <div class="offers-status-section" id="offers">
                <div class="card-header">
                    <h3>My Donation Offers</h3>
                    <span class="badge info"><?php echo count($donation_offers); ?> offers</span>
                </div>
                <div class="offers-timeline">
                    <?php if (empty($donation_offers)): ?>
                        <div class="no-offers">
                            <i class="fas fa-heart"></i>
                            <h4>No Donation Offers</h4>
                            <p>You haven't submitted any donation offers yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($donation_offers as $offer): ?>
                            <div class="offer-item <?php echo $offer['status']; ?>">
                                <div class="offer-status-indicator <?php echo $offer['status']; ?>">
                                    <i class="fas <?php echo ($offer['status'] == 'pending') ? 'fa-clock' : (($offer['status'] == 'accepted') ? 'fa-check' : (($offer['status'] == 'completed') ? 'fa-check-circle' : 'fa-times-circle')); ?>"></i>
                                </div>
                                <div class="offer-details">
                                    <div class="offer-header">
                                        <h4><?php echo htmlspecialchars($offer['blood_type']); ?> Blood Donation Offer</h4>
                                        <span class="offer-id">#<?php echo htmlspecialchars($offer['offer_id']); ?></span>
                                    </div>
                                    <div class="offer-info">
                                        <p><strong>Hospital:</strong> <?php echo htmlspecialchars($offer['hospital_name'] ?? 'Hospital'); ?></p>
                                        <p><strong>Preferred Date:</strong> <?php echo date('M j, Y', strtotime($offer['preferred_date'])); ?></p>
                                        <p><strong>Preferred Time:</strong> <?php echo date('g:i A', strtotime($offer['preferred_time'])); ?></p>
                                        <p><strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($offer['created_at'])); ?></p>
                                        <p><strong>Status:</strong> <span class="status-badge <?php echo $offer['status']; ?>"><?php echo ucfirst($offer['status']); ?></span></p>
                                        <?php if ($offer['notes']): ?>
                                            <p><strong>Notes:</strong> <?php echo htmlspecialchars($offer['notes']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="offer-actions">
                                    <?php if ($offer['hospital_phone']): ?>
                                        <a href="tel:<?php echo $offer['hospital_phone']; ?>" class="btn-sm secondary">
                                            <i class="fas fa-phone"></i>
                                            Contact Hospital
                                        </a>
                                    <?php endif; ?>
                                    
                                    <!-- Donor Actions based on status -->
                                    <?php if ($offer['status'] === 'pending'): ?>
                                        <!-- Edit Offer -->
                                        <button class="btn-sm primary" onclick="toggleEditForm(<?php echo $offer['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        
                                        <!-- Cancel Offer -->
                                        <form method="POST" style="display: inline-block; margin-left: 5px;">
                                            <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                            <input type="hidden" name="donor_action" value="cancel">
                                            <button type="submit" class="btn-sm danger" 
                                                    onclick="return confirm('Are you sure you want to cancel this donation offer?')">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </form>
                                        
                                    <?php elseif ($offer['status'] === 'accepted'): ?>
                                        <!-- Cancel Accepted Offer -->
                                        <form method="POST" style="display: inline-block;">
                                            <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                            <input type="hidden" name="donor_action" value="cancel">
                                            <button type="submit" class="btn-sm warning" 
                                                    onclick="return confirm('Are you sure you want to cancel this accepted offer? The hospital has already approved it.')">
                                                <i class="fas fa-exclamation-triangle"></i> Cancel
                                            </button>
                                        </form>
                                        
                                    <?php elseif (in_array($offer['status'], ['completed', 'rejected', 'cancelled'])): ?>
                                        <!-- Delete from History -->
                                        <form method="POST" style="display: inline-block;">
                                            <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                            <input type="hidden" name="donor_action" value="delete">
                                            <button type="submit" class="btn-sm secondary" 
                                                    onclick="return confirm('Are you sure you want to delete this offer from your history? This cannot be undone.')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Edit Form (hidden by default) -->
                                <?php if ($offer['status'] === 'pending'): ?>
                                    <div id="edit-form-<?php echo $offer['id']; ?>" class="edit-form" style="display: none; margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                                        <h5>Edit Donation Offer</h5>
                                        <form method="POST">
                                            <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                            <input type="hidden" name="donor_action" value="edit">
                                            
                                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                                <div style="flex: 1;">
                                                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">New Date:</label>
                                                    <input type="date" name="new_date" value="<?php echo $offer['preferred_date']; ?>" 
                                                           min="<?php echo date('Y-m-d'); ?>" required
                                                           style="width: 100%; padding: 5px; border: 1px solid #ddd; border-radius: 3px;">
                                                </div>
                                                <div style="flex: 1;">
                                                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">New Time:</label>
                                                    <select name="new_time" required style="width: 100%; padding: 5px; border: 1px solid #ddd; border-radius: 3px;">
                                                        <option value="09:00:00" <?php echo ($offer['preferred_time'] == '09:00:00') ? 'selected' : ''; ?>>9:00 AM</option>
                                                        <option value="10:00:00" <?php echo ($offer['preferred_time'] == '10:00:00') ? 'selected' : ''; ?>>10:00 AM</option>
                                                        <option value="11:00:00" <?php echo ($offer['preferred_time'] == '11:00:00') ? 'selected' : ''; ?>>11:00 AM</option>
                                                        <option value="14:00:00" <?php echo ($offer['preferred_time'] == '14:00:00') ? 'selected' : ''; ?>>2:00 PM</option>
                                                        <option value="15:00:00" <?php echo ($offer['preferred_time'] == '15:00:00') ? 'selected' : ''; ?>>3:00 PM</option>
                                                        <option value="16:00:00" <?php echo ($offer['preferred_time'] == '16:00:00') ? 'selected' : ''; ?>>4:00 PM</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div style="margin-bottom: 10px;">
                                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Notes:</label>
                                                <textarea name="new_notes" rows="2" placeholder="Updated notes..." 
                                                          style="width: 100%; padding: 5px; border: 1px solid #ddd; border-radius: 3px;"><?php echo htmlspecialchars($offer['notes']); ?></textarea>
                                            </div>
                                            
                                            <div style="text-align: right;">
                                                <button type="button" class="btn-sm secondary" onclick="toggleEditForm(<?php echo $offer['id']; ?>)">
                                                    Cancel
                                                </button>
                                                <button type="submit" class="btn-sm primary" style="margin-left: 5px;">
                                                    <i class="fas fa-save"></i> Update Offer
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Management -->
            <div class="profile-management-section" id="profile">
                <div class="card-header">
                    <h3>Profile Information</h3>
                </div>
                <div class="profile-info-grid">
                    <div class="profile-section">
                        <h4>Personal Information</h4>
                        <div class="profile-item">
                            <span class="label">Full Name:</span>
                            <span class="value"><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Blood Type:</span>
                            <span class="value blood-type-badge"><?php echo htmlspecialchars($user_data['blood_type']); ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Date of Birth:</span>
                            <span class="value"><?php echo date('F j, Y', strtotime($user_data['date_of_birth'])); ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Gender:</span>
                            <span class="value"><?php echo ucfirst($user_data['gender']); ?></span>
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
                            <span class="label">Address:</span>
                            <span class="value"><?php echo htmlspecialchars(($user_data['address'] ?? '') . ', ' . ($user_data['city'] ?? '') . ', ' . ($user_data['state'] ?? '')); ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Emergency Contact:</span>
                            <span class="value"><?php echo htmlspecialchars(($user_data['emergency_contact_name'] ?? '') . ' - ' . ($user_data['emergency_contact_phone'] ?? '')); ?></span>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h4>Donation Information</h4>
                        <div class="profile-item">
                            <span class="label">Donor ID:</span>
                            <span class="value"><?php echo htmlspecialchars($user_data['donor_id']); ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Weight:</span>
                            <span class="value"><?php echo htmlspecialchars($user_data['weight'] ?? 'Not provided'); ?> kg</span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Height:</span>
                            <span class="value"><?php echo htmlspecialchars($user_data['height'] ?? 'Not provided'); ?> cm</span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Health Status:</span>
                            <span class="value"><?php echo ucfirst($user_data['health_status']); ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Total Donations:</span>
                            <span class="value"><?php echo $user_data['total_donations']; ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Last Donation:</span>
                            <span class="value"><?php echo $user_data['last_donation_date'] ? date('F j, Y', strtotime($user_data['last_donation_date'])) : 'Never'; ?></span>
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

        // Form validation - no interference with submission
        document.getElementById('donationOfferForm').addEventListener('submit', function(e) {
            console.log('Form submission started');
            console.log('Form data:', new FormData(this));
            
            // Just log - don't modify the button at all
            console.log('Form will submit naturally');
        });
        
        // Toggle edit form function
        function toggleEditForm(offerId) {
            const editForm = document.getElementById('edit-form-' + offerId);
            if (editForm.style.display === 'none' || editForm.style.display === '') {
                editForm.style.display = 'block';
            } else {
                editForm.style.display = 'none';
            }
        }
    </script>
</body>
</html>