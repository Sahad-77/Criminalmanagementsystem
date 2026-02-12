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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $case_number = trim($_POST['case_number']);
    $full_name = trim($_POST['full_name']);
    $alias = trim($_POST['alias']);
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $crime_type = $_POST['crime_type'];
    $arrest_date = $_POST['arrest_date'];
    $arresting_officer = trim($_POST['arresting_officer']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    
    // Validate required fields
    if (empty($case_number) || empty($full_name) || empty($crime_type) || empty($arrest_date)) {
        $_SESSION['error_message'] = "Please fill in all required fields.";
        header("Location: add_case.php");
        exit();
    }
    
    // Check if case number already exists
    $check_sql = "SELECT id FROM cases WHERE case_number = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$case_number]);
    if ($check_stmt->fetch()) {
        $_SESSION['error_message'] = "Case number already exists. Please use a different case number.";
        header("Location: add_case.php");
        exit();
    }
    
    // Insert new case
    $sql = "INSERT INTO cases (case_number, full_name, alias, age, gender, address, phone, 
                               crime_type, arrest_date, arresting_officer, description, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$case_number, $full_name, $alias, $age, $gender, $address, $phone, 
                    $crime_type, $arrest_date, $arresting_officer, $description, $status]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message'] = "Case created successfully!";
        header("Location: case_management.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Failed to create case. Please try again.";
        header("Location: add_case.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Case - Criminal Management System</title>
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
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
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
        /* Form */
        .form-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
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
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #555;
        }
        .form-group label.required::after {
            content: " *";
            color: #ff4444;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #ff4444;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
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
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        /* Responsive Design */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .nav-links {
                flex-direction: column;
                gap: 1rem;
            }
            .form-actions {
                flex-direction: column;
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
                <i class="fas fa-plus"></i>
                Add New Case
            </h1>
         
        <!-- Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message success">
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="message error">
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Form -->
        <div class="form-section">
            <h2><i class="fas fa-file-alt"></i> Case Information</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="case_number" class="required">Case Number</label>
                        <input type="text" id="case_number" name="case_number" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name" class="required">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="alias">Alias/Nickname</label>
                        <input type="text" id="alias" name="alias">
                    </div>
                    
                    <div class="form-group">
                        <label for="age" class="required">Age</label>
                        <input type="number" id="age" name="age" min="1" max="120" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="crime_type" class="required">Crime Type</label>
                        <select id="crime_type" name="crime_type" required>
                            <option value="">Select Crime Type</option>
                            <option value="Theft">Theft</option>
                            <option value="Assault">Assault</option>
                            <option value="Drug Possession">Drug Possession</option>
                            <option value="Drug Trafficking">Drug Trafficking</option>
                            <option value="Burglary">Burglary</option>
                            <option value="Robbery">Robbery</option>
                            <option value="Fraud">Fraud</option>
                            <option value="DUI">DUI</option>
                            <option value="Domestic Violence">Domestic Violence</option>
                            <option value="Sexual Assault">Sexual Assault</option>
                            <option value="Murder">Murder</option>
                            <option value="Kidnapping">Kidnapping</option>
                            <option value="Arson">Arson</option>
                            <option value="Vandalism">Vandalism</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="arrest_date" class="required">Arrest Date</label>
                        <input type="date" id="arrest_date" name="arrest_date" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="arresting_officer">Arresting Officer</label>
                        <input type="text" id="arresting_officer" name="arresting_officer">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Case Status</label>
                        <select id="status" name="status">
                            <option value="active">Active</option>
                            <option value="investigation">Under Investigation</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" placeholder="Enter full address..."></textarea>
                </div>
                
                <div class="form-group full-width">
                    <label for="description">Case Description</label>
                    <textarea id="description" name="description" placeholder="Provide detailed description of the case..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Case
                    </button>
                    <a href="case_management.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const caseNumber = document.getElementById('case_number').value.trim();
            const fullName = document.getElementById('full_name').value.trim();
            const age = document.getElementById('age').value;
            const crimeType = document.getElementById('crime_type').value;
            const arrestDate = document.getElementById('arrest_date').value;
            
            if (!caseNumber || !fullName || !age || !crimeType || !arrestDate) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
            
            if (age < 1 || age > 120) {
                e.preventDefault();
                alert('Please enter a valid age between 1 and 120.');
                return;
            }
        });

        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('arrest_date').max = today;

        // Auto-hide messages
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