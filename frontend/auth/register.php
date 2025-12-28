<?php
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard/' . $_SESSION['user_type'] . '.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - BloodConnect</title>
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
            background: linear-gradient(135deg, #fef2f2, #fee2e2, #fecaca, #f87171);
            min-height: 100vh;
            font-size: 16px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"%3E%3Cdefs%3E%3Cpattern id="bloodDrop" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"%3E%3Ccircle cx="10" cy="10" r="1" fill="%23dc2626" opacity="0.1"/%3E%3C/pattern%3E%3C/defs%3E%3Crect width="100" height="100" fill="url(%23bloodDrop)"/%3E%3C/svg%3E') repeat;
            pointer-events: none;
            z-index: -1;
        }

        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, #dc2626, #7f1d1d, #450a0a);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 8px 32px rgba(220, 38, 38, 0.3);
            border-bottom: 3px solid #fca5a5;
            position: relative;
            z-index: 100;
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
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .auth-content-modern {
            display: flex;
            justify-content: center;
        }

        .auth-card-modern {
            background: white;
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(220, 38, 38, 0.15);
            border: 3px solid rgba(254, 202, 202, 0.3);
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
            height: 6px;
            background: linear-gradient(90deg, #dc2626, #7f1d1d, #450a0a, #7f1d1d, #dc2626);
            background-size: 200% 100%;
            animation: gradientShift 3s ease-in-out infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
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
            font-size: 2.5rem;
            opacity: 0.1;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(5deg); }
        }

        .auth-logo-modern {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #dc2626, #7f1d1d);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 15px 35px rgba(220, 38, 38, 0.3);
            border: 4px solid rgba(254, 202, 202, 0.5);
            position: relative;
        }

        .auth-logo-modern::before {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            background: linear-gradient(45deg, #dc2626, #7f1d1d, #450a0a, #7f1d1d);
            border-radius: 50%;
            z-index: -1;
            animation: rotate 4s linear infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .auth-logo-modern i {
            font-size: 2.2rem;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }

        .auth-header-modern h1 {
            font-size: 2.8rem;
            font-weight: 800;
            color: #7f1d1d;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #dc2626, #7f1d1d);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .auth-header-modern p {
            color: #64748b;
            font-size: 1.2rem;
            font-weight: 500;
        }

        /* Role Options */
        .role-options-modern {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            padding: 2rem;
        }

        .role-option-modern {
            padding: 2.5rem;
            border: 3px solid #f1f5f9;
            border-radius: 20px;
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.4s ease;
            background: linear-gradient(135deg, #ffffff, #fefefe);
            display: block;
            position: relative;
            overflow: hidden;
        }

        .role-option-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s ease;
        }

        .role-option-modern:hover::before {
            left: 100%;
        }

        .role-option-modern:hover {
            border-color: #dc2626;
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(220, 38, 38, 0.2);
            text-decoration: none;
            color: inherit;
        }

        .role-icon-modern {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            transition: all 0.4s ease;
            position: relative;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .role-option-modern:hover .role-icon-modern {
            transform: scale(1.15) rotate(5deg);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .role-icon-modern i {
            font-size: 2rem;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .donor-logo {
            background: linear-gradient(135deg, #dc2626, #7f1d1d);
            border: 3px solid rgba(254, 202, 202, 0.5);
        }

        .patient-logo {
            background: linear-gradient(135deg, #10b981, #059669);
            border: 3px solid rgba(167, 243, 208, 0.5);
        }

        .hospital-logo {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            border: 3px solid rgba(186, 230, 253, 0.5);
        }

        .role-option-modern h4 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.75rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .role-option-modern p {
            color: #64748b;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .role-features {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            text-align: left;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .role-features small {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #475569;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .role-option-modern:hover .role-features small {
            color: #1e293b;
        }

        .role-features i {
            color: #10b981;
            font-size: 0.9rem;
            width: 16px;
            text-align: center;
        }

        /* Divider */
        .auth-divider-modern {
            text-align: center;
            margin: 2rem 0;
            position: relative;
        }

        .auth-divider-modern::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 2rem;
            right: 2rem;
            height: 2px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
        }

        .auth-divider-modern span {
            background: white;
            padding: 0 1.5rem;
            color: #64748b;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Footer */
        .auth-footer-modern {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-top: 1px solid #e2e8f0;
        }

        .auth-footer-modern p {
            margin-bottom: 0.5rem;
            color: #64748b;
            font-weight: 500;
        }

        .auth-link-modern {
            color: #dc2626;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
            position: relative;
        }

        .auth-link-modern::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #dc2626, #7f1d1d);
            transition: width 0.3s ease;
        }

        .auth-link-modern:hover::after {
            width: 100%;
        }

        .auth-link-modern:hover {
            color: #7f1d1d;
            text-decoration: none;
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
                font-size: 2.2rem;
            }

            .role-options-modern {
                padding: 1.5rem;
                gap: 1.5rem;
            }

            .role-option-modern {
                padding: 2rem;
            }

            .role-icon-modern {
                width: 70px;
                height: 70px;
            }

            .role-icon-modern i {
                font-size: 1.75rem;
            }

            .role-option-modern h4 {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 480px) {
            .auth-header-modern h1 {
                font-size: 1.9rem;
            }

            .role-option-modern {
                padding: 1.5rem;
            }

            .role-icon-modern {
                width: 60px;
                height: 60px;
            }

            .role-icon-modern i {
                font-size: 1.5rem;
            }
        }

        /* Special animations for blood theme */
        @keyframes bloodPulse {
            0%, 100% { 
                box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4);
            }
            50% { 
                box-shadow: 0 0 0 20px rgba(220, 38, 38, 0);
            }
        }

        .donor-logo {
            animation: bloodPulse 3s infinite;
        }

        /* Hover effects for different roles */
        .role-option-modern:hover .donor-logo {
            background: linear-gradient(135deg, #7f1d1d, #450a0a);
        }

        .role-option-modern:hover .patient-logo {
            background: linear-gradient(135deg, #059669, #047857);
        }

        .role-option-modern:hover .hospital-logo {
            background: linear-gradient(135deg, #0284c7, #0369a1);
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
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h1>Join BloodConnect</h1>
                    <p>Choose your role to get started and help save lives</p>
                </div>

                <div class="role-options-modern">
                    <a href="register-patient.php" class="role-option-modern">
                        <div class="role-icon-modern patient-logo">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        <h4>Patient</h4>
                        <p>Register as a patient to request blood when needed for medical treatment and emergency situations</p>
                        <div class="role-features">
                            <small><i class="fas fa-check"></i> Request blood transfusions</small>
                            <small><i class="fas fa-check"></i> Track request status in real-time</small>
                            <small><i class="fas fa-check"></i> Emergency priority requests</small>
                            <small><i class="fas fa-check"></i> Medical history management</small>
                        </div>
                    </a>

                    <a href="register-donor.php" class="role-option-modern">
                        <div class="role-icon-modern donor-logo">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h4>Blood Donor</h4>
                        <p>Register as a donor to help save lives by donating blood regularly and responding to emergency calls</p>
                        <div class="role-features">
                            <small><i class="fas fa-check"></i> Schedule donation appointments</small>
                            <small><i class="fas fa-check"></i> Track donation history & eligibility</small>
                            <small><i class="fas fa-check"></i> Receive emergency donation alerts</small>
                            <small><i class="fas fa-check"></i> Health monitoring & reminders</small>
                        </div>
                    </a>

                    <a href="register-hospital.php" class="role-option-modern">
                        <div class="role-icon-modern hospital-logo">
                            <i class="fas fa-hospital"></i>
                        </div>
                        <h4>Hospital / Blood Bank</h4>
                        <p>Register your medical institution to manage blood inventory, process requests, and coordinate with donors</p>
                        <div class="role-features">
                            <small><i class="fas fa-check"></i> Manage blood inventory levels</small>
                            <small><i class="fas fa-check"></i> Process patient blood requests</small>
                            <small><i class="fas fa-check"></i> Coordinate with registered donors</small>
                            <small><i class="fas fa-check"></i> Generate reports & analytics</small>
                        </div>
                    </a>
                </div>

                <div class="auth-divider-modern">
                    <span>or</span>
                </div>

                <div class="auth-footer-modern">
                    <p>Already have an account? <a href="login.php" class="auth-link-modern">Sign in here</a></p>
                    <p style="margin-top: 1rem; font-size: 0.875rem; color: #6b7280;">
                        By registering, you agree to our <a href="#" class="auth-link-modern">Terms of Service</a> and <a href="#" class="auth-link-modern">Privacy Policy</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Enhanced interactions and animations
        document.addEventListener('DOMContentLoaded', function() {
            const roleOptions = document.querySelectorAll('.role-option-modern');
            
            // Add enhanced hover effects
            roleOptions.forEach(option => {
                const icon = option.querySelector('.role-icon-modern');
                const features = option.querySelectorAll('.role-features small');
                
                option.addEventListener('mouseenter', function() {
                    // Animate features on hover
                    features.forEach((feature, index) => {
                        setTimeout(() => {
                            feature.style.transform = 'translateX(10px)';
                            feature.style.color = '#1e293b';
                        }, index * 100);
                    });
                });
                
                option.addEventListener('mouseleave', function() {
                    // Reset features animation
                    features.forEach(feature => {
                        feature.style.transform = 'translateX(0)';
                        feature.style.color = '#475569';
                    });
                });
                
                // Add click animation
                option.addEventListener('click', function(e) {
                    // Create ripple effect
                    const ripple = document.createElement('div');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        background: radial-gradient(circle, rgba(220, 38, 38, 0.3) 0%, transparent 70%);
                        border-radius: 50%;
                        transform: scale(0);
                        animation: ripple 0.6s ease-out;
                        pointer-events: none;
                        z-index: 1;
                    `;
                    
                    this.style.position = 'relative';
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
            
            // Add CSS for ripple animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(2);
                        opacity: 0;
                    }
                }
                
                .role-features small {
                    transition: all 0.3s ease;
                }
            `;
            document.head.appendChild(style);
            
            // Add blood drop animation to background
            function createBloodDrop() {
                const drop = document.createElement('div');
                drop.innerHTML = '🩸';
                drop.style.cssText = `
                    position: fixed;
                    font-size: ${Math.random() * 20 + 10}px;
                    left: ${Math.random() * 100}vw;
                    top: -50px;
                    opacity: ${Math.random() * 0.3 + 0.1};
                    pointer-events: none;
                    z-index: -1;
                    animation: fall ${Math.random() * 3 + 2}s linear forwards;
                `;
                
                document.body.appendChild(drop);
                
                setTimeout(() => {
                    drop.remove();
                }, 5000);
            }
            
            // Create falling blood drops occasionally
            setInterval(createBloodDrop, 3000);
            
            // Add CSS for falling animation
            const fallStyle = document.createElement('style');
            fallStyle.textContent = `
                @keyframes fall {
                    to {
                        transform: translateY(100vh) rotate(360deg);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(fallStyle);
        });
    </script>
</body>
</html>