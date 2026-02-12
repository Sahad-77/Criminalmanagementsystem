<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "criminal_management";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $badge_number = trim($_POST['badge_number']);
        $department = $_POST['department'];
        $role = $_POST['role'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate input
        if (empty($fullname) || empty($email) || empty($username) || empty($badge_number) || 
            empty($department) || empty($role) || empty($password) || empty($confirm_password)) {
            $_SESSION['register_error'] = "All fields are required.";
            header("Location: register.php");
            exit();
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['register_error'] = "Please enter a valid email address.";
            header("Location: register.php");
            exit();
        }
        
        // Check password length
        if (strlen($password) < 8) {
            $_SESSION['register_error'] = "Password must be at least 8 characters long.";
            header("Location: register.php");
            exit();
        }
        
        // Check if passwords match
        if ($password !== $confirm_password) {
            $_SESSION['register_error'] = "Passwords do not match.";
            header("Location: register.php");
            exit();
        }
        
        // Check if username already exists
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $_SESSION['register_error'] = "Username already exists. Please choose a different username.";
            header("Location: register.php");
            exit();
        }
        
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['register_error'] = "Email already exists. Please use a different email address.";
            header("Location: register.php");
            exit();
        }
        
        // Check if badge number already exists
        $sql = "SELECT id FROM users WHERE badge_number = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$badge_number]);
        if ($stmt->fetch()) {
            $_SESSION['register_error'] = "Badge number already exists. Please use a different badge number.";
            header("Location: register.php");
            exit();
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $sql = "INSERT INTO users (fullname, email, username, badge_number, department, role, password, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$fullname, $email, $username, $badge_number, $department, $role, $hashed_password]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['register_success'] = "Registration successful! You can now login with your credentials.";
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['register_error'] = "Registration failed. Please try again.";
            header("Location: register.php");
            exit();
        }
        
    } else {
        // If not POST request, redirect to register
        header("Location: register.php");
        exit();
    }
    
} catch(PDOException $e) {
    $_SESSION['register_error'] = "Database connection error. Please try again later.";
    header("Location: register.php");
    exit();
}

$conn = null;
?> 