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
    $hospital_name = trim($_POST['hospitalName']);
    $hospital_type = $_POST['hospitalType'];
    $license_number = trim($_POST['licenseNumber']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zip_code = trim($_POST['zipCode']);
    
    // Validation
    if (empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($hospital_name) || empty($hospital_type) || empty($license_number)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (strlen($password) < 8) {
        $error_message = 'Password must be at least 8 characters long.';
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
                    VALUES (?, ?, 'hospital', ?, ?, ?, ?, ?, ?, ?, 'USA', NOW())
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
                
                // Generate hospital ID
                $hospital_id = 'HP-' . date('Y') . '-' . str_pad($user_id, 6, '0', STR_PAD_LEFT);
                
                // Insert hospital profile
                $hospital_stmt = $conn->prepare("
                    INSERT INTO hospitals (user_id, hospital_id, hospital_name, hospital_type, license_number, 
                                         accreditation_level, bed_capacity, has_blood_bank, emergency_services, 
                                         trauma_center_level, is_24_7, website, emergency_phone, blood_bank_phone, 
                                         is_verified, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, NOW())
                ");
                $hospital_stmt->execute([
                    $user_id,
                    $hospital_id,
                    $hospital_name,
                    $hospital_type,
                    $license_number,
                    $_POST['accreditation'] ?? 'level_1',
                    $_POST['bedCapacity'] ?? null,
                    isset($_POST['hasBloodBank']) ? 1 : 0,
                    isset($_POST['emergencyServices']) ? 1 : 0,
                    $_POST['traumaLevel'] ?? 'none',
                    isset($_POST['is247']) ? 1 : 0,
                    $_POST['website'] ?? null,
                    $_POST['emergencyPhone'] ?? null,
                    $_POST['bloodBankPhone'] ?? null
                ]);
                
                $hospital_profile_id = $conn->lastInsertId();
                
                // Initialize blood inventory if has blood bank
                if (isset($_POST['hasBloodBank'])) {
                    $blood_types = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                    foreach ($blood_types as $blood_type) {
                        $inventory_stmt = $conn->prepare("
                            INSERT INTO blood_inventory (hospital_id, blood_type, units_available, low_stock_threshold, critical_stock_threshold)
                            VALUES (?, ?, 0, 10, 5)
                        ");
                        $inventory_stmt->execute([$hospital_profile_id, $blood_type]);
                    }
                }
                
                $conn->commit();
                $success_message = 'Hospital registration successful! Your account is pending admin approval. You will be notified once approved.';
                
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
    <title>Hospital Registration - BloodConnect</title>
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
            background: linear-gradient(135deg, #eff6ff, #dbeafe, #bfdbfe);
            min-height: 100vh;
            font-size: 16px;
        }

        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, #0ea5e9, #0284c7, #0369a1);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 8px 32px rgba(14, 165, 233, 0.3);
            border-bottom: 3px solid #7dd3fc;
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
            color: #bae6fd;
            text-shadow: 0 0 20px rgba(186, 230, 253, 0.5);
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
            box-shadow: 0 20px 40px rgba(14, 165, 233, 0.15);
            border: 2px solid rgba(186, 230, 253, 0.3);
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
            background: linear-gradient(90deg, #0ea5e9, #0284c7, #0369a1);
        }

        .auth-header-modern {
            text-align: center;
            padding: 3rem 2rem 2rem;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            position: relative;
        }

        .auth-header-modern::after {
            content: '🏥';
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
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 30px rgba(14, 165, 233, 0.3);
            border: 3px solid rgba(186, 230, 253, 0.5);
        }

        .auth-logo-modern i {
            font-size: 2rem;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .auth-header-modern h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #0369a1;
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
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            border-radius: 2px;
        }

        .form-section h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0369a1;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section h3 i {
            color: #0ea5e9;
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
            border-color: #0ea5e9;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
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
            accent-color: #0ea5e9;
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
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: white;
            border: 2px solid transparent;
            box-shadow: 0 8px 25px rgba(14, 165, 233, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(14, 165, 233, 0.4);
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
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .auth-link-modern:hover {
            color: #0284c7;
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
                        <i class="fas fa-hospital"></i>
                    </div>
                    <h1>Hospital Registration</h1>
                    <p>Register your hospital to manage blood inventory and requests</p>
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
                        <br><a href="login.php" class="btn btn-primary btn-sm" style="margin-top: 10px;">Go to Login</a>
                    </div>
                <?php endif; ?>

                <form class="auth-form-modern" method="POST">
                    <!-- Contact Person Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-user"></i> Contact Person Information</h3>
                        
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
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" class="form-control-modern" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Hospital Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-hospital"></i> Hospital Information</h3>
                        
                        <div class="form-group-modern">
                            <label for="hospitalName">Hospital Name *</label>
                            <input type="text" id="hospitalName" name="hospitalName" class="form-control-modern" required value="<?php echo htmlspecialchars($_POST['hospitalName'] ?? ''); ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group-modern">
                                <label for="hospitalType">Hospital Type *</label>
                                <select id="hospitalType" name="hospitalType" class="form-control-modern" required>
                                    <option value="">Select Type</option>
                                    <option value="government" <?php echo (($_POST['hospitalType'] ?? '') == 'government') ? 'selected' : ''; ?>>Government</option>
                                    <option value="private" <?php echo (($_POST['hospitalType'] ?? '') == 'private') ? 'selected' : ''; ?>>Private</option>
                                    <option value="charitable" <?php echo (($_POST['hospitalType'] ?? '') == 'charitable') ? 'selected' : ''; ?>>Charitable</option>
                                    <option value="specialty" <?php echo (($_POST['hospitalType'] ?? '') == 'specialty') ? 'selected' : ''; ?>>Specialty</option>
                                </select>
                            </div>
                            <div class="form-group-modern">
                                <label for="licenseNumber">License Number *</label>
                                <input type="text" id="licenseNumber" name="licenseNumber" class="form-control-modern" required value="<?php echo htmlspecialchars($_POST['licenseNumber'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group-modern">
                                <label for="accreditation">Accreditation Level</label>
                                <select id="accreditation" name="accreditation" class="form-control-modern">
                                    <option value="level_1" <?php echo (($_POST['accreditation'] ?? 'level_1') == 'level_1') ? 'selected' : ''; ?>>Level 1</option>
                                    <option value="level_2" <?php echo (($_POST['accreditation'] ?? 'level_1') == 'level_2') ? 'selected' : ''; ?>>Level 2</option>
                                    <option value="level_3" <?php echo (($_POST['accreditation'] ?? 'level_1') == 'level_3') ? 'selected' : ''; ?>>Level 3</option>
                                    <option value="level_4" <?php echo (($_POST['accreditation'] ?? 'level_1') == 'level_4') ? 'selected' : ''; ?>>Level 4</option>
                                </select>
                            </div>
                            <div class="form-group-modern">
                                <label for="bedCapacity">Bed Capacity</label>
                                <input type="number" id="bedCapacity" name="bedCapacity" class="form-control-modern" min="1" max="10000" value="<?php echo htmlspecialchars($_POST['bedCapacity'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Services -->
                    <div class="form-section">
                        <h3><i class="fas fa-cogs"></i> Services & Facilities</h3>
                        
                        <div class="form-group-modern">
                            <label class="checkbox-label-modern">
                                <input type="checkbox" name="hasBloodBank" value="1" <?php echo isset($_POST['hasBloodBank']) ? 'checked' : ''; ?>>
                                <span class="checkmark-modern"></span>
                                Has Blood Bank
                            </label>
                        </div>

                        <div class="form-group-modern">
                            <label class="checkbox-label-modern">
                                <input type="checkbox" name="emergencyServices" value="1" <?php echo isset($_POST['emergencyServices']) ? 'checked' : ''; ?>>
                                <span class="checkmark-modern"></span>
                                Emergency Services
                            </label>
                        </div>

                        <div class="form-group-modern">
                            <label class="checkbox-label-modern">
                                <input type="checkbox" name="is247" value="1" <?php echo isset($_POST['is247']) ? 'checked' : ''; ?>>
                                <span class="checkmark-modern"></span>
                                24/7 Operations
                            </label>
                        </div>

                        <div class="form-group-modern">
                            <label for="traumaLevel">Trauma Center Level</label>
                            <select id="traumaLevel" name="traumaLevel" class="form-control-modern">
                                <option value="none" <?php echo (($_POST['traumaLevel'] ?? 'none') == 'none') ? 'selected' : ''; ?>>None</option>
                                <option value="level_1" <?php echo (($_POST['traumaLevel'] ?? 'none') == 'level_1') ? 'selected' : ''; ?>>Level 1</option>
                                <option value="level_2" <?php echo (($_POST['traumaLevel'] ?? 'none') == 'level_2') ? 'selected' : ''; ?>>Level 2</option>
                                <option value="level_3" <?php echo (($_POST['traumaLevel'] ?? 'none') == 'level_3') ? 'selected' : ''; ?>>Level 3</option>
                                <option value="level_4" <?php echo (($_POST['traumaLevel'] ?? 'none') == 'level_4') ? 'selected' : ''; ?>>Level 4</option>
                            </select>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-map-marker-alt"></i> Address Information</h3>
                        
                        <div class="form-group-modern">
                            <label for="address">Street Address *</label>
                            <input type="text" id="address" name="address" class="form-control-modern" required value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group-modern">
                                <label for="city">City *</label>
                                <input type="text" id="city" name="city" class="form-control-modern" required value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                            </div>
                            <div class="form-group-modern">
                                <label for="state">State *</label>
                                <input type="text" id="state" name="state" class="form-control-modern" required value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>">
                            </div>
                            <div class="form-group-modern">
                                <label for="zipCode">ZIP Code *</label>
                                <input type="text" id="zipCode" name="zipCode" class="form-control-modern" required value="<?php echo htmlspecialchars($_POST['zipCode'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-phone"></i> Additional Contact Information</h3>
                        
                        <div class="form-row">
                            <div class="form-group-modern">
                                <label for="emergencyPhone">Emergency Phone</label>
                                <input type="tel" id="emergencyPhone" name="emergencyPhone" class="form-control-modern" value="<?php echo htmlspecialchars($_POST['emergencyPhone'] ?? ''); ?>">
                            </div>
                            <div class="form-group-modern">
                                <label for="bloodBankPhone">Blood Bank Phone</label>
                                <input type="tel" id="bloodBankPhone" name="bloodBankPhone" class="form-control-modern" value="<?php echo htmlspecialchars($_POST['bloodBankPhone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group-modern">
                            <label for="website">Website</label>
                            <input type="url" id="website" name="website" class="form-control-modern" placeholder="https://" value="<?php echo htmlspecialchars($_POST['website'] ?? ''); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block auth-btn-modern">
                        <i class="fas fa-hospital"></i>
                        Register Hospital
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
            
            // Hospital type selection enhancement
            const hospitalTypeSelect = document.getElementById('hospitalType');
            if (hospitalTypeSelect) {
                hospitalTypeSelect.addEventListener('change', function() {
                    if (this.value) {
                        this.style.background = 'linear-gradient(135deg, #dbeafe, #bfdbfe)';
                        this.style.color = '#0369a1';
                        this.style.fontWeight = '600';
                    } else {
                        this.style.background = 'white';
                        this.style.color = '#1e293b';
                        this.style.fontWeight = 'normal';
                    }
                });
            }
            
            // Checkbox enhancement
            const checkboxes = form.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const label = this.closest('.checkbox-label-modern');
                    if (this.checked) {
                        label.style.background = 'linear-gradient(135deg, #dbeafe, #bfdbfe)';
                        label.style.color = '#0369a1';
                        label.style.fontWeight = '600';
                    } else {
                        label.style.background = 'transparent';
                        label.style.color = '#374151';
                        label.style.fontWeight = '500';
                    }
                });
            });
            
            // Form submission enhancement
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('.btn-primary');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering Hospital...';
                submitBtn.disabled = true;
            });
        });
    </script>
</body>
</html>