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

// Handle schedule actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_schedule'])) {
        $case_id = $_POST['case_id'];
        $criminal_id = $_POST['criminal_id'];
        $court_date = $_POST['court_date'];
        $court_time = $_POST['court_time'];
        $court_type = $_POST['court_type'];
        $judge_name = $_POST['judge_name'];
        $notes = $_POST['notes'];

        // Combine date and time for court_date (datetime)
        $court_datetime = $court_date . ' ' . $court_time . ':00';

        // Insert into court_appearances (columns based on your DB)
        $sql = "INSERT INTO court_appearances 
            (case_id, criminal_id, court_name, court_date, appearance_type, notes) 
            VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $case_id,
            $criminal_id,
            $judge_name, // using judge_name as court_name for demo
            $court_datetime,
            $court_type,
            $notes
        ]);

        $_SESSION['success_message'] = "Court appearance scheduled successfully!";
        header("Location: court_schedule.php");
        exit();
    }
}

// Get upcoming court appearances
$schedule_sql = "SELECT ca.*, c.case_number, c.title as case_title, cr.fullname as criminal_name
                 FROM court_appearances ca
                 JOIN cases c ON ca.case_id = c.id
                 JOIN criminals cr ON ca.criminal_id = cr.id
                 WHERE ca.court_date >= CURDATE()
                 ORDER BY ca.court_date ASC";
$schedule_stmt = $conn->prepare($schedule_sql);
$schedule_stmt->execute();
$schedule = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available cases for scheduling
$cases_sql = "SELECT id, case_number, title FROM cases 
              WHERE status IN ('open', 'under_investigation')
              ORDER BY created_at DESC";
$cases_stmt = $conn->prepare($cases_sql);
$cases_stmt->execute();
$cases = $cases_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available criminals for scheduling
$criminals_sql = "SELECT id, fullname FROM criminals ORDER BY fullname";
$criminals_stmt = $conn->prepare($criminals_sql);
$criminals_stmt->execute();
$criminals = $criminals_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's schedule
$today_sql = "SELECT ca.*, c.case_number, c.title as case_title, cr.fullname as criminal_name
              FROM court_appearances ca
              JOIN cases c ON ca.case_id = c.id
              JOIN criminals cr ON ca.criminal_id = cr.id
              WHERE DATE(ca.court_date) = CURDATE()
              ORDER BY ca.court_date ASC";
$today_stmt = $conn->prepare($today_sql);
$today_stmt->execute();
$today_schedule = $today_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Court Schedule - Criminal Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Use dashboard styles for consistency */
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
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        /* Background animation */
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

        /* Header styles */
        .header h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .breadcrumb {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }

        /* Navigation bar styles */
        .nav-bar {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            padding: 0.5rem 0;
            position: relative;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
            min-height: 40px;
            margin-bottom: 2rem;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 1.2rem;
            padding-left: 0;
            margin: 0;
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

        .nav-links a:hover,
        .nav-links a.active {
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

        /* Message styles */
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px 16px;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 600;
            font-size: 1rem;
        }

        /* Section styles */
        .today-section, .schedule-section, .schedule-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .today-header, .schedule-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .today-header i, .schedule-header i {
            font-size: 2rem;
            color: #ff4444;
        }

        .today-header h2, .schedule-header h2 {
            font-size: 1.5rem;
            color: #333;
        }

        .schedule-count {
            color: #666;
            font-size: 1.1rem;
            margin-left: auto;
        }

        /* Schedule grid styles */
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .schedule-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            padding: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .schedule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .schedule-header-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .schedule-title {
            font-weight: 600;
            color: #333;
            font-size: 1.2rem;
        }

        .schedule-date {
            color: #666;
            font-size: 1rem;
        }

        .schedule-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            background: #e2e3e5;
            color: #333;
        }

        .status-scheduled {
            background: #cce5ff;
            color: #004085;
        }

        .schedule-details {
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            min-width: 120px;
        }

        .detail-value {
            color: #333;
        }

        .schedule-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        /* Button styles */
        .btn {
            padding: 10px 20px;
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

        .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
            color: white;
        }

        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(23,162,184,0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40,167,69,0.4);
        }

        .no-schedule {
            text-align: center;
            color: #666;
            padding: 2rem 0;
        }

        .no-schedule i {
            font-size: 2.5rem;
            color: #ff4444;
            margin-bottom: 1rem;
        }

        .no-schedule h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .schedule-form h2 {
            color: #ff4444;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 2rem;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-row {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        label {
            font-weight: 600;
            margin-bottom: 0.3rem;
            color: #333;
        }

        input, select, textarea {
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            background: #f4f6fa;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #ff4444;
            background: #fff;
        }

        textarea {
            resize: vertical;
        }

        @media (max-width: 900px) {
            .container {
                padding: 10px;
            }
            .schedule-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .form-row {
                flex-direction: column;
                gap: 1rem;
            }
            .today-section, .schedule-section, .schedule-form {
                padding: 1rem;
                border-radius: 12px;
            }
            .header h1 {
                font-size: 1.5rem;
            }
            .nav-links {
                flex-direction: column;
                gap: 0.5rem;
            }
            .nav-links a {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
            .logout-btn {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
            .message.success {
                font-size: 0.9rem;
                padding: 8px 12px;
            }
            .today-header h2, .schedule-header h2 {
                font-size: 1.2rem;
            }
            .schedule-card {
                padding: 1rem;
            }
            .schedule-title {
                font-size: 1rem;
            }
            .schedule-date, .detail-label {
                font-size: 0.9rem;
            }
            .detail-value {
                font-size: 0.9rem;
            }
            .btn {
                font-size: 0.9rem;
                padding: 8px 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-calendar-alt"></i>
                Court Schedule
            </h1>
            <div class="breadcrumb">
                <a href="dashboard.php">Dashboard</a> > Court Schedule
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

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message success">
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <!-- Today's Schedule -->
        <div class="today-section">
            <div class="today-header">
                <i class="fas fa-calendar-day today-icon"></i>
                <h2>Today's Court Schedule</h2>
            </div>
            <?php if (count($today_schedule) > 0): ?>
                <div class="schedule-grid">
                    <?php foreach ($today_schedule as $appearance): ?>
                        <div class="schedule-card">
                            <div class="schedule-header-card">
                                <div>
                                    <div class="schedule-title"><?php echo htmlspecialchars($appearance['criminal_name']); ?></div>
                                    <div class="schedule-date"><?php echo date('g:i A', strtotime($appearance['court_date'])); ?></div>
                                </div>
                                <span class="schedule-status status-scheduled">
                                    Scheduled
                                </span>
                            </div>
                            <div class="schedule-details">
                                <div class="detail-item">
                                    <span class="detail-label">Case Number</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($appearance['case_number']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Court Type</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($appearance['appearance_type']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Court Name</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($appearance['court_name']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Notes</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($appearance['notes']); ?></span>
                                </div>
                            </div>
                            <div class="schedule-actions">
                                <a href="view_case.php?id=<?php echo $appearance['case_id']; ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i> View Case
                                </a>
                                <a href="edit_appearance.php?id=<?php echo $appearance['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-schedule">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Court Appearances Today</h3>
                    <p>There are no scheduled court appearances for today.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Schedule Form -->
        <div class="schedule-form">
            <h2><i class="fas fa-plus"></i> Schedule New Court Appearance</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="case_id">Select Case</label>
                        <select id="case_id" name="case_id" required>
                            <option value="">Choose a case...</option>
                            <?php foreach ($cases as $case): ?>
                                <option value="<?php echo $case['id']; ?>">
                                    <?php echo htmlspecialchars($case['case_number']); ?> 
                                    (<?php echo htmlspecialchars($case['title']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="criminal_id">Select Criminal</label>
                        <select id="criminal_id" name="criminal_id" required>
                            <option value="">Choose a criminal...</option>
                            <?php foreach ($criminals as $criminal): ?>
                                <option value="<?php echo $criminal['id']; ?>">
                                    <?php echo htmlspecialchars($criminal['fullname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="court_date">Court Date</label>
                        <input type="date" id="court_date" name="court_date" required>
                    </div>
                    <div class="form-group">
                        <label for="court_time">Court Time</label>
                        <input type="time" id="court_time" name="court_time" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="court_type">Appearance Type</label>
                        <select id="court_type" name="court_type" required>
                            <option value="">Select Type</option>
                            <option value="arraignment">Arraignment</option>
                            <option value="preliminary_hearing">Preliminary Hearing</option>
                            <option value="trial">Trial</option>
                            <option value="sentencing">Sentencing</option>
                            <option value="appeal">Appeal</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="judge_name">Court Name</label>
                        <input type="text" id="judge_name" name="judge_name" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Additional notes about the court appearance..."></textarea>
                </div>
                <button type="submit" name="add_schedule" class="btn btn-primary">
                    <i class="fas fa-calendar-plus"></i> Schedule Appearance
                </button>
            </form>
        </div>

        <!-- Upcoming Schedule -->
        <div class="schedule-section">
            <div class="schedule-header">
                <h2><i class="fas fa-calendar-week"></i> Upcoming Court Schedule</h2>
                <div class="schedule-count">
                    <?php echo count($schedule); ?> upcoming appearance(s)
                </div>
            </div>
            <?php if (count($schedule) > 0): ?>
                <div class="schedule-grid">
                    <?php foreach ($schedule as $appearance): ?>
                        <div class="schedule-card">
                            <div class="schedule-header-card">
                                <div>
                                    <div class="schedule-title"><?php echo htmlspecialchars($appearance['criminal_name']); ?></div>
                                    <div class="schedule-date">
                                        <?php echo date('M d, Y', strtotime($appearance['court_date'])); ?> 
                                        at <?php echo date('g:i A', strtotime($appearance['court_date'])); ?>
                                    </div>
                                </div>
                                <span class="schedule-status status-scheduled">
                                    Scheduled
                                </span>
                            </div>
                            <div class="schedule-details">
                                <div class="detail-item">
                                    <span class="detail-label">Case Number</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($appearance['case_number']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Appearance Type</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($appearance['appearance_type']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Court Name</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($appearance['court_name']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Notes</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($appearance['notes']); ?></span>
                                </div>
                            </div>
                            <div class="schedule-actions">
                                <a href="view_case.php?id=<?php echo $appearance['case_id']; ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i> View Case
                                </a>
                                <a href="edit_appearance.php?id=<?php echo $appearance['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="mark_completed.php?id=<?php echo $appearance['id']; ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-check"></i> Mark Complete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-schedule">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Upcoming Court Appearances</h3>
                    <p>No court appearances are scheduled for the future.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const caseId = document.getElementById('case_id').value;
            const criminalId = document.getElementById('criminal_id').value;
            const courtDate = document.getElementById('court_date').value;
            const courtTime = document.getElementById('court_time').value;
            const courtType = document.getElementById('court_type').value;
            const judgeName = document.getElementById('judge_name').value;

            if (!caseId || !criminalId || !courtDate || !courtTime || !courtType || !judgeName) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
        });

        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('court_date').min = today;

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