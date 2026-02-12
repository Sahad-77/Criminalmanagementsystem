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

$message = '';
$message_type = '';

// Handle backup creation
if (isset($_POST['create_backup'])) {
    try {
        $backup_name = 'criminal_management_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backup_path = 'backups/' . $backup_name;
        
        // Create backups directory if it doesn't exist
        if (!is_dir('backups')) {
            mkdir('backups', 0755, true);
        }
        
        // Create backup using mysqldump
        $command = "mysqldump -h $servername -u $username";
        if (!empty($password)) {
            $command .= " -p$password";
        }
        $command .= " $dbname > $backup_path";
        
        exec($command, $output, $return_var);
        
        if ($return_var === 0) {
            // Log backup creation
            $activity_sql = "INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (?, ?, ?, NOW())";
            $activity_stmt = $conn->prepare($activity_sql);
            $activity_stmt->execute([$_SESSION['user_id'], 'CREATE_BACKUP', "Created backup: $backup_name"]);
            
            $message = "Backup created successfully: $backup_name";
            $message_type = 'success';
        } else {
            $message = "Error creating backup. Please check your MySQL configuration.";
            $message_type = 'error';
        }
    } catch(Exception $e) {
        $message = "Error creating backup: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle backup restoration
if (isset($_POST['restore_backup']) && isset($_POST['backup_file'])) {
    try {
        $backup_file = $_POST['backup_file'];
        $backup_path = 'backups/' . $backup_file;
        
        if (file_exists($backup_path)) {
            // Create restore command
            $command = "mysql -h $servername -u $username";
            if (!empty($password)) {
                $command .= " -p$password";
            }
            $command .= " $dbname < $backup_path";
            
            exec($command, $output, $return_var);
            
            if ($return_var === 0) {
                // Log backup restoration
                $activity_sql = "INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (?, ?, ?, NOW())";
                $activity_stmt = $conn->prepare($activity_sql);
                $activity_stmt->execute([$_SESSION['user_id'], 'RESTORE_BACKUP', "Restored backup: $backup_file"]);
                
                $message = "Backup restored successfully: $backup_file";
                $message_type = 'success';
            } else {
                $message = "Error restoring backup. Please check your MySQL configuration.";
                $message_type = 'error';
            }
        } else {
            $message = "Backup file not found.";
            $message_type = 'error';
        }
    } catch(Exception $e) {
        $message = "Error restoring backup: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle backup deletion
if (isset($_POST['delete_backup']) && isset($_POST['backup_file'])) {
    try {
        $backup_file = $_POST['backup_file'];
        $backup_path = 'backups/' . $backup_file;
        
        if (file_exists($backup_path)) {
            if (unlink($backup_path)) {
                // Log backup deletion
                $activity_sql = "INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (?, ?, ?, NOW())";
                $activity_stmt = $conn->prepare($activity_sql);
                $activity_stmt->execute([$_SESSION['user_id'], 'DELETE_BACKUP', "Deleted backup: $backup_file"]);
                
                $message = "Backup deleted successfully: $backup_file";
                $message_type = 'success';
            } else {
                $message = "Error deleting backup file.";
                $message_type = 'error';
            }
        } else {
            $message = "Backup file not found.";
            $message_type = 'error';
        }
    } catch(Exception $e) {
        $message = "Error deleting backup: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Get existing backups
$backups = [];
if (is_dir('backups')) {
    $backup_files = glob('backups/*.sql');
    foreach ($backup_files as $file) {
        $backups[] = [
            'filename' => basename($file),
            'size' => filesize($file),
            'date' => filemtime($file)
        ];
    }
    // Sort by date (newest first)
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

// Get database statistics
try {
    $db_stats_sql = "SELECT 
                        COUNT(*) as total_tables,
                        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'db_size_mb'
                     FROM information_schema.tables 
                     WHERE table_schema = ?";
    $db_stats_stmt = $conn->prepare($db_stats_sql);
    $db_stats_stmt->execute([$dbname]);
    $db_stats = $db_stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get table sizes
    $table_sizes_sql = "SELECT 
                          table_name,
                          ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb'
                        FROM information_schema.tables 
                        WHERE table_schema = ?
                        ORDER BY (data_length + index_length) DESC";
    $table_sizes_stmt = $conn->prepare($table_sizes_sql);
    $table_sizes_stmt->execute([$dbname]);
    $table_sizes = $table_sizes_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $db_stats = ['total_tables' => 0, 'db_size_mb' => 0];
    $table_sizes = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup System - Criminal Management System</title>
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

        /* Message */
        .message {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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

        /* Content Sections */
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

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.4);
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

        /* Backup List */
        .backup-list {
            list-style: none;
            max-height: 400px;
            overflow-y: auto;
        }

        .backup-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #ff4444;
            transition: transform 0.3s ease;
        }

        .backup-item:hover {
            transform: translateX(5px);
        }

        .backup-item h4 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .backup-item p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }

        .backup-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        /* Table Sizes */
        .table-sizes {
            list-style: none;
            max-height: 300px;
            overflow-y: auto;
        }

        .table-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            border-bottom: 1px solid #eee;
        }

        .table-item:last-child {
            border-bottom: none;
        }

        .table-name {
            font-weight: 600;
            color: #333;
        }

        .table-size {
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

            .backup-actions {
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
                <li><a href="evidence_management.php">Evidence Management</a></li>
                <li><a href="report_generation.php">Reports</a></li>
                <li><a href="document_management.php">Documents</a></li>
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
            <h1><i class="fas fa-database"></i> Backup System</h1>
            <p>Database backup, restore, and management for data protection</p>
        </div>

        <!-- Message Display -->
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?> fade-in-up">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card fade-in-up">
                <i class="fas fa-database"></i>
                <div class="stat-number"><?php echo $db_stats['total_tables']; ?></div>
                <div class="stat-label">Total Tables</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-hdd"></i>
                <div class="stat-number"><?php echo $db_stats['db_size_mb']; ?> MB</div>
                <div class="stat-label">Database Size</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-download"></i>
                <div class="stat-number"><?php echo count($backups); ?></div>
                <div class="stat-label">Available Backups</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-clock"></i>
                <div class="stat-number">
                    <?php 
                    echo count($backups) > 0 ? 
                        date('M j', $backups[0]['date']) : 'N/A';
                    ?>
                </div>
                <div class="stat-label">Latest Backup</div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Backup Management -->
            <div class="section-card fade-in-up">
                <div class="section-header">
                    <h3><i class="fas fa-download"></i> Backup Management</h3>
                </div>
                
                <form method="POST" style="margin-bottom: 1.5rem;">
                    <button type="submit" name="create_backup" class="btn btn-success" style="width: 100%;">
                        <i class="fas fa-plus"></i> Create New Backup
                    </button>
                </form>

                <h4>Available Backups</h4>
                <ul class="backup-list">
                    <?php if (count($backups) > 0): ?>
                        <?php foreach ($backups as $backup): ?>
                            <li class="backup-item">
                                <h4><?php echo htmlspecialchars($backup['filename']); ?></h4>
                                <p><strong>Size:</strong> <?php echo round($backup['size'] / 1024, 2); ?> KB</p>
                                <p><strong>Date:</strong> <?php echo date('M j, Y g:i A', $backup['date']); ?></p>
                                <div class="backup-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                        <button type="submit" name="restore_backup" class="btn btn-warning btn-sm" 
                                                onclick="return confirm('Are you sure you want to restore this backup? This will overwrite current data.')">
                                            <i class="fas fa-undo"></i> Restore
                                        </button>
                                    </form>
                                    <a href="backups/<?php echo htmlspecialchars($backup['filename']); ?>" 
                                       class="btn btn-primary btn-sm" download>
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                        <button type="submit" name="delete_backup" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Are you sure you want to delete this backup?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="backup-item">
                            <p>No backups available. Create your first backup to get started.</p>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Database Information -->
            <div class="section-card fade-in-up">
                <div class="section-header">
                    <h3><i class="fas fa-info-circle"></i> Database Information</h3>
                </div>
                
                <h4>Table Sizes</h4>
                <ul class="table-sizes">
                    <?php foreach ($table_sizes as $table): ?>
                        <li class="table-item">
                            <span class="table-name"><?php echo htmlspecialchars($table['table_name']); ?></span>
                            <span class="table-size"><?php echo $table['size_mb']; ?> MB</span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div style="margin-top: 2rem;">
                    <h4>Backup Recommendations</h4>
                    <ul style="color: #666; font-size: 0.9rem;">
                        <li>Create backups before major system updates</li>
                        <li>Store backups in a secure, off-site location</li>
                        <li>Test restore procedures regularly</li>
                        <li>Keep multiple backup versions</li>
                        <li>Monitor backup file sizes and storage space</li>
                    </ul>
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