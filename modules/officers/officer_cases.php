<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
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
$officer = null;
$cases = [];
$error = '';
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role IN ('officer', 'investigator')");
    $stmt->execute([$_GET['id']]);
    $officer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($officer) {
        // Fetch cases where this officer is assigned or is the lead investigator
        $case_stmt = $conn->prepare("SELECT * FROM cases WHERE assigned_officer = ? OR lead_investigator = ? ORDER BY created_at DESC");
        $case_stmt->execute([$officer['id'], $officer['id']]);
        $cases = $case_stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error = "Officer not found.";
    }
} else {
    $error = "Invalid officer ID.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Cases - Criminal Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; margin: 0; }
        .background-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; overflow: hidden; }
        .moving-bg { position: absolute; width: 200%; height: 200%; background: linear-gradient(45deg, rgba(25, 25, 112, 0.8) 0%, rgba(47, 84, 235, 0.8) 25%, rgba(138, 43, 226, 0.8) 50%, rgba(75, 0, 130, 0.8) 75%, rgba(25, 25, 112, 0.8) 100%); animation: moveBackground 20s linear infinite; }
        .bg-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); }
        @keyframes moveBackground { 0% { transform: translate(-50%, -50%) rotate(0deg); } 100% { transform: translate(-50%, -50%) rotate(360deg); } }
        .navbar { background: rgba(0, 0, 0, 0.9); backdrop-filter: blur(10px); padding: 0.7rem 0; position: fixed; width: 100%; top: 0; z-index: 1000; box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3); }
        .nav-content { display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .logo { font-size: 1.5rem; font-weight: bold; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .nav-links { display: flex; list-style: none; gap: 1.2rem; }
        .nav-links a { text-decoration: none; color: #fff; font-weight: 500; transition: color 0.3s ease; padding: 6px 12px; border-radius: 20px; }
        .nav-links a:hover { color: #ff4444; background: rgba(255, 255, 255, 0.1); }
        .container { max-width: 900px; margin: 120px auto 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 2px 16px rgba(0,0,0,0.08); padding: 2.5rem 2rem; }
        .section-title { text-align: center; margin-bottom: 2rem; }
        .cases-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .cases-table th, .cases-table td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
        .cases-table th { background: #f8f9fa; font-weight: 600; color: #333; }
        .cases-table tr:hover { background: #f8f9fa; }
        .no-cases { text-align: center; color: #888; margin: 2rem 0; }
        .back-btn { display: inline-block; margin-top: 1.5rem; background: linear-gradient(135deg, #333 0%, #555 100%); color: white; padding: 10px 24px; border-radius: 50px; text-decoration: none; font-size: 1rem; transition: all 0.3s ease; }
        .back-btn:hover { background: #222; }
        .officer-header { text-align: center; margin-bottom: 1.5rem; }
        .officer-header h2 { margin-bottom: 0.3rem; }
        .officer-header .role-badge { padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; background: #d1ecf1; color: #0c5460; display: inline-block; margin-left: 0.5rem; }
        .officer-header .role-investigator { background: #e2e3e5; color: #383d41; }
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
                <i class="fas fa-shield-alt"></i>
                Criminal Management System
            </a>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="officer_management.php">Officer Management</a></li>
                <li><a href="criminal_management.php">Criminals</a></li>
                <li><a href="case_management.php">Cases</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
    <div class="container">
        <?php if ($error): ?>
            <div class="no-cases"><strong><?php echo $error; ?></strong></div>
        <?php elseif ($officer): ?>
            <div class="officer-header">
                <h2><?php echo htmlspecialchars($officer['fullname']); ?> <span class="role-badge <?php echo 'role-' . $officer['role']; ?>"><?php echo ucfirst($officer['role']); ?></span></h2>
                <div><i class="fas fa-id-badge"></i> Badge #: <?php echo htmlspecialchars($officer['badge_number']); ?> | <i class="fas fa-building"></i> <?php echo htmlspecialchars($officer['department']); ?></div>
            </div>
            <div class="section-title">
                <h3>Cases Assigned</h3>
            </div>
            <?php if (count($cases) > 0): ?>
                <table class="cases-table">
                    <thead>
                        <tr>
                            <th>Case ID</th>
                            <th>Case Type</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cases as $case): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($case['id']); ?></td>
                            <td><?php echo htmlspecialchars($case['case_type']); ?></td>
                            <td><?php echo htmlspecialchars($case['status']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($case['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($case['description']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-cases">No cases assigned to this officer.</div>
            <?php endif; ?>
            <a href="officer_management.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Officers</a>
        <?php endif; ?>
    </div>
</body>
</html>
