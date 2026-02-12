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

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$case_filter = isset($_GET['case_id']) ? $_GET['case_id'] : '';
$type_filter = isset($_GET['evidence_type']) ? $_GET['evidence_type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$officer_filter = isset($_GET['officer_id']) ? $_GET['officer_id'] : '';

// Build query for evidence
$where_conditions = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(e.evidence_number LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($case_filter)) {
    $where_conditions[] = "e.case_id = ?";
    $params[] = $case_filter;
}

if (!empty($type_filter)) {
    $where_conditions[] = "e.evidence_type = ?";
    $params[] = $type_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "e.status = ?";
    $params[] = $status_filter;
}

if (!empty($officer_filter)) {
    $where_conditions[] = "e.collected_by = ?";
    $params[] = $officer_filter;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

$sql = "SELECT e.*, c.case_number, c.case_title, cr.fullname as criminal_name, 
               u.fullname as officer_name, u.badge_number
        FROM evidence e
        LEFT JOIN cases c ON e.case_id = c.id
        LEFT JOIN criminals cr ON c.criminal_id = cr.id
        LEFT JOIN users u ON e.collected_by = u.id
        $where_clause
        ORDER BY e.collected_date DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$evidence_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available cases for filter
$cases_sql = "SELECT id, case_number, case_title FROM cases ORDER BY case_number";
$cases_stmt = $conn->prepare($cases_sql);
$cases_stmt->execute();
$cases = $cases_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get officers for filter
$officers_sql = "SELECT id, fullname, badge_number FROM users WHERE role IN ('officer', 'investigator') ORDER BY fullname";
$officers_stmt = $conn->prepare($officers_sql);
$officers_stmt->execute();
$officers = $officers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get evidence statistics
$stats_sql = "SELECT 
                COUNT(*) as total_evidence,
                COUNT(CASE WHEN status = 'collected' THEN 1 END) as collected_evidence,
                COUNT(CASE WHEN status = 'analyzed' THEN 1 END) as analyzed_evidence,
                COUNT(CASE WHEN status = 'returned' THEN 1 END) as returned_evidence,
                COUNT(CASE WHEN status = 'destroyed' THEN 1 END) as destroyed_evidence
              FROM evidence";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->execute();
$evidence_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evidence Management - Criminal Management System</title>
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

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-card i {
            font-size: 2.5rem;
            color: #ff4444;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: #666;
            font-weight: 600;
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
            grid-template-columns: 1fr 1fr 1fr 1fr auto;
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

        /* Evidence Table */
        .evidence-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
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

        .evidence-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .evidence-table th,
        .evidence-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .evidence-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .evidence-table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-collected {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-analyzed {
            background: #d4edda;
            color: #155724;
        }

        .status-returned {
            background: #fff3cd;
            color: #856404;
        }

        .status-destroyed {
            background: #f8d7da;
            color: #721c24;
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

            .evidence-table {
                font-size: 0.9rem;
            }

            .evidence-table th,
            .evidence-table td {
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
                <li><a href="evidence_management.php">Evidence Management</a></li>
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
            <h1><i class="fas fa-fingerprint"></i> Evidence Management</h1>
            <p>Track and manage physical and digital evidence with chain of custody</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card fade-in-up">
                <i class="fas fa-boxes"></i>
                <div class="stat-number"><?php echo $evidence_stats['total_evidence']; ?></div>
                <div class="stat-label">Total Evidence</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-search"></i>
                <div class="stat-number"><?php echo $evidence_stats['collected_evidence']; ?></div>
                <div class="stat-label">Collected</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-microscope"></i>
                <div class="stat-number"><?php echo $evidence_stats['analyzed_evidence']; ?></div>
                <div class="stat-label">Analyzed</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-undo"></i>
                <div class="stat-number"><?php echo $evidence_stats['returned_evidence']; ?></div>
                <div class="stat-label">Returned</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-trash"></i>
                <div class="stat-number"><?php echo $evidence_stats['destroyed_evidence']; ?></div>
                <div class="stat-label">Destroyed</div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="search-section fade-in-up">
            <form method="GET" class="search-form">
                <div class="form-group">
                    <label for="search">Search Evidence</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Evidence number, description, location...">
                </div>
                <div class="form-group">
                    <label for="case_id">Case</label>
                    <select id="case_id" name="case_id">
                        <option value="">All Cases</option>
                        <?php foreach ($cases as $case): ?>
                            <option value="<?php echo $case['id']; ?>" <?php echo $case_filter == $case['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($case['case_number'] . ' - ' . $case['case_title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="evidence_type">Evidence Type</label>
                    <select id="evidence_type" name="evidence_type">
                        <option value="">All Types</option>
                        <option value="Physical" <?php echo $type_filter === 'Physical' ? 'selected' : ''; ?>>Physical</option>
                        <option value="Digital" <?php echo $type_filter === 'Digital' ? 'selected' : ''; ?>>Digital</option>
                        <option value="Documentary" <?php echo $type_filter === 'Documentary' ? 'selected' : ''; ?>>Documentary</option>
                        <option value="Biological" <?php echo $type_filter === 'Biological' ? 'selected' : ''; ?>>Biological</option>
                        <option value="Chemical" <?php echo $type_filter === 'Chemical' ? 'selected' : ''; ?>>Chemical</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Status</option>
                        <option value="collected" <?php echo $status_filter === 'collected' ? 'selected' : ''; ?>>Collected</option>
                        <option value="analyzed" <?php echo $status_filter === 'analyzed' ? 'selected' : ''; ?>>Analyzed</option>
                        <option value="returned" <?php echo $status_filter === 'returned' ? 'selected' : ''; ?>>Returned</option>
                        <option value="destroyed" <?php echo $status_filter === 'destroyed' ? 'selected' : ''; ?>>Destroyed</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Evidence Section -->
        <div class="evidence-section fade-in-up">
            <div class="section-header">
                <h2>Evidence Records (<?php echo count($evidence_list); ?> records)</h2>
                <a href="add_evidence.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add Evidence
                </a>
            </div>

            <div class="table-container">
                <table class="evidence-table">
                    <thead>
                        <tr>
                            <th>Evidence</th>
                            <th>Case</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Collected By</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($evidence_list) > 0): ?>
                            <?php foreach ($evidence_list as $evidence): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($evidence['evidence_number']); ?></strong>
                                        <br>
                                        <small><?php echo htmlspecialchars($evidence['description']); ?></small>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($evidence['case_number']); ?></div>
                                        <small><?php echo htmlspecialchars($evidence['case_title']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($evidence['evidence_type']); ?></td>
                                    <td><?php echo htmlspecialchars($evidence['location']); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($evidence['officer_name']); ?></div>
                                        <small>Badge: <?php echo htmlspecialchars($evidence['badge_number']); ?></small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $evidence['status']; ?>">
                                            <?php echo ucfirst($evidence['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="view_evidence.php?id=<?php echo $evidence['id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="edit_evidence.php?id=<?php echo $evidence['id']; ?>" class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="evidence_chain.php?id=<?php echo $evidence['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-link"></i> Chain
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem;">
                                    <i class="fas fa-box-open" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                                    <h3>No Evidence Found</h3>
                                    <p>No evidence records match your search criteria.</p>
                                    <a href="add_evidence.php" class="btn btn-success">
                                        <i class="fas fa-plus"></i> Add First Evidence
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

        // Animate statistics numbers
        function animateNumbers() {
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const target = parseInt(stat.textContent);
                if (!isNaN(target)) {
                    const duration = 2000;
                    const increment = target / (duration / 16);
                    let current = 0;

                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            current = target;
                            clearInterval(timer);
                        }
                        stat.textContent = Math.floor(current).toLocaleString();
                    }, 16);
                }
            });
        }

        // Trigger animation when page loads
        setTimeout(animateNumbers, 500);
    </script>
</body>
</html> 