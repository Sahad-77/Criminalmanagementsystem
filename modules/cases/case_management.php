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

// Get cases with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(case_number LIKE ? OR title LIKE ? OR case_type LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) FROM cases $where_clause";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_cases = $count_stmt->fetchColumn();
$total_pages = ceil($total_cases / $limit);

// Get cases
$cases_sql = "SELECT * FROM cases $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$cases_stmt = $conn->prepare($cases_sql);
$cases_stmt->execute($params);
$cases = $cases_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch criminals for each case
$case_criminals = [];
if (count($cases) > 0) {
    $case_ids = array_column($cases, 'id');
    $in = str_repeat('?,', count($case_ids) - 1) . '?';
$sql = "SELECT cc.case_id, c.fullname, c.date_of_birth, c.gender FROM case_criminals cc JOIN criminals c ON cc.criminal_id = c.id WHERE cc.case_id IN ($in)";
    $stmt = $conn->prepare($sql);
    $stmt->execute($case_ids);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $case_criminals[$row['case_id']][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Management - Criminal Management System</title>
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
            padding: 0.5rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
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
        .logo i {
            color: #ff4444;
            font-size: 1.2rem;
        }
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
            background: rgba(255, 255, 255, 0.1);
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
        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .header h1 {
            color: #333;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .header h1 i {
            color: #ff4444;
        }
        .breadcrumb {
            color: #666;
            font-size: 0.9rem;
        }
        .breadcrumb a {
            color: #ff4444;
            text-decoration: none;
        }
        /* Search and Filter */
        .search-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #555;
        }
        .form-group input,
        .form-group select {
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
        .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(23, 162, 184, 0.4);
        }
        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 193, 7, 0.4);
        }
        /* Cases List */
        .cases-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .cases-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .cases-count {
            font-size: 1.1rem;
            color: #666;
        }
        .cases-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
        }
        .case-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 5px solid #ff4444;
        }
        .case-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        .case-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        .case-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .case-number {
            color: #666;
            font-size: 0.9rem;
        }
        .case-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-closed {
            background: #f8d7da;
            color: #721c24;
        }
        .status-investigation {
            background: #cce5ff;
            color: #004085;
        }
        .case-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        .detail-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.2rem;
        }
        .detail-value {
            font-weight: 600;
            color: #333;
        }
        .case-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
        }
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            background: white;
            border: 1px solid #e1e5e9;
            transition: all 0.3s ease;
        }
        .pagination a:hover {
            background: #ff4444;
            color: white;
            border-color: #ff4444;
        }
        .pagination .current {
            background: #ff4444;
            color: white;
            border-color: #ff4444;
        }
        .no-cases {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        .no-cases i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        /* Responsive Design */
        @media (max-width: 768px) {
            .search-form {
                grid-template-columns: 1fr;
            }
            .cases-grid {
                grid-template-columns: 1fr;
            }
            .case-details {
                grid-template-columns: 1fr;
            }
            .nav-links {
                flex-direction: column;
                gap: 1rem;
            }
            .main-content {
                padding: 1rem;
            }
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
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </nav>
 <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-folder-open"></i>
                Case Management
            </h1>
        <!-- Search and Filter -->
        <div class="search-section">
            <h2><i class="fas fa-search"></i> Search Cases</h2>
            <form method="GET" class="search-form">
                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" 
                           placeholder="Case number, name, or crime type" 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        <option value="investigation" <?php echo $status_filter === 'investigation' ? 'selected' : ''; ?>>Investigation</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Cases List -->
        <div class="cases-section">
            <div class="cases-header">
                <h2><i class="fas fa-list"></i> Criminal Cases</h2>
                <div class="cases-count">
                    <?php echo $total_cases; ?> total case(s)
                </div>
                <a href="add_case.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add New Case
                </a>
            </div>

            <?php if (count($cases) > 0): ?>
                <div class="cases-grid">
                    <?php foreach ($cases as $case): ?>
                        <div class="case-card">
                            <div class="case-header">
                                <div>
                                    <div class="case-title"><?php echo htmlspecialchars($case['title']); ?></div>
                                    <div class="case-number">Case #: <?php echo htmlspecialchars($case['case_number']); ?></div>
                                </div>
                                <span class="case-status status-<?php echo $case['status']; ?>">
                                    <?php echo ucfirst($case['status']); ?>
                                </span>
                            </div>
                            <div class="case-details">
                                <div class="detail-item">
                                    <span class="detail-label">Crime Type</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($case['case_type']); ?></span>
                                </div>
                                <?php if (!empty($case_criminals[$case['id']])): ?>
                                    <?php foreach ($case_criminals[$case['id']] as $criminal): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Criminal Name</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($criminal['fullname']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Age</span>
                                            <span class="detail-value">
                                                <?php
                                                if (!empty($criminal['date_of_birth'])) {
                                                    $dob = new DateTime($criminal['date_of_birth']);
                                                    $now = new DateTime();
                                                    $age = $now->diff($dob)->y;
                                                    echo $age . ' years';
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Gender</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($criminal['gender']); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Criminal</span>
                                        <span class="detail-value">No criminal linked</span>
                                    </div>
                                <?php endif; ?>
                                <div class="detail-item">
                                    <span class="detail-label">Created</span>
                                    <span class="detail-value"><?php echo date('M d, Y', strtotime($case['created_at'])); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Status</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($case['status']); ?></span>
                                </div>
                            </div>
                            <div class="case-actions">
                                <a href="view_case.php?id=<?php echo $case['id']; ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="edit_case.php?id=<?php echo $case['id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="evidence_management.php?case_id=<?php echo $case['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-fingerprint"></i> Evidence
                                </a>
                                <a href="court_schedule.php?case_id=<?php echo $case['id']; ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-calendar"></i> Court
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-cases">
                    <i class="fas fa-folder-open"></i>
                    <h3>No Cases Found</h3>
                    <p>No cases match your search criteria. Try adjusting your filters or add a new case.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const search = document.getElementById('search').value.trim();
            const status = document.getElementById('status').value;
            
            if (!search && !status) {
                e.preventDefault();
                alert('Please enter a search term or select a status filter.');
                return;
            }
        });
    </script>
</body>
</html> 