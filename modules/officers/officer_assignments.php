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

// Handle assignment actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['assign_officer'])) {
        $officer_id = $_POST['officer_id'];
        $case_id = $_POST['case_id'];
        $assignment_date = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO officer_assignments (officer_id, case_id, assignment_date, status) VALUES (?, ?, ?, 'active')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$officer_id, $case_id, $assignment_date]);
        
        $_SESSION['success_message'] = "Officer assigned successfully!";
        header("Location: officer_assignments.php");
        exit();
    }
    
    if (isset($_POST['remove_assignment'])) {
        $assignment_id = $_POST['assignment_id'];
        
        $sql = "UPDATE officer_assignments SET status = 'inactive', end_date = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$assignment_id]);
        
        $_SESSION['success_message'] = "Assignment removed successfully!";
        header("Location: officer_assignments.php");
        exit();
    }
}

// Get active assignments
$assignments_sql = "SELECT oa.*, u.fullname as officer_name, u.badge_number, u.department, 
                           c.case_number, c.case_type, c.status as case_status
                    FROM officer_assignments oa
                    JOIN users u ON oa.officer_id = u.id
                    JOIN cases c ON oa.case_id = c.id
                    WHERE oa.status = 'active'
                    ORDER BY oa.assignment_date DESC";
$assignments_stmt = $conn->prepare($assignments_sql);
$assignments_stmt->execute();
$assignments = $assignments_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available officers
$officers_sql = "SELECT id, fullname, badge_number, department, role FROM users 
                 WHERE role IN ('officer', 'investigator') AND status = 'active'
                 ORDER BY fullname";
$officers_stmt = $conn->prepare($officers_sql);
$officers_stmt->execute();
$officers = $officers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available cases
$cases_sql = "SELECT id, case_number, case_type, status FROM cases 
              WHERE status IN ('open', 'under_investigation')
              ORDER BY created_at DESC";
$cases_stmt = $conn->prepare($cases_sql);
$cases_stmt->execute();
$cases = $cases_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Assignments - Criminal Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Main background and container */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            background: linear-gradient(45deg, rgba(25,25,112,0.8) 0%, rgba(47,84,235,0.8) 25%, rgba(138,43,226,0.8) 50%, rgba(75,0,130,0.8) 75%, rgba(25,25,112,0.8) 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
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

        /* Messages */
        .message {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Assignment Form */
        .assignment-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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

        .form-group select,
        .form-group input {
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #ff4444;
        }

        .btn {
            padding: 12px 30px;
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

        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(220,53,69,0.4);
        }

        /* Assignments List */
        .assignments-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .assignments-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .assignments-count {
            font-size: 1.1rem;
            color: #666;
        }

        .assignments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 1.5rem;
        }

        .assignment-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 5px solid #ff4444;
        }

        .assignment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .assignment-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .assignment-date {
            color: #666;
            font-size: 0.9rem;
        }

        .assignment-details {
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

        .assignment-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .no-assignments {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-assignments i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .assignments-grid {
                grid-template-columns: 1fr;
            }
            .container {
                padding: 20px 5px;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        /* Misc styles */
        .header h1 i,
        .assignment-title {
            color: #ff4444;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-users-cog"></i>
                Officer Assignments
    </h1>

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

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message success">
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <!-- Assignment Form -->
        <div class="assignment-form">
            <h2><i class="fas fa-plus"></i> New Assignment</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="officer_id">Select Officer</label>
                        <select id="officer_id" name="officer_id" required>
                            <option value="">Choose an officer...</option>
                            <?php foreach ($officers as $officer): ?>
                                <option value="<?php echo $officer['id']; ?>">
                                    <?php echo htmlspecialchars($officer['fullname']); ?> 
                                    (Badge: <?php echo htmlspecialchars($officer['badge_number']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="case_id">Select Case</label>
                        <select id="case_id" name="case_id" required>
                            <option value="">Choose a case...</option>
                            <?php foreach ($cases as $case): ?>
                                <option value="<?php echo $case['id']; ?>">
                                    <?php echo htmlspecialchars($case['case_number']); ?> 
                                    (<?php echo htmlspecialchars($case['case_type']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <button type="submit" name="assign_officer" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Assign Officer
                </button>
            </form>
        </div>

        <!-- Assignments List -->
        <div class="assignments-section">
            <div class="assignments-header">
                <h2><i class="fas fa-list"></i> Current Assignments</h2>
                <div class="assignments-count">
                    <?php echo count($assignments); ?> active assignment(s)
                </div>
            </div>

            <?php if (count($assignments) > 0): ?>
                <div class="assignments-grid">
                    <?php foreach ($assignments as $assignment): ?>
                        <div class="assignment-card">
                            <div class="assignment-header">
                                <div>
                                    <div class="assignment-title">
                                        <?php echo htmlspecialchars($assignment['officer_name']); ?>
                                    </div>
                                    <div class="assignment-date">
                                        Assigned: <?php echo date('M d, Y', strtotime($assignment['assignment_date'])); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="assignment-details">
                                <div class="detail-item">
                                    <span class="detail-label">Badge Number</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($assignment['badge_number']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Department</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($assignment['department']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Case Number</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($assignment['case_number']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Case Type</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($assignment['case_type']); ?></span>
                                </div>
                            </div>
                            
                            <div class="assignment-actions">
                                <a href="view_case.php?id=<?php echo $assignment['case_id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> View Case
                                </a>
                                <a href="view_officer.php?id=<?php echo $assignment['officer_id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-user"></i> View Officer
                                </a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                    <button type="submit" name="remove_assignment" class="btn btn-danger btn-sm" 
                                            onclick="return confirm('Are you sure you want to remove this assignment?')">
                                        <i class="fas fa-times"></i> Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-assignments">
                    <i class="fas fa-users-slash"></i>
                    <h3>No Active Assignments</h3>
                    <p>No officers are currently assigned to cases. Use the form above to create new assignments.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const officerId = document.getElementById('officer_id').value;
            const caseId = document.getElementById('case_id').value;
            
            if (!officerId || !caseId) {
                e.preventDefault();
                alert('Please select both an officer and a case.');
                return;
            }
        });

        // Auto-hide success messages
        setTimeout(function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(function(message) {
                message.style.opacity = '0';
                message.style.transition = 'opacity 0.5s ease';
                setTimeout(function() {
                    message.remove();
                }, 500);
            });
        }, 3000);
    </script>
</body>
</html>