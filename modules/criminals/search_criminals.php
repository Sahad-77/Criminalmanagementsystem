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

// Search functionality
$search_results = [];
$search_performed = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
    $search_performed = true;
    $search_term = trim($_POST['search_term']);
    $crime_type = $_POST['crime_type'];
    $status = $_POST['status'];
    $date_from = $_POST['date_from'];
    $date_to = $_POST['date_to'];
    
    $sql = "SELECT * FROM criminals WHERE 1=1";
    $params = [];
    
    if (!empty($search_term)) {
        $sql .= " AND (full_name LIKE ? OR alias LIKE ? OR case_number LIKE ?)";
        $search_param = "%$search_term%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    if (!empty($crime_type)) {
        $sql .= " AND crime_type = ?";
        $params[] = $crime_type;
    }
    
    if (!empty($status)) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    if (!empty($date_from)) {
        $sql .= " AND arrest_date >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $sql .= " AND arrest_date <= ?";
        $params[] = $date_to;
    }
    
    $sql .= " ORDER BY arrest_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get crime types for filter
$crime_types_sql = "SELECT DISTINCT crime_type FROM criminals WHERE crime_type IS NOT NULL ORDER BY crime_type";
$crime_types_stmt = $conn->prepare($crime_types_sql);
$crime_types_stmt->execute();
$crime_types = $crime_types_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Criminals - Criminal Management System</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
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

        /* Navigation */
        .nav-bar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: #ff4444;
            color: white;
        }

        .logout-btn {
            background: #ff4444;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #cc0000;
        }

        /* Search Form */
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

        .search-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
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

        .btn-secondary {
            background: linear-gradient(135deg, #333 0%, #555 100%);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(51, 51, 51, 0.4);
        }

        /* Results Section */
        .results-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .results-count {
            font-size: 1.1rem;
            color: #666;
        }

        .criminal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
        }

        .criminal-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 5px solid #ff4444;
        }

        .criminal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .criminal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .criminal-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .criminal-alias {
            color: #666;
            font-style: italic;
        }

        .criminal-status {
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

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .criminal-details {
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

        .criminal-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-results i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .search-form {
                grid-template-columns: 1fr;
            }

            .criminal-grid {
                grid-template-columns: 1fr;
            }

            .criminal-details {
                grid-template-columns: 1fr;
            }

            .nav-links {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-search"></i>
                Search Criminals
            </h1>
            <div class="breadcrumb">
                <a href="dashboard.php">Dashboard</a> > Search Criminals
            </div>
        </div>

        <!-- Navigation -->
        <div class="nav-bar">
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

        <!-- Search Form -->
        <div class="search-section">
            <h2><i class="fas fa-filter"></i> Advanced Search</h2>
            <form method="POST" class="search-form">
                <div class="form-group">
                    <label for="search_term">Search Term</label>
                    <input type="text" id="search_term" name="search_term" 
                           placeholder="Name, alias, or case number" 
                           value="<?php echo isset($_POST['search_term']) ? htmlspecialchars($_POST['search_term']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="crime_type">Crime Type</label>
                    <select id="crime_type" name="crime_type">
                        <option value="">All Crime Types</option>
                        <?php foreach ($crime_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" 
                                    <?php echo (isset($_POST['crime_type']) && $_POST['crime_type'] === $type) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date_from">Date From</label>
                    <input type="date" id="date_from" name="date_from" 
                           value="<?php echo isset($_POST['date_from']) ? htmlspecialchars($_POST['date_from']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="date_to">Date To</label>
                    <input type="date" id="date_to" name="date_to" 
                           value="<?php echo isset($_POST['date_to']) ? htmlspecialchars($_POST['date_to']) : ''; ?>">
                </div>
                
                <div class="search-buttons">
                    <button type="submit" name="search" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="search_criminals.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Results Section -->
        <div class="results-section">
            <div class="results-header">
                <h2><i class="fas fa-list"></i> Search Results</h2>
                <?php if ($search_performed): ?>
                    <div class="results-count">
                        Found <?php echo count($search_results); ?> criminal(s)
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($search_performed): ?>
                <?php if (count($search_results) > 0): ?>
                    <div class="criminal-grid">
                        <?php foreach ($search_results as $criminal): ?>
                            <div class="criminal-card">
                                <div class="criminal-header">
                                    <div>
                                        <div class="criminal-name"><?php echo htmlspecialchars($criminal['full_name']); ?></div>
                                        <?php if (!empty($criminal['alias'])): ?>
                                            <div class="criminal-alias">Alias: <?php echo htmlspecialchars($criminal['alias']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="criminal-status status-<?php echo $criminal['status']; ?>">
                                        <?php echo ucfirst($criminal['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="criminal-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Case Number</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($criminal['case_number']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Crime Type</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($criminal['crime_type']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Arrest Date</span>
                                        <span class="detail-value"><?php echo date('M d, Y', strtotime($criminal['arrest_date'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Age</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($criminal['age']); ?> years</span>
                                    </div>
                                </div>
                                
                                <div class="criminal-actions">
                                    <a href="view_criminal.php?id=<?php echo $criminal['id']; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="edit_criminal.php?id=<?php echo $criminal['id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="criminal_history.php?id=<?php echo $criminal['id']; ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-history"></i> History
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>No Results Found</h3>
                        <p>No criminals match your search criteria. Try adjusting your search parameters.</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>Search for Criminals</h3>
                    <p>Use the search form above to find criminal records in the system.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const searchTerm = document.getElementById('search_term').value.trim();
            const crimeType = document.getElementById('crime_type').value;
            const status = document.getElementById('status').value;
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = document.getElementById('date_to').value;
            
            if (!searchTerm && !crimeType && !status && !dateFrom && !dateTo) {
                e.preventDefault();
                alert('Please enter at least one search criteria.');
                return;
            }
        });

        // Date validation
        document.getElementById('date_to').addEventListener('change', function() {
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = this.value;
            
            if (dateFrom && dateTo && dateFrom > dateTo) {
                alert('End date must be after start date.');
                this.value = '';
            }
        });
    </script>
</body>
</html> 