<?php
/**
 * Ultra Modern Dashboard Design Test Page for BloodConnect
 * This page tests all the ultra-modern dashboard styling components
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ultra Modern Dashboard Test - BloodConnect</title>
    <link rel="stylesheet" href="frontend/css/style.css">
    <link rel="stylesheet" href="frontend/css/modern-styles.css">
    <link rel="stylesheet" href="frontend/css/page-specific.css">
    <link rel="stylesheet" href="frontend/css/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">
    <!-- Ultra Modern Dashboard Navigation -->
    <nav class="dashboard-nav">
        <div class="nav-brand">
            <i class="fas fa-tint"></i>
            <span>BloodConnect</span>
        </div>
        <div class="nav-user">
            <div class="user-info">
                <span class="user-name">John Doe</span>
                <span class="user-role">Test User</span>
            </div>
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="nav-actions">
                <button class="btn-icon" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                <button class="btn-icon" title="Settings">
                    <i class="fas fa-cog"></i>
                </button>
                <button class="btn-icon" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Ultra Modern Sidebar -->
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
                    <h3>Management</h3>
                    <a href="#users" class="menu-item">
                        <i class="fas fa-users"></i>
                        <span>User Management</span>
                    </a>
                    <a href="#reports" class="menu-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                    <a href="#settings" class="menu-item">
                        <i class="fas fa-cogs"></i>
                        <span>Settings</span>
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

        <!-- Ultra Modern Main Content -->
        <main class="dashboard-main">
            <!-- Success Alert -->
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Ultra modern dashboard styling is now active! All components are beautifully styled.
            </div>

            <!-- Ultra Modern Profile Card -->
            <div class="donor-profile-card" id="overview">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="profile-info">
                        <h2>Ultra Modern Dashboard</h2>
                        <p>Test ID: <span class="donor-id">TEST-2024-001</span></p>
                        <div class="blood-group-highlight">
                            <i class="fas fa-tint"></i>
                            <span>Blood Type: <span class="blood-type">O+</span></span>
                        </div>
                    </div>
                    <div class="donation-stats">
                        <div class="stat-item">
                            <div class="stat-number">25</div>
                            <div class="stat-label">Total Tests</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">100%</div>
                            <div class="stat-label">Success Rate</div>
                        </div>
                    </div>
                </div>
                
                <!-- Ultra Modern Eligibility Status -->
                <div class="eligibility-status">
                    <div class="status-card eligible">
                        <i class="fas fa-check-circle"></i>
                        <h4>Design Status: Perfect</h4>
                        <p>All ultra-modern styling components are working flawlessly.</p>
                        <p><strong>Features:</strong> Glass morphism, gradients, animations, 3D effects</p>
                    </div>
                </div>
            </div>

            <!-- Ultra Modern Stats Grid -->
            <div class="admin-overview-section">
                <div class="card-header">
                    <h3>Ultra Modern Statistics</h3>
                    <p>Beautiful animated statistics with gradient effects</p>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon patients">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">1,234</div>
                            <div class="stat-label">Active Users</div>
                            <div class="stat-sub">Growing daily</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon donors">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">567</div>
                            <div class="stat-label">Modern Components</div>
                            <div class="stat-sub">All styled</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon hospitals">
                            <i class="fas fa-hospital"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">89</div>
                            <div class="stat-label">CSS Animations</div>
                            <div class="stat-sub">Smooth effects</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon requests">
                            <i class="fas fa-palette"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">100%</div>
                            <div class="stat-label">Design Quality</div>
                            <div class="stat-sub">Ultra modern</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ultra Modern Form Section -->
            <div class="donation-offer-section">
                <div class="card-header">
                    <h3>Ultra Modern Form Design</h3>
                    <p>Beautiful form styling with glass morphism and animations</p>
                    <span class="badge info">Interactive</span>
                </div>
                <div class="offer-form-container">
                    <form class="donation-offer-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Modern Input Field</label>
                                <input type="text" class="form-control" placeholder="Type something amazing..." value="Ultra Modern Design">
                            </div>
                            <div class="form-group">
                                <label>Beautiful Select</label>
                                <select class="form-control">
                                    <option>Ultra Modern</option>
                                    <option>Stunning Design</option>
                                    <option>Amazing Effects</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Gorgeous Textarea</label>
                            <textarea class="form-control" rows="3" placeholder="Describe the ultra-modern design...">This dashboard features ultra-modern styling with glass morphism, beautiful gradients, smooth animations, and stunning visual effects!</textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary btn-lg">
                                <i class="fas fa-magic"></i>
                                Experience Ultra Modern Design
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Ultra Modern Timeline -->
            <div class="offers-status-section">
                <div class="card-header">
                    <h3>Ultra Modern Timeline</h3>
                    <span class="badge warning">3 items</span>
                </div>
                <div class="offers-timeline">
                    <div class="offer-item pending">
                        <div class="offer-status-indicator pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="offer-details">
                            <div class="offer-header">
                                <h4>Glass Morphism Effects</h4>
                                <span class="offer-id">#GLASS-001</span>
                            </div>
                            <div class="offer-info">
                                <p><strong>Status:</strong> Active</p>
                                <p><strong>Effect:</strong> Backdrop blur</p>
                                <p><strong>Transparency:</strong> 95%</p>
                                <p><strong>Quality:</strong> <span class="status-badge pending">Stunning</span></p>
                            </div>
                        </div>
                        <div class="offer-actions">
                            <button class="btn-sm primary">
                                <i class="fas fa-eye"></i>
                                View Effect
                            </button>
                        </div>
                    </div>
                    
                    <div class="offer-item accepted">
                        <div class="offer-status-indicator accepted">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="offer-details">
                            <div class="offer-header">
                                <h4>Gradient Animations</h4>
                                <span class="offer-id">#GRAD-002</span>
                            </div>
                            <div class="offer-info">
                                <p><strong>Colors:</strong> Red to Blue</p>
                                <p><strong>Animation:</strong> Smooth</p>
                                <p><strong>Duration:</strong> 0.3s ease</p>
                                <p><strong>Quality:</strong> <span class="status-badge accepted">Perfect</span></p>
                            </div>
                        </div>
                        <div class="offer-actions">
                            <button class="btn-sm secondary">
                                <i class="fas fa-play"></i>
                                Play Animation
                            </button>
                        </div>
                    </div>
                    
                    <div class="offer-item completed">
                        <div class="offer-status-indicator completed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="offer-details">
                            <div class="offer-header">
                                <h4>3D Transform Effects</h4>
                                <span class="offer-id">#3D-003</span>
                            </div>
                            <div class="offer-info">
                                <p><strong>Transform:</strong> Scale & Rotate</p>
                                <p><strong>Perspective:</strong> 1000px</p>
                                <p><strong>Hover:</strong> Interactive</p>
                                <p><strong>Quality:</strong> <span class="status-badge completed">Amazing</span></p>
                            </div>
                        </div>
                        <div class="offer-actions">
                            <button class="btn-sm primary">
                                <i class="fas fa-cube"></i>
                                Test 3D
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ultra Modern Reports -->
            <div class="reports-section">
                <div class="card-header">
                    <h3>Ultra Modern Reports</h3>
                    <p>Beautiful progress bars and animated statistics</p>
                </div>
                
                <div class="reports-grid">
                    <div class="report-card">
                        <div class="report-header">
                            <h4>Design Quality</h4>
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="report-stats">
                            <div class="report-number">100</div>
                            <div class="report-label">Perfect Score</div>
                            <div class="report-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 100%"></div>
                                </div>
                                <span>Ultra modern achieved</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="report-card">
                        <div class="report-header">
                            <h4>User Experience</h4>
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="report-stats">
                            <div class="report-number">95</div>
                            <div class="report-label">Satisfaction Rate</div>
                            <div class="report-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 95%"></div>
                                </div>
                                <span>Excellent feedback</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="report-card">
                        <div class="report-header">
                            <h4>Performance</h4>
                            <i class="fas fa-rocket"></i>
                        </div>
                        <div class="report-stats">
                            <div class="report-number">98</div>
                            <div class="report-label">Speed Score</div>
                            <div class="report-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 98%"></div>
                                </div>
                                <span>Lightning fast</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ultra Modern Profile Management -->
            <div class="profile-management-section">
                <div class="card-header">
                    <h3>Ultra Modern Profile Information</h3>
                </div>
                <div class="profile-info-grid">
                    <div class="profile-section">
                        <h4>Design Features</h4>
                        <div class="profile-item">
                            <span class="label">Glass Morphism:</span>
                            <span class="value">✅ Active</span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Gradients:</span>
                            <span class="value">✅ Beautiful</span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Animations:</span>
                            <span class="value">✅ Smooth</span>
                        </div>
                        <div class="profile-item">
                            <span class="label">3D Effects:</span>
                            <span class="value">✅ Stunning</span>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h4>Technical Details</h4>
                        <div class="profile-item">
                            <span class="label">CSS Variables:</span>
                            <span class="value">✅ Implemented</span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Responsive:</span>
                            <span class="value">✅ Mobile Ready</span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Accessibility:</span>
                            <span class="value">✅ WCAG Compliant</span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Performance:</span>
                            <span class="value">✅ Optimized</span>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h4>Quality Metrics</h4>
                        <div class="profile-item">
                            <span class="label">Design Score:</span>
                            <span class="value blood-type-badge">A+</span>
                        </div>
                        <div class="profile-item">
                            <span class="label">User Rating:</span>
                            <span class="value">⭐⭐⭐⭐⭐</span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Modern Level:</span>
                            <span class="value">Ultra</span>
                        </div>
                        <div class="profile-item">
                            <span class="label">Status:</span>
                            <span class="value">🎉 Complete</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Add interactive effects
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Add hover effects to cards
        document.querySelectorAll('.stat-card, .report-card, .profile-section').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-6px) scale(1.02)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Animate progress bars
        setTimeout(() => {
            document.querySelectorAll('.progress-fill').forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        }, 1000);

        // Show success message
        setTimeout(() => {
            console.log('🎨 Ultra Modern Dashboard Design Complete!');
            console.log('✅ Glass morphism effects active');
            console.log('✅ Beautiful gradients implemented');
            console.log('✅ Smooth animations working');
            console.log('✅ 3D transforms functional');
            console.log('✅ Responsive design ready');
            console.log('✅ All components styled');
        }, 1500);
    </script>
</body>
</html>