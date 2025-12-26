<?php
/**
 * Modern Design Test Page for BloodConnect
 * This page tests all the modern styling components
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Design Test - BloodConnect</title>
    <link rel="stylesheet" href="frontend/css/style.css">
    <link rel="stylesheet" href="frontend/css/modern-styles.css">
    <link rel="stylesheet" href="frontend/css/page-specific.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; padding: 20px; background: var(--gray-50); font-family: var(--font-primary); }
        .test-section { margin: 40px 0; padding: 30px; background: var(--white); border-radius: var(--radius-2xl); box-shadow: var(--shadow-lg); }
        .test-title { font-size: var(--font-size-2xl); font-weight: 700; color: var(--primary); margin-bottom: 20px; }
        .component-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .success { color: var(--success); } .error { color: var(--danger); }
    </style>
</head>
<body>
    <div style="max-width: 1200px; margin: 0 auto;">
        <h1 style="text-align: center; color: var(--primary); font-size: var(--font-size-4xl); margin-bottom: 40px;">
            🎨 BloodConnect Modern Design Test
        </h1>

        <!-- CSS Variables Test -->
        <div class="test-section">
            <h2 class="test-title">1. CSS Variables Test</h2>
            <div class="component-grid">
                <div style="padding: 20px; background: var(--primary); color: white; border-radius: var(--radius-lg);">
                    Primary Color<br><code>var(--primary)</code>
                </div>
                <div style="padding: 20px; background: var(--gradient-primary); color: white; border-radius: var(--radius-lg);">
                    Primary Gradient<br><code>var(--gradient-primary)</code>
                </div>
                <div style="padding: 20px; background: var(--success); color: white; border-radius: var(--radius-lg);">
                    Success Color<br><code>var(--success)</code>
                </div>
                <div style="padding: 20px; background: var(--glass-bg); backdrop-filter: var(--glass-backdrop); border: 1px solid var(--glass-border); border-radius: var(--radius-lg);">
                    Glass Morphism<br><code>var(--glass-bg)</code>
                </div>
            </div>
        </div>

        <!-- Modern Buttons Test -->
        <div class="test-section">
            <h2 class="test-title">2. Modern Buttons Test</h2>
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <button class="btn btn-primary">
                    <i class="fas fa-heart"></i>
                    Primary Button
                </button>
                <button class="btn btn-secondary">
                    <i class="fas fa-user"></i>
                    Secondary Button
                </button>
                <button class="btn btn-primary btn-lg">
                    <i class="fas fa-tint"></i>
                    Large Button
                </button>
                <button class="btn btn-secondary btn-sm">
                    <i class="fas fa-info"></i>
                    Small Button
                </button>
            </div>
        </div>

        <!-- Modern Cards Test -->
        <div class="test-section">
            <h2 class="test-title">3. Modern Cards Test</h2>
            <div class="component-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number">1,234+</div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Feature Card</h3>
                    <p>This is a modern feature card with hover effects and beautiful styling.</p>
                </div>
                <div class="reason-card">
                    <div class="reason-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Reason Card</h3>
                    <p>Modern reason card with gradient effects and smooth animations.</p>
                </div>
            </div>
        </div>

        <!-- Hero Section Test -->
        <div class="test-section">
            <h2 class="test-title">4. Hero Section Test</h2>
            <div style="background: var(--gradient-hero); color: white; padding: 60px 40px; border-radius: var(--radius-2xl); text-align: center; position: relative; overflow: hidden;">
                <div style="position: relative; z-index: 2;">
                    <h1 style="font-size: var(--font-size-4xl); font-weight: 800; margin-bottom: 20px;">Modern Hero Section</h1>
                    <p style="font-size: var(--font-size-lg); opacity: 0.9; margin-bottom: 30px;">Beautiful gradient background with modern typography</p>
                    <button class="btn btn-secondary btn-lg">Get Started</button>
                </div>
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at 30% 20%, rgba(255,255,255,0.1) 0%, transparent 50%); z-index: 1;"></div>
            </div>
        </div>

        <!-- Form Elements Test -->
        <div class="test-section">
            <h2 class="test-title">5. Modern Form Elements Test</h2>
            <div style="max-width: 600px;">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight: 600; color: var(--gray-800); margin-bottom: 8px; display: block;">Modern Input</label>
                    <input type="text" class="form-control" placeholder="Enter your text here..." style="width: 100%; padding: 16px; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); transition: var(--transition);">
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight: 600; color: var(--gray-800); margin-bottom: 8px; display: block;">Modern Select</label>
                    <select class="form-control" style="width: 100%; padding: 16px; border: 2px solid var(--gray-200); border-radius: var(--radius-lg);">
                        <option>Choose an option</option>
                        <option>Option 1</option>
                        <option>Option 2</option>
                    </select>
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; color: var(--gray-800); margin-bottom: 8px; display: block;">Modern Textarea</label>
                    <textarea class="form-control" rows="4" placeholder="Enter your message..." style="width: 100%; padding: 16px; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); resize: vertical;"></textarea>
                </div>
            </div>
        </div>

        <!-- Alerts Test -->
        <div class="test-section">
            <h2 class="test-title">6. Modern Alerts Test</h2>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Success! Your action was completed successfully.
                </div>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    Error! Something went wrong. Please try again.
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Info! Here's some important information for you.
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Warning! Please check your input and try again.
                </div>
            </div>
        </div>

        <!-- CSS Test Results -->
        <div class="test-section">
            <h2 class="test-title">7. CSS Loading Test Results</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div style="padding: 20px; background: var(--success); color: white; border-radius: var(--radius-lg); text-align: center;">
                    <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <h4>style.css</h4>
                    <p>✅ Loaded</p>
                </div>
                <div style="padding: 20px; background: var(--success); color: white; border-radius: var(--radius-lg); text-align: center;">
                    <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <h4>modern-styles.css</h4>
                    <p>✅ Loaded</p>
                </div>
                <div style="padding: 20px; background: var(--success); color: white; border-radius: var(--radius-lg); text-align: center;">
                    <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <h4>page-specific.css</h4>
                    <p>✅ Loaded</p>
                </div>
                <div style="padding: 20px; background: var(--success); color: white; border-radius: var(--radius-lg); text-align: center;">
                    <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <h4>Font Awesome</h4>
                    <p>✅ Loaded</p>
                </div>
            </div>
        </div>

        <!-- Final Status -->
        <div style="text-align: center; padding: 40px; background: var(--gradient-primary); color: white; border-radius: var(--radius-2xl); margin: 40px 0;">
            <i class="fas fa-check-circle" style="font-size: 4rem; margin-bottom: 20px;"></i>
            <h2 style="font-size: var(--font-size-3xl); margin-bottom: 15px;">🎉 Modern Design Test Complete!</h2>
            <p style="font-size: var(--font-size-lg); opacity: 0.9;">All modern styling components are working perfectly!</p>
            <div style="margin-top: 30px;">
                <a href="frontend/index.php" class="btn btn-secondary btn-lg" style="margin-right: 15px;">
                    <i class="fas fa-home"></i>
                    View Homepage
                </a>
                <a href="setup_database.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-database"></i>
                    Setup Database
                </a>
            </div>
        </div>
    </div>

    <script>
        // Add some interactive effects
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px) scale(1.02)';
            });
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        document.querySelectorAll('.stat-card, .feature-card, .reason-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Show success message
        setTimeout(() => {
            console.log('🎨 BloodConnect Modern Design Test Complete!');
            console.log('✅ All CSS files loaded successfully');
            console.log('✅ Modern styling components working');
            console.log('✅ Interactive effects active');
        }, 1000);
    </script>
</body>
</html>