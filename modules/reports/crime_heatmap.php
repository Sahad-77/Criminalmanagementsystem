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

// Get crime data for heatmap (by case_type)
$heatmap_sql = "SELECT 
                    case_type,
                    COUNT(*) as crime_count,
                    COUNT(CASE WHEN status IN ('open','under_investigation','pending_trial') THEN 1 END) as active_cases,
                    COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_cases
                FROM cases
                GROUP BY case_type
                ORDER BY crime_count DESC";
$heatmap_stmt = $conn->prepare($heatmap_sql);
$heatmap_stmt->execute();
$heatmap_data = $heatmap_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get monthly crime trends
$trends_sql = "SELECT 
                    DATE_FORMAT(incident_date, '%Y-%m') as month,
                    COUNT(*) as crime_count
                 FROM cases
                 WHERE incident_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                 GROUP BY DATE_FORMAT(incident_date, '%Y-%m')
                 ORDER BY month";
$trends_stmt = $conn->prepare($trends_sql);
$trends_stmt->execute();
$trends_data = $trends_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total crimes, hotspots, trend rate, avg criminal age
$total_crimes = array_sum(array_column($heatmap_data, 'crime_count'));
$hotspot_count = count(array_filter($heatmap_data, function($row){ return $row['crime_count'] > 10; }));
$avg_age = 0;
$criminals_stmt = $conn->prepare("SELECT AVG(TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE())) as avg_age FROM criminals");
$criminals_stmt->execute();
$avg_age = round($criminals_stmt->fetchColumn());

// Calculate trend rate
$trend_rate = 0;
if (count($trends_data) >= 2) {
    $recent = $trends_data[count($trends_data)-1]['crime_count'];
    $previous = $trends_data[count($trends_data)-2]['crime_count'];
    $trend_rate = $previous ? (($recent - $previous) / $previous) * 100 : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Crime Heatmap - Criminal Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Dashboard CSS reference */
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
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 2rem;
        }
        .chart-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
            text-align: center;
        }
        .heatmap-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .heatmap-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-left: 5px solid #ff4444;
        }
        .heatmap-card:hover {
            transform: translateY(-5px);
        }
        .heatmap-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .crime-type {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }
        .crime-count {
            background: #ff4444;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .heatmap-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        .detail-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.2rem;
        }
        .detail-value {
            font-weight: 600;
            color: #333;
        }
        .heatmap-progress {
            margin-top: 1rem;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e1e5e9;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff4444, #cc0000);
            transition: width 0.3s ease;
        }
        .progress-label {
            font-size: 0.8rem;
            color: #666;
            text-align: center;
        }
        @media (max-width: 900px) {
            .main-content { padding: 1rem; }
            .stats-grid { grid-template-columns: 1fr; gap: 1rem; }
            .section-card { padding: 1rem; border-radius: 12px; }
            .heatmap-grid { grid-template-columns: 1fr; }
            .heatmap-details { grid-template-columns: 1fr; }
            .chart-container { height: 300px; }
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
                <i class="fas fa-fire"></i>
                Crime Heatmap
            </a>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="criminal_management.php">Criminal Management</a></li>
                <li><a href="officer_management.php">Officer Management</a></li>
                <li><a href="crime_statistics.php">Crime Statistics</a></li>
                <li><a href="court_management.php">Court Management</a></li>
                <li><a href="system_management.php">System Management</a></li>
            </ul>
        </div>
    </nav>
    <div class="main-content">
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="stat-number"><?php echo $total_crimes; ?></div>
                <div class="stat-label">Total Crimes</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-fire"></i>
                <div class="stat-number"><?php echo $hotspot_count; ?></div>
                <div class="stat-label">Crime Hotspots</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-chart-line"></i>
                <div class="stat-number"><?php echo ($trend_rate > 0 ? '+' : '') . number_format($trend_rate, 1); ?>%</div>
                <div class="stat-label">Trend Rate</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-shield-alt"></i>
                <div class="stat-number"><?php echo $avg_age; ?></div>
                <div class="stat-label">Avg Criminal Age</div>
            </div>
        </div>
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-chart-line"></i>
                <h3>Monthly Crime Trend (Last 12 Months)</h3>
            </div>
            <div class="chart-container">
                <canvas id="trendsChart"></canvas>
            </div>
        </div>
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-fire"></i>
                <h3>Crime Type Heatmap</h3>
            </div>
            <div class="heatmap-grid">
                <?php foreach ($heatmap_data as $crime): ?>
                    <div class="heatmap-card">
                        <div class="heatmap-header">
                            <div class="crime-type"><?php echo ucfirst(str_replace('_',' ', $crime['case_type'])); ?></div>
                            <div class="crime-count"><?php echo $crime['crime_count']; ?></div>
                        </div>
                        <div class="heatmap-details">
                            <div class="detail-item">
                                <span class="detail-label">Total Cases</span>
                                <span class="detail-value"><?php echo $crime['crime_count']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Active Cases</span>
                                <span class="detail-value"><?php echo $crime['active_cases']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Closed Cases</span>
                                <span class="detail-value"><?php echo $crime['closed_cases']; ?></span>
                            </div>
                        </div>
                        <div class="heatmap-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo ($total_crimes ? ($crime['crime_count']/$total_crimes)*100 : 0); ?>%"></div>
                            </div>
                            <div class="progress-label">
                                <?php echo round($total_crimes ? ($crime['crime_count']/$total_crimes)*100 : 0, 1); ?>% of total crimes
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <script>
        // Trends Chart
        const trendsData = <?php echo json_encode($trends_data); ?>;
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: trendsData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Crime Count',
                    data: trendsData.map(item => item.crime_count),
                    borderColor: '#ff4444',
                    backgroundColor: 'rgba(255, 68, 68, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.1)' } },
                    x: { grid: { color: 'rgba(0,0,0,0.1)' } }
                }
            }
        });
    </script>
</body>
</html>