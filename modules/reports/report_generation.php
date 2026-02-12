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

// Get report statistics
try {
    // Case statistics
    $case_stats_sql = "SELECT 
                        COUNT(*) as total_cases,
                        COUNT(CASE WHEN status = 'open' THEN 1 END) as open_cases,
                        COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_cases,
                        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_cases
                       FROM cases";
    $case_stats_stmt = $conn->prepare($case_stats_sql);
    $case_stats_stmt->execute();
    $case_stats = $case_stats_stmt->fetch(PDO::FETCH_ASSOC);

    // Criminal statistics
    $criminal_stats_sql = "SELECT 
                            COUNT(*) as total_criminals,
                            COUNT(CASE WHEN status = 'wanted' THEN 1 END) as wanted_criminals,
                            COUNT(CASE WHEN status = 'arrested' THEN 1 END) as arrested_criminals,
                            COUNT(CASE WHEN status = 'convicted' THEN 1 END) as convicted_criminals
                           FROM criminals";
    $criminal_stats_stmt = $conn->prepare($criminal_stats_sql);
    $criminal_stats_stmt->execute();
    $criminal_stats = $criminal_stats_stmt->fetch(PDO::FETCH_ASSOC);

    // Officer statistics
    $officer_stats_sql = "SELECT 
                           COUNT(*) as total_officers,
                           COUNT(CASE WHEN role = 'officer' THEN 1 END) as police_officers,
                           COUNT(CASE WHEN role = 'investigator' THEN 1 END) as investigators,
                           COUNT(CASE WHEN status = 'active' THEN 1 END) as active_officers
                          FROM users WHERE role IN ('officer', 'investigator')";
    $officer_stats_stmt = $conn->prepare($officer_stats_sql);
    $officer_stats_stmt->execute();
    $officer_stats = $officer_stats_stmt->fetch(PDO::FETCH_ASSOC);

    // Evidence statistics
    $evidence_stats_sql = "SELECT 
                            COUNT(*) as total_evidence,
                            COUNT(CASE WHEN status = 'collected' THEN 1 END) as collected_evidence,
                            COUNT(CASE WHEN status = 'analyzed' THEN 1 END) as analyzed_evidence
                           FROM evidence";
    $evidence_stats_stmt = $conn->prepare($evidence_stats_sql);
    $evidence_stats_stmt->execute();
    $evidence_stats = $evidence_stats_stmt->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $case_stats = ['total_cases' => 0, 'open_cases' => 0, 'closed_cases' => 0, 'pending_cases' => 0];
    $criminal_stats = ['total_criminals' => 0, 'wanted_criminals' => 0, 'arrested_criminals' => 0, 'convicted_criminals' => 0];
    $officer_stats = ['total_officers' => 0, 'police_officers' => 0, 'investigators' => 0, 'active_officers' => 0];
    $evidence_stats = ['total_evidence' => 0, 'collected_evidence' => 0, 'analyzed_evidence' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Generation - Criminal Management System</title>
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

        /* Report Types Grid */
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .report-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
        }

        .report-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .report-header i {
            font-size: 2.5rem;
            color: #ff4444;
        }

        .report-header h3 {
            font-size: 1.5rem;
            color: #333;
        }

        .report-description {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .report-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
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

        /* Quick Reports Section */
        .quick-reports {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .quick-reports h2 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quick-reports h2 i {
            color: #ff4444;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .reports-grid {
                grid-template-columns: 1fr;
            }

            .report-actions {
                grid-template-columns: 1fr;
            }

            .quick-actions {
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
                <li><a href="evidence_management.php">Evidence Management</a></li>
                <li><a href="report_generation.php">Reports</a></li>
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
            <h1><i class="fas fa-file-alt"></i> Report Generation</h1>
            <p>Generate comprehensive reports and analytics for law enforcement operations</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card fade-in-up">
                <i class="fas fa-folder-open"></i>
                <div class="stat-number"><?php echo $case_stats['total_cases']; ?></div>
                <div class="stat-label">Total Cases</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-user-slash"></i>
                <div class="stat-number"><?php echo $criminal_stats['total_criminals']; ?></div>
                <div class="stat-label">Total Criminals</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-users"></i>
                <div class="stat-number"><?php echo $officer_stats['total_officers']; ?></div>
                <div class="stat-label">Total Officers</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-fingerprint"></i>
                <div class="stat-number"><?php echo $evidence_stats['total_evidence']; ?></div>
                <div class="stat-label">Total Evidence</div>
            </div>
        </div>

        <!-- Quick Reports Section -->
        <div class="quick-reports fade-in-up">
            <h2><i class="fas fa-bolt"></i> Quick Reports</h2>
            <div class="quick-actions">
                <a href="generate_report.php?type=daily_summary" class="btn btn-primary">
                    <i class="fas fa-calendar-day"></i> Daily Summary
                </a>
                <a href="generate_report.php?type=weekly_activity" class="btn btn-success">
                    <i class="fas fa-calendar-week"></i> Weekly Activity
                </a>
                <a href="generate_report.php?type=monthly_statistics" class="btn btn-info">
                    <i class="fas fa-chart-bar"></i> Monthly Statistics
                </a>
                <a href="generate_report.php?type=case_status" class="btn btn-warning">
                    <i class="fas fa-clipboard-list"></i> Case Status Report
                </a>
            </div>
        </div>

        <!-- Report Types Grid -->
        <div class="reports-grid">
            <!-- Case Reports -->
            <div class="report-card fade-in-up">
                <div class="report-header">
                    <i class="fas fa-folder"></i>
                    <h3>Case Reports</h3>
                </div>
                <div class="report-description">
                    Generate detailed case reports including investigation progress, evidence collected, and court proceedings.
                </div>
                <div class="report-actions">
                    <a href="generate_report.php?type=case_summary" class="btn btn-primary">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="generate_report.php?type=case_summary&format=excel" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                </div>
            </div>

            <!-- Criminal Reports -->
            <div class="report-card fade-in-up">
                <div class="report-header">
                    <i class="fas fa-user-slash"></i>
                    <h3>Criminal Reports</h3>
                </div>
                <div class="report-description">
                    Comprehensive criminal profiles, arrest records, and wanted persons lists with detailed information.
                </div>
                <div class="report-actions">
                    <a href="generate_report.php?type=criminal_profile" class="btn btn-primary">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="generate_report.php?type=criminal_profile&format=excel" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                </div>
            </div>

            <!-- Officer Performance -->
            <div class="report-card fade-in-up">
                <div class="report-header">
                    <i class="fas fa-users"></i>
                    <h3>Officer Performance</h3>
                </div>
                <div class="report-description">
                    Officer activity reports, case assignments, and performance metrics for evaluation and training.
                </div>
                <div class="report-actions">
                    <a href="generate_report.php?type=officer_performance" class="btn btn-primary">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="generate_report.php?type=officer_performance&format=excel" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                </div>
            </div>

            <!-- Evidence Reports -->
            <div class="report-card fade-in-up">
                <div class="report-header">
                    <i class="fas fa-fingerprint"></i>
                    <h3>Evidence Reports</h3>
                </div>
                <div class="report-description">
                    Evidence inventory, chain of custody reports, and analysis results for legal proceedings.
                </div>
                <div class="report-actions">
                    <a href="generate_report.php?type=evidence_inventory" class="btn btn-primary">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="generate_report.php?type=evidence_inventory&format=excel" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                </div>
            </div>

            <!-- Court Reports -->
            <div class="report-card fade-in-up">
                <div class="report-header">
                    <i class="fas fa-gavel"></i>
                    <h3>Court Reports</h3>
                </div>
                <div class="report-description">
                    Court appearance schedules, case status updates, and legal document summaries for court proceedings.
                </div>
                <div class="report-actions">
                    <a href="generate_report.php?type=court_schedule" class="btn btn-primary">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="generate_report.php?type=court_schedule&format=excel" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                </div>
            </div>

            <!-- Statistical Reports -->
            <div class="report-card fade-in-up">
                <div class="report-header">
                    <i class="fas fa-chart-line"></i>
                    <h3>Statistical Reports</h3>
                </div>
                <div class="report-description">
                    Crime statistics, trend analysis, and comparative reports for strategic planning and resource allocation.
                </div>
                <div class="report-actions">
                    <a href="generate_report.php?type=crime_statistics" class="btn btn-primary">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="generate_report.php?type=crime_statistics&format=excel" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Excel
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
                const target = parseInt(stat.textContent);
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
                        stat.textContent = Math.floor(current).toLocaleString();
                    }, 16);
                }
            });
        }

        // Trigger animation when page loads
        setTimeout(animateNumbers, 500);
    </script>
</body>
</html> 