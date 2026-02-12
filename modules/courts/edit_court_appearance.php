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

// Get appearance ID
$appearance_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($appearance_id <= 0) {
    header("Location: court_management.php");
    exit();
}

// Fetch appearance details
$sql = "SELECT ca.*, c.case_number, c.title as case_title, cr.fullname as criminal_name
        FROM court_appearances ca
        LEFT JOIN cases c ON ca.case_id = c.id
        LEFT JOIN criminals cr ON ca.criminal_id = cr.id
        WHERE ca.id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$appearance_id]);
$appearance = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appearance) {
    header("Location: court_management.php");
    exit();
}

// Get cases and criminals for dropdowns
$cases_stmt = $conn->prepare("SELECT id, case_number, title FROM cases ORDER BY case_number");
$cases_stmt->execute();
$cases = $cases_stmt->fetchAll(PDO::FETCH_ASSOC);

$criminals_stmt = $conn->prepare("SELECT id, fullname FROM criminals ORDER BY fullname");
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

    $update_sql = "UPDATE court_appearances SET
        case_id = :case_id,
        criminal_id = :criminal_id,
        court_name = :court_name,
        court_date = :court_date,
        appearance_type = :appearance_type,
        outcome = :outcome,
        next_hearing_date = :next_hearing_date,
        notes = :notes
        WHERE id = :id";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->execute([
        'case_id' => $case_id,
        'criminal_id' => $criminal_id,
        'court_name' => $court_name,
        'court_date' => $court_date,
        'appearance_type' => $appearance_type,
        'outcome' => $outcome,
        'next_hearing_date' => $next_hearing_date ?: null,
        'notes' => $notes,
        'id' => $appearance_id
    ]);
    header("Location: court_management.php");
    exit();
}

$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Court Appearance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .background-container {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%; z-index: -1; overflow: hidden;
        }
        .moving-bg {
            position: absolute; width: 200%; height: 200%;
            background: linear-gradient(45deg, rgba(25,25,112,0.8) 0%, rgba(47,84,235,0.8) 25%, rgba(138,43,226,0.8) 50%, rgba(75,0,130,0.8) 75%, rgba(25,25,112,0.8) 100%);
            animation: moveBackground 20s linear infinite;
        }
        .bg-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4);
        }
        @keyframes moveBackground {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        .container {
            max-width: 600px;
            margin: 100px auto 0 auto;
            background: rgba(255,255,255,0.98);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            padding: 2rem;
        }
        h2 {
            color: #ff4444;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }
        label {
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        input, select, textarea {
            padding: 10px 14px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #ff4444;
        }
        .btn {
            padding: 10px 24px;
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
        }
        .back-link:hover {
            color: #ff4444;
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
        <h2><i class="fas fa-edit"></i> Edit Court Appearance</h2>
        <form method="POST">
            <label for="case_id">Case</label>
            <select name="case_id" id="case_id" required>
                <?php foreach ($cases as $case): ?>
                    <option value="<?php echo $case['id']; ?>" <?php if($appearance['case_id']==$case['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($case['case_number']); ?> - <?php echo htmlspecialchars($case['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="criminal_id">Criminal</label>
            <select name="criminal_id" id="criminal_id" required>
                <?php foreach ($criminals as $criminal): ?>
                    <option value="<?php echo $criminal['id']; ?>" <?php if($appearance['criminal_id']==$criminal['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($criminal['fullname']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="court_name">Court Name</label>
            <input type="text" name="court_name" id="court_name" value="<?php echo htmlspecialchars($appearance['court_name']); ?>" required>

            <label for="court_date">Court Date & Time</label>
            <input type="datetime-local" name="court_date" id="court_date" value="<?php echo date('Y-m-d\TH:i', strtotime($appearance['court_date'])); ?>" required>

            <label for="appearance_type">Appearance Type</label>
            <select name="appearance_type" id="appearance_type" required>
                <?php
                $types = ['arraignment','preliminary_hearing','trial','sentencing','appeal','other'];
                foreach ($types as $type):
                ?>
                    <option value="<?php echo $type; ?>" <?php if($appearance['appearance_type']==$type) echo 'selected'; ?>>
                        <?php echo ucfirst(str_replace('_',' ', $type)); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="outcome">Outcome</label>
            <input type="text" name="outcome" id="outcome" value="<?php echo htmlspecialchars($appearance['outcome']); ?>">

            <label for="next_hearing_date">Next Hearing Date & Time</label>
            <input type="datetime-local" name="next_hearing_date" id="next_hearing_date" value="<?php echo $appearance['next_hearing_date'] ? date('Y-m-d\TH:i', strtotime($appearance['next_hearing_date'])) : ''; ?>">

            <label for="notes">Notes</label>
            <textarea name="notes" id="notes" rows="3"><?php echo htmlspecialchars($appearance['notes']); ?></textarea>

            <button type="submit" class="btn"><i class="fas fa-save"></i> Save Changes</button>
        </form>
    </div>
</body>