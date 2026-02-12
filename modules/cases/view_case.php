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

$case_id = (int)$_GET['id'];

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

// Fetch case details
$stmt = $conn->prepare("SELECT * FROM cases WHERE id = ?");
$stmt->execute([$case_id]);
$case = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$case) {
    header("Location: criminal_management.php");
    exit();
}

// Fetch associated criminals
$sql = "SELECT c.id, c.fullname, c.alias, cc.role_in_case FROM criminals c JOIN case_criminals cc ON c.id = cc.criminal_id WHERE cc.case_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$case_id]);
$criminals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch evidence
$evidence_stmt = $conn->prepare("SELECT * FROM evidence WHERE case_id = ? ORDER BY collected_date DESC");
$evidence_stmt->execute([$case_id]);
$evidence = $evidence_stmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch arrests
$arrests_stmt = $conn->prepare("SELECT a.*, u.fullname as officer_name FROM arrests a LEFT JOIN users u ON a.arresting_officer = u.id WHERE a.case_id = ? ORDER BY arrest_date DESC");
$arrests_stmt->execute([$case_id]);
$arrests = $arrests_stmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch court appearances
$court_stmt = $conn->prepare("SELECT ca.*, cr.fullname as criminal_name FROM court_appearances ca LEFT JOIN criminals cr ON ca.criminal_id = cr.id WHERE ca.case_id = ? ORDER BY court_date DESC");
$court_stmt->execute([$case_id]);
$court_appearances = $court_stmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch case notes
$notes_stmt = $conn->prepare("SELECT cn.*, u.fullname as user_name FROM case_notes cn LEFT JOIN users u ON cn.user_id = u.id WHERE cn.case_id = ? ORDER BY created_at DESC");
$notes_stmt->execute([$case_id]);
$case_notes = $notes_stmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch witnesses
$wit_stmt = $conn->prepare("SELECT * FROM witnesses WHERE case_id = ? ORDER BY statement_date DESC");
$wit_stmt->execute([$case_id]);
$witnesses = $wit_stmt->fetchAll(PDO::FETCH_ASSOC);

// For back button: if only one criminal, link back to their cases; else, link to criminal_management.php
$back_link = 'criminal_management.php';
if (count($criminals) === 1) {
    $back_link = 'criminal_cases.php?id=' . $criminals[0]['id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Details - Criminal Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
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
        .main-content { margin-top: 80px; padding: 2rem; max-width: 900px; margin-left: auto; margin-right: auto; }
        .section-card { background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 2rem; box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 1.5rem; font-size: 2rem; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; }
        .details-table th, .details-table td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #eee; }
        .details-table th { width: 200px; background: #f8f8f8; color: #444; }
        .details-table td { background: #fff; }
        .actions { margin-top: 2rem; }
        .btn { padding: 12px 24px; border: none; border-radius: 50px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-secondary { background: linear-gradient(135deg, #333 0%, #555 100%); color: white; }
        .btn-secondary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(51,51,51,0.4); }
        .criminals-list { margin-top: 2rem; }
        .criminals-list h3 { margin-bottom: 1rem; color: #333; }
        .criminals-list ul { list-style: none; padding: 0; }
        .criminals-list li { margin-bottom: 0.7rem; }
        .criminals-list a { color: #1976d2; text-decoration: none; font-weight: 500; }
        .criminals-list a:hover { text-decoration: underline; }
        .related-section { margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #eee; }
        .related-section h3 { margin-bottom: 1rem; color: #333; }
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
        <div class="section-card">
            <h1><i class="fas fa-folder"></i> Case Details</h1>
            <table class="details-table">
                <tr><th>Case Number</th><td><?php echo htmlspecialchars($case['case_number']); ?></td></tr>
                <tr><th>Title</th><td><?php echo htmlspecialchars($case['title']); ?></td></tr>
                <tr><th>Type</th><td><?php echo htmlspecialchars(ucfirst($case['case_type'])); ?></td></tr>
                <tr><th>Status</th><td><?php echo htmlspecialchars(ucfirst($case['status'])); ?></td></tr>
                <tr><th>Priority</th><td><?php echo htmlspecialchars(ucfirst($case['priority'])); ?></td></tr>
                <tr><th>Location</th><td><?php echo htmlspecialchars($case['location']); ?></td></tr>
                <tr><th>Incident Date</th><td><?php echo date('M j, Y g:i A', strtotime($case['incident_date'])); ?></td></tr>
                <tr><th>Reported Date</th><td><?php echo date('M j, Y g:i A', strtotime($case['reported_date'])); ?></td></tr>
                <tr><th>Estimated Value</th><td><?php echo number_format($case['estimated_value'], 2); ?></td></tr>
                <tr><th>Created At</th><td><?php echo date('M j, Y g:i A', strtotime($case['created_at'])); ?></td></tr>
                <tr><th>Updated At</th><td><?php echo date('M j, Y g:i A', strtotime($case['updated_at'])); ?></td></tr>
                <tr><th>Description</th><td><?php echo nl2br(htmlspecialchars($case['description'])); ?></td></tr>
            </table>
            <div class="criminals-list">
                <h3><i class="fas fa-user-slash"></i> Associated Criminals</h3>
                <ul>
                    <?php if (count($criminals) > 0): ?>
                        <?php foreach ($criminals as $c): ?>
                            <li>
                                <a href="view_criminal.php?id=<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['fullname']); ?></a>
                                <?php if ($c['alias']) echo ' ("' . htmlspecialchars($c['alias']) . '")'; ?>
                                <span style="color:#888;">[<?php echo htmlspecialchars(ucfirst($c['role_in_case'])); ?>]</span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No criminals associated with this case.</li>
                    <?php endif; ?>
                </ul>
            </div>
            <!-- Evidence Section -->
            <div class="related-section">
                <h3><i class="fas fa-fingerprint"></i> Evidence</h3>
                <?php if (count($evidence) > 0): ?>
                <table class="details-table">
                    <thead>
                        <tr><th>Number</th><th>Name</th><th>Type</th><th>Status</th><th>Collected By</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evidence as $ev): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ev['evidence_number']); ?></td>
                            <td><?php echo htmlspecialchars($ev['name']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($ev['type'])); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($ev['status'])); ?></td>
                            <td><?php echo htmlspecialchars($ev['collected_by']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($ev['collected_date'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?><p>No evidence for this case.</p><?php endif; ?>
            </div>
            <!-- Arrests Section -->
            <div class="related-section">
                <h3><i class="fas fa-handcuffs"></i> Arrests</h3>
                <?php if (count($arrests) > 0): ?>
                <table class="details-table">
                    <thead>
                        <tr><th>Criminal ID</th><th>Officer</th><th>Date</th><th>Location</th><th>Charges</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($arrests as $ar): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ar['criminal_id']); ?></td>
                            <td><?php echo htmlspecialchars($ar['officer_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($ar['arrest_date'])); ?></td>
                            <td><?php echo htmlspecialchars($ar['arrest_location']); ?></td>
                            <td><?php echo htmlspecialchars($ar['charges']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($ar['status'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?><p>No arrests for this case.</p><?php endif; ?>
            </div>
            <!-- Court Appearances Section -->
            <div class="related-section">
                <h3><i class="fas fa-gavel"></i> Court Appearances</h3>
                <?php if (count($court_appearances) > 0): ?>
                <table class="details-table">
                    <thead>
                        <tr><th>Court</th><th>Criminal</th><th>Date</th><th>Type</th><th>Outcome</th><th>Notes</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($court_appearances as $ca): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ca['court_name']); ?></td>
                            <td><?php echo htmlspecialchars($ca['criminal_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($ca['court_date'])); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($ca['appearance_type'])); ?></td>
                            <td><?php echo htmlspecialchars($ca['outcome']); ?></td>
                            <td><?php echo htmlspecialchars($ca['notes']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?><p>No court appearances for this case.</p><?php endif; ?>
            </div>
            <!-- Case Notes Section -->
            <div class="related-section">
                <h3><i class="fas fa-sticky-note"></i> Case Notes</h3>
                <?php if (count($case_notes) > 0): ?>
                <ul>
                    <?php foreach ($case_notes as $note): ?>
                    <li><strong><?php echo htmlspecialchars($note['title']); ?></strong> (<?php echo htmlspecialchars($note['note_type']); ?>) by <?php echo htmlspecialchars($note['user_name']); ?> on <?php echo date('M j, Y', strtotime($note['created_at'])); ?><br><?php echo nl2br(htmlspecialchars($note['content'])); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?><p>No notes for this case.</p><?php endif; ?>
            </div>
            <!-- Witnesses Section -->
            <div class="related-section">
                <h3><i class="fas fa-user-friends"></i> Witnesses</h3>
                <?php if (count($witnesses) > 0): ?>
                <table class="details-table">
                    <thead>
                        <tr><th>Name</th><th>Statement</th><th>Date</th><th>Confidential</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($witnesses as $wit): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($wit['fullname']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($wit['statement'])); ?></td>
                            <td><?php echo date('M j, Y', strtotime($wit['statement_date'])); ?></td>
                            <td><?php echo $wit['is_confidential'] ? 'Yes' : 'No'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?><p>No witnesses for this case.</p><?php endif; ?>
            </div>
            <div class="actions">
                <a href="<?php echo $back_link; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
    </div>
</body>
</html> 