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

// Get statistics
$stats = [];
try {
    // Total cases
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cases");
    $stmt->execute();
    $stats['total_cases'] = $stmt->fetch()['count'];

    // Solved cases
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cases WHERE status = 'closed'");
    $stmt->execute();
    $stats['solved_cases'] = $stmt->fetch()['count'];

    // Unsolved cases
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cases WHERE status IN ('open','under_investigation','pending_trial','cold_case')");
    $stmt->execute();
    $stats['unsolved_cases'] = $stmt->fetch()['count'];

    // Total arrests
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM arrests");
    $stmt->execute();
    $stats['arrests'] = $stmt->fetch()['count'];

    // Total criminals
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM criminals");
    $stmt->execute();
    $stats['criminals'] = $stmt->fetch()['count'];

    // Total officers/investigators
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role IN ('officer','investigator')");
    $stmt->execute();
    $stats['officers'] = $stmt->fetch()['count'];

    // Crime by type
    $stmt = $conn->prepare("SELECT case_type, COUNT(*) as count FROM cases GROUP BY case_type ORDER BY count DESC");
    $stmt->execute();
    $crime_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Crime by location
    $stmt = $conn->prepare("SELECT location, COUNT(*) as count FROM cases GROUP BY location ORDER BY count DESC LIMIT 10");
    $stmt->execute();
    $crime_locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Monthly crime trend (last 12 months)
    $stmt = $conn->prepare("SELECT DATE_FORMAT(incident_date, '%Y-%m') as month, COUNT(*) as count FROM cases WHERE incident_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month ASC");
    $stmt->execute();
    $crime_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top officers by cases handled
    $stmt = $conn->prepare("SELECT u.fullname, COUNT(c.id) as cases_handled FROM users u LEFT JOIN cases c ON u.id = c.assigned_officer GROUP BY u.id ORDER BY cases_handled DESC LIMIT 5");
    $stmt->execute();
    $top_officers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $stats = ['total_cases'=>0,'solved_cases'=>0,'unsolved_cases'=>0,'arrests'=>0,'criminals'=>0,'officers'=>0];
    $crime_types = [];
    $crime_locations = [];
    $crime_trend = [];
    $top_officers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Crime Statistics - Criminal Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Referenced from dashboard.php and other files */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .background-container {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%; z-index: -1; overflow: hidden;
        }
        .moving-bg {
            position: absolute; width: 200%; height: 200%;
            background: linear-gradient(45deg, rgba(25,25,112,0.8) 0%, rgba(47,84,235,0.8) 25%, rgba(138,43,226,0.8) 50%, rgba(75,0,130,0.8) 75%, rgba(25,25,112,0.8) 100%);
            animation: moveBackground 20s linear infinite;
        }
        .bg-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4);
        }
        @keyframes moveBackground {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        .navbar {
            background: rgba(0,0,0,0.9);
            backdrop-filter: blur(10px);
            padding: 0.5rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0,0,0,0.3);
        }
        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 10px;
        }
        .logo {
            font-size: 1.2rem;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .logo i { color: #ff4444; }
        .nav-links {
            display: flex;
            list-style: none;
            gap: 1.2rem;
        }
        .nav-links a {
            text-decoration: none;
            color: #fff;
            font-weight: 500;
            transition: color 0.3s ease;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 0.95rem;
        }
        .nav-links a:hover,
        .nav-links a.active {
            color: #ff4444;
            background: rgba(255,255,255,0.1);
        }
        .main-content {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
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
            font-size: 1.1rem;
            color: #666;
            font-weight: 600;
        }
        .section-card {
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
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
        .chart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }
        .chart-table th, .chart-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        .chart-table th {
            background: #f4f6fa;
            color: #333;
        }
        .chart-table tr:hover {
            background: #f9f9f9;
        }
        .trend-bar {
            display: inline-block;
            height: 18px;
            border-radius: 8px;
            background: linear-gradient(90deg, #ff4444 0%, #cc0000 100%);
            margin-right: 8px;
        }
        .trend-label {
            font-size: 0.95rem;
            color: #555;
        }
        .top-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .top-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        .top-list li:last-child {
            border-bottom: none;
        }
        @media (max-width: 900px) {
            .main-content { padding: 1rem; }
            .stats-grid { grid-template-columns: 1fr; gap: 1rem; }
            .section-card { padding: 1rem; border-radius: 12px; }
        }
    </style>
</head>
<body>
    <div class="background-container">
        <div class="moving-bg"></div>
        <div class="bg-overlay"></div>
    </div>
    <nav class="navbar">
        <div class="nav-content">
            <a href="dashboard.php" class="logo">
                <i class="fas fa-chart-bar"></i>
                Crime Statistics
            </a>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="criminal_management.php">Criminal Management</a></li>
                <li><a href="officer_management.php">Officer Management</a></li>
                <li><a href="crime_statistics.php" class="active">Crime Statistics</a></li>
                <li><a href="court_management.php">Court Management</a></li>
                <li><a href="system_management.php">System Management</a></li>
            </ul>
        </div>
    </nav>
    <div class="main-content">
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-folder-open"></i>
                <div class="stat-number"><?php echo $stats['total_cases']; ?></div>
                <div class="stat-label">Total Cases</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <div class="stat-number"><?php echo $stats['solved_cases']; ?></div>
                <div class="stat-label">Solved Cases</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-exclamation-circle"></i>
                <div class="stat-number"><?php echo $stats['unsolved_cases']; ?></div>
                <div class="stat-label">Unsolved Cases</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-handcuffs"></i>
                <div class="stat-number"><?php echo $stats['arrests']; ?></div>
                <div class="stat-label">Total Arrests</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-slash"></i>
                <div class="stat-number"><?php echo $stats['criminals']; ?></div>
                <div class="stat-label">Criminals</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <div class="stat-number"><?php echo $stats['officers']; ?></div>
                <div class="stat-label">Officers/Investigators</div>
            </div>
        </div>
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-chart-pie"></i>
                <h3>Crimes by Type</h3>
            </div>
            <table class="chart-table">
                <thead>
                    <tr>
                        <th>Crime Type</th>
                        <th>Cases</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($crime_types as $row): ?>
                        <tr>
                            <td><?php echo ucfirst(str_replace('_',' ', $row['case_type'])); ?></td>
                            <td><?php echo $row['count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-map-marker-alt"></i>
                <h3>Top Crime Locations</h3>
            </div>
            <table class="chart-table">
                <thead>
                    <tr>
                        <th>Location</th>
                        <th>Cases</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($crime_locations as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td><?php echo $row['count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-chart-line"></i>
                <h3>Monthly Crime Trend (Last 12 Months)</h3>
            </div>
            <table class="chart-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Cases</th>
                        <th>Trend</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $max_trend = 0;
                    foreach ($crime_trend as $row) {
                        if ($row['count'] > $max_trend) $max_trend = $row['count'];
                    }
                    foreach ($crime_trend as $row):
                        $bar_width = $max_trend ? intval(($row['count']/$max_trend)*120) : 0;
                    ?>
                        <tr>
                            <td><?php echo $row['month']; ?></td>
                            <td><?php echo $row['count']; ?></td>
                            <td>
                                <span class="trend-bar" style="width:<?php echo $bar_width; ?>px"></span>
                                <span class="trend-label"><?php echo $row['count']; ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-user-tie"></i>
                <h3>Top Officers by Cases Handled</h3>
            </div>
            <ul class="top-list">
                <?php foreach ($top_officers as $officer): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($officer['fullname']); ?></strong>
                        <span style="color:#666;">— <?php echo $officer['cases_handled']; ?> cases</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</body>
</html>