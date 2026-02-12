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

// Handle report generation
$report_data = [];
$report_generated = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate_report'])) {
    $report_generated = true;
    $report_type = $_POST['report_type'];
    $date_from = $_POST['date_from'];
    $date_to = $_POST['date_to'];
    $case_type = $_POST['case_type'];
    $status = $_POST['status'];
    $risk_level = $_POST['risk_level'];
    $officer_id = $_POST['officer_id'];

    // Build query for cases and join with criminals, officers, and arrests
    $sql = "SELECT 
                c.case_number,
                c.title,
                c.case_type,
                c.status as case_status,
                c.incident_date,
                c.location,
                c.priority,
                c.estimated_value,
                u.fullname as officer_name,
                cr.fullname as criminal_name,
                cr.gender,
                cr.risk_level,
                cr.status as criminal_status,
                cr.date_of_birth,
                a.arrest_date,
                a.status as arrest_status
            FROM cases c
            LEFT JOIN users u ON c.assigned_officer = u.id
            LEFT JOIN case_criminals cc ON c.id = cc.case_id
            LEFT JOIN criminals cr ON cc.criminal_id = cr.id
            LEFT JOIN arrests a ON c.id = a.case_id AND cr.id = a.criminal_id
            WHERE 1=1";
    $params = [];

    if (!empty($date_from)) {
        $sql .= " AND c.incident_date >= ?";
        $params[] = $date_from;
    }
    if (!empty($date_to)) {
        $sql .= " AND c.incident_date <= ?";
        $params[] = $date_to;
    }
    if (!empty($case_type)) {
        $sql .= " AND c.case_type = ?";
        $params[] = $case_type;
    }
    if (!empty($status)) {
        $sql .= " AND c.status = ?";
        $params[] = $status;
    }
    if (!empty($risk_level)) {
        $sql .= " AND cr.risk_level = ?";
        $params[] = $risk_level;
    }
    if (!empty($officer_id)) {
        $sql .= " AND c.assigned_officer = ?";
        $params[] = $officer_id;
    }

    $sql .= " ORDER BY c.incident_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get case types for filter
$case_types_sql = "SELECT DISTINCT case_type FROM cases ORDER BY case_type";
$case_types_stmt = $conn->prepare($case_types_sql);
$case_types_stmt->execute();
$case_types = $case_types_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get officers for filter
$officers_sql = "SELECT id, fullname FROM users WHERE role IN ('officer','investigator') ORDER BY fullname";
$officers_stmt = $conn->prepare($officers_sql);
$officers_stmt->execute();
$officers = $officers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary statistics
$summary_sql = "SELECT 
                    COUNT(*) as total_cases,
                    COUNT(CASE WHEN status IN ('open','under_investigation') THEN 1 END) as active_cases,
                    COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_cases,
                    COUNT(CASE WHEN incident_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_cases,
                    AVG(estimated_value) as avg_value
                FROM cases";
$summary_stmt = $conn->prepare($summary_sql);
$summary_stmt->execute();
$summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

// Get top crime types
$top_types_sql = "SELECT case_type, COUNT(*) as count FROM cases GROUP BY case_type ORDER BY count DESC LIMIT 5";
$top_types_stmt = $conn->prepare($top_types_sql);
$top_types_stmt->execute();
$top_types = $top_types_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top locations
$top_locations_sql = "SELECT location, COUNT(*) as count FROM cases GROUP BY location ORDER BY count DESC LIMIT 5";
$top_locations_stmt = $conn->prepare($top_locations_sql);
$top_locations_stmt->execute();
$top_locations = $top_locations_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Crime Reports - Criminal Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Referenced from dashboard.php */
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
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header h1 {
            color: #333;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .header h1 i { color: #ff4444; }
        .breadcrumb {
            color: #666;
            font-size: 0.9rem;
        }
        .breadcrumb a { color: #ff4444; text-decoration: none; }
        .nav-bar {
            background: rgba(0,0,0,0.9);
            border-radius: 15px;
            padding: 1rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            flex-wrap: wrap;
        }
        .nav-links a {
            text-decoration: none;
            color: #fff;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .nav-links a:hover { background: #ff4444; color: white; }
        .logout-btn {
            background: #ff4444;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .logout-btn:hover { background: #cc0000; }
        .summary-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .summary-card {
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        .summary-card:hover { transform: translateY(-5px); }
        .summary-icon { font-size: 2rem; color: #ff4444; margin-bottom: 1rem; }
        .summary-number { font-size: 1.8rem; font-weight: bold; color: #333; margin-bottom: 0.5rem; }
        .summary-label { color: #666; font-size: 0.9rem; }
        .report-form {
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; margin-bottom: 0.5rem; color: #555; }
        .form-group input, .form-group select {
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #ff4444; }
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
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(255,68,68,0.4); }
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(40,167,69,0.4); }
        .btn-secondary {
            background: linear-gradient(135deg, #333 0%, #555 100%);
            color: white;
        }
        .btn-secondary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(51,51,51,0.4); }
        .report-results {
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .results-count { font-size: 1.1rem; color: #666; }
        .report-actions { display: flex; gap: 1rem; flex-wrap: wrap; }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .report-table th, .report-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e1e5e9;
        }
        .report-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .report-table tr:hover { background: #f8f9fa; }
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-open, .status-under_investigation { background: #d4edda; color: #155724; }
        .status-closed { background: #f8d7da; color: #721c24; }
        .status-pending_trial { background: #fff3cd; color: #856404; }
        .status-cold_case { background: #e2e3e5; color: #333; }
        .no-results {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        .no-results i { font-size: 4rem; color: #ccc; margin-bottom: 1rem; }
        .top-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .top-card {
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            padding: 1.5rem;
            text-align: center;
        }
        .top-card h4 { color: #ff4444; margin-bottom: 1rem; }
        .top-list { list-style: none; padding: 0; margin: 0; }
        .top-list li { padding: 0.5rem 0; border-bottom: 1px solid #eee; }
        .top-list li:last-child { border-bottom: none; }
        @media (max-width: 900px) {
            .container { padding: 10px; }
            .summary-section { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
            .top-section { grid-template-columns: 1fr; }
            .report-table th, .report-table td { padding: 8px 12px; }
        }
        @media print {
            .nav-bar, .report-form, .report-actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="background-container">
        <div class="moving-bg"></div>
        <div class="bg-overlay"></div>
    </div>
    <div class="container">
        <!-- Header -->
       
        <!-- Navigation -->
        <div class="nav-bar">
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="criminal_management.php">Criminal Management</a></li>
                <li><a href="officer_management.php">Officer Management</a></li>
                <li><a href="crime_statistics.php">Crime Statistics</a></li>
                <li><a href="court_management.php">Court Management</a></li>
                <li><a href="system_management.php">System Management</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </div>
        <!-- Summary Cards -->
        <div class="summary-section">
            <div class="summary-card">
                <div class="summary-icon"><i class="fas fa-folder-open"></i></div>
                <div class="summary-number"><?php echo number_format($summary['total_cases']); ?></div>
                <div class="summary-label">Total Cases</div>
            </div>
            <div class="summary-card">
                <div class="summary-icon"><i class="fas fa-search"></i></div>
                <div class="summary-number"><?php echo number_format($summary['active_cases']); ?></div>
                <div class="summary-label">Active Cases</div>
            </div>
            <div class="summary-card">
                <div class="summary-icon"><i class="fas fa-check-circle"></i></div>
                <div class="summary-number"><?php echo number_format($summary['closed_cases']); ?></div>
                <div class="summary-label">Closed Cases</div>
            </div>
            <div class="summary-card">
                <div class="summary-icon"><i class="fas fa-calendar"></i></div>
                <div class="summary-number"><?php echo number_format($summary['recent_cases']); ?></div>
                <div class="summary-label">Recent (30 days)</div>
            </div>
            <div class="summary-card">
                <div class="summary-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="summary-number"><?php echo number_format($summary['avg_value'],2); ?></div>
                <div class="summary-label">Avg. Case Value</div>
            </div>
        </div>
        <!-- Top Crime Types & Locations -->
        <div class="top-section">
            <div class="top-card">
                <h4><i class="fas fa-chart-pie"></i> Top Crime Types</h4>
                <ul class="top-list">
                    <?php foreach ($top_types as $type): ?>
                        <li><?php echo ucfirst(str_replace('_',' ', $type['case_type'])); ?> — <?php echo $type['count']; ?> cases</li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="top-card">
                <h4><i class="fas fa-map-marker-alt"></i> Top Locations</h4>
                <ul class="top-list">
                    <?php foreach ($top_locations as $loc): ?>
                        <li><?php echo htmlspecialchars($loc['location']); ?> — <?php echo $loc['count']; ?> cases</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <!-- Report Form -->
        <div class="report-form">
            <h2><i class="fas fa-filter"></i> Generate Report</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="report_type">Report Type</label>
                        <select id="report_type" name="report_type" required>
                            <option value="">Select Report Type</option>
                            <option value="comprehensive" <?php echo (isset($_POST['report_type']) && $_POST['report_type'] === 'comprehensive') ? 'selected' : ''; ?>>Comprehensive Report</option>
                            <option value="crime_type" <?php echo (isset($_POST['report_type']) && $_POST['report_type'] === 'crime_type') ? 'selected' : ''; ?>>Crime Type Analysis</option>
                            <option value="temporal" <?php echo (isset($_POST['report_type']) && $_POST['report_type'] === 'temporal') ? 'selected' : ''; ?>>Temporal Analysis</option>
                            <option value="status" <?php echo (isset($_POST['report_type']) && $_POST['report_type'] === 'status') ? 'selected' : ''; ?>>Status Report</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="case_type">Case Type</label>
                        <select id="case_type" name="case_type">
                            <option value="">All Case Types</option>
                            <?php foreach ($case_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (isset($_POST['case_type']) && $_POST['case_type'] === $type) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_',' ', $type)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Case Status</label>
                        <select id="status" name="status">
                            <option value="">All Status</option>
                            <option value="open" <?php echo (isset($_POST['status']) && $_POST['status'] === 'open') ? 'selected' : ''; ?>>Open</option>
                            <option value="under_investigation" <?php echo (isset($_POST['status']) && $_POST['status'] === 'under_investigation') ? 'selected' : ''; ?>>Under Investigation</option>
                            <option value="pending_trial" <?php echo (isset($_POST['status']) && $_POST['status'] === 'pending_trial') ? 'selected' : ''; ?>>Pending Trial</option>
                            <option value="closed" <?php echo (isset($_POST['status']) && $_POST['status'] === 'closed') ? 'selected' : ''; ?>>Closed</option>
                            <option value="cold_case" <?php echo (isset($_POST['status']) && $_POST['status'] === 'cold_case') ? 'selected' : ''; ?>>Cold Case</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="risk_level">Risk Level</label>
                        <select id="risk_level" name="risk_level">
                            <option value="">All Risk Levels</option>
                            <option value="low" <?php echo (isset($_POST['risk_level']) && $_POST['risk_level'] === 'low') ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo (isset($_POST['risk_level']) && $_POST['risk_level'] === 'medium') ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo (isset($_POST['risk_level']) && $_POST['risk_level'] === 'high') ? 'selected' : ''; ?>>High</option>
                            <option value="extreme" <?php echo (isset($_POST['risk_level']) && $_POST['risk_level'] === 'extreme') ? 'selected' : ''; ?>>Extreme</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="officer_id">Assigned Officer</label>
                        <select id="officer_id" name="officer_id">
                            <option value="">All Officers</option>
                            <?php foreach ($officers as $officer): ?>
                                <option value="<?php echo $officer['id']; ?>" <?php echo (isset($_POST['officer_id']) && $_POST['officer_id'] == $officer['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($officer['fullname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_from">Date From</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo isset($_POST['date_from']) ? htmlspecialchars($_POST['date_from']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_to">Date To</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo isset($_POST['date_to']) ? htmlspecialchars($_POST['date_to']) : ''; ?>">
                    </div>
                </div>
                <button type="submit" name="generate_report" class="btn btn-primary">
                    <i class="fas fa-file-alt"></i> Generate Report
                </button>
            </form>
        </div>
        <!-- Report Results -->
        <?php if ($report_generated): ?>
            <div class="report-results">
                <div class="results-header">
                    <h2><i class="fas fa-list"></i> Report Results</h2>
                    <div class="results-count">
                        Found <?php echo count($report_data); ?> record(s)
                    </div>
                    <div class="report-actions">
                        <button onclick="window.print()" class="btn btn-success">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                        <button onclick="exportToCSV()" class="btn btn-secondary">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                    </div>
                </div>
                <?php if (count($report_data) > 0): ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Case Number</th>
                                <th>Title</th>
                                <th>Crime Type</th>
                                <th>Location</th>
                                <th>Incident Date</th>
                                <th>Priority</th>
                                <th>Officer</th>
                                <th>Criminal</th>
                                <th>Gender</th>
                                <th>Risk Level</th>
                                <th>Case Status</th>
                                <th>Arrest Date</th>
                                <th>Arrest Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['case_number']); ?></td>
                                    <td><?php echo htmlspecialchars($record['title']); ?></td>
                                    <td><?php echo ucfirst(str_replace('_',' ', $record['case_type'])); ?></td>
                                    <td><?php echo htmlspecialchars($record['location']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($record['incident_date'])); ?></td>
                                    <td><?php echo ucfirst($record['priority']); ?></td>
                                    <td><?php echo htmlspecialchars($record['officer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['criminal_name']); ?></td>
                                    <td><?php echo ucfirst($record['gender']); ?></td>
                                    <td><?php echo ucfirst($record['risk_level']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $record['case_status']; ?>">
                                            <?php echo ucfirst(str_replace('_',' ', $record['case_status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $record['arrest_date'] ? date('M d, Y', strtotime($record['arrest_date'])) : '-'; ?></td>
                                    <td><?php echo $record['arrest_status'] ? ucfirst($record['arrest_status']) : '-'; ?></td>
                                    <td>
                                        <?php if ($record['criminal_name']): ?>
                                            <a href="view_criminal.php?name=<?php echo urlencode($record['criminal_name']); ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        <?php endif; ?>
                                        <a href="view_case.php?number=<?php echo urlencode($record['case_number']); ?>" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-folder-open"></i> Case
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>No Results Found</h3>
                        <p>No records match your report criteria. Try adjusting your filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const reportType = document.getElementById('report_type').value;
            if (!reportType) {
                e.preventDefault();
                alert('Please select a report type.');
                return;
            }
        });
        // Date validation
        document.getElementById('date_to').addEventListener('change', function() {
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = this.value;
            if (dateFrom && dateTo && dateFrom > dateTo) {
                alert('End date must be after start date.');
                this.value = '';
            }
        });
        // Export to CSV function
        function exportToCSV() {
            const table = document.querySelector('.report-table');
            if (!table) return;
            let csv = [];
            const rows = table.querySelectorAll('tr');
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                for (let j = 0; j < cols.length - 1; j++) { // Skip last column (actions)
                    let text = cols[j].innerText.replace(/"/g, '""');
                    row.push('"' + text + '"');
                }
                csv.push(row.join(','));
            }
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', 'crime_report.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }
    </script>
</body>
</html>