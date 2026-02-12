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

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$risk_filter = isset($_GET['risk_level']) ? $_GET['risk_level'] : '';

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(fullname LIKE ? OR alias LIKE ? OR identification_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($risk_filter)) {
    $where_conditions[] = "risk_level = ?";
    $params[] = $risk_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "SELECT * FROM criminals $where_clause ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$criminals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criminal Management - Criminal Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f4f6f9;
        }

        /* Moving Background */
        .background-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .moving-bg {
            position: absolute;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, 
                rgba(25, 25, 112, 0.8) 0%, 
                rgba(47, 84, 235, 0.8) 25%, 
                rgba(138, 43, 226, 0.8) 50%, 
                rgba(75, 0, 130, 0.8) 75%, 
                rgba(25, 25, 112, 0.8) 100%);
            animation: moveBackground 20s linear infinite;
        }

        .bg-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
        }

        @keyframes moveBackground {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Navigation */
        .navbar {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
        }

        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            color: #ff4444;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #fff;
            font-weight: 500;
            transition: color 0.3s ease;
            padding: 8px 16px;
            border-radius: 20px;
        }

        .nav-links a:hover {
            color: #ff4444;
            background: rgba(255, 255, 255, 0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
        }

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

        .logout-btn:hover {
            background: #cc0000;
        }

        /* Main Content */
        .main-content {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .page-header h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #666;
            font-size: 1.1rem;
        }

        /* Search and Filter Section */
        .search-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .search-form {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #ff4444;
        }

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
            box-shadow: 0 10px 20px rgba(255, 68, 68, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #333 0%, #555 100%);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(51, 51, 51, 0.4);
        }

        /* Criminals Table */
        .criminals-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .section-header h2 {
            font-size: 1.8rem;
            color: #333;
        }

        .table-container {
            overflow-x: auto;
        }

        .criminals-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .criminals-table th,
        .criminals-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .criminals-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .criminals-table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-wanted {
            background: #fff3cd;
            color: #856404;
        }

        .status-arrested {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-convicted {
            background: #d4edda;
            color: #155724;
        }

        .status-released {
            background: #e2e3e5;
            color: #383d41;
        }

        .status-deceased {
            background: #f8d7da;
            color: #721c24;
        }

        .risk-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .risk-low {
            background: #d4edda;
            color: #155724;
        }

        .risk-medium {
            background: #fff3cd;
            color: #856404;
        }

        .risk-high {
            background: #f8d7da;
            color: #721c24;
        }

        .risk-extreme {
            background: #721c24;
            color: #fff;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
            color: white;
        }

        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(23, 162, 184, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 193, 7, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(220, 53, 69, 0.4);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .search-form {
                grid-template-columns: 1fr;
            }

            .section-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .criminals-table {
                font-size: 0.9rem;
            }

            .criminals-table th,
            .criminals-table td {
                padding: 0.5rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .main-content {
                padding: 1rem;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
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
                <span><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header fade-in-up">
            <h1><i class="fas fa-user-slash"></i> Criminal Management</h1>
            <p>Manage criminal records, search, add, edit, and track criminal activities</p>
        </div>

        <!-- Search and Filter Section -->
        <div class="search-section fade-in-up">
            <form method="GET" class="search-form">
                <div class="form-group">
                    <label for="search">Search Criminals</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, alias, or ID number...">
                </div>
                <div class="form-group">
                    <label for="status">Status Filter</label>
                    <select id="status" name="status">
                        <option value="">All Status</option>
                        <option value="wanted" <?php echo $status_filter === 'wanted' ? 'selected' : ''; ?>>Wanted</option>
                        <option value="arrested" <?php echo $status_filter === 'arrested' ? 'selected' : ''; ?>>Arrested</option>
                        <option value="convicted" <?php echo $status_filter === 'convicted' ? 'selected' : ''; ?>>Convicted</option>
                        <option value="released" <?php echo $status_filter === 'released' ? 'selected' : ''; ?>>Released</option>
                        <option value="deceased" <?php echo $status_filter === 'deceased' ? 'selected' : ''; ?>>Deceased</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="risk_level">Risk Level</label>
                    <select id="risk_level" name="risk_level">
                        <option value="">All Risk Levels</option>
                        <option value="low" <?php echo $risk_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $risk_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $risk_filter === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="extreme" <?php echo $risk_filter === 'extreme' ? 'selected' : ''; ?>>Extreme</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Criminals Section -->
        <div class="criminals-section fade-in-up">
            <div class="section-header">
                <h2>Criminal Records (<?php echo count($criminals); ?> records)</h2>
                <a href="add_criminal.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add New Criminal
                </a>
            </div>

            <div class="table-container">
                <table class="criminals-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Alias</th>
                            <th>ID Number</th>
                            <th>Status</th>
                            <th>Risk Level</th>
                            <th>Age</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($criminals) > 0): ?>
                            <?php foreach ($criminals as $criminal): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($criminal['fullname']); ?></strong>
                                        <br>
                                        <small><?php echo htmlspecialchars($criminal['nationality'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($criminal['alias'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($criminal['identification_number']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $criminal['status']; ?>">
                                            <?php echo ucfirst($criminal['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="risk-badge risk-<?php echo $criminal['risk_level']; ?>">
                                            <?php echo ucfirst($criminal['risk_level']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($criminal['date_of_birth']) {
                                            $dob = new DateTime($criminal['date_of_birth']);
                                            $now = new DateTime();
                                            $age = $now->diff($dob)->y;
                                            echo $age . ' years';
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($criminal['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="view_criminal.php?id=<?php echo $criminal['id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="edit_criminal.php?id=<?php echo $criminal['id']; ?>" class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="criminal_cases.php?id=<?php echo $criminal['id']; ?>" class="btn btn-secondary btn-sm">
                                                <i class="fas fa-folder"></i> Cases
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 2rem;">
                                    <i class="fas fa-user-slash" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                                    <h3>No Criminals Found</h3>
                                    <p>No criminal records match your search criteria.</p>
                                    <a href="add_criminal.php" class="btn btn-success">
                                        <i class="fas fa-plus"></i> Add First Criminal
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Add animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all elements for fade-in animation
        document.querySelectorAll('.fade-in-up').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html> 