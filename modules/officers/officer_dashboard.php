<?php

session_start();

// Check if user is logged in and is an officer/investigator
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !in_array($_SESSION['role'], ['officer', 'investigator'])) {
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

$officer_id = $_SESSION['user_id'];

// Assigned cases
$cases_sql = "SELECT c.id, c.case_number, c.title, c.case_type, c.status, c.priority, c.incident_date
              FROM cases c
              WHERE c.assigned_officer = :officer_id OR c.lead_investigator = :officer_id
              ORDER BY c.incident_date DESC";
$cases_stmt = $conn->prepare($cases_sql);
$cases_stmt->execute(['officer_id' => $officer_id]);
$assigned_cases = $cases_stmt->fetchAll(PDO::FETCH_ASSOC);

// Upcoming court appearances
$court_sql = "SELECT ca.*, c.case_number, c.title
              FROM court_appearances ca
              JOIN cases c ON ca.case_id = c.id
              WHERE c.assigned_officer = :officer_id OR c.lead_investigator = :officer_id
              AND ca.court_date >= NOW()
              ORDER BY ca.court_date ASC";
$court_stmt = $conn->prepare($court_sql);
$court_stmt->execute(['officer_id' => $officer_id]);
$court_appearances = $court_stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent activity (last 10 actions)
$activity_sql = "SELECT action, table_name, details, created_at
                 FROM activity_log
                 WHERE user_id = :officer_id
                 ORDER BY created_at DESC
                 LIMIT 10";
$activity_stmt = $conn->prepare($activity_sql);
$activity_stmt->execute(['officer_id' => $officer_id]);
$recent_activity = $activity_stmt->fetchAll(PDO::FETCH_ASSOC);

// Performance stats
// Use stored procedure if available; otherwise fall back to plain SELECTs to avoid mysql.proc error
try {
    $stats_sql = "CALL GetOfficerStats(:officer_id)";
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->execute(['officer_id' => $officer_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    $stats_stmt->closeCursor();
} catch (PDOException $e) {
    // Fallback: compute basic stats without stored procedures
    $fallback_sql = "
        SELECT
            COUNT(*) AS total_cases,
            SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open_cases,
            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) AS closed_cases
        FROM cases
        WHERE assigned_officer = :officer_id OR lead_investigator = :officer_id
    ";
    $stmt = $conn->prepare($fallback_sql);
    $stmt->execute(['officer_id' => $officer_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats = [
        'total_cases' => (int)($row['total_cases'] ?? 0),
        'open_cases' => (int)($row['open_cases'] ?? 0),
        'closed_cases' => (int)($row['closed_cases'] ?? 0),
        // Set optional metrics to 0 in fallback; adjust if your schema provides these
        'total_arrests' => 0,
        'evidence_collected' => 0,
    ];
}

// Officer profile
$user_sql = "SELECT fullname, email, badge_number, department, role, phone, address, profile_image, last_login
             FROM users WHERE id = :officer_id";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->execute(['officer_id' => $officer_id]);
$profile = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Handle case status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_case_status'])) {
    $case_id = $_POST['case_id'];
    $new_status = $_POST['new_status'];
    $update_sql = "UPDATE cases SET status = :new_status WHERE id = :case_id AND (assigned_officer = :officer_id OR lead_investigator = :officer_id)";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->execute([
        'new_status' => $new_status,
        'case_id' => $case_id,
        'officer_id' => $officer_id
    ]);
    header("Location: officer_dashboard.php");
    exit();
}

$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Officer Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Referenced from criminal-management-front.php */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6fa;
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .navbar {
            background: rgba(0,0,0,0.9);
            backdrop-filter: blur(10px);
            padding: 0.5rem 0; /* Reduced padding */
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
        }
        .logo {
            font-size: 1.2rem; /* Reduced font size */
            font-weight: bold;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px; /* Slightly reduced gap */
        }
        .logo i { color: #ff4444; }
        .nav-links {
            display: flex;
            list-style: none;
            gap: 1rem; /* Reduced gap */
            margin: 0;
            padding: 0;
        }
        .nav-links a {
            text-decoration: none;
            color: #fff;
            font-weight: 500;
            transition: color 0.3s ease;
            padding: 6px 12px; /* Reduced padding */
            border-radius: 16px; /* Slightly reduced radius */
            font-size: 0.95rem; /* Slightly reduced font size */
        }
        .nav-links a:hover {
            color: #ff4444;
            background: rgba(255,255,255,0.1);
        }
        .main-content {
            margin-top: 100px;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        .card-title {
            font-size: 1.4rem;
            color: #ff4444;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        .profile-section {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
        }
        .profile-info h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
        }
        .profile-info p {
            margin: 0.2rem 0;
            color: #555;
        }
        .stats-list {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }
        .stat-item {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 1rem 2rem;
            text-align: center;
            min-width: 120px;
        }
        .stat-number {
            font-size: 2rem;
            color: #ff4444;
            font-weight: bold;
        }
        .stat-label {
            font-size: 1rem;
            color: #666;
        }
        .case-list, .court-list, .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .case-item, .court-item, .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .case-item:last-child, .court-item:last-child, .activity-item:last-child {
            border-bottom: none;
        }
        .case-title {
            font-weight: 600;
            color: #333;
        }
        .case-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-open { background: #d4edda; color: #155724; }
        .status-under_investigation { background: #fff3cd; color: #856404; }
        .status-pending_trial { background: #cce5ff; color: #004085; }
        .status-closed { background: #f8d7da; color: #721c24; }
        .status-cold_case { background: #e2e3e5; color: #6c757d; }
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
        @media (max-width: 900px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .container { padding: 10px; }
            .profile-section { flex-direction: column; gap: 1rem; }
            .stats-list { flex-direction: column; gap: 1rem; }
        }
    </style>
</head>
<body>
    <div class="background-container">
        <div class="moving-bg"></div>
        <div class="bg-overlay"></div>
    </div>
    <nav class="navbar">
        <div class="container">
            <div class="nav-content">
                <a href="dashboard.php" class="logo">
                    <i class="fas fa-shield-alt"></i>
                    Officer Dashboard
                </a>
                <ul class="nav-links">
                    <li><a href="officer_dashboard.php">Home</a></li>
                    <li><a href="#cases">Assigned Cases</a></li>
                    <li><a href="#court">Court Appearances</a></li>
                    <li><a href="#activity">Recent Activity</a></li>
                    <li><a href="#profile">Profile</a></li>
                    <li><a href="logout.php" class="btn-secondary">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container main-content">
        <!-- Officer Profile -->
        <section id="profile" class="card">
            <div class="profile-section">
                <?php if (!empty($profile['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($profile['profile_image']); ?>" alt="Profile Image" class="profile-avatar" />
                <?php else: ?>
                    <div class="profile-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                <?php endif; ?>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($profile['fullname']); ?></h2>
                    <p><strong>Badge:</strong> <?php echo htmlspecialchars($profile['badge_number']); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($profile['department']); ?></p>
                    <p><strong>Role:</strong> <?php echo ucfirst($profile['role']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?></p>
                    <?php if ($profile['phone']): ?>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($profile['phone']); ?></p>
                    <?php endif; ?>
                    <?php if ($profile['address']): ?>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($profile['address']); ?></p>
                    <?php endif; ?>
                   
                </div>
            </div>
            <div class="stats-list">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['total_cases'] ?? 0; ?></div>
                    <div class="stat-label">Total Cases</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['open_cases'] ?? 0; ?></div>
                    <div class="stat-label">Open Cases</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['closed_cases'] ?? 0; ?></div>
                    <div class="stat-label">Closed Cases</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['total_arrests'] ?? 0; ?></div>
                    <div class="stat-label">Arrests Made</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['evidence_collected'] ?? 0; ?></div>
                    <div class="stat-label">Evidence Collected</div>
                </div>
            </div>
        </section>
        <!-- Assigned Cases -->
        <section id="cases" class="card">
            <div class="card-title"><i class="fas fa-briefcase"></i> Assigned Cases</div>
            <?php if (count($assigned_cases) > 0): ?>
                <ul class="case-list">
                    <?php foreach ($assigned_cases as $case): ?>
                        <li class="case-item">
                            <span>
                                <span class="case-title"><?php echo htmlspecialchars($case['case_number']); ?> - <?php echo htmlspecialchars($case['title']); ?></span>
                                <span class="case-status status-<?php echo $case['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $case['status'])); ?></span>
                            </span>
                            <span>
                                <a href="view_case.php?id=<?php echo $case['id']; ?>" class="btn btn-primary btn-sm">View</a>
                                <!-- Edit Status Form -->
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                                    <select name="new_status" required>
                                        <option value="">Change Status</option>
                                        <option value="open" <?php if($case['status']=='open') echo 'selected'; ?>>Open</option>
                                        <option value="under_investigation" <?php if($case['status']=='under_investigation') echo 'selected'; ?>>Under Investigation</option>
                                        <option value="pending_trial" <?php if($case['status']=='pending_trial') echo 'selected'; ?>>Pending Trial</option>
                                        <option value="closed" <?php if($case['status']=='closed') echo 'selected'; ?>>Closed</option>
                                        <option value="cold_case" <?php if($case['status']=='cold_case') echo 'selected'; ?>>Cold Case</option>
                                    </select>
                                    <button type="submit" name="update_case_status" class="btn btn-secondary btn-sm">Update</button>
                                </form>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No cases assigned.</p>
            <?php endif; ?>
        </section>
        <!-- Upcoming Court Appearances -->
        <section id="court" class="card">
            <div class="card-title"><i class="fas fa-gavel"></i> Upcoming Court Appearances</div>
            <?php if (count($court_appearances) > 0): ?>
                <ul class="court-list">
                    <?php foreach ($court_appearances as $court): ?>
                        <li class="court-item">
                            <span>
                                <strong><?php echo htmlspecialchars($court['court_name']); ?></strong> -
                                <?php echo htmlspecialchars($court['appearance_type']); ?> -
                                <span><?php echo date('M d, Y H:i', strtotime($court['court_date'])); ?></span>
                                <br>
                                <span class="case-title"><?php echo htmlspecialchars($court['case_number']); ?> - <?php echo htmlspecialchars($court['title']); ?></span>
                            </span>
                            <span>
                                <a href="view_case.php?id=<?php echo $court['case_id']; ?>" class="btn btn-primary btn-sm">View Case</a>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No upcoming court appearances.</p>
            <?php endif; ?>
        </section>
        <!-- Recent Activity -->
        <section id="activity" class="card">
            <div class="card-title"><i class="fas fa-history"></i> Recent Activity</div>
            <?php if (count($recent_activity) > 0): ?>
                <ul class="activity-list">
                    <?php foreach ($recent_activity as $activity): ?>
                        <li class="activity-item">
                            <span>
                                <strong><?php echo htmlspecialchars($activity['action']); ?></strong>
                                <?php if ($activity['table_name']): ?>
                                    on <em><?php echo htmlspecialchars($activity['table_name']); ?></em>
                                <?php endif; ?>
                                <br>
                                <span><?php echo htmlspecialchars($activity['details']); ?></span>
                            </span>
                            <span>
                                <small><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></small>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No recent activity.</p>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>