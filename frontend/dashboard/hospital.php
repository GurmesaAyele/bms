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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            background: #f8fafc;
            font-size: 16px;
        }

        /* Dashboard Navigation */
        .dashboard-nav {
            background: linear-gradient(135deg, #0ea5e9, #0284c7, #0369a1);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 8px 32px rgba(14, 165, 233, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #7dd3fc;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 800;
        }

        .nav-brand i {
            font-size: 1.8rem;
            color: #bae6fd;
            text-shadow: 0 0 20px rgba(186, 230, 253, 0.5);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .user-role {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .nav-actions .btn-icon {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 8px 12px;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-actions .btn-icon:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        /* Dashboard Container */
        .dashboard-container {
            display: flex;
            min-height: calc(100vh - 80px);
        }

        /* Sidebar */
        .dashboard-sidebar {
            width: 280px;
            background: white;
            border-right: 1px solid #e2e8f0;
            padding: 2rem 0;
            position: sticky;
            top: 80px;
            height: calc(100vh - 80px);
            overflow-y: auto;
        }

        .sidebar-menu {
            padding: 0 1rem;
        }

        .menu-section {
            margin-bottom: 2rem;
        }

        .menu-section h3 {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 1rem;
            padding: 0 1rem;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 1rem;
            color: #64748b;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.25rem;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .menu-item:hover {
            background: #f1f5f9;
            color: #0ea5e9;
        }

        .menu-item.active {
            background: #eff6ff;
            color: #0ea5e9;
            border-left: 3px solid #0ea5e9;
        }

        .menu-item i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .dashboard-main {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #059669;
            border: 2px solid #6ee7b7;
        }

        .alert-danger {
            background: #fee2e2;
            color: #dc2626;
            border: 2px solid #fca5a5;
        }

        .alert-warning {
            background: #fef3c7;
            color: #d97706;
            border: 2px solid #fcd34d;
        }

        /* Profile Card */
        .hospital-profile-card {
            background: linear-gradient(135deg, #0ea5e9, #0284c7, #0369a1);
            color: white;
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(14, 165, 233, 0.3);
            border: 2px solid rgba(186, 230, 253, 0.2);
        }

        .hospital-profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(186, 230, 253, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }

        .hospital-profile-card::after {
            content: '🏥';
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 3rem;
            opacity: 0.1;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            position: relative;
            z-index: 1;
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
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .profile-info {
            flex: 1;
        }

        .profile-info h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .profile-info p {
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .hospital-type-highlight {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .hospital-type {
            background: linear-gradient(135deg, #bae6fd, #7dd3fc);
            color: #0369a1;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 800;
            font-size: 1.1rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            box-shadow: 0 4px 15px rgba(186, 230, 253, 0.4);
        }

        .verification-status {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .status-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-badge.verified {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 2px solid rgba(16, 185, 129, 0.3);
        }

        .status-badge.pending {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            border: 2px solid rgba(245, 158, 11, 0.3);
        }

        /* Statistics Cards */
        .stats-section {
            margin-bottom: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 2px solid #f1f5f9;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(14, 165, 233, 0.15);
            border-color: #0ea5e9;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .stat-icon.requests {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .stat-icon.requests::before {
            background: linear-gradient(90deg, #f59e0b, #d97706);
        }

        .stat-icon.offers {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
        }

        .stat-icon.offers::before {
            background: linear-gradient(90deg, #dc2626, #b91c1c);
        }

        .stat-icon.completed {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .stat-icon.completed::before {
            background: linear-gradient(90deg, #10b981, #059669);
        }

        .stat-icon.inventory {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        .stat-icon.inventory::before {
            background: linear-gradient(90deg, #8b5cf6, #7c3aed);
        }

        .stat-content {
            text-align: left;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .stat-sub {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        /* Cards */
        .requests-management-section,
        .offers-management-section,
        .inventory-section,
        .hospital-profile-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 2px solid #f1f5f9;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card-header {
            padding: 2rem 2rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge.info {
            background: #dbeafe;
            color: #1d4ed8;
        }

        /* Request/Offer Cards */
        .requests-list,
        .offers-list {
            padding: 2rem;
        }

        .no-requests,
        .no-offers {
            text-align: center;
            padding: 3rem 2rem;
            color: #64748b;
        }

        .no-requests i,
        .no-offers i {
            font-size: 3rem;
            color: #0ea5e9;
            margin-bottom: 1rem;
        }

        .no-requests h4,
        .no-offers h4 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }

        .request-card,
        .offer-card {
            background: #f8fafc;
            border: 2px solid #f1f5f9;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .request-card:hover,
        .offer-card:hover {
            box-shadow: 0 8px 25px rgba(14, 165, 233, 0.1);
            border-color: #e2e8f0;
        }

        .request-card.pending,
        .offer-card.pending {
            border-left: 4px solid #f59e0b;
        }

        .request-card.approved,
        .offer-card.accepted {
            border-left: 4px solid #10b981;
        }

        .request-card.completed,
        .offer-card.completed {
            border-left: 4px solid #059669;
        }

        .request-card.rejected,
        .offer-card.rejected {
            border-left: 4px solid #ef4444;
        }

        .request-header,
        .offer-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .request-info h4,
        .offer-info h4 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .request-info p,
        .offer-info p {
            margin-bottom: 0.25rem;
            color: #4b5563;
            font-size: 0.9rem;
        }

        .request-status,
        .offer-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .request-status.pending,
        .offer-status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .request-status.approved,
        .offer-status.accepted {
            background: #d1fae5;
            color: #065f46;
        }

        .request-status.completed,
        .offer-status.completed {
            background: #dcfce7;
            color: #14532d;
        }

        .request-status.rejected,
        .offer-status.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .request-details,
        .offer-details {
            margin-bottom: 1rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .medical-reason,
        .notes {
            background: #f1f5f9;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .blood-type-badge {
            background: linear-gradient(135deg, #dc2626, #7f1d1d);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 800;
            font-size: 1rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
            border: 2px solid rgba(254, 202, 202, 0.3);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-1px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            transform: translateY(-1px);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        /* Blood Inventory */
        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            padding: 2rem;
        }

        .inventory-card {
            background: white;
            border: 2px solid #f1f5f9;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .inventory-card:hover {
            box-shadow: 0 8px 25px rgba(14, 165, 233, 0.1);
            border-color: #e2e8f0;
            transform: translateY(-2px);
        }

        .blood-type-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0 auto 1rem;
        }

        .units-available {
            font-size: 2rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .stock-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .stock-status.good {
            background: #d1fae5;
            color: #065f46;
        }

        .stock-status.low {
            background: #fef3c7;
            color: #92400e;
        }

        .stock-status.critical {
            background: #fee2e2;
            color: #991b1b;
        }

        .last-updated {
            font-size: 0.8rem;
            color: #64748b;
        }

        /* Profile Management */
        .profile-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            padding: 2rem;
        }

        .profile-section {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .profile-section h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1rem;
        }

        .profile-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .profile-item:last-child {
            border-bottom: none;
        }

        .profile-item .label {
            font-weight: 500;
            color: #64748b;
        }

        .profile-item .value {
            font-weight: 600;
            color: #1e293b;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .dashboard-sidebar {
                width: 100%;
                height: auto;
                position: static;
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
            }

            .dashboard-main {
                padding: 1rem;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .inventory-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .profile-info-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .nav-user {
                flex-direction: column;
                gap: 10px;
            }

            .user-info {
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .dashboard-nav {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .inventory-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
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