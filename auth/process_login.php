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
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        // Validate input
        if (empty($username) || empty($password) || empty($role)) {
            $_SESSION['login_error'] = "All fields are required.";
            header("Location: login.php");
            exit();
        }
        
        // Check if user exists
        $sql = "SELECT id, username, password, fullname, role, status FROM users WHERE username = ? AND role = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$username, $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Check if user is active
            if ($user['status'] === 'active') {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                // Redirect based on role
                switch ($user['role']) {
                    case 'admin':
                        header("Location: dashboard.php");
                        break;
                    case 'officer':
                        header("Location: officer_dashboard.php");
                        break;
                    case 'investigator':
                        header("Location: investigator_dashboard.php");
                        break;
                    default:
                        header("Location: dashboard.php");
                }
                exit();
            } else {
                $_SESSION['login_error'] = "Your account is inactive. Please contact administrator.";
                header("Location: login.php");
                exit();
            }
        } else {
            $_SESSION['login_error'] = "Invalid username, password, or role.";
            header("Location: login.php");
            exit();
        }
    } else {
        // If not POST request, redirect to login
        header("Location: login.php");
        exit();
    }
    
} catch(PDOException $e) {
    $_SESSION['login_error'] = "Database connection error. Please try again later.";
    header("Location: login.php");
    exit();
}

$conn = null;
?> 