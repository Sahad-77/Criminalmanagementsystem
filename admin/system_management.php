<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
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

// Get system statistics
try {
    // User statistics
    $user_stats_sql = "SELECT 
                        COUNT(*) as total_users,
                        COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_users,
                        COUNT(CASE WHEN role = 'officer' THEN 1 END) as officer_users,
                        COUNT(CASE WHEN role = 'investigator' THEN 1 END) as investigator_users,
                        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_users,
                        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_users
                       FROM users";
    $user_stats_stmt = $conn->prepare($user_stats_sql);
    $user_stats_stmt->execute();
    $user_stats = $user_stats_stmt->fetch(PDO::FETCH_ASSOC);

    // System activity
    $activity_sql = "SELECT 
                      COUNT(*) as total_activities,
                      COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_activities,
                      COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_activities
                     FROM activity_logs";
    $activity_stmt = $conn->prepare($activity_sql);
    $activity_stmt->execute();
    $activity_stats = $activity_stmt->fetch(PDO::FETCH_ASSOC);

    // Recent activity logs
    $recent_activity_sql = "SELECT al.*, u.fullname as user_name 
                            FROM activity_logs al
                            LEFT JOIN users u ON al.user_id = u.id
                            ORDER BY al.created_at DESC 
                            LIMIT 10";
    $recent_activity_stmt = $conn->prepare($recent_activity_sql);
    $recent_activity_stmt->execute();
    $recent_activities = $recent_activity_stmt->fetchAll(PDO::FETCH_ASSOC);

    // System settings
    $settings_sql = "SELECT * FROM system_settings ORDER BY setting_key";
    $settings_stmt = $conn->prepare($settings_sql);
    $settings_stmt->execute();
    $system_settings = $settings_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Database size (approximate)
    $db_size_sql = "SELECT 
                      ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'db_size_mb'
                     FROM information_schema.tables 
                     WHERE table_schema = ?";
    $db_size_stmt = $conn->prepare($db_size_sql);
    $db_size_stmt->execute([$dbname]);
    $db_size = $db_size_stmt->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $user_stats = ['total_users' => 0, 'admin_users' => 0, 'officer_users' => 0, 'investigator_users' => 0, 'active_users' => 0, 'inactive_users' => 0];
    $activity_stats = ['total_activities' => 0, 'today_activities' => 0, 'week_activities' => 0];
    $recent_activities = [];
    $system_settings = [];
    $db_size = ['db_size_mb' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Management - Criminal Management System</title>
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
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
        }

        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            color: #ff4444;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #fff;
            font-weight: 500;
            transition: color 0.3s ease;
            padding: 8px 16px;
            border-radius: 20px;
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

        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .page-header h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #666;
            font-size: 1.1rem;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            font-size: 2.5rem;
            color: #ff4444;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: #666;
            font-weight: 600;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
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
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-header h3 {
            font-size: 1.5rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-header i {
            color: #ff4444;
        }

        /* Activity Logs */
        .activity-list {
            list-style: none;
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #ff4444;
            transition: transform 0.3s ease;
        }

        .activity-item:hover {
            transform: translateX(5px);
        }

        .activity-item h4 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .activity-item p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }

        .activity-time {
            font-size: 0.8rem;
            color: #888;
        }

        /* System Settings */
        .settings-list {
            list-style: none;
        }

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s ease;
        }

        .setting-item:hover {
            background: #f8f9fa;
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-info h4 {
            color: #333;
            margin-bottom: 0.3rem;
        }

        .setting-info p {
            color: #666;
            font-size: 0.9rem;
        }

        .setting-value {
            font-weight: 600;
            color: #ff4444;
        }

        /* Quick Actions */
        .actions-grid {
            display: grid;
            gap: 1rem;
        }

        .btn {
            padding: 15px 24px;
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
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 68, 68, 0.4);
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

        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(220, 53, 69, 0.4);
        }

        /* System Health */
        .health-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .health-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .health-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .health-item:hover {
            transform: translateY(-2px);
        }

        .health-item i {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .health-item.healthy i {
            color: #28a745;
        }

        .health-item.warning i {
            color: #ffc107;
        }

        .health-item.danger i {
            color: #dc3545;
        }

        .health-item h4 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .health-item p {
            color: #666;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .health-grid {
                grid-template-columns: 1fr;
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
                <span><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header fade-in-up">
            <h1><i class="fas fa-cogs"></i> System Management</h1>
            <p>Administrative tools for system configuration, user management, and system monitoring</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card fade-in-up">
                <i class="fas fa-users"></i>
                <div class="stat-number"><?php echo $user_stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-user-shield"></i>
                <div class="stat-number"><?php echo $user_stats['admin_users']; ?></div>
                <div class="stat-label">Administrators</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-shield-alt"></i>
                <div class="stat-number"><?php echo $user_stats['officer_users']; ?></div>
                <div class="stat-label">Police Officers</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-search"></i>
                <div class="stat-number"><?php echo $user_stats['investigator_users']; ?></div>
                <div class="stat-label">Investigators</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-database"></i>
                <div class="stat-number"><?php echo $db_size['db_size_mb']; ?> MB</div>
                <div class="stat-label">Database Size</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-chart-line"></i>
                <div class="stat-number"><?php echo $activity_stats['total_activities']; ?></div>
                <div class="stat-label">Total Activities</div>
            </div>
        </div>

        <!-- System Health Section -->
        <div class="health-section fade-in-up">
            <div class="section-header">
                <h3><i class="fas fa-heartbeat"></i> System Health</h3>
            </div>
            <div class="health-grid">
                <div class="health-item healthy">
                    <i class="fas fa-server"></i>
                    <h4>Database</h4>
                    <p>Connected and operational</p>
                </div>
                <div class="health-item healthy">
                    <i class="fas fa-shield-alt"></i>
                    <h4>Security</h4>
                    <p>All systems secure</p>
                </div>
                <div class="health-item healthy">
                    <i class="fas fa-clock"></i>
                    <h4>Uptime</h4>
                    <p>99.9% availability</p>
                </div>
                <div class="health-item healthy">
                    <i class="fas fa-hdd"></i>
                    <h4>Storage</h4>
                    <p><?php echo $db_size['db_size_mb']; ?> MB used</p>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Activity -->
            <div class="section-card fade-in-up">
                <div class="section-header">
                    <h3><i class="fas fa-history"></i> Recent Activity</h3>
                    <a href="activity_logs.php" class="btn btn-info btn-sm">
                        <i class="fas fa-eye"></i> View All
                    </a>
                </div>
                <ul class="activity-list">
                    <?php if (count($recent_activities) > 0): ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <li class="activity-item">
                                <h4><?php echo htmlspecialchars($activity['action']); ?></h4>
                                <p><strong>User:</strong> <?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?></p>
                                <p><?php echo htmlspecialchars($activity['description']); ?></p>
                                <p class="activity-time">
                                    <i class="fas fa-clock"></i> 
                                    <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                </p>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="activity-item">
                            <p>No recent activity found.</p>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- System Settings -->
            <div class="section-card fade-in-up">
                <div class="section-header">
                    <h3><i class="fas fa-cog"></i> System Settings</h3>
                    <a href="edit_settings.php" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </div>
                <ul class="settings-list">
                    <?php if (count($system_settings) > 0): ?>
                        <?php foreach ($system_settings as $setting): ?>
                            <li class="setting-item">
                                <div class="setting-info">
                                    <h4><?php echo htmlspecialchars($setting['setting_key']); ?></h4>
                                    <p><?php echo htmlspecialchars($setting['description']); ?></p>
                                </div>
                                <div class="setting-value">
                                    <?php echo htmlspecialchars($setting['setting_value']); ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="setting-item">
                            <div class="setting-info">
                                <h4>No Settings Found</h4>
                                <p>System settings will appear here</p>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="section-card fade-in-up">
            <div class="section-header">
                <h3><i class="fas fa-tools"></i> Administrative Actions</h3>
            </div>
            <div class="actions-grid">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <a href="user_management.php" class="btn btn-primary">
                        <i class="fas fa-users-cog"></i> User Management
                    </a>
                    <a href="backup_restore.php" class="btn btn-success">
                        <i class="fas fa-download"></i> Backup & Restore
                    </a>
                    <a href="system_logs.php" class="btn btn-info">
                        <i class="fas fa-file-alt"></i> System Logs
                    </a>
                    <a href="security_settings.php" class="btn btn-warning">
                        <i class="fas fa-lock"></i> Security Settings
                    </a>
                    <a href="database_maintenance.php" class="btn btn-danger">
                        <i class="fas fa-database"></i> Database Maintenance
                    </a>
                    <a href="system_updates.php" class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> System Updates
                    </a>
                </div>
            </div>
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
                const text = stat.textContent;
                const target = parseFloat(text.replace(/[^\d.]/g, ''));
                if (!isNaN(target)) {
                    const duration = 2000;
                    const increment = target / (duration / 16);
                    let current = 0;

                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            current = target;
                            clearInterval(timer);
                        }
                        if (text.includes('MB')) {
                            stat.textContent = Math.floor(current) + ' MB';
                        } else {
                            stat.textContent = Math.floor(current).toLocaleString();
                        }
                    }, 16);
                }
            });
        }

        // Trigger animation when page loads
        setTimeout(animateNumbers, 500);
    </script>
</body>
</html> 