<?php
session_start();
require_once '../../backend/config/database.php';

$error_message = '';
$success_message = '';

// Handle admin bypass
if ($_POST && isset($_POST['admin_bypass'])) {
    // Create admin session directly
    $_SESSION['user_id'] = 1; // Assuming admin user ID is 1
    $_SESSION['user_email'] = 'admin@bloodconnect.com';
    $_SESSION['user_type'] = 'admin';
    $_SESSION['user_name'] = 'System Administrator';
    
    // Redirect to admin dashboard
    header('Location: ../dashboard/admin.php');
    exit();
}

// Handle login form submission
if ($_POST && isset($_POST['email']) && isset($_POST['password'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error_message = 'Please fill in all fields.';
    } else {
        try {
            // Get user by email
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                // Update last login
                $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update_stmt->execute([$user['id']]);
                
                // Redirect based on user type
                switch ($user['user_type']) {
                    case 'patient':
                        header('Location: ../dashboard/patient.php');
                        break;
                    case 'donor':
                        header('Location: ../dashboard/donor.php');
                        break;
                    case 'hospital':
                        header('Location: ../dashboard/hospital.php');
                        break;
                    case 'admin':
                        header('Location: ../dashboard/admin.php');
                        break;
                    default:
                        header('Location: ../dashboard/patient.php');
                }
                exit();
            } else {
                $error_message = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $error_message = 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BloodConnect</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* CRITICAL: Override all external CSS with maximum specificity */
        body.login-page .login-container {
            min-height: 100vh !important;
            display: flex !important;
            align-items: flex-start !important;
            justify-content: center !important;
            padding: 1rem !important;
            padding-top: 2rem !important;
        }

        body.login-page .login-card {
            max-height: none !important;
            height: auto !important;
            overflow: visible !important;
            padding: 1rem !important;
            margin: 0 auto !important;
            max-width: 400px !important;
            width: 100% !important;
            position: relative !important;
            z-index: 999 !important;
        }

        /* ULTRA COMPACT LAYOUT */
        body.login-page .logo {
            margin-bottom: 0.3rem !important;
            text-align: center !important;
        }

        body.login-page .logo h1 {
            margin: 0.2rem 0 0 0 !important;
            font-size: 1.4rem !important;
            line-height: 1 !important;
        }

        body.login-page .logo-icon {
            margin-bottom: 0.2rem !important;
            width: 40px !important;
            height: 40px !important;
            font-size: 1.2rem !important;
        }

        body.login-page .welcome {
            margin-bottom: 0.5rem !important;
            text-align: center !important;
        }

        body.login-page .welcome h2 {
            margin: 0 !important;
            font-size: 1.1rem !important;
            line-height: 1 !important;
        }

        body.login-page .welcome h2::after {
            display: none !important;
        }

        /* FORM COMPRESSION */
        body.login-page .login-form {
            margin-bottom: 0.5rem !important;
        }

        body.login-page .input-group {
            margin-bottom: 0.5rem !important;
        }

        body.login-page .input-wrapper input {
            padding: 0.6rem 0.8rem 0.6rem 2.2rem !important;
            font-size: 0.9rem !important;
        }

        body.login-page .input-wrapper i {
            left: 0.8rem !important;
            font-size: 0.9rem !important;
        }

        body.login-page .login-btn {
            margin: 0.3rem 0 !important;
            padding: 0.7rem !important;
            font-size: 0.9rem !important;
        }

        /* ALERT COMPRESSION */
        body.login-page .alert {
            margin-bottom: 0.5rem !important;
            padding: 0.5rem 0.8rem !important;
            font-size: 0.8rem !important;
        }

        /* ADMIN BUTTON - MAXIMUM VISIBILITY */
        body.login-page .admin-access {
            margin: 0.5rem 0 !important;
            position: relative !important;
            z-index: 9999 !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            background: rgba(255, 255, 255, 0.95) !important;
            padding: 0.5rem !important;
            border-radius: 8px !important;
            border: 2px solid #dc2626 !important;
        }

        body.login-page .divider {
            text-align: center !important;
            margin: 0.3rem 0 !important;
            position: relative !important;
            display: block !important;
        }

        body.login-page .divider::before {
            content: '' !important;
            position: absolute !important;
            top: 50% !important;
            left: 0 !important;
            right: 0 !important;
            height: 1px !important;
            background: #dc2626 !important;
        }

        body.login-page .divider span {
            background: white !important;
            padding: 0 0.5rem !important;
            color: #dc2626 !important;
            font-size: 0.7rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
        }

        body.login-page .admin-btn {
            width: 100% !important;
            padding: 0.8rem !important;
            background: linear-gradient(135deg, #dc2626, #7f1d1d) !important;
            color: white !important;
            border: 3px solid #dc2626 !important;
            border-radius: 8px !important;
            font-family: inherit !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.5rem !important;
            position: relative !important;
            overflow: visible !important;
            margin: 0 !important;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4) !important;
            font-size: 0.9rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            z-index: 9999 !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        body.login-page .admin-btn:hover {
            background: linear-gradient(135deg, #7f1d1d, #450a0a) !important;
            transform: translateY(-2px) scale(1.02) !important;
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.6) !important;
            border-color: #7f1d1d !important;
        }

        body.login-page .admin-btn i {
            font-size: 1rem !important;
            color: white !important;
        }

        body.login-page .admin-btn span {
            font-weight: 700 !important;
            font-size: 0.9rem !important;
            color: white !important;
        }

        /* REMOVE OVERLAPPING SMALL TEXT */
        body.login-page .admin-btn small {
            display: none !important;
        }

        /* REGISTER SECTION COMPRESSION */
        body.login-page .register-section {
            margin-top: 0.5rem !important;
        }

        body.login-page .register-section p {
            margin-bottom: 0.3rem !important;
            font-size: 0.8rem !important;
        }

        body.login-page .register-options {
            gap: 0.3rem !important;
        }

        body.login-page .register-btn {
            padding: 0.5rem 0.6rem !important;
            font-size: 0.7rem !important;
        }

        body.login-page .register-btn i {
            font-size: 0.9rem !important;
        }

        /* FORCE VISIBILITY ON ALL ELEMENTS */
        body.login-page .admin-access,
        body.login-page .admin-access *,
        body.login-page .divider,
        body.login-page .divider *,
        body.login-page .admin-btn,
        body.login-page .admin-btn * {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            z-index: 9999 !important;
        }

        body.login-page .admin-btn {
            display: flex !important;
        }

        /* MOBILE ULTRA COMPACT */
        @media (max-width: 768px) {
            body.login-page .login-container {
                padding: 0.5rem !important;
                padding-top: 1rem !important;
            }
            
            body.login-page .login-card {
                padding: 0.8rem !important;
                margin: 0 auto !important;
            }
            
            body.login-page .logo h1 {
                font-size: 1.2rem !important;
            }
            
            body.login-page .welcome h2 {
                font-size: 1rem !important;
            }
            
            body.login-page .admin-btn {
                padding: 0.7rem !important;
                font-size: 0.8rem !important;
            }
        }

        /* OVERRIDE ANY CONFLICTING STYLES */
        body.login-page * {
            line-height: 1.2 !important;
        }

        /* ENSURE ADMIN SECTION IS ALWAYS VISIBLE */
        body.login-page .admin-access {
            min-height: 60px !important;
            background: rgba(220, 38, 38, 0.1) !important;
        }
    </style>
</head>
<body class="login-page">
    <!-- Home Button -->
    <a href="../index.php" class="home-btn">
        <i class="fas fa-home"></i>
        Home
    </a>

    <!-- Background Elements -->
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <!-- Main Container -->
    <div class="login-container">
        <div class="login-card">
            <!-- Logo -->
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-tint"></i>
                </div>
                <h1>BloodConnect</h1>
            </div>

            <!-- Welcome Text -->
            <div class="welcome">
                <h2>Welcome Back</h2>
            </div>

            <!-- Alerts -->
            <?php if ($error_message): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" class="login-form">
                <div class="input-group">
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email address" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>

                <div class="input-group">
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="eye-icon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-btn">
                    <span class="btn-text">Sign In</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <!-- Admin Quick Access - BELOW SIGN IN BUTTON -->
            <div class="admin-access">
                <div class="divider">
                    <span>Quick Access</span>
                </div>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="admin_bypass" value="1">
                    <button type="submit" class="admin-btn">
                        <i class="fas fa-user-shield"></i>
                        <span>Admin Dashboard</span>
                    </button>
                </form>
            </div>

            <!-- Register Options -->
            <div class="register-section">
                <p>New to BloodConnect?</p>
                <div class="register-options">
                    <a href="register-patient.php" class="register-btn">
                        <i class="fas fa-user-injured"></i>
                        <span>Need Blood</span>
                    </a>
                    <a href="register-donor.php" class="register-btn">
                        <i class="fas fa-heart"></i>
                        <span>Donate</span>
                    </a>
                    <a href="register-hospital.php" class="register-btn">
                        <i class="fas fa-hospital"></i>
                        <span>Hospital</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (password.type === 'password') {
                password.type = 'text';
                eyeIcon.className = 'fas fa-eye-slash';
            } else {
                password.type = 'password';
                eyeIcon.className = 'fas fa-eye';
            }
        }

        document.querySelector('form').addEventListener('submit', function() {
            const btn = document.querySelector('.login-btn');
            const btnText = btn.querySelector('.btn-text');
            const btnIcon = btn.querySelector('i');
            
            btnText.textContent = 'Signing In...';
            btnIcon.className = 'fas fa-spinner fa-spin';
            btn.disabled = true;
        });

        // Add entrance animation
        window.addEventListener('load', function() {
            document.querySelector('.login-card').classList.add('animate-in');
        });
    </script>
</body>
</html>