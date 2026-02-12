<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: criminal_management.php");
    exit();
}

$criminal_id = (int)$_GET['id'];

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

// Fetch criminal info
$stmt = $conn->prepare("SELECT fullname, alias FROM criminals WHERE id = ?");
$stmt->execute([$criminal_id]);
$criminal = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$criminal) {
    header("Location: criminal_management.php");
    exit();
}

// Fetch cases for this criminal
$sql = "SELECT c.id, c.case_number, c.title, c.case_type, c.status, c.priority, c.incident_date
        FROM cases c
        JOIN case_criminals cc ON c.id = cc.case_id
        WHERE cc.criminal_id = ?
        ORDER BY c.incident_date DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$criminal_id]);
$cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criminal Cases - Criminal Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Copy relevant CSS from criminal_management.php for consistency */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background: #f4f6f9; }
        .background-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; overflow: hidden; }
        .moving-bg { position: absolute; width: 200%; height: 200%; background: linear-gradient(45deg, rgba(25,25,112,0.8) 0%, rgba(47,84,235,0.8) 25%, rgba(138,43,226,0.8) 50%, rgba(75,0,130,0.8) 75%, rgba(25,25,112,0.8) 100%); animation: moveBackground 20s linear infinite; }
        .bg-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); }
        @keyframes moveBackground { 0% { transform: translate(-50%, -50%) rotate(0deg); } 100% { transform: translate(-50%, -50%) rotate(360deg); } }
        .navbar { background: rgba(0,0,0,0.9); backdrop-filter: blur(10px); padding: 1rem 0; position: fixed; width: 100%; top: 0; z-index: 1000; box-shadow: 0 2px 20px rgba(0,0,0,0.3); }
        .nav-content { display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .logo { font-size: 1.8rem; font-weight: bold; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .logo i { color: #ff4444; }
        .nav-links { display: flex; list-style: none; gap: 2rem; }
        .nav-links a { text-decoration: none; color: #fff; font-weight: 500; transition: color 0.3s ease; padding: 8px 16px; border-radius: 20px; }
        .nav-links a:hover { color: #ff4444; background: rgba(255,255,255,0.1); }
        .user-info { display: flex; align-items: center; gap: 1rem; color: white; }
        .logout-btn { background: #ff4444; color: white; padding: 8px 16px; border: none; border-radius: 20px; text-decoration: none; font-size: 0.9rem; transition: all 0.3s ease; }
        .logout-btn:hover { background: #cc0000; }
        .main-content { margin-top: 80px; padding: 2rem; max-width: 1200px; margin-left: auto; margin-right: auto; }
        .page-header { background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .page-header h1 { font-size: 2.2rem; color: #333; margin-bottom: 0.5rem; }
        .page-header p { color: #666; font-size: 1.1rem; }
        .cases-section { background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .section-header h2 { font-size: 1.5rem; color: #333; }
        .table-container { overflow-x: auto; }
        .cases-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .cases-table th, .cases-table td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
        .cases-table th { background: #f8f9fa; font-weight: 600; color: #333; }
        .cases-table tr:hover { background: #f8f9fa; }
        .btn-info { background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%); color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 0.95rem; font-weight: 500; transition: all 0.3s; }
        .btn-info:hover { box-shadow: 0 10px 20px rgba(23,162,184,0.2); }
        .btn-secondary { background: linear-gradient(135deg, #333 0%, #555 100%); color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 0.95rem; font-weight: 500; transition: all 0.3s; }
        .btn-secondary:hover { box-shadow: 0 10px 20px rgba(51,51,51,0.2); }
        @media (max-width: 768px) { .nav-links { display: none; } .main-content { padding: 1rem; } .cases-table th, .cases-table td { padding: 0.5rem; } }
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
        <div class="page-header">
            <h1><i class="fas fa-folder"></i> Cases for <?php echo htmlspecialchars($criminal['fullname']); ?><?php if ($criminal['alias']) echo ' ("' . htmlspecialchars($criminal['alias']) . '")'; ?></h1>
            <p>All cases associated with this criminal</p>
            <a href="criminal_management.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Criminals</a>
        </div>
        <div class="cases-section">
            <div class="section-header">
                <h2>Case List (<?php echo count($cases); ?>)</h2>
            </div>
            <div class="table-container">
                <table class="cases-table">
                    <thead>
                        <tr>
                            <th>Case Number</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($cases) > 0): ?>
                            <?php foreach ($cases as $case): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($case['case_number']); ?></td>
                                    <td><?php echo htmlspecialchars($case['title']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($case['case_type'])); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($case['status'])); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($case['priority'])); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($case['incident_date'])); ?></td>
                                    <td>
                                        <a href="view_case.php?id=<?php echo $case['id']; ?>" class="btn-info"><i class="fas fa-eye"></i> View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center;padding:2rem;">
                                    <i class="fas fa-folder-open" style="font-size:2rem;color:#ccc;"></i><br>
                                    <strong>No cases found for this criminal.</strong>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html> 