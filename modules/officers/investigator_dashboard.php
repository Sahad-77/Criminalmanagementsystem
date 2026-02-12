<?php
session_start();

// Check if user is logged in and is an investigator
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'investigator') {
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

$investigator_id = $_SESSION['user_id'];

// Assigned cases
$cases_sql = "SELECT c.id, c.case_number, c.title, c.case_type, c.status, c.priority, c.incident_date
              FROM cases c
              WHERE c.lead_investigator = :investigator_id
              ORDER BY c.incident_date DESC";
$cases_stmt = $conn->prepare($cases_sql);
$cases_stmt->execute(['investigator_id' => $investigator_id]);
$assigned_cases = $cases_stmt->fetchAll(PDO::FETCH_ASSOC);

// Evidence collected
$evidence_sql = "SELECT e.*, c.case_number, c.title
                 FROM evidence e
                 JOIN cases c ON e.case_id = c.id
                 WHERE e.collected_by = :investigator_id
                 ORDER BY e.collected_date DESC";
$evidence_stmt = $conn->prepare($evidence_sql);
$evidence_stmt->execute(['investigator_id' => $investigator_id]);
$evidence_list = $evidence_stmt->fetchAll(PDO::FETCH_ASSOC);

// Suspect profiles for assigned cases
$suspects_sql = "SELECT cr.*, cc.role_in_case, c.case_number
                 FROM criminals cr
                 JOIN case_criminals cc ON cr.id = cc.criminal_id
                 JOIN cases c ON cc.case_id = c.id
                 WHERE c.lead_investigator = :investigator_id
                 ORDER BY cr.fullname";
$suspects_stmt = $conn->prepare($suspects_sql);
$suspects_stmt->execute(['investigator_id' => $investigator_id]);
$suspects = $suspects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent activity
$activity_sql = "SELECT action, table_name, details, created_at
                 FROM activity_log
                 WHERE user_id = :investigator_id
                 ORDER BY created_at DESC
                 LIMIT 10";
$activity_stmt = $conn->prepare($activity_sql);
$activity_stmt->execute(['investigator_id' => $investigator_id]);
$recent_activity = $activity_stmt->fetchAll(PDO::FETCH_ASSOC);

// Performance stats
$stats_sql = "CALL GetOfficerStats(:investigator_id)";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->execute(['investigator_id' => $investigator_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
$stats_stmt->closeCursor();

// Investigator profile
$user_sql = "SELECT fullname, email, badge_number, department, role, phone, address, profile_image, last_login
             FROM users WHERE id = :investigator_id";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->execute(['investigator_id' => $investigator_id]);
$profile = $user_stmt->fetch(PDO::FETCH_ASSOC);

$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Investigator Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
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
            gap: 1rem;
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
            overflow: hidden;
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
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
        .case-list, .evidence-list, .suspect-list, .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .case-item, .evidence-item, .suspect-item, .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .case-item:last-child, .evidence-item:last-child, .suspect-item:last-child, .activity-item:last-child {
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
                    <i class="fas fa-user-secret"></i>
                    Investigator Dashboard
                </a>
                <ul class="nav-links">
                    <li><a href="investigator_dashboard.php">Home</a></li>
                    <li><a href="#cases">Assigned Cases</a></li>
                    <li><a href="#evidence">Evidence</a></li>
                    <li><a href="#suspects">Suspects</a></li>
                    <li><a href="#activity">Recent Activity</a></li>
                    <li><a href="#profile">Profile</a></li>
                    <li><a href="logout.php" class="btn-secondary">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container main-content">
        <!-- Investigator Profile -->
        <section id="profile" class="card">
            <div class="profile-section">
                <div class="profile-avatar">
                    <?php if (!empty($profile['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($profile['profile_image']); ?>" alt="Profile Image" />
                    <?php else: ?>
                        <i class="fas fa-user-secret"></i>
                    <?php endif; ?>
                </div>
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
                    <p><strong>Last Login:</strong> <?php echo htmlspecialchars($profile['last_login']); ?></p>
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
                                <a href="add_case_note.php?case_id=<?php echo $case['id']; ?>" class="btn btn-secondary btn-sm">Add Note</a>
                                <a href="upload_evidence.php?case_id=<?php echo $case['id']; ?>" class="btn btn-secondary btn-sm">Upload Evidence</a>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No cases assigned.</p>
            <?php endif; ?>
        </section>
        <!-- Evidence Collected -->
        <section id="evidence" class="card">
            <div class="card-title"><i class="fas fa-microscope"></i> Evidence Collected</div>
            <?php if (count($evidence_list) > 0): ?>
                <ul class="evidence-list">
                    <?php foreach ($evidence_list as $evidence): ?>
                        <li class="evidence-item">
                            <span>
                                <strong><?php echo htmlspecialchars($evidence['evidence_number']); ?></strong> -
                                <?php echo htmlspecialchars($evidence['name']); ?> (<?php echo htmlspecialchars($evidence['type']); ?>)
                                <br>
                                <span class="case-title"><?php echo htmlspecialchars($evidence['case_number']); ?> - <?php echo htmlspecialchars($evidence['title']); ?></span>
                            </span>
                            <span>
                                <a href="view_evidence.php?id=<?php echo $evidence['id']; ?>" class="btn btn-primary btn-sm">View</a>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No evidence collected.</p>
            <?php endif; ?>
        </section>
        <!-- Suspect Profiles -->
        <section id="suspects" class="card">
            <div class="card-title"><i class="fas fa-user-slash"></i> Suspects in Assigned Cases</div>
            <?php if (count($suspects) > 0): ?>
                <ul class="suspect-list">
                    <?php foreach ($suspects as $suspect): ?>
                        <li class="suspect-item">
                            <span>
                                <strong><?php echo htmlspecialchars($suspect['fullname']); ?></strong>
                                <?php if ($suspect['alias']): ?>
                                    (<?php echo htmlspecialchars($suspect['alias']); ?>)
                                <?php endif; ?>
                                - <em><?php echo htmlspecialchars($suspect['role_in_case']); ?></em>
                                <br>
                                <span class="case-title"><?php echo htmlspecialchars($suspect['case_number']); ?></span>
                            </span>
                            <span>
                                <a href="view_criminal.php?id=<?php echo $suspect['id']; ?>" class="btn btn-primary btn-sm">View Profile</a>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No suspects found for assigned cases.</p>
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