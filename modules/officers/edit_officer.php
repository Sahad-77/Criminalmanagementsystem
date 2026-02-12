<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
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
$officer = null;
$success = $error = '';
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role IN ('officer', 'investigator')");
    $stmt->execute([$_GET['id']]);
    $officer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$officer) {
        $error = "Officer not found.";
    }
} else {
    $error = "Invalid officer ID.";
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $officer) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $badge_number = trim($_POST['badge_number']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $profile_image = $officer['profile_image'] ?? null;
    // Handle image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 2 * 1024 * 1024; // 2MB
        $file_type = mime_content_type($_FILES['profile_image']['tmp_name']);
        $file_size = $_FILES['profile_image']['size'];
        if (!in_array($file_type, $allowed_types)) {
            $error = "Only JPG and PNG images are allowed.";
        } elseif ($file_size > $max_size) {
            $error = "Image size must be less than 2MB.";
        } else {
            $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $newName = uniqid() . '_officer.' . $ext;
            $uploadDir = 'uploads/criminal_photos/';
            $uploadPath = $uploadDir . $newName;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                $profile_image = $newName;
            } else {
                $error = "Failed to upload image.";
            }
        }
    }
    if (!$error) {
        $update = $conn->prepare("UPDATE users SET fullname=?, email=?, phone=?, department=?, badge_number=?, role=?, status=?, profile_image=? WHERE id=?");
        $update->execute([$fullname, $email, $phone, $department, $badge_number, $role, $status, $profile_image, $officer['id']]);
        $success = "Officer updated successfully.";
        // Refresh officer data
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$officer['id']]);
        $officer = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Officer - Criminal Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; margin: 0; }
        .background-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; overflow: hidden; }
        .moving-bg { position: absolute; width: 200%; height: 200%; background: linear-gradient(45deg, rgba(25, 25, 112, 0.8) 0%, rgba(47, 84, 235, 0.8) 25%, rgba(138, 43, 226, 0.8) 50%, rgba(75, 0, 130, 0.8) 75%, rgba(25, 25, 112, 0.8) 100%); animation: moveBackground 20s linear infinite; }
        .bg-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); }
        @keyframes moveBackground { 0% { transform: translate(-50%, -50%) rotate(0deg); } 100% { transform: translate(-50%, -50%) rotate(360deg); } }
        .navbar { background: rgba(0, 0, 0, 0.9); backdrop-filter: blur(10px); padding: 0.7rem 0; position: fixed; width: 100%; top: 0; z-index: 1000; box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3); }
        .nav-content { display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .logo { font-size: 1.5rem; font-weight: bold; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .nav-links { display: flex; list-style: none; gap: 1.2rem; }
        .nav-links a { text-decoration: none; color: #fff; font-weight: 500; transition: color 0.3s ease; padding: 6px 12px; border-radius: 20px; }
        .nav-links a:hover { color: #ff4444; background: rgba(255, 255, 255, 0.1); }
        .container { max-width: 600px; margin: 120px auto 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 2px 16px rgba(0,0,0,0.08); padding: 2.5rem 2rem; }
        .form-title { text-align: center; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 12px 16px; border: 2px solid #e1e5e9; border-radius: 10px; font-size: 1rem; transition: border-color 0.3s ease; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #ff4444; }
        .profile-img-preview { display: block; margin: 0 auto 1rem auto; border-radius: 50%; width: 120px; height: 120px; object-fit: cover; background: #eee; }
        .btn { padding: 12px 24px; border: none; border-radius: 50px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(255, 68, 68, 0.4); }
        .back-btn { display: inline-block; margin-top: 1.5rem; background: linear-gradient(135deg, #333 0%, #555 100%); color: white; padding: 10px 24px; border-radius: 50px; text-decoration: none; font-size: 1rem; transition: all 0.3s ease; }
        .back-btn:hover { background: #222; }
        .alert-success { background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center; }
    </style>
</head>
<body>
    <div class="background-container">
        <div class="moving-bg"></div>
        <div class="bg-overlay"></div>
    </div>
    <nav class="navbar">
        <div class="nav-content">
            <a href="dashboard.php" class="logo">
                <i class="fas fa-shield-alt"></i>
                Criminal Management System
            </a>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="officer_management.php">Officer Management</a></li>
                <li><a href="criminal_management.php">Criminals</a></li>
                <li><a href="case_management.php">Cases</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
    <div class="container">
        <div class="form-title">
            <h2>Edit Officer</h2>
        </div>
        <?php if ($success): ?><div class="alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($officer): ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group" style="text-align:center;">
                <img src="<?php echo $officer['profile_image'] ? 'uploads/criminal_photos/' . htmlspecialchars($officer['profile_image']) : 'https://via.placeholder.com/120?text=No+Image'; ?>" class="profile-img-preview" id="imgPreview">
                <input type="file" name="profile_image" accept="image/jpeg,image/png" onchange="previewImage(event)">
                <div style="font-size:0.95rem;color:#888;margin-top:0.5rem;">Allowed: JPG, PNG | Max size: 2MB</div>
            </div>
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($officer['fullname']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($officer['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($officer['phone']); ?>">
            </div>
            <div class="form-group">
                <label for="department">Department</label>
                <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($officer['department']); ?>">
            </div>
            <div class="form-group">
                <label for="badge_number">Badge Number</label>
                <input type="text" id="badge_number" name="badge_number" value="<?php echo htmlspecialchars($officer['badge_number']); ?>">
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="officer" <?php if($officer['role']==='officer') echo 'selected'; ?>>Police Officer</option>
                    <option value="investigator" <?php if($officer['role']==='investigator') echo 'selected'; ?>>Investigator</option>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" <?php if($officer['status']==='active') echo 'selected'; ?>>Active</option>
                    <option value="inactive" <?php if($officer['status']==='inactive') echo 'selected'; ?>>Inactive</option>
                    <option value="suspended" <?php if($officer['status']==='suspended') echo 'selected'; ?>>Suspended</option>
                </select>
            </div>
            <div style="text-align:center;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                <a href="view_officer.php?id=<?php echo $officer['id']; ?>" class="back-btn"><i class="fas fa-arrow-left"></i> Cancel</a>
            </div>
        </form>
        <script>
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function(){
                document.getElementById('imgPreview').src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
        </script>
        <?php endif; ?>
    </div>
</body>
</html>
