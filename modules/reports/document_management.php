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
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$type_filter = isset($_GET['document_type']) ? $_GET['document_type'] : '';
$uploader_filter = isset($_GET['uploader_id']) ? $_GET['uploader_id'] : '';

// Build query for documents
$where_conditions = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(d.document_name LIKE ? OR d.description LIKE ? OR d.tags LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($case_filter)) {
    $where_conditions[] = "d.case_id = ?";
    $params[] = $case_filter;
}

if (!empty($category_filter)) {
    $where_conditions[] = "d.category = ?";
    $params[] = $category_filter;
}

if (!empty($type_filter)) {
    $where_conditions[] = "d.document_type = ?";
    $params[] = $type_filter;
}

if (!empty($uploader_filter)) {
    $where_conditions[] = "d.uploaded_by = ?";
    $params[] = $uploader_filter;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

$sql = "SELECT d.*, c.case_number, c.case_title, u.fullname as uploader_name, u.badge_number
        FROM documents d
        LEFT JOIN cases c ON d.case_id = c.id
        LEFT JOIN users u ON d.uploaded_by = u.id
        $where_clause
        ORDER BY d.upload_date DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available cases for filter
$cases_sql = "SELECT id, case_number, case_title FROM cases ORDER BY case_number";
$cases_stmt = $conn->prepare($cases_sql);
$cases_stmt->execute();
$cases = $cases_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get uploaders for filter
$uploaders_sql = "SELECT id, fullname, badge_number FROM users WHERE role IN ('officer', 'investigator', 'admin') ORDER BY fullname";
$uploaders_stmt = $conn->prepare($uploaders_sql);
$uploaders_stmt->execute();
$uploaders = $uploaders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get document statistics
$stats_sql = "SELECT 
                COUNT(*) as total_documents,
                COUNT(CASE WHEN document_type = 'pdf' THEN 1 END) as pdf_documents,
                COUNT(CASE WHEN document_type = 'image' THEN 1 END) as image_documents,
                COUNT(CASE WHEN document_type = 'video' THEN 1 END) as video_documents,
                COUNT(CASE WHEN document_type = 'audio' THEN 1 END) as audio_documents,
                ROUND(SUM(file_size) / 1024 / 1024, 2) as total_size_mb
              FROM documents";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->execute();
$document_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Management - Criminal Management System</title>
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

        /* Documents Grid */
        .documents-section {
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

        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .document-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid #ff4444;
        }

        .document-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .document-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .document-icon {
            width: 50px;
            height: 50px;
            background: #ff4444;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .document-info h4 {
            color: #333;
            margin-bottom: 0.3rem;
            font-size: 1.1rem;
        }

        .document-info p {
            color: #666;
            font-size: 0.9rem;
        }

        .document-details {
            margin-bottom: 1rem;
        }

        .document-details p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }

        .document-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 8px 12px;
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

        /* File Type Icons */
        .file-type-pdf { background: #dc3545; }
        .file-type-image { background: #28a745; }
        .file-type-video { background: #6f42c1; }
        .file-type-audio { background: #fd7e14; }
        .file-type-document { background: #17a2b8; }

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

            .documents-grid {
                grid-template-columns: 1fr;
            }

            .document-actions {
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
                <li><a href="report_generation.php">Reports</a></li>
                <li><a href="document_management.php">Documents</a></li>
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
            <h1><i class="fas fa-file-upload"></i> Document Management</h1>
            <p>Upload, organize, and manage case documents, evidence files, and legal paperwork</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card fade-in-up">
                <i class="fas fa-file-alt"></i>
                <div class="stat-number"><?php echo $document_stats['total_documents']; ?></div>
                <div class="stat-label">Total Documents</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-file-pdf"></i>
                <div class="stat-number"><?php echo $document_stats['pdf_documents']; ?></div>
                <div class="stat-label">PDF Files</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-image"></i>
                <div class="stat-number"><?php echo $document_stats['image_documents']; ?></div>
                <div class="stat-label">Images</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-video"></i>
                <div class="stat-number"><?php echo $document_stats['video_documents']; ?></div>
                <div class="stat-label">Videos</div>
            </div>
            <div class="stat-card fade-in-up">
                <i class="fas fa-hdd"></i>
                <div class="stat-number"><?php echo $document_stats['total_size_mb']; ?> MB</div>
                <div class="stat-label">Total Size</div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="search-section fade-in-up">
            <form method="GET" class="search-form">
                <div class="form-group">
                    <label for="search">Search Documents</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Document name, description, tags...">
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
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All Categories</option>
                        <option value="Evidence" <?php echo $category_filter === 'Evidence' ? 'selected' : ''; ?>>Evidence</option>
                        <option value="Legal" <?php echo $category_filter === 'Legal' ? 'selected' : ''; ?>>Legal</option>
                        <option value="Reports" <?php echo $category_filter === 'Reports' ? 'selected' : ''; ?>>Reports</option>
                        <option value="Photos" <?php echo $category_filter === 'Photos' ? 'selected' : ''; ?>>Photos</option>
                        <option value="Videos" <?php echo $category_filter === 'Videos' ? 'selected' : ''; ?>>Videos</option>
                        <option value="Audio" <?php echo $category_filter === 'Audio' ? 'selected' : ''; ?>>Audio</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="document_type">File Type</label>
                    <select id="document_type" name="document_type">
                        <option value="">All Types</option>
                        <option value="pdf" <?php echo $type_filter === 'pdf' ? 'selected' : ''; ?>>PDF</option>
                        <option value="image" <?php echo $type_filter === 'image' ? 'selected' : ''; ?>>Image</option>
                        <option value="video" <?php echo $type_filter === 'video' ? 'selected' : ''; ?>>Video</option>
                        <option value="audio" <?php echo $type_filter === 'audio' ? 'selected' : ''; ?>>Audio</option>
                        <option value="document" <?php echo $type_filter === 'document' ? 'selected' : ''; ?>>Document</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Documents Section -->
        <div class="documents-section fade-in-up">
            <div class="section-header">
                <h2>Documents (<?php echo count($documents); ?> files)</h2>
                <a href="upload_document.php" class="btn btn-success">
                    <i class="fas fa-upload"></i> Upload Document
                </a>
            </div>

            <div class="documents-grid">
                <?php if (count($documents) > 0): ?>
                    <?php foreach ($documents as $document): ?>
                        <div class="document-card">
                            <div class="document-header">
                                <div class="document-icon file-type-<?php echo $document['document_type']; ?>">
                                    <?php
                                    $icon = 'fas fa-file';
                                    switch($document['document_type']) {
                                        case 'pdf': $icon = 'fas fa-file-pdf'; break;
                                        case 'image': $icon = 'fas fa-file-image'; break;
                                        case 'video': $icon = 'fas fa-file-video'; break;
                                        case 'audio': $icon = 'fas fa-file-audio'; break;
                                        case 'document': $icon = 'fas fa-file-alt'; break;
                                    }
                                    ?>
                                    <i class="<?php echo $icon; ?>"></i>
                                </div>
                                <div class="document-info">
                                    <h4><?php echo htmlspecialchars($document['document_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($document['case_number'] ?? 'No Case'); ?></p>
                                </div>
                            </div>
                            <div class="document-details">
                                <p><strong>Category:</strong> <?php echo htmlspecialchars($document['category']); ?></p>
                                <p><strong>Size:</strong> <?php echo round($document['file_size'] / 1024, 2); ?> KB</p>
                                <p><strong>Uploaded:</strong> <?php echo date('M j, Y', strtotime($document['upload_date'])); ?></p>
                                <p><strong>By:</strong> <?php echo htmlspecialchars($document['uploader_name']); ?></p>
                                <?php if (!empty($document['description'])): ?>
                                    <p><strong>Description:</strong> <?php echo htmlspecialchars(substr($document['description'], 0, 100)); ?>...</p>
                                <?php endif; ?>
                            </div>
                            <div class="document-actions">
                                <a href="view_document.php?id=<?php echo $document['id']; ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="download_document.php?id=<?php echo $document['id']; ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <a href="edit_document.php?id=<?php echo $document['id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                        <i class="fas fa-file-upload" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                        <h3>No Documents Found</h3>
                        <p>No documents match your search criteria.</p>
                        <a href="upload_document.php" class="btn btn-success">
                            <i class="fas fa-upload"></i> Upload First Document
                        </a>
                    </div>
                <?php endif; ?>
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
                const text = stat.textContent;
                const target = parseFloat(text.replace(/[^\d.]/g, ''));
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
                        if (text.includes('MB')) {
                            stat.textContent = Math.floor(current) + ' MB';
                        } else {
                            stat.textContent = Math.floor(current).toLocaleString();
                        }
                    }, 16);
                }
            });
        }

        // Trigger animation when page loads
        setTimeout(animateNumbers, 500);
    </script>
</body>
</html> 