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

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$case_filter = isset($_GET['case_id']) ? $_GET['case_id'] : '';
$court_filter = isset($_GET['court']) ? $_GET['court'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build query for court appearances
$where_conditions = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(c.case_number LIKE :search OR c.title LIKE :search OR cr.fullname LIKE :search OR ca.court_name LIKE :search)";
    $params['search'] = "%$search%";
}
if (!empty($case_filter)) {
    $where_conditions[] = "ca.case_id = :case_id";
    $params['case_id'] = $case_filter;
}
if (!empty($court_filter)) {
    $where_conditions[] = "ca.court_name = :court";
    $params['court'] = $court_filter;
}
if (!empty($type_filter)) {
    $where_conditions[] = "ca.appearance_type = :type";
    $params['type'] = $type_filter;
}
if (!empty($date_filter)) {
    $where_conditions[] = "DATE(ca.court_date) = :date";
    $params['date'] = $date_filter;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get court appearances
$sql = "SELECT ca.*, c.case_number, c.title as case_title, c.case_type, cr.fullname as criminal_name
        FROM court_appearances ca
        LEFT JOIN cases c ON ca.case_id = c.id
        LEFT JOIN criminals cr ON ca.criminal_id = cr.id
        $where_clause
        ORDER BY ca.court_date DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$court_appearances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get cases for filter
$cases_sql = "SELECT id, case_number, title FROM cases ORDER BY case_number";
$cases_stmt = $conn->prepare($cases_sql);
$cases_stmt->execute();
$cases = $cases_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get distinct courts for filter
$courts_sql = "SELECT DISTINCT court_name FROM court_appearances ORDER BY court_name";
$courts_stmt = $conn->prepare($courts_sql);
$courts_stmt->execute();
$courts = $courts_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get distinct appearance types for filter
$types_sql = "SELECT DISTINCT appearance_type FROM court_appearances ORDER BY appearance_type";
$types_stmt = $conn->prepare($types_sql);
$types_stmt->execute();
$types = $types_stmt->fetchAll(PDO::FETCH_COLUMN);

// Upcoming appearances (next 7 days)
$upcoming_sql = "SELECT ca.*, c.case_number, c.title as case_title, cr.fullname as criminal_name
                 FROM court_appearances ca
                 LEFT JOIN cases c ON ca.case_id = c.id
                 LEFT JOIN criminals cr ON ca.criminal_id = cr.id
                 WHERE ca.court_date >= CURDATE() 
                 AND ca.court_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                 ORDER BY ca.court_date ASC
                 LIMIT 10";
$upcoming_stmt = $conn->prepare($upcoming_sql);
$upcoming_stmt->execute();
$upcoming = $upcoming_stmt->fetchAll(PDO::FETCH_ASSOC);

$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Court Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
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
        .nav-links a:hover {
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
        .card {
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-title {
            font-size: 1.4rem;
            color: #ff4444;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        .filter-form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        .filter-form input, .filter-form select {
            padding: 10px 14px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        .filter-form input:focus, .filter-form select:focus {
            outline: none;
            border-color: #ff4444;
        }
        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255,68,68,0.4);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #333 0%, #555 100%);
            color: white;
        }
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(51,51,51,0.4);
        }
        .court-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .court-table th, .court-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        .court-table th {
            background: #f4f6fa;
            color: #333;
        }
        .court-table tr:hover {
            background: #f9f9f9;
        }
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            background: #e2e3e5;
            color: #333;
        }
        .appearance-arraignment { background: #d4edda; color: #155724; }
        .appearance-preliminary_hearing { background: #fff3cd; color: #856404; }
        .appearance-trial { background: #cce5ff; color: #004085; }
        .appearance-sentencing { background: #f8d7da; color: #721c24; }
        .appearance-appeal { background: #e2e3e5; color: #6c757d; }
        .appearance-other { background: #f4f6fa; color: #333; }
        .calendar-section {
            margin-bottom: 2rem;
        }
        .calendar-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .calendar-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .calendar-item:last-child {
            border-bottom: none;
        }
        .calendar-date {
            font-weight: bold;
            color: #ff4444;
        }
        .calendar-case {
            color: #333;
        }
        .calendar-criminal {
            color: #555;
        }
        .calendar-type {
            font-size: 0.95rem;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            background: #e2e3e5;
            color: #333;
            margin-left: 1rem;
        }
        .notes {
            color: #666;
            font-size: 0.95rem;
        }
        @media (max-width: 900px) {
            .main-content { padding: 1rem; }
            .filter-form { flex-direction: column; gap: 0.5rem; }
            .court-table th, .court-table td { padding: 8px 5px; }
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
                <i class="fas fa-gavel"></i>
                Court Management
            </a>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="criminal_management.php">Criminal Management</a></li>
                <li><a href="officer_management.php">Officer Management</a></li>
                <li><a href="case_management.php">Case Management</a></li>
                <li><a href="court_management.php" style="color:#ff4444;">Court Management</a></li>
                <li><a href="system_management.php">System Management</a></li>
            </ul>
        </div>
    </nav>
    <div class="main-content">
        <div class="card">
            <div class="card-title"><i class="fas fa-calendar"></i> Upcoming Court Appearances (Next 7 Days)</div>
            <?php if (count($upcoming) > 0): ?>
                <ul class="calendar-list">
                    <?php foreach ($upcoming as $item): ?>
                        <li class="calendar-item">
                            <span>
                                <span class="calendar-date"><?php echo date('M d, Y H:i', strtotime($item['court_date'])); ?></span>
                                <span class="calendar-type appearance-<?php echo $item['appearance_type']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $item['appearance_type'])); ?>
                                </span>
                                <span class="calendar-case">
                                    <a href="view_case.php?id=<?php echo $item['case_id']; ?>">
                                        <?php echo htmlspecialchars($item['case_number']); ?> - <?php echo htmlspecialchars($item['case_title']); ?>
                                    </a>
                                </span>
                                <span class="calendar-criminal">
                                    <a href="view_criminal.php?id=<?php echo $item['criminal_id']; ?>">
                                        <?php echo htmlspecialchars($item['criminal_name']); ?>
                                    </a>
                                </span>
                            </span>
                            <?php if ($item['notes']): ?>
                                <span class="notes"><?php echo htmlspecialchars($item['notes']); ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No upcoming court appearances.</p>
            <?php endif; ?>
        </div>
        <div class="card">
            <div class="card-title"><i class="fas fa-filter"></i> Filter & Search Court Appearances</div>
            <form class="filter-form" method="GET" action="court_management.php">
                <input type="text" name="search" placeholder="Search by case, criminal, court..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="case_id">
                    <option value="">All Cases</option>
                    <?php foreach ($cases as $case): ?>
                        <option value="<?php echo $case['id']; ?>" <?php if($case_filter==$case['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($case['case_number']); ?> - <?php echo htmlspecialchars($case['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="court">
                    <option value="">All Courts</option>
                    <?php foreach ($courts as $court): ?>
                        <option value="<?php echo htmlspecialchars($court); ?>" <?php if($court_filter==$court) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($court); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="type">
                    <option value="">All Types</option>
                    <?php foreach ($types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php if($type_filter==$type) echo 'selected'; ?>>
                            <?php echo ucfirst(str_replace('_',' ', $type)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                <a href="court_management.php" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
            </form>
        </div>
        <div class="card">
            <div class="card-title"><i class="fas fa-list"></i> All Court Appearances</div>
            <table class="court-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Case</th>
                        <th>Criminal</th>
                        <th>Court</th>
                        <th>Type</th>
                        <th>Outcome</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($court_appearances) > 0): ?>
                    <?php foreach ($court_appearances as $row): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($row['court_date'])); ?></td>
                            <td>
                                <a href="view_case.php?id=<?php echo $row['case_id']; ?>">
                                    <?php echo htmlspecialchars($row['case_number']); ?> - <?php echo htmlspecialchars($row['case_title']); ?>
                                </a>
                            </td>
                            <td>
                                <a href="view_criminal.php?id=<?php echo $row['criminal_id']; ?>">
                                    <?php echo htmlspecialchars($row['criminal_name']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($row['court_name']); ?></td>
                            <td>
                                <span class="status-badge appearance-<?php echo $row['appearance_type']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $row['appearance_type'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['outcome']); ?></td>
                            <td><?php echo htmlspecialchars($row['notes']); ?></td>
                            <td>
                                <a href="edit_court_appearance.php?id=<?php echo $row['id']; ?>" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                <a href="delete_court_appearance.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm" onclick="return confirm('Are you sure you want to delete this appearance?');"><i class="fas fa-trash"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8">No court appearances found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>