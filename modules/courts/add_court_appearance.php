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

$error_message = '';
$success_message = '';

// Get available cases
$cases_sql = "SELECT c.id, c.case_number, c.title FROM cases c ORDER BY c.case_number";
$cases_stmt = $conn->prepare($cases_sql);
$cases_stmt->execute();
$cases = $cases_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available criminals
$criminals_sql = "SELECT id, fullname FROM criminals ORDER BY fullname";
$criminals_stmt = $conn->prepare($criminals_sql);
$criminals_stmt->execute();
$criminals = $criminals_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $case_id = $_POST['case_id'];
    $criminal_id = $_POST['criminal_id'];
    $court_name = $_POST['court_name'];
    $court_date = $_POST['court_date'];
    $appearance_type = $_POST['appearance_type'];
    $outcome = $_POST['outcome'];
    $next_hearing_date = $_POST['next_hearing_date'];
    $notes = $_POST['notes'];

    if (!$case_id || !$criminal_id || !$court_name || !$court_date || !$appearance_type) {
        $error_message = "Please fill in all required fields.";
    } else {
        $insert_sql = "INSERT INTO court_appearances 
            (case_id, criminal_id, court_name, court_date, appearance_type, outcome, next_hearing_date, notes) 
            VALUES (:case_id, :criminal_id, :court_name, :court_date, :appearance_type, :outcome, :next_hearing_date, :notes)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->execute([
            'case_id' => $case_id,
            'criminal_id' => $criminal_id,
            'court_name' => $court_name,
            'court_date' => $court_date,
            'appearance_type' => $appearance_type,
            'outcome' => $outcome,
            'next_hearing_date' => $next_hearing_date ?: null,
            'notes' => $notes
        ]);
        $success_message = "Court appearance added successfully!";
    }
}

$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Court Appearance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f4f6f9;
        }
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
        .container {
            max-width: 600px;
            margin: 100px auto 0 auto;
            background: rgba(255,255,255,0.98);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 2.5rem;
        }
        h2 {
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
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%);
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255,68,68,0.4);
        }
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .back-link:hover {
            color: #ff4444;
        }
        .message {
            padding: 10px 16px;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 600;
            font-size: 1rem;
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
        @media (max-width: 700px) {
            .container {
                padding: 1rem;
                margin: 40px auto 0 auto;
                border-radius: 12px;
            }
            h2 {
                font-size: 1.3rem;
            }
            form {
                gap: 1rem;
            }
            input, select, textarea {
                padding: 10px 14px;
                font-size: 0.9rem;
            }
            .btn {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="background-container">
        <div class="moving-bg"></div>
        <div class="bg-overlay"></div>
    </div>
    <div class="container">
        <a href="court_management.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Court Management</a>
        <h2><i class="fas fa-plus"></i> Add Court Appearance</h2>
        <?php if ($success_message): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form method="POST">
            <label for="case_id">Case</label>
            <select name="case_id" id="case_id" required>
                <option value="">Select Case</option>
                <?php foreach ($cases as $case): ?>
                    <option value="<?php echo $case['id']; ?>">
                        <?php echo htmlspecialchars($case['case_number']); ?> - <?php echo htmlspecialchars($case['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="criminal_id">Criminal</label>
            <select name="criminal_id" id="criminal_id" required>
                <option value="">Select Criminal</option>
                <?php foreach ($criminals as $criminal): ?>
                    <option value="<?php echo $criminal['id']; ?>">
                        <?php echo htmlspecialchars($criminal['fullname']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="court_name">Court Name</label>
            <input type="text" name="court_name" id="court_name" required>

            <label for="court_date">Court Date & Time</label>
            <input type="datetime-local" name="court_date" id="court_date" required>

            <label for="appearance_type">Appearance Type</label>
            <select name="appearance_type" id="appearance_type" required>
                <option value="">Select Type</option>
                <option value="arraignment">Arraignment</option>
                <option value="preliminary_hearing">Preliminary Hearing</option>
                <option value="trial">Trial</option>
                <option value="sentencing">Sentencing</option>
                <option value="appeal">Appeal</option>
                <option value="other">Other</option>
            </select>

            <label for="outcome">Outcome</label>
            <input type="text" name="outcome" id="outcome">

            <label for="next_hearing_date">Next Hearing Date & Time</label>
            <input type="datetime-local" name="next_hearing_date" id="next_hearing_date">

            <label for="notes">Notes</label>
            <textarea name="notes" id="notes" rows="3"></textarea>

            <button type="submit" class="btn"><i class="fas fa-save"></i> Add Appearance</button>
        </form>
    </div>
</body>
</html>