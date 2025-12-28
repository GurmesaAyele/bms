<?php
session_start();
require_once '../../backend/config/database.php';

$error_message = '';
$success_message = '';

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