<?php
session_start();
require_once '../backend/config/database.php';

// Get some statistics for the about page
try {
    $stats_stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM users WHERE user_type = 'donor' AND is_active = 1) as donor_count,
            (SELECT COUNT(*) FROM users WHERE user_type = 'hospital' AND is_active = 1) as hospital_count,
            (SELECT COUNT(*) FROM blood_requests WHERE status = 'completed') as completed_requests,
            (SELECT COUNT(*) FROM users WHERE user_type = 'patient' AND is_active = 1) as patient_count
    ");
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch();
} catch (PDOException $e) {
    $stats = [
        'donor_count' => 1000,
        'hospital_count' => 50,
        'completed_requests' => 5000,
        'patient_count' => 2000
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - BloodConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* BloodConnect About Page Styles - Clean & Modern */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            background-color: #ffffff;
            font-size: 16px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            padding: 1rem 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }

        .nav-brand i {
            font-size: 1.8rem;
            color: #fecaca;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 20px;
            list-style: none;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .nav-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            gap: 4px;
        }

        .nav-toggle span {
            width: 25px;
            height: 3px;
            background: white;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        /* About Hero Section */
        .about-hero {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            padding: 80px 0;
            text-align: center;
            position: relative;
        }

        .about-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 40%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 600px;
            margin: 0 auto;
        }

        .hero-content h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 900;
            margin-bottom: 20px;
            color: white;
        }

        .hero-content p {
            font-size: 1.3rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 0;
        }

        /* Section Header */
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header h2 {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 15px;
            position: relative;
        }

        .section-header h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: #dc2626;
            border-radius: 2px;
        }

        .section-header p {
            font-size: 1.1rem;
            color: #64748b;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Mission Section */
        .mission-section {
            padding: 80px 0;
            background: white;
        }

        .mission-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 60px;
            align-items: center;
        }

        .mission-text h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
        }

        .mission-text p {
            font-size: 1.1rem;
            color: #64748b;
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .mission-features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 30px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 10px;
            border-left: 4px solid #dc2626;
        }

        .feature-item i {
            color: #dc2626;
            font-size: 1.2rem;
        }

        .feature-item span {
            font-weight: 600;
            color: #1e293b;
        }

        .mission-image {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .image-placeholder {
            width: 100%;
            height: 300px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }

        .image-placeholder i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .image-placeholder h4 {
            font-size: 1.5rem;
            font-weight: 700;
        }

        /* Stats Section */
        .stats-section {
            background: #f8fafc;
            padding: 80px 0;
            border-top: 1px solid #e2e8f0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 40px 20px;
            text-align: center;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #dc2626;
            box-shadow: 0 10px 25px rgba(220, 38, 38, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.5rem;
            color: white;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 900;
            color: #dc2626;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Values Section */
        .values-section {
            padding: 80px 0;
            background: white;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }

        .value-card {
            background: white;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid #f1f5f9;
        }

        .value-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(220, 38, 38, 0.15);
            border-color: #dc2626;
        }

        .value-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 1.8rem;
            color: white;
        }

        .value-card h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 15px;
        }

        .value-card p {
            color: #64748b;
            line-height: 1.6;
            font-size: 15px;
        }

        /* Team Section (Process Steps) */
        .team-section {
            background: #f8fafc;
            padding: 80px 0;
            border-top: 1px solid #e2e8f0;
        }

        .process-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .step-card {
            background: white;
            padding: 40px 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .step-card:hover {
            transform: translateY(-5px);
            border-color: #dc2626;
            box-shadow: 0 10px 25px rgba(220, 38, 38, 0.15);
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 1.5rem;
            font-weight: 900;
            color: white;
        }

        .step-content h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 15px;
        }

        .step-content p {
            color: #64748b;
            line-height: 1.6;
            font-size: 15px;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            padding: 80px 0;
            text-align: center;
        }

        .cta-content h2 {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            margin-bottom: 20px;
            color: white;
        }

        .cta-content p {
            font-size: 1.2rem;
            margin-bottom: 40px;
            color: rgba(255, 255, 255, 0.9);
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 30px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            min-width: 160px;
            justify-content: center;
        }

        .btn-primary {
            background: white;
            color: #dc2626;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-primary:hover {
            background: #f8fafc;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-secondary:hover {
            background: white;
            color: #dc2626;
            transform: translateY(-3px);
        }

        .btn-lg {
            padding: 18px 36px;
            font-size: 18px;
        }

        /* Footer */
        .footer {
            background: #1e293b;
            color: white;
            padding: 60px 0 30px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.4rem;
            font-weight: 800;
            color: white;
            margin-bottom: 15px;
        }

        .footer-brand i {
            font-size: 1.6rem;
            color: #dc2626;
        }

        .footer-section h4 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #dc2626;
            position: relative;
        }

        .footer-section h4::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 30px;
            height: 2px;
            background: #dc2626;
            border-radius: 1px;
        }

        .footer-section p {
            color: #cbd5e1;
            line-height: 1.6;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 8px;
        }

        .footer-links a {
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 14px;
        }

        .footer-links a:hover {
            color: #dc2626;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }

        .social-link {
            width: 40px;
            height: 40px;
            background: rgba(220, 38, 38, 0.1);
            border: 2px solid rgba(220, 38, 38, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .social-link:hover {
            background: #dc2626;
            color: white;
            border-color: #dc2626;
            transform: translateY(-2px);
        }

        .contact-info {
            margin-top: 15px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: #cbd5e1;
            font-weight: 500;
            font-size: 14px;
        }

        .contact-item i {
            color: #dc2626;
            width: 16px;
            font-size: 14px;
        }

        .footer-bottom {
            border-top: 1px solid rgba(220, 38, 38, 0.2);
            padding-top: 20px;
            text-align: center;
            color: #94a3b8;
            font-size: 14px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .nav-toggle {
                display: flex;
            }
            
            .about-hero {
                padding: 60px 0;
            }
            
            .mission-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .mission-features {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .values-grid,
            .process-steps {
                grid-template-columns: 1fr;
                gap: 25px;
            }
            
            .value-card,
            .step-card {
                padding: 30px 25px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }
            
            .btn {
                width: 100%;
                max-width: 280px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 15px;
            }
            
            .nav-container {
                padding: 0 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }

        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <i class="fas fa-tint"></i>
                <span>BloodConnect</span>
            </div>
            <div class="nav-menu">
                <a href="index.php" class="nav-link">Home</a>
                <a href="about.php" class="nav-link active">About</a>
                <a href="services.php" class="nav-link">Services</a>
                <a href="contact.php" class="nav-link">Contact</a>
                <a href="become-donor.php" class="nav-link">Become a Donor</a>
                <a href="request-blood.php" class="nav-link">Request Blood</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard/<?php echo $_SESSION['user_type']; ?>.php" class="nav-link">Dashboard</a>
                    <a href="auth/logout.php" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="auth/login.php" class="nav-link">Login</a>
                <?php endif; ?>
            </div>
            <div class="nav-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="about-hero">
        <div class="container">
            <div class="hero-content">
                <h1>About BloodConnect</h1>
                <p>Connecting lives through the gift of blood donation</p>
            </div>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="mission-section">
        <div class="container">
            <div class="section-header">
                <h2>Our Mission</h2>
                <p>Saving lives by connecting blood donors with those in need</p>
            </div>
            <div class="mission-content">
                <div class="mission-text">
                    <h3>Bridging the Gap Between Donors and Recipients</h3>
                    <p>BloodConnect is a comprehensive blood donation management system designed to streamline the process of blood donation and distribution. Our platform connects blood donors, patients, hospitals, and blood banks in a seamless network that saves lives.</p>
                    <p>We believe that every drop of blood donated has the potential to save up to three lives. Our mission is to make blood donation more accessible, efficient, and impactful for everyone involved.</p>
                    <div class="mission-features">
                        <div class="feature-item">
                            <i class="fas fa-heart"></i>
                            <span>Compassionate Care</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>Safe & Secure</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-users"></i>
                            <span>Community Driven</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-clock"></i>
                            <span>24/7 Available</span>
                        </div>
                    </div>
                </div>
                <div class="mission-image">
                    <div class="image-placeholder">
                        <i class="fas fa-hands-helping"></i>
                        <h4>Saving Lives Together</h4>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['donor_count'] ?? 0); ?>+</div>
                    <div class="stat-label">Active Donors</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hospital"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['hospital_count'] ?? 0); ?>+</div>
                    <div class="stat-label">Partner Hospitals</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['completed_requests'] ?? 0); ?>+</div>
                    <div class="stat-label">Lives Saved</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['patient_count'] ?? 0); ?>+</div>
                    <div class="stat-label">Registered Patients</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="values-section">
        <div class="container">
            <div class="section-header">
                <h2>Our Core Values</h2>
                <p>The principles that guide everything we do</p>
            </div>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Compassion</h3>
                    <p>We approach every interaction with empathy and understanding, recognizing the critical nature of blood donation.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Safety</h3>
                    <p>Patient safety and donor well-being are our top priorities. We maintain the highest standards of medical safety.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Trust</h3>
                    <p>We build trust through transparency, reliability, and consistent delivery of our promises to all stakeholders.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3>Innovation</h3>
                    <p>We continuously improve our platform using the latest technology to make blood donation more efficient.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Community</h3>
                    <p>We foster a strong community of donors, patients, and healthcare providers working together to save lives.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <h3>Integrity</h3>
                    <p>We operate with the highest ethical standards and maintain complete transparency in all our operations.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
        <div class="container">
            <div class="section-header">
                <h2>How BloodConnect Works</h2>
                <p>Simple steps to save lives</p>
            </div>
            <div class="process-steps">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Register</h3>
                        <p>Sign up as a donor, patient, or hospital to join our life-saving network.</p>
                    </div>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Connect</h3>
                        <p>Our platform matches donors with recipients based on blood type and location.</p>
                    </div>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Donate</h3>
                        <p>Schedule and complete your donation at a convenient time and location.</p>
                    </div>
                </div>
                <div class="step-card">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h3>Save Lives</h3>
                        <p>Your donation helps save up to three lives and makes a real difference.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Make a Difference?</h2>
                <p>Join our community of life-savers and help us build a healthier world</p>
                <div class="cta-buttons">
                    <a href="become-donor.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-heart"></i>
                        Become a Donor
                    </a>
                    <a href="request-blood.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-hand-holding-medical"></i>
                        Request Blood
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-brand">
                        <i class="fas fa-tint"></i>
                        <span>BloodConnect</span>
                    </div>
                    <p>Connecting blood donors with those in need to save lives and build healthier communities.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="services.php">Services</a></li>
                        <li><a href="become-donor.php">Become a Donor</a></li>
                        <li><a href="request-blood.php">Request Blood</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul class="footer-links">
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>+1 (555) 123-4567</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>info@bloodconnect.com</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>123 Health St, Medical City</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 BloodConnect. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>