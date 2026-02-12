<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view_criminal.php");
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

// Fetch criminal details
try {
    $stmt = $conn->prepare("SELECT * FROM criminals WHERE id = ?");
    $stmt->execute([$criminal_id]);
    $criminal = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$criminal) {
        header("Location: view_criminals.php");
        exit();
    }
} catch(PDOException $e) {
    $criminal = null;
}

// Get user info for navbar
$user_name = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : '';
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criminal Details - Criminal Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f4f6f9;
        }
        .background-container {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        .moving-bg {
            position: absolute;
            width: 200%; height: 200%;
            background: linear-gradient(45deg, rgba(25,25,112,0.8) 0%, rgba(47,84,235,0.8) 25%, rgba(138,43,226,0.8) 50%, rgba(75,0,130,0.8) 75%, rgba(25,25,112,0.8) 100%);
            animation: moveBackground 20s linear infinite;
        }
        .bg-overlay {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.4);
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
            min-height: 40px;
        }
        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 10px;
            min-height: 40px;
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
        .logo i { color: #ff4444; font-size: 1.2rem; }
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
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
        }
        .user-info .user-name { font-weight: 600; }
        .logout-btn {
            background: #ff4444;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .logout-btn:hover { background: #cc0000; }
        .main-content {
            margin-top: 80px;
            padding: 2rem;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        .section-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .section-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }
        .details-table th, .details-table td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .details-table th {
            width: 200px;
            background: #f8f8f8;
            color: #444;
        }
        .details-table td { background: #fff; }
        .actions { margin-top: 2rem; }
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
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255,68,68,0.4);
        }
        .btn-edit {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40,167,69,0.4);
        }
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .main-content { padding: 1rem; }
        }
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
                <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                <span>(<?php echo ucfirst($user_role); ?>)</span>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>
    <!-- Main Content -->
    <div class="main-content">
        <div class="section-card">
            <h1><i class="fas fa-user-slash"></i> Criminal Details</h1>
            <?php if ($criminal): ?>
            <?php if (!empty($criminal['photo'])): ?>
                <div style="text-align:center;margin-bottom:1.5rem;">
                    <img src="<?php echo htmlspecialchars($criminal['photo']); ?>" alt="Criminal Photo" style="max-width:200px;max-height:200px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);">
                </div>
            <?php endif; ?>
            <table class="details-table">
                <tr><th>ID</th><td><?php echo htmlspecialchars($criminal['id']); ?></td></tr>
                <tr><th>Full Name</th><td><?php echo htmlspecialchars($criminal['fullname']); ?></td></tr>
                <tr><th>Alias</th><td><?php echo htmlspecialchars($criminal['alias']); ?></td></tr>
                <tr><th>Date of Birth</th><td><?php echo htmlspecialchars($criminal['date_of_birth']); ?></td></tr>
                <tr><th>Gender</th><td><?php echo htmlspecialchars($criminal['gender']); ?></td></tr>
                <tr><th>Nationality</th><td><?php echo htmlspecialchars($criminal['nationality']); ?></td></tr>
                <tr><th>Identification #</th><td><?php echo htmlspecialchars($criminal['identification_number']); ?></td></tr>
                <tr><th>Address</th><td><?php echo htmlspecialchars($criminal['address']); ?></td></tr>
                <tr><th>Phone</th><td><?php echo htmlspecialchars($criminal['phone']); ?></td></tr>
                <tr><th>Email</th><td><?php echo htmlspecialchars($criminal['email']); ?></td></tr>
                <tr><th>Height (cm)</th><td><?php echo htmlspecialchars($criminal['height']); ?></td></tr>
                <tr><th>Weight (kg)</th><td><?php echo htmlspecialchars($criminal['weight']); ?></td></tr>
                <tr><th>Eye Color</th><td><?php echo htmlspecialchars($criminal['eye_color']); ?></td></tr>
                <tr><th>Hair Color</th><td><?php echo htmlspecialchars($criminal['hair_color']); ?></td></tr>
                <tr><th>Distinguishing Marks</th><td><?php echo htmlspecialchars($criminal['distinguishing_marks']); ?></td></tr>
                <tr><th>Status</th><td><?php echo htmlspecialchars($criminal['status']); ?></td></tr>
                <tr><th>Risk Level</th><td><?php echo htmlspecialchars($criminal['risk_level']); ?></td></tr>
                <tr><th>Created At</th><td><?php echo htmlspecialchars($criminal['created_at']); ?></td></tr>
                <tr><th>Updated At</th><td><?php echo htmlspecialchars($criminal['updated_at']); ?></td></tr>
            </table>
            <div class="actions">
                
                <a href="edit_criminal.php?id=<?php echo $criminal['id']; ?>" class="btn btn-edit"><i class="fas fa-edit"></i> Edit</a>
            </div>
            <?php else: ?>
                <p>Criminal not found.</p>
                
            <?php endif; ?>
        </div>
    </div>
</body>
</html>