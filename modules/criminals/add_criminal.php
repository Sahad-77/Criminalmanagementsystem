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

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = ['fullname', 'date_of_birth', 'gender', 'identification_number', 'status', 'risk_level'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            throw new Exception("Please fill in all required fields: " . implode(', ', $missing_fields));
        }
        
        // Check if identification number already exists
        $stmt = $conn->prepare("SELECT id FROM criminals WHERE identification_number = ?");
        $stmt->execute([$_POST['identification_number']]);
        if ($stmt->fetch()) {
            throw new Exception("Identification number already exists in the system.");
        }
        
        // Handle photo upload
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $targetDir = "uploads/criminal_photos/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $filename = uniqid() . '_' . basename($_FILES['photo']['name']);
            $targetFile = $targetDir . $filename;
            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($imageFileType, $allowedTypes)) {
                throw new Exception("Only JPG, JPEG, PNG, GIF, and WEBP files are allowed for the photo.");
            }
            if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                throw new Exception("Photo size must be less than 2MB.");
            }
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
                $photoPath = $targetFile;
            } else {
                throw new Exception("Failed to upload photo.");
            }
        }
        
        // Insert new criminal
        $sql = "INSERT INTO criminals (fullname, alias, date_of_birth, gender, nationality, identification_number, 
                address, phone, email, height, weight, eye_color, hair_color, distinguishing_marks, 
                status, risk_level, created_by, photo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $_POST['fullname'],
            $_POST['alias'] ?? null,
            $_POST['date_of_birth'],
            $_POST['gender'],
            $_POST['nationality'] ?? null,
            $_POST['identification_number'],
            $_POST['address'] ?? null,
            $_POST['phone'] ?? null,
            $_POST['email'] ?? null,
            $_POST['height'] ? floatval($_POST['height']) : null,
            $_POST['weight'] ? floatval($_POST['weight']) : null,
            $_POST['eye_color'] ?? null,
            $_POST['hair_color'] ?? null,
            $_POST['distinguishing_marks'] ?? null,
            $_POST['status'],
            $_POST['risk_level'],
            $_SESSION['user_id'],
            $photoPath
        ]);
        
        $criminal_id = $conn->lastInsertId();
        
        // Log activity
        $log_sql = "INSERT INTO activity_log (user_id, action, table_name, record_id, details) VALUES (?, 'create', 'criminals', ?, ?)";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->execute([
            $_SESSION['user_id'],
            $criminal_id,
            "Added new criminal: " . $_POST['fullname']
        ]);
        
        $message = "Criminal record added successfully!";
        $message_type = "success";
        
        // Redirect to view the new criminal
        header("Location: view_criminal.php?id=" . $criminal_id);
        exit();
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Criminal - Criminal Management System</title>
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
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 1s ease-out;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .form-header i {
            font-size: 4rem;
            color: #ff4444;
            margin-bottom: 1rem;
        }

        /* Message Styles */
        .message {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
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

        /* Form Styles */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }

        .form-group label.required::after {
            content: " *";
            color: #ff4444;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            background: white;
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
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
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
            .nav-links {
                display: none;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .main-content {
                padding: 1rem;
            }

            .form-container {
                padding: 2rem;
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
        <div class="form-container">
            <div class="form-header">
                <i class="fas fa-user-plus"></i>
                <h1>Add New Criminal</h1>
                <p>Enter the criminal's information to create a new record</p>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="addCriminalForm" enctype="multipart/form-data">
                <!-- Personal Information -->
                <h3 style="margin-bottom: 1rem; color: #333; border-bottom: 2px solid #ff4444; padding-bottom: 0.5rem;">
                    <i class="fas fa-user"></i> Personal Information
                </h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fullname" class="required">Full Name</label>
                        <input type="text" id="fullname" name="fullname" required value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="alias">Alias/Nickname</label>
                        <input type="text" id="alias" name="alias" value="<?php echo htmlspecialchars($_POST['alias'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="date_of_birth" class="required">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" required value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="gender" class="required">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male" <?php echo ($_POST['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($_POST['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo ($_POST['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nationality">Nationality</label>
                        <input type="text" id="nationality" name="nationality" value="<?php echo htmlspecialchars($_POST['nationality'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="identification_number" class="required">Identification Number</label>
                        <input type="text" id="identification_number" name="identification_number" required value="<?php echo htmlspecialchars($_POST['identification_number'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Contact Information -->
                <h3 style="margin: 2rem 0 1rem 0; color: #333; border-bottom: 2px solid #ff4444; padding-bottom: 0.5rem;">
                    <i class="fas fa-address-book"></i> Contact Information
                </h3>

                <div class="form-group full-width">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" placeholder="Enter full address..."><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Physical Description -->
                <h3 style="margin: 2rem 0 1rem 0; color: #333; border-bottom: 2px solid #ff4444; padding-bottom: 0.5rem;">
                    <i class="fas fa-user-tie"></i> Physical Description
                </h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="height">Height (cm)</label>
                        <input type="number" id="height" name="height" step="0.1" min="50" max="250" value="<?php echo htmlspecialchars($_POST['height'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="weight">Weight (kg)</label>
                        <input type="number" id="weight" name="weight" step="0.1" min="20" max="200" value="<?php echo htmlspecialchars($_POST['weight'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="eye_color">Eye Color</label>
                        <select id="eye_color" name="eye_color">
                            <option value="">Select Eye Color</option>
                            <option value="brown" <?php echo ($_POST['eye_color'] ?? '') === 'brown' ? 'selected' : ''; ?>>Brown</option>
                            <option value="blue" <?php echo ($_POST['eye_color'] ?? '') === 'blue' ? 'selected' : ''; ?>>Blue</option>
                            <option value="green" <?php echo ($_POST['eye_color'] ?? '') === 'green' ? 'selected' : ''; ?>>Green</option>
                            <option value="hazel" <?php echo ($_POST['eye_color'] ?? '') === 'hazel' ? 'selected' : ''; ?>>Hazel</option>
                            <option value="gray" <?php echo ($_POST['eye_color'] ?? '') === 'gray' ? 'selected' : ''; ?>>Gray</option>
                            <option value="other" <?php echo ($_POST['eye_color'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="hair_color">Hair Color</label>
                        <select id="hair_color" name="hair_color">
                            <option value="">Select Hair Color</option>
                            <option value="black" <?php echo ($_POST['hair_color'] ?? '') === 'black' ? 'selected' : ''; ?>>Black</option>
                            <option value="brown" <?php echo ($_POST['hair_color'] ?? '') === 'brown' ? 'selected' : ''; ?>>Brown</option>
                            <option value="blonde" <?php echo ($_POST['hair_color'] ?? '') === 'blonde' ? 'selected' : ''; ?>>Blonde</option>
                            <option value="red" <?php echo ($_POST['hair_color'] ?? '') === 'red' ? 'selected' : ''; ?>>Red</option>
                            <option value="gray" <?php echo ($_POST['hair_color'] ?? '') === 'gray' ? 'selected' : ''; ?>>Gray</option>
                            <option value="white" <?php echo ($_POST['hair_color'] ?? '') === 'white' ? 'selected' : ''; ?>>White</option>
                            <option value="other" <?php echo ($_POST['hair_color'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="distinguishing_marks">Distinguishing Marks</label>
                    <textarea id="distinguishing_marks" name="distinguishing_marks" placeholder="Describe any scars, tattoos, birthmarks, or other distinguishing features..."><?php echo htmlspecialchars($_POST['distinguishing_marks'] ?? ''); ?></textarea>
                </div>

                <!-- Status and Risk Assessment -->
                <h3 style="margin: 2rem 0 1rem 0; color: #333; border-bottom: 2px solid #ff4444; padding-bottom: 0.5rem;">
                    <i class="fas fa-exclamation-triangle"></i> Status & Risk Assessment
                </h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="status" class="required">Current Status</label>
                        <select id="status" name="status" required>
                            <option value="">Select Status</option>
                            <option value="wanted" <?php echo ($_POST['status'] ?? '') === 'wanted' ? 'selected' : ''; ?>>Wanted</option>
                            <option value="arrested" <?php echo ($_POST['status'] ?? '') === 'arrested' ? 'selected' : ''; ?>>Arrested</option>
                            <option value="convicted" <?php echo ($_POST['status'] ?? '') === 'convicted' ? 'selected' : ''; ?>>Convicted</option>
                            <option value="released" <?php echo ($_POST['status'] ?? '') === 'released' ? 'selected' : ''; ?>>Released</option>
                            <option value="deceased" <?php echo ($_POST['status'] ?? '') === 'deceased' ? 'selected' : ''; ?>>Deceased</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="risk_level" class="required">Risk Level</label>
                        <select id="risk_level" name="risk_level" required>
                            <option value="">Select Risk Level</option>
                            <option value="low" <?php echo ($_POST['risk_level'] ?? '') === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo ($_POST['risk_level'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo ($_POST['risk_level'] ?? '') === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="extreme" <?php echo ($_POST['risk_level'] ?? '') === 'extreme' ? 'selected' : ''; ?>>Extreme</option>
                        </select>
                    </div>
                </div>

                <!-- Photo Upload -->
                <h3 style="margin: 2rem 0 1rem 0; color: #333; border-bottom: 2px solid #ff4444; padding-bottom: 0.5rem;">
                    <i class="fas fa-camera"></i> Photo
                </h3>
                <div class="form-group full-width">
                    <label for="photo">Upload Photo (JPG, PNG, GIF, WEBP, max 2MB)</label>
                    <input type="file" id="photo" name="photo" accept="image/*">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Criminal Record
                    </button>
                    <a href="criminal_management.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Form validation
        document.getElementById('addCriminalForm').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#ff4444';
                    isValid = false;
                } else {
                    field.style.borderColor = '#e1e5e9';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields marked with *');
                return;
            }

            // Add loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;
            
            // Reset button after form submission
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });

        // Real-time validation
        document.querySelectorAll('input[required], select[required]').forEach(field => {
            field.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    this.style.borderColor = '#ff4444';
                } else {
                    this.style.borderColor = '#e1e5e9';
                }
            });
        });
    </script>
</body>
</html> 