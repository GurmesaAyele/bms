<?php
session_start();
require_once '../../backend/config/database.php';

$error_message = '';
$success_message = '';

// Handle registration form submission
if ($_POST) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $first_name = trim($_POST['firstName']);
    $last_name = trim($_POST['lastName']);
    $phone = trim($_POST['phone']);
    $blood_type = $_POST['bloodType'];
    $date_of_birth = $_POST['dateOfBirth'];
    $gender = $_POST['gender'];
    $weight = $_POST['weight'];
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zip_code = trim($_POST['zipCode']);
    
    // Validation
    if (empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($blood_type) || empty($date_of_birth) || empty($gender) || empty($weight)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (strlen($password) < 8) {
        $error_message = 'Password must be at least 8 characters long.';
    } elseif ($weight < 50) {
        $error_message = 'Minimum weight requirement (50kg) not met for blood donation.';
    } else {
        try {
            // Check if email already exists
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check_stmt->execute([$email]);
            if ($check_stmt->fetch()) {
                $error_message = 'Email already registered. Please use a different email.';
            } else {
                // Start transaction
                $conn->beginTransaction();
                
                // Insert user
                $user_stmt = $conn->prepare("
                    INSERT INTO users (email, password_hash, user_type, first_name, last_name, phone, address, city, state, zip_code, country, created_at)
                    VALUES (?, ?, 'donor', ?, ?, ?, ?, ?, ?, ?, 'USA', NOW())
                ");
                $user_stmt->execute([
                    $email,
                    password_hash($password, PASSWORD_DEFAULT),
                    $first_name,
                    $last_name,
                    $phone,
                    $address,
                    $city,
                    $state,
                    $zip_code
                ]);
                
                $user_id = $conn->lastInsertId();
                
                // Generate donor ID
                $donor_id = 'DN-' . date('Y') . '-' . str_pad($user_id, 6, '0', STR_PAD_LEFT);
                
                // Insert donor profile
                $donor_stmt = $conn->prepare("
                    INSERT INTO donors (user_id, donor_id, blood_type, date_of_birth, gender, weight, height, 
                                      health_status, preferred_donation_time, emergency_contact_name, emergency_contact_phone, 
                                      known_allergies, medical_conditions, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $donor_stmt->execute([
                    $user_id,
                    $donor_id,
                    $blood_type,
                    $date_of_birth,
                    $gender,
                    $weight,
                    $_POST['height'] ?? null,
                    $_POST['healthStatus'] ?? 'good',
                    $_POST['preferredTime'] ?? 'any',
                    $_POST['emergencyContact'] ?? null,
                    $_POST['emergencyPhone'] ?? null,
                    $_POST['allergies'] ?? null,
                    $_POST['medicalConditions'] ?? null
                ]);
                
                $conn->commit();
                $success_message = 'Registration successful! You can now login with your credentials.';
                
                // Clear form data
                $_POST = array();
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $error_message = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Registration - BloodConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            background: linear-gradient(135deg, #fef2f2, #fee2e2, #fecaca);
            min-height: 100vh;
            font-size: 16px;
        }

        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, #dc2626, #7f1d1d, #450a0a);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 8px 32px rgba(220, 38, 38, 0.3);
            border-bottom: 3px solid #fca5a5;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 800;
            text-decoration: none;
            color: white;
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

        .nav-menu {
            display: flex;
            gap: 2rem;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Registration Container */
        .auth-container-modern {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .auth-content-modern {
            display: flex;
            justify-content: center;
        }

        .auth-card-modern {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(220, 38, 38, 0.15);
            border: 2px solid rgba(254, 202, 202, 0.3);
            overflow: hidden;
            width: 100%;
            position: relative;
        }

        .auth-card-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #dc2626, #7f1d1d, #450a0a);
        }

        .auth-header-modern {
            text-align: center;
            padding: 3rem 2rem 2rem;
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            position: relative;
        }

        .auth-header-modern::after {
            content: '🩸';
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 2rem;
            opacity: 0.1;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .auth-logo-modern {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #dc2626, #7f1d1d);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 30px rgba(220, 38, 38, 0.3);
            border: 3px solid rgba(254, 202, 202, 0.5);
        }

        .auth-logo-modern i {
            font-size: 2rem;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .auth-header-modern h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #7f1d1d;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .auth-header-modern p {
            color: #64748b;
            font-size: 1.1rem;
            font-weight: 500;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            border: 2px solid;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border-color: #6ee7b7;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fca5a5);
            color: #991b1b;
            border-color: #f87171;
        }

        /* Form */
        .auth-form-modern {
            padding: 2rem;
        }

        .form-section {
            margin-bottom: 2.5rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #fefefe, #f8fafc);
            border-radius: 15px;
            border: 2px solid #f1f5f9;
            position: relative;
        }

        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #dc2626, #7f1d1d);
            border-radius: 2px;
        }

        .form-section h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #7f1d1d;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section h3 i {
            color: #dc2626;
            font-size: 1.2rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group-modern {
            margin-bottom: 1.5rem;
        }

        .form-group-modern label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.95rem;
        }

        .form-control-modern {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            font-family: inherit;
        }

        .form-control-modern:focus {
            outline: none;
            border-color: #dc2626;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
            transform: translateY(-1px);
        }

        .form-help {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 0.25rem;
            font-style: italic;
        }

        /* Checkbox */
        .checkbox-label-modern {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            font-weight: 500;
            color: #374151;
            padding: 0.75rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .checkbox-label-modern:hover {
            background: #f8fafc;
        }

        .checkbox-label-modern input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #dc2626;
        }

        /* Button */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            text-transform: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #dc2626, #7f1d1d);
            color: white;
            border: 2px solid transparent;
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #7f1d1d, #450a0a);
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(220, 38, 38, 0.4);
        }

        .btn-lg {
            padding: 1.25rem 2.5rem;
            font-size: 1.2rem;
        }

        .btn-block {
            width: 100%;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .auth-btn-modern {
            margin-top: 1rem;
        }

        /* Footer */
        .auth-footer-modern {
            text-align: center;
            padding: 2rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .auth-footer-modern p {
            margin-bottom: 0.5rem;
            color: #64748b;
        }

        .auth-link-modern {
            color: #dc2626;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .auth-link-modern:hover {
            color: #7f1d1d;
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }

            .auth-container-modern {
                margin: 1rem auto;
                padding: 0 1rem;
            }

            .auth-header-modern {
                padding: 2rem 1rem 1.5rem;
            }

            .auth-header-modern h1 {
                font-size: 2rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .auth-form-modern {
                padding: 1.5rem;
            }

            .form-section {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .auth-header-modern h1 {
                font-size: 1.75rem;
            }

            .btn-lg {
                padding: 1rem 2rem;
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="nav-brand">
                <i class="fas fa-tint"></i>
                <span>BloodConnect</span>
            </a>
            <div class="nav-menu">
                <a href="../index.php" class="nav-link">Home</a>
                <a href="../about.php" class="nav-link">About</a>
                <a href="../contact.php" class="nav-link">Contact</a>
                <a href="login.php" class="nav-link">Login</a>
            </div>
        </div>
    </nav>

    <!-- Registration Container -->
    <div class="auth-container-modern">
        <div class="auth-content-modern single-card">
            <div class="auth-card-modern">
                <div class="auth-header-modern">
                    <div class="auth-logo-modern">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h1>Donor Registration</h1>
                    <p>Register as a blood donor and help save lives</p>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                        <br><a href="login.php" class="btn btn-primary btn-sm" style="margin-top: 10px;">Login Now</a>
                    </div>
                <?php endif; ?>

                <form class="auth-form-modern" method="POST">
                    <!-- Personal Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-user"></i> Personal Information</h3>
                        
                        <div class="form-row">
                            <div class="form-group-modern">
                                <label for="firstName">First Name *</label>
                                <input type="text" id="firstName" name="firstName" class="form-control-modern" required value="<?php echo htmlspecialchars($_POST['firstName'] ?? ''); ?>">
                            </div>
                            <div class="form-group-modern">
                                <label for="lastName">Last Name *</label>
                                <input type="text" id="lastName" name="lastName" class="form-control-modern" required value="<?php echo htmlspecialchars($_POST['lastName'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group-modern">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-control-modern" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>

                        <div class="form-group-modern">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" class="form-control-modern" required minlength="8">
                            <small class="form-help">Minimum 8 characters</small>
                        </div>

                        <div class="form-group-modern">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control-modern" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Medical Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-heartbeat"></i> Medical Information</h3>
                        
                        <div class="form-row">
                            <div class="form-group-modern">
                                <label for="bloodType">Blood Type *</label>
                                <select id="bloodType" name="bloodType" class="form-control-modern" required>
                                    <option value="">Select Blood Type</option>
                                    <option value="A+" <?php echo (($_POST['bloodType'] ?? '') == 'A+') ? 'selected' : ''; ?>>A+</option>
                                    <option value="A-" <?php echo (($_POST['bloodType'] ?? '') == 'A-') ? 'selected' : ''; ?>>A-</option>
                                    <option value="B+" <?php echo (($_POST['bloodType'] ?? '') == 'B+') ? 'selected' : ''; ?>>B+</option>
                                    <option value="B-" <?php echo (($_POST['bloodType'] ?? '') == 'B-') ? 'selected' : ''; ?>>B-</option>
                                    <option value="AB+" <?php echo (($_POST['bloodType'] ?? '') == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                    <option value="AB-" <?php echo (($_POST['bloodType'] ?? '') == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                    <option value="O+" <?php echo (($_POST['bloodType'] ?? '') == 'O+') ? 'selected' : ''; ?>>O+</option>
                                    <option value="O-" <?php echo (($_POST['bloodType'] ?? '') == 'O-') ? 'selected' : ''; ?>>O-</option>
                                </select>
                            </div>
                            <div class="form-group-modern">
                                <label for="dateOfBirth">Date of Birth *</label>
                                <input type="date" id="dateOfBirth" name="dateOfBirth" class="form-control-modern" required value="<?php echo htmlspecialchars($_POST['dateOfBirth'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group-modern">
                                <label for="gender">Gender *</label>
                                <select id="gender" name="gender" class="form-control-modern" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo (($_POST['gender'] ?? '') == 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo (($_POST['gender'] ?? '') == 'female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo (($_POST['gender'] ?? '') == 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group-modern">
                                <label for="weight">Weight (kg) *</label>
                                <input type="number" id="weight" name="weight" class="form-control-modern" min="50" max="300" required value="<?php echo htmlspecialchars($_POST['weight'] ?? ''); ?>">
                                <small class="form-help">Minimum 50kg required for donation</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group-modern">
                                <label for="height">Height (cm)</label>
                                <input type="number" id="height" name="height" class="form-control-modern" min="100" max="250" value="<?php echo htmlspecialchars($_POST['height'] ?? ''); ?>">
                            </div>
                            <div class="form-group-modern">
                                <label for="healthStatus">Health Status</label>
                                <select id="healthStatus" name="healthStatus" class="form-control-modern">
                                    <option value="excellent" <?php echo (($_POST['healthStatus'] ?? 'good') == 'excellent') ? 'selected' : ''; ?>>Excellent</option>
                                    <option value="good" <?php echo (($_POST['healthStatus'] ?? 'good') == 'good') ? 'selected' : ''; ?>>Good</option>
                                    <option value="fair" <?php echo (($_POST['healthStatus'] ?? 'good') == 'fair') ? 'selected' : ''; ?>>Fair</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group-modern">
                            <label for="preferredTime">Preferred Donation Time</label>
                            <select id="preferredTime" name="preferredTime" class="form-control-modern">
                                <option value="any" <?php echo (($_POST['preferredTime'] ?? 'any') == 'any') ? 'selected' : ''; ?>>Any Time</option>
                                <option value="morning" <?php echo (($_POST['preferredTime'] ?? 'any') == 'morning') ? 'selected' : ''; ?>>Morning</option>
                                <option value="afternoon" <?php echo (($_POST['preferredTime'] ?? 'any') == 'afternoon') ? 'selected' : ''; ?>>Afternoon</option>
                                <option value="evening" <?php echo (($_POST['preferredTime'] ?? 'any') == 'evening') ? 'selected' : ''; ?>>Evening</option>
                            </select>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-map-marker-alt"></i> Address Information</h3>
                        
                        <div class="form-group-modern">
                            <label for="address">Street Address</label>
                            <input type="text" id="address" name="address" class="form-control-modern" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group-modern">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" class="form-control-modern" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                            </div>
                            <div class="form-group-modern">
                                <label for="state">State</label>
                                <input type="text" id="state" name="state" class="form-control-modern" value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>">
                            </div>
                            <div class="form-group-modern">
                                <label for="zipCode">ZIP Code</label>
                                <input type="text" id="zipCode" name="zipCode" class="form-control-modern" value="<?php echo htmlspecialchars($_POST['zipCode'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="form-section">
                        <h3><i class="fas fa-phone"></i> Emergency Contact</h3>
                        
                        <div class="form-row">
                            <div class="form-group-modern">
                                <label for="emergencyContact">Emergency Contact Name</label>
                                <input type="text" id="emergencyContact" name="emergencyContact" class="form-control-modern" value="<?php echo htmlspecialchars($_POST['emergencyContact'] ?? ''); ?>">
                            </div>
                            <div class="form-group-modern">
                                <label for="emergencyPhone">Emergency Contact Phone</label>
                                <input type="tel" id="emergencyPhone" name="emergencyPhone" class="form-control-modern" value="<?php echo htmlspecialchars($_POST['emergencyPhone'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Medical History -->
                    <div class="form-section">
                        <h3><i class="fas fa-notes-medical"></i> Medical History (Optional)</h3>
                        
                        <div class="form-group-modern">
                            <label for="allergies">Known Allergies</label>
                            <textarea id="allergies" name="allergies" class="form-control-modern" rows="2" placeholder="List any known allergies..."><?php echo htmlspecialchars($_POST['allergies'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group-modern">
                            <label for="medicalConditions">Medical Conditions</label>
                            <textarea id="medicalConditions" name="medicalConditions" class="form-control-modern" rows="2" placeholder="List any current medical conditions..."><?php echo htmlspecialchars($_POST['medicalConditions'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block auth-btn-modern">
                        <i class="fas fa-heart"></i>
                        Register as Donor
                    </button>
                </form>

                <div class="auth-footer-modern">
                    <p>Already have an account? <a href="login.php" class="auth-link-modern">Sign in here</a></p>
                    <p>Want to register as a different user type? <a href="register.php" class="auth-link-modern">Choose here</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Form validation and enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.auth-form-modern');
            const inputs = form.querySelectorAll('.form-control-modern');
            
            // Add floating label effect
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.classList.remove('focused');
                    }
                });
                
                // Check if input has value on load
                if (input.value) {
                    input.parentElement.classList.add('focused');
                }
            });
            
            // Blood type selection enhancement
            const bloodTypeSelect = document.getElementById('bloodType');
            if (bloodTypeSelect) {
                bloodTypeSelect.addEventListener('change', function() {
                    if (this.value) {
                        this.style.background = 'linear-gradient(135deg, #fee2e2, #fecaca)';
                        this.style.color = '#7f1d1d';
                        this.style.fontWeight = '600';
                    } else {
                        this.style.background = 'white';
                        this.style.color = '#1e293b';
                        this.style.fontWeight = 'normal';
                    }
                });
            }
            
            // Form submission enhancement
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('.btn-primary');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
                submitBtn.disabled = true;
            });
        });
    </script>
</body>
</html>