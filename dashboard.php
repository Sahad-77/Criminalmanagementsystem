<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "criminal_management";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['fullname'];

// Get statistics
$stats = [];
try {
    // Total criminals
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM criminals");
    $stmt->execute();
    $stats['criminals'] = $stmt->fetch()['count'];
    
    // Total cases
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cases");
    $stmt->execute();
    $stats['cases'] = $stmt->fetch()['count'];
    
    // Active cases
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cases WHERE status IN ('open', 'under_investigation')");
    $stmt->execute();
    $stats['active_cases'] = $stmt->fetch()['count'];
    
    // Total officers
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role IN ('officer', 'investigator')");
    $stmt->execute();
    $stats['officers'] = $stmt->fetch()['count'];
    
    // Wanted criminals
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM criminals WHERE status = 'wanted'");
    $stmt->execute();
    $stats['wanted'] = $stmt->fetch()['count'];
    
    // Recent arrests
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM arrests WHERE arrest_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $stats['recent_arrests'] = $stmt->fetch()['count'];
    
} catch(PDOException $e) {
    $stats = ['criminals' => 0, 'cases' => 0, 'active_cases' => 0, 'officers' => 0, 'wanted' => 0, 'recent_arrests' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Criminal Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f4f6f9;
        }

        /* Moving Background */
        .background-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .moving-bg {
            position: absolute;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, 
                rgba(25, 25, 112, 0.8) 0%, 
                rgba(47, 84, 235, 0.8) 25%, 
                rgba(138, 43, 226, 0.8) 50%, 
                rgba(75, 0, 130, 0.8) 75%, 
                rgba(25, 25, 112, 0.8) 100%);
            animation: moveBackground 20s linear infinite;
        }

        .bg-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
        }

        @keyframes moveBackground {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Navigation */
        .navbar {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
              padding: 0.5rem 0; /* Reduced from 1rem 0 */
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
            min-height: 40px; /* Added for compactness */
        }

        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 10px; /* Reduced from 20px */
            min-height: 40px; /* Added for compactness */
        }

        .logo {
            font-size: 1.2rem; /* Reduced from 1.8rem */
            font-weight: bold;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px; /* Reduced from 10px */
        }

        .logo i {
            color: #ff4444;
            font-size: 1.2rem; /* Reduced from default */
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 1.2rem; /* Reduced from 2rem */
        }

        .nav-links a {
            text-decoration: none;
            color: #fff;
            font-weight: 500;
            transition: color 0.3s ease;
            padding: 6px 12px; /* Reduced from 8px 16px */
            border-radius: 16px; /* Reduced from 20px */
            font-size: 0.95rem; /* Slightly smaller */
        }

        .nav-links a:hover {
            color: #ff4444;
            background: rgba(255, 255, 255, 0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
        }

        .user-info .user-name {
            font-weight: 600;
        }

        .logout-btn {
            background: #ff4444;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #cc0000;
        }

        /* Main Content */
        .main-content {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .dashboard-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .dashboard-header p {
            color: #666;
            font-size: 1.1rem;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-card i {
            font-size: 3rem;
            color: #ff4444;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            color: #666;
            font-weight: 600;
        }

        /* Dashboard Sections */
        .dashboard-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .section-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .section-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .section-header i {
            font-size: 2rem;
            color: #ff4444;
        }

        .section-header h3 {
            font-size: 1.5rem;
            color: #333;
        }

        .section-content {
            margin-bottom: 1.5rem;
        }

        .section-content p {
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .section-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 68, 68, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #333 0%, #555 100%);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(51, 51, 51, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.4);
        }

        .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
            color: white;
        }

        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(23, 162, 184, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 193, 7, 0.4);
        }

        /* Recent Activity */
        .recent-activity {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }

        .activity-icon.case { background: #ff4444; }
        .activity-icon.criminal { background: #28a745; }
        .activity-icon.arrest { background: #17a2b8; }
        .activity-icon.officer { background: #ffc107; }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.2rem;
        }

        .activity-time {
            font-size: 0.9rem;
            color: #666;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .dashboard-sections {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .section-actions {
                flex-direction: column;
            }

            .main-content {
                padding: 1rem;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>
    <!-- Moving Background -->
    <div class="background-container">
        <div class="moving-bg"></div>
        <div class="bg-overlay"></div>
    </div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-content">
            <a href="dashboard.php" class="logo">
                <i class="fas fa-shield-alt"></i>
                Criminal Management System
            </a>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="criminal_management.php">Criminal Management</a></li>
                <li><a href="officer_management.php">Officer Management</a></li>
                <li><a href="crime_statistics.php">Crime Statistics</a></li>
                <li><a href="court_management.php">Court Management</a></li>
                <li><a href="system_management.php">System Management</a></li>
            </ul>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                <span>(<?php echo ucfirst($user_role); ?>)</span>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Dashboard Header -->
        <div class="dashboard-header fade-in-up">
            <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
            <p>Here's an overview of your Criminal Management System</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card fade-in-up">
                <i class="fas fa-user-slash"></i>
                <div class="stat-number"><?php echo $stats['criminals']; ?></div>
                <div class="stat-label">Total Criminals</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-folder-open"></i>
                <div class="stat-number"><?php echo $stats['cases']; ?></div>
                <div class="stat-label">Total Cases</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-search"></i>
                <div class="stat-number"><?php echo $stats['active_cases']; ?></div>
                <div class="stat-label">Active Cases</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-users"></i>
                <div class="stat-number"><?php echo $stats['officers']; ?></div>
                <div class="stat-label">Officers</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="stat-number"><?php echo $stats['wanted']; ?></div>
                <div class="stat-label">Wanted Criminals</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-handcuffs"></i>
                <div class="stat-number"><?php echo $stats['recent_arrests']; ?></div>
                <div class="stat-label">Recent Arrests (30 days)</div>
            </div>
        </div>

        <!-- Dashboard Sections -->
        <div class="dashboard-sections">
            <!-- Criminal Management Section -->
            <div class="section-card fade-in-up">
                <div class="section-header">
                    <i class="fas fa-user-slash"></i>
                    <h3>Criminal Management</h3>
                </div>
                <div class="section-content">
                    <p>Manage criminal records, add new criminals, edit existing records, and track criminal activities. Maintain comprehensive profiles with biometric data and case associations.</p>
                </div>
                <div class="section-actions">
                    <a href="criminal_management.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View All Criminals
                    </a>
                    <a href="add_criminal.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add New Criminal
                    </a>
                    <a href="search_criminals.php" class="btn btn-info">
                        <i class="fas fa-search"></i> Search Criminals
                    </a>
                </div>
            </div>

            <!-- Officer Management Section -->
            <div class="section-card fade-in-up">
                <div class="section-header">
                    <i class="fas fa-users"></i>
                    <h3>Officer Management</h3>
                </div>
                <div class="section-content">
                    <p>Manage police officers and investigators. Add new officers, assign cases, track performance, and maintain officer profiles with department assignments and role management.</p>
                </div>
                <div class="section-actions">
                    <a href="officer_management.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View All Officers
                    </a>
                    <a href="add_officer.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add New Officer
                    </a>
                    <a href="officer_assignments.php" class="btn btn-info">
                        <i class="fas fa-tasks"></i> Case Assignments
                    </a>
                </div>
            </div>

            <!-- Crime Statistics Section -->
            <div class="section-card fade-in-up">
                <div class="section-header">
                    <i class="fas fa-chart-bar"></i>
                    <h3>Crime Statistics</h3>
                </div>
                <div class="section-content">
                    <p>Analyze crime patterns, view statistics by location, case type, and time period. Identify high-crime areas and track crime trends for better resource allocation.</p>
                </div>
                <div class="section-actions">
                    <a href="crime_statistics.php" class="btn btn-primary">
                        <i class="fas fa-chart-line"></i> View Statistics
                    </a>
                    <a href="crime_heatmap.php" class="btn btn-warning">
                        <i class="fas fa-map"></i> Crime Heatmap
                    </a>
                    <a href="crime_reports.php" class="btn btn-info">
                        <i class="fas fa-file-alt"></i> Generate Reports
                    </a>
                </div>
            </div>

            <!-- Court Management Section -->
            <div class="section-card fade-in-up">
                <div class="section-header">
                    <i class="fas fa-gavel"></i>
                    <h3>Court Management</h3>
                </div>
                <div class="section-content">
                    <p>Track court appearances, manage case proceedings, record verdicts, and maintain court schedules. Monitor case progress through the judicial system.</p>
                </div>
                <div class="section-actions">
                    <a href="court_management.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> Court Cases
                    </a>
                    <a href="add_court_appearance.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Appearance
                    </a>
                    <a href="court_schedule.php" class="btn btn-info">
                        <i class="fas fa-calendar"></i> Court Schedule
                    </a>
                </div>
            </div>

            <!-- Case Management Section -->
            <div class="section-card fade-in-up">
                <div class="section-header">
                    <i class="fas fa-folder-open"></i>
                    <h3>Case Management</h3>
                </div>
                <div class="section-content">
                    <p>Create and manage investigation cases, assign officers, track evidence, and monitor case progress. Maintain detailed case files with notes and updates.</p>
                </div>
                <div class="section-actions">
                    <a href="case_management.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View All Cases
                    </a>
                    <a href="add_case.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Create New Case
                    </a>
                    <a href="evidence_management.php" class="btn btn-info">
                        <i class="fas fa-microscope"></i> Evidence Management
                    </a>
                </div>
            </div>

            <!-- System Management Section -->
            <div class="section-card fade-in-up">
                <div class="section-header">
                    <i class="fas fa-cogs"></i>
                    <h3>System Management</h3>
                </div>
                <div class="section-content">
                    <p>Manage system settings, user accounts, database maintenance, and system configurations. Monitor system performance and security settings.</p>
                </div>
                <div class="section-actions">
                    <a href="system_management.php" class="btn btn-primary">
                        <i class="fas fa-cog"></i> System Settings
                    </a>
                    <a href="user_management.php" class="btn btn-success">
                        <i class="fas fa-user-cog"></i> User Management
                    </a>
                    <a href="activity_log.php" class="btn btn-info">
                        <i class="fas fa-history"></i> Activity Log
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="recent-activity fade-in-up">
            <h3><i class="fas fa-clock"></i> Recent Activity</h3>
            <?php
            try {
                $sql = "SELECT al.*, u.fullname as user_name 
                        FROM activity_log al 
                        JOIN users u ON al.user_id = u.id 
                        ORDER BY al.created_at DESC 
                        LIMIT 10";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($activities) > 0) {
                    foreach ($activities as $activity) {
                        $icon_class = 'case';
                        if (strpos($activity['action'], 'criminal') !== false) $icon_class = 'criminal';
                        elseif (strpos($activity['action'], 'arrest') !== false) $icon_class = 'arrest';
                        elseif (strpos($activity['action'], 'officer') !== false) $icon_class = 'officer';
                        
                        echo '<div class="activity-item">';
                        echo '<div class="activity-icon ' . $icon_class . '">';
                        echo '<i class="fas fa-' . ($icon_class === 'case' ? 'folder' : ($icon_class === 'criminal' ? 'user-slash' : ($icon_class === 'arrest' ? 'handcuffs' : 'users'))) . '"></i>';
                        echo '</div>';
                        echo '<div class="activity-content">';
                        echo '<div class="activity-title">' . htmlspecialchars($activity['details']) . '</div>';
                        echo '<div class="activity-time">By ' . htmlspecialchars($activity['user_name']) . ' • ' . date('M j, Y g:i A', strtotime($activity['created_at'])) . '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No recent activity to display.</p>';
                }
            } catch(PDOException $e) {
                echo '<p>Unable to load recent activity.</p>';
            }
            ?>
        </div>
    </div>

    <script>
        // Add animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all elements for fade-in animation
        document.querySelectorAll('.fade-in-up').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });

        // Animate statistics numbers
        function animateNumbers() {
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const target = parseInt(stat.textContent);
                const duration = 2000;
                const increment = target / (duration / 16);
                let current = 0;

                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    stat.textContent = Math.floor(current).toLocaleString();
                }, 16);
            });
        }

        // Trigger animation when page loads
        setTimeout(animateNumbers, 500);
    </script>
</body>
</html> 