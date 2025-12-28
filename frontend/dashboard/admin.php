<?php
session_start();
require_once '../../backend/config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Get user data
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch();
    
    if (!$user_data) {
        header('Location: ../auth/login.php');
        exit();
    }
} catch (PDOException $e) {
    die("Error loading user data: " . $e->getMessage());
}

// Handle hospital approval/rejection
if ($_POST && isset($_POST['action']) && isset($_POST['hospital_id'])) {
    $hospital_id = (int)$_POST['hospital_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE hospitals SET is_verified = 1, verified_at = NOW(), verified_by = ? WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $hospital_id]);
            $success_message = "Hospital approved successfully!";
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE hospitals SET is_verified = 0, is_active = 0 WHERE id = ?");
            $stmt->execute([$hospital_id]);
            $success_message = "Hospital rejected successfully!";
        }
    } catch (PDOException $e) {
        $error_message = "Failed to update hospital status.";
    }
}

// Get system statistics
try {
    $stats_stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM users WHERE user_type = 'patient' AND is_active = 1) as patient_count,
            (SELECT COUNT(*) FROM users WHERE user_type = 'donor' AND is_active = 1) as donor_count,
            (SELECT COUNT(*) FROM users WHERE user_type = 'hospital' AND is_active = 1) as hospital_count,
            (SELECT COUNT(*) FROM hospitals WHERE is_verified = 1) as verified_hospitals,
            (SELECT COUNT(*) FROM blood_requests) as total_requests,
            (SELECT COUNT(*) FROM blood_requests WHERE status = 'completed') as completed_requests,
            (SELECT COUNT(*) FROM donation_offers) as total_offers,
            (SELECT COUNT(*) FROM donation_offers WHERE status = 'completed') as completed_offers
    ");
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch();
} catch (PDOException $e) {
    $stats = [
        'patient_count' => 0,
        'donor_count' => 0,
        'hospital_count' => 0,
        'verified_hospitals' => 0,
        'total_requests' => 0,
        'completed_requests' => 0,
        'total_offers' => 0,
        'completed_offers' => 0
    ];
}

// Get chart data for the last 7 days
try {
    $chart_data_stmt = $conn->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(CASE WHEN user_type = 'patient' THEN 1 END) as patients,
            COUNT(CASE WHEN user_type = 'donor' THEN 1 END) as donors,
            COUNT(CASE WHEN user_type = 'hospital' THEN 1 END) as hospitals
        FROM users 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $chart_data_stmt->execute();
    $chart_data = $chart_data_stmt->fetchAll();
} catch (PDOException $e) {
    $chart_data = [];
}

// Get pending hospital registrations
try {
    $pending_hospitals_stmt = $conn->prepare("
        SELECT h.*, u.first_name, u.last_name, u.email, u.phone, u.address, u.city, u.state, u.created_at
        FROM hospitals h
        JOIN users u ON h.user_id = u.id
        WHERE h.is_verified = 0 AND u.is_active = 1
        ORDER BY u.created_at DESC
    ");
    $pending_hospitals_stmt->execute();
    $pending_hospitals = $pending_hospitals_stmt->fetchAll();
} catch (PDOException $e) {
    $pending_hospitals = [];
}

// Get recent activities
try {
    $recent_users_stmt = $conn->prepare("
        SELECT u.*, 
               CASE 
                   WHEN u.user_type = 'patient' THEN p.patient_id
                   WHEN u.user_type = 'donor' THEN d.donor_id
                   WHEN u.user_type = 'hospital' THEN h.hospital_id
                   ELSE NULL
               END as type_id
        FROM users u
        LEFT JOIN patients p ON u.id = p.user_id AND u.user_type = 'patient'
        LEFT JOIN donors d ON u.id = d.user_id AND u.user_type = 'donor'
        LEFT JOIN hospitals h ON u.id = h.user_id AND u.user_type = 'hospital'
        WHERE u.user_type != 'admin'
        ORDER BY u.created_at DESC
        LIMIT 10
    ");
    $recent_users_stmt->execute();
    $recent_users = $recent_users_stmt->fetchAll();
} catch (PDOException $e) {
    $recent_users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BloodConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* BloodConnect Admin Dashboard Styles - Clean & Modern */
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

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Dashboard Navigation */
        .dashboard-nav {
            background: linear-gradient(135deg, #dc2626, #7f1d1d, #450a0a);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 8px 32px rgba(220, 38, 38, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #fca5a5;
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
            color: #fecaca;
            text-shadow: 0 0 20px rgba(254, 202, 202, 0.5);
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

        /* Dashboard Main */
        .dashboard-main {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Admin Header */
        .admin-header {
            background: linear-gradient(135deg, #dc2626, #7f1d1d, #450a0a);
            color: white;
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(220, 38, 38, 0.3);
            border: 2px solid rgba(254, 202, 202, 0.2);
        }

        .admin-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(254, 202, 202, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }

        .admin-header::after {
            content: '⚕️';
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

        .admin-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .admin-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
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

        /* Statistics Overview */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
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
            background: linear-gradient(90deg, #dc2626, #b91c1c);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(220, 38, 38, 0.15);
            border-color: #dc2626;
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
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
        }

        .stat-icon.users {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
        }

        .stat-icon.hospitals {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
        }

        .stat-icon.requests {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .stat-icon.offers {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1;
        }

        .stat-label {
            font-size: 1rem;
            color: #64748b;
            font-weight: 600;
        }

        /* Chart Container */
        .chart-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 2px solid #f1f5f9;
            margin-bottom: 2rem;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .chart-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }

        /* Tab Container */
        .tab-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 2px solid #f1f5f9;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .tab-nav {
            display: flex;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .tab-btn {
            flex: 1;
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .tab-btn::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #dc2626;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .tab-btn.active::after {
            transform: scaleX(1);
        }

        .tab-btn:hover,
        .tab-btn.active {
            color: #dc2626;
            background: white;
        }

        .tab-content {
            padding: 2rem;
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Hospital Cards */
        .hospital-card {
            background: white;
            border: 2px solid #f1f5f9;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .hospital-card:hover {
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.1);
            border-color: #e2e8f0;
            transform: translateY(-2px);
        }

        .hospital-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .hospital-info h4 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .hospital-meta {
            font-size: 0.9rem;
            color: #64748b;
        }

        .hospital-meta p {
            margin-bottom: 0.25rem;
        }

        .hospital-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .btn-approve {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-approve:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-1px);
        }

        .btn-reject {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-reject:hover {
            background: linear-gradient(135deg, #b91c1c, #991b1b);
            transform: translateY(-1px);
        }

        /* User List */
        .user-list {
            display: grid;
            gap: 1rem;
        }

        .user-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 10px;
            transition: all 0.3s ease;
            border: 2px solid #f1f5f9;
        }

        .user-item:hover {
            background: white;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.1);
            border-color: #e2e8f0;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .user-type {
            font-size: 0.85rem;
            color: #64748b;
            text-transform: capitalize;
        }

        .user-date {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 3rem;
            color: #10b981;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }

        .empty-state p {
            margin-bottom: 1.5rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-main {
                padding: 1rem;
            }

            .admin-title {
                font-size: 2rem;
            }

            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .tab-nav {
                flex-direction: column;
            }

            .tab-btn {
                border-radius: 0;
            }

            .hospital-header {
                flex-direction: column;
                gap: 1rem;
            }

            .hospital-actions {
                align-self: stretch;
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

            .stats-overview {
                grid-template-columns: 1fr;
            }

            .chart-container,
            .tab-container {
                margin: 0 -1rem;
                border-radius: 0;
            }
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body>
    <!-- Dashboard Navigation -->
    <nav class="dashboard-nav">
        <div class="nav-brand">
            <i class="fas fa-tint"></i>
            <span>BloodConnect Admin</span>
        </div>
        <div class="nav-user">
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></span>
                <span class="user-role">Administrator</span>
            </div>
            <div class="user-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="nav-actions">
                <a href="../auth/logout.php" class="btn-icon" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-main">
        <!-- Admin Header -->
        <div class="admin-header">
            <h1 class="admin-title">Admin Dashboard</h1>
            <p class="admin-subtitle">Manage and monitor the BloodConnect system</p>
        </div>

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

        <!-- Statistics Overview -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo $stats['patient_count'] + $stats['donor_count']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon hospitals">
                        <i class="fas fa-hospital"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo $stats['verified_hospitals']; ?></div>
                <div class="stat-label">Verified Hospitals</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon requests">
                        <i class="fas fa-hand-holding-medical"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo $stats['completed_requests']; ?></div>
                <div class="stat-label">Completed Requests</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon offers">
                        <i class="fas fa-heart"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo $stats['completed_offers']; ?></div>
                <div class="stat-label">Completed Donations</div>
            </div>
        </div>

        <!-- Real-time Chart -->
        <div class="chart-container">
            <div class="chart-header">
                <h3 class="chart-title">User Registration Trends (Last 7 Days)</h3>
            </div>
            <canvas id="registrationChart" width="400" height="200"></canvas>
        </div>

        <!-- Tab Container -->
        <div class="tab-container">
            <div class="tab-nav">
                <button class="tab-btn active" onclick="showTab('hospitals')">
                    <i class="fas fa-hospital"></i>
                    Hospital Approvals (<?php echo count($pending_hospitals); ?>)
                </button>
                <button class="tab-btn" onclick="showTab('users')">
                    <i class="fas fa-users"></i>
                    Recent Users
                </button>
                <button class="tab-btn" onclick="showTab('reports')">
                    <i class="fas fa-chart-bar"></i>
                    System Reports
                </button>
            </div>

            <!-- Hospital Approvals Tab -->
            <div id="hospitals-tab" class="tab-content active">
                <?php if (empty($pending_hospitals)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h3>All Caught Up!</h3>
                        <p>No pending hospital approvals at this time.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pending_hospitals as $hospital): ?>
                        <div class="hospital-card">
                            <div class="hospital-header">
                                <div class="hospital-info">
                                    <h4><?php echo htmlspecialchars($hospital['hospital_name']); ?></h4>
                                    <div class="hospital-meta">
                                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($hospital['first_name'] . ' ' . $hospital['last_name']); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($hospital['email']); ?></p>
                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($hospital['phone'] ?? 'Not provided'); ?></p>
                                        <p><strong>License:</strong> <?php echo htmlspecialchars($hospital['license_number']); ?></p>
                                        <p><strong>Type:</strong> <?php echo ucfirst($hospital['hospital_type']); ?></p>
                                        <p><strong>Applied:</strong> <?php echo date('M j, Y g:i A', strtotime($hospital['created_at'])); ?></p>
                                    </div>
                                </div>
                                <div class="hospital-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="hospital_id" value="<?php echo $hospital['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn-approve" onclick="return confirm('Approve this hospital?')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="hospital_id" value="<?php echo $hospital['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn-reject" onclick="return confirm('Reject this hospital application?')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Recent Users Tab -->
            <div id="users-tab" class="tab-content">
                <div class="user-list">
                    <?php foreach ($recent_users as $user): ?>
                        <div class="user-item">
                            <div class="user-avatar">
                                <i class="fas fa-<?php echo $user['user_type'] === 'patient' ? 'user' : ($user['user_type'] === 'donor' ? 'heart' : 'hospital'); ?>"></i>
                            </div>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                <div class="user-type"><?php echo ucfirst($user['user_type']); ?></div>
                            </div>
                            <div class="user-date">
                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Reports Tab -->
            <div id="reports-tab" class="tab-content">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                    <div class="stat-card">
                        <h4>User Distribution</h4>
                        <p>Patients: <?php echo $stats['patient_count']; ?></p>
                        <p>Donors: <?php echo $stats['donor_count']; ?></p>
                        <p>Hospitals: <?php echo $stats['hospital_count']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h4>Blood Requests</h4>
                        <p>Total: <?php echo $stats['total_requests']; ?></p>
                        <p>Completed: <?php echo $stats['completed_requests']; ?></p>
                        <p>Success Rate: <?php echo $stats['total_requests'] > 0 ? round(($stats['completed_requests'] / $stats['total_requests']) * 100, 1) : 0; ?>%</p>
                    </div>
                    <div class="stat-card">
                        <h4>Donations</h4>
                        <p>Total Offers: <?php echo $stats['total_offers']; ?></p>
                        <p>Completed: <?php echo $stats['completed_offers']; ?></p>
                        <p>Success Rate: <?php echo $stats['total_offers'] > 0 ? round(($stats['completed_offers'] / $stats['total_offers']) * 100, 1) : 0; ?>%</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }

        // Chart.js configuration
        const ctx = document.getElementById('registrationChart').getContext('2d');
        
        // Prepare chart data
        const chartData = <?php echo json_encode(array_reverse($chart_data)); ?>;
        const labels = chartData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        
        const patientsData = chartData.map(item => parseInt(item.patients));
        const donorsData = chartData.map(item => parseInt(item.donors));
        const hospitalsData = chartData.map(item => parseInt(item.hospitals));

        const registrationChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Patients',
                        data: patientsData,
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Donors',
                        data: donorsData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Hospitals',
                        data: hospitalsData,
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                family: 'Inter',
                                size: 12,
                                weight: '600'
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                family: 'Inter'
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Inter'
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    }
                },
                elements: {
                    point: {
                        radius: 6,
                        hoverRadius: 8
                    }
                }
            }
        });

        // Auto-refresh chart data every 30 seconds
        setInterval(() => {
            // In a real application, you would fetch new data here
            // For now, we'll just add some animation
            registrationChart.update('active');
        }, 30000);
    </script>
</body>
</html>