
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
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role IN ('officer', 'investigator')");
    $stmt->execute([$_GET['id']]);
    $officer = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Details - Criminal Management System</title>
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
        .container { max-width: 600px; margin: 120px auto 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 2px 16px rgba(0,0,0,0.08); padding: 2.5rem 2rem; }
        .officer-profile { text-align: center; }
        .officer-avatar { margin-bottom: 1.2rem; }
        .officer-avatar i { font-size: 5rem; color: #3a3a3a; }
        .officer-info h2 { margin-bottom: 0.3rem; font-size: 2rem; }
        .officer-role { margin-bottom: 0.5rem; }
        .role-badge { padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; background: #d1ecf1; color: #0c5460; }
        .role-investigator { background: #e2e3e5; color: #383d41; }
        .officer-dept, .officer-badge, .officer-email, .officer-phone, .officer-status, .officer-joined { margin-bottom: 0.5rem; color: #555; font-size: 1.05rem; }
        .status-badge { padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .status-suspended { background: #fff3cd; color: #856404; }
        .back-btn { display: inline-block; margin-top: 1.5rem; background: linear-gradient(135deg, #333 0%, #555 100%); color: white; padding: 10px 24px; border-radius: 50px; text-decoration: none; font-size: 1rem; transition: all 0.3s ease; }
        .back-btn:hover { background: #222; }
        .not-found { text-align: center; margin: 4rem 0; }
        .not-found i { font-size: 4rem; color: #ccc; }
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
        <?php if ($officer): ?>
            <div class="officer-profile">
                <div class="officer-avatar">
                    <?php if (!empty($officer['profile_image'])): ?>
                        <img src="uploads/criminal_photos/<?php echo htmlspecialchars($officer['profile_image']); ?>" alt="Profile Image" style="width:120px;height:120px;border-radius:50%;object-fit:cover;background:#eee;">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                </div>
                <div class="officer-info">
                    <h2><?php echo htmlspecialchars($officer['fullname']); ?></h2>
                    <div class="officer-role">
                        <span class="role-badge <?php echo 'role-' . $officer['role']; ?>">
                            <?php echo ucfirst($officer['role']); ?>
                        </span>
                    </div>
                    <div class="officer-dept">
                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($officer['department']); ?>
                    </div>
                    <div class="officer-badge">
                        <i class="fas fa-id-badge"></i> Badge #: <?php echo htmlspecialchars($officer['badge_number']); ?>
                    </div>
                    <div class="officer-email">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($officer['email']); ?>
                    </div>
                    <div class="officer-phone">
                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($officer['phone'] ?? 'N/A'); ?>
                    </div>
                    <div class="officer-status">
                        <span class="status-badge status-<?php echo $officer['status']; ?>">
                            <?php echo ucfirst($officer['status']); ?>
                        </span>
                    </div>
                    <div class="officer-joined">
                        <i class="fas fa-calendar-alt"></i> Joined: <?php echo date('M j, Y', strtotime($officer['created_at'])); ?>
                    </div>
                </div>
                <a href="officer_management.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Officers</a>
            </div>
        <?php else: ?>
            <div class="not-found">
                <i class="fas fa-user-slash"></i>
                <h3>Officer Not Found</h3>
                <p>The officer you are looking for does not exist or has been removed.</p>
                <a href="officer_management.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Officers</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
