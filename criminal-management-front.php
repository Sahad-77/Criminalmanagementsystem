<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criminal Management System - Secure Law Enforcement Platform</title>
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
            overflow-x: hidden;
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
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

        /* Hero Section */
        .hero {
            padding: 120px 0 80px;
            text-align: center;
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-content {
            max-width: 800px;
        }

        .hero h1 {
            font-size: 4rem;
            margin-bottom: 1rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            animation: fadeInUp 1s ease-out;
        }

        .hero p {
            font-size: 1.4rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            animation: fadeInUp 1s ease-out 0.3s both;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease-out 0.6s both;
        }

        /* Forms Section */
        .forms-section {
            padding: 80px 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #333;
        }

        .officer-list {
            margin-top: 2rem;
        }

        .officer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .officer-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .officer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .officer-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .officer-avatar i {
            font-size: 2rem;
            color: white;
        }

        .officer-info {
            flex: 1;
        }

        .officer-info h3 {
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .officer-role {
            color: #666;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .officer-badge {
            color: #888;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }

        .officer-dept {
            color: #888;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }

        .officer-email {
            color: #888;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .officer-status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .no-officers {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .no-officers i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        .no-officers h3 {
            color: #666;
            margin-bottom: 1rem;
        }

        .no-officers p {
            color: #888;
        }

        .error-message {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .error-message i {
            font-size: 4rem;
            color: #ff4444;
            margin-bottom: 1rem;
        }

        .error-message h3 {
            color: #333;
            margin-bottom: 1rem;
        }

        .error-message p {
            color: #666;
        }

        .forms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 3rem;
        }

        .form-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .form-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
        }

        .form-card h3 {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: #333;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
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
            width: 100%;
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

        /* Overview Section */
        .overview {
            padding: 80px 0;
            background: rgba(0, 0, 0, 0.8);
            color: white;
        }

        .overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .overview-card {
            text-align: center;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }

        .overview-card:hover {
            transform: translateY(-5px);
        }

        .overview-card i {
            font-size: 3rem;
            color: #ff4444;
            margin-bottom: 1rem;
        }

        .overview-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        /* Statistics Section */
        .statistics {
            padding: 80px 0;
            background: rgba(255, 255, 255, 0.95);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .stat-card {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: #ff4444;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            color: #666;
            font-weight: 600;
        }

        /* About Us Section */
        .about-us {
            padding: 80px 0;
            background: rgba(0, 0, 0, 0.8);
            color: white;
        }

        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .about-text h3 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: #ff4444;
        }

        .about-text p {
            margin-bottom: 1rem;
            line-height: 1.8;
        }

        .about-features {
            list-style: none;
        }

        .about-features li {
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
            position: relative;
        }

        .about-features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #ff4444;
            font-weight: bold;
        }

        /* Contact Section */
        .contact {
            padding: 80px 0;
            background: rgba(255, 255, 255, 0.95);
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }

        .contact-info {
            background: #333;
            color: white;
            padding: 2rem;
            border-radius: 15px;
        }

        .contact-info h3 {
            margin-bottom: 1.5rem;
            color: #ff4444;
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            gap: 1rem;
        }

        .contact-item i {
            color: #ff4444;
            font-size: 1.2rem;
        }

        /* Footer */
        .footer {
            background: #000;
            color: white;
            text-align: center;
            padding: 2rem 0;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .nav-links {
                display: none;
            }

            .forms-grid {
                grid-template-columns: 1fr;
            }

            .officer-grid {
                grid-template-columns: 1fr;
            }

            .officer-card {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .officer-avatar {
                width: 60px;
                height: 60px;
            }

            .officer-avatar i {
                font-size: 1.5rem;
            }

            .about-content,
            .contact-grid {
                grid-template-columns: 1fr;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
        }

        /* Scroll Indicator */
        .scroll-indicator {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            z-index: 1001;
        }

        .scroll-progress {
            height: 100%;
            background: linear-gradient(90deg, #ff4444, #cc0000);
            width: 0%;
            transition: width 0.3s ease;
        }

        /* Officer List Styles */
        .officer-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .officer-card {
            background: #f9f9f9;
            border-radius: 15px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .officer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .officer-avatar {
            width: 60px;
            height: 60px;
            background: #ff4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }

        .officer-info {
            flex-grow: 1;
        }

        .officer-info h3 {
            font-size: 1.3rem;
            margin-bottom: 0.3rem;
            color: #333;
        }

        .officer-role {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.3rem;
        }

        .officer-role i {
            margin-right: 5px;
        }

        .officer-badge {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 0.3rem;
        }

        .officer-dept {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 0.3rem;
        }

        .officer-email {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 0.3rem;
        }

        .officer-email i {
            margin-right: 5px;
        }

        .officer-status {
            font-size: 0.8rem;
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-block;
        }

        .status-active {
            background-color: #e0f2f7;
            color: #007bff;
        }

        .status-inactive {
            background-color: #fde2e2;
            color: #dc3545;
        }

        .no-officers {
            text-align: center;
            padding: 4rem 2rem;
            background: #f9f9f9;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .no-officers i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        .no-officers h3 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: #555;
        }

        .no-officers p {
            font-size: 1rem;
            color: #888;
        }

        .error-message {
            text-align: center;
            padding: 4rem 2rem;
            background: #fde2e2;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .error-message i {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }

        .error-message h3 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: #dc3545;
        }

        .error-message p {
            font-size: 1rem;
            color: #888;
        }
    </style>
</head>
<body>
    <!-- Moving Background -->
    <div class="background-container">
        <div class="moving-bg"></div>
        <div class="bg-overlay"></div>
    </div>

    <!-- Scroll Progress Indicator -->
    <div class="scroll-indicator">
        <div class="scroll-progress" id="scrollProgress"></div>
    </div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-content">
                <a href="#" class="logo">
                    <i class="fas fa-shield-alt"></i>
                    Criminal Management System
                </a>
                <ul class="nav-links">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#overview">Overview</a></li>
                    <li><a href="#statistics">Statistics</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#contact">Contact</a></li>
                    
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Criminal Management System</h1>
                <p>Advanced law enforcement platform for secure criminal record management, investigation tracking, and police officer coordination.</p>
                <div class="hero-buttons">
                    <a href="login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="register.php" class="btn btn-secondary">
                            <i class="fas fa-user-plus"></i> Register
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Forms Section -->
    <section id="forms" class="forms-section">
        <div class="container">
            <h2 class="section-title">Officer Directory</h2>
            <div class="officer-list">
                <?php
                // Database connection
                $servername = "localhost";
                $username = "root";
                $password = "";
                $dbname = "criminal_management";

                try {
                    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Query to get officers from database
                    $sql = "SELECT id, fullname, email, username, role, badge_number, department, status, created_at FROM users WHERE role IN ('officer', 'investigator') ORDER BY fullname ASC";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $officers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($officers) > 0) {
                        echo '<div class="officer-grid">';
                        foreach ($officers as $officer) {
                            $statusClass = $officer['status'] === 'active' ? 'status-active' : 'status-inactive';
                            $roleIcon = $officer['role'] === 'investigator' ? 'fas fa-search' : 'fas fa-shield-alt';
                            $roleLabel = ucfirst($officer['role']);
                            
                            echo '<div class="officer-card">';
                            echo '<div class="officer-avatar">';
                            echo '<i class="' . $roleIcon . '"></i>';
                            echo '</div>';
                            echo '<div class="officer-info">';
                            echo '<h3>' . htmlspecialchars($officer['fullname']) . '</h3>';
                            echo '<p class="officer-role"><i class="' . $roleIcon . '"></i> ' . $roleLabel . '</p>';
                            echo '<p class="officer-badge">Badge: ' . htmlspecialchars($officer['badge_number']) . '</p>';
                            echo '<p class="officer-dept">Department: ' . htmlspecialchars($officer['department']) . '</p>';
                            echo '<p class="officer-email"><i class="fas fa-envelope"></i> ' . htmlspecialchars($officer['email']) . '</p>';
                            echo '<span class="officer-status ' . $statusClass . '">' . ucfirst($officer['status']) . '</span>';
                            echo '</div>';
                            echo '</div>';
                        }
                        echo '</div>';
                    } else {
                        echo '<div class="no-officers">';
                        echo '<i class="fas fa-users-slash"></i>';
                        echo '<h3>No Officers Found</h3>';
                        echo '<p>No officers are currently registered in the system.</p>';
                        echo '</div>';
                    }
                    
                } catch(PDOException $e) {
                    echo '<div class="error-message">';
                    echo '<i class="fas fa-exclamation-triangle"></i>';
                    echo '<h3>Database Connection Error</h3>';
                    echo '<p>Unable to connect to the database. Please check your connection settings.</p>';
                    echo '</div>';
                }
                
                $conn = null;
                ?>
            </div>
        </div>
    </section>

    <!-- Overview Section -->
    <section id="overview" class="overview">
        <div class="container">
            <h2 class="section-title">System Overview</h2>
            <div class="overview-grid">
                <div class="overview-card">
                    <i class="fas fa-database"></i>
                    <h3>Criminal Records</h3>
                    <p>Comprehensive database management for criminal records, case files, and investigation details.</p>
                </div>
                <div class="overview-card">
                    <i class="fas fa-search"></i>
                    <h3>Investigation Tools</h3>
                    <p>Advanced search and investigation tools for efficient case management and evidence tracking.</p>
                </div>
                <div class="overview-card">
                    <i class="fas fa-users"></i>
                    <h3>Officer Management</h3>
                    <p>Complete police officer database with assignment tracking and performance monitoring.</p>
                </div>
                <div class="overview-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Analytics & Reports</h3>
                    <p>Real-time analytics and comprehensive reporting for data-driven law enforcement decisions.</p>
                </div>
                <div class="overview-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Security & Access</h3>
                    <p>Multi-level security with role-based access control and audit trails for compliance.</p>
                </div>
                <div class="overview-card">
                    <i class="fas fa-mobile-alt"></i>
                    <h3>Mobile Access</h3>
                    <p>Mobile-responsive design for field officers to access critical information on the go.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section id="statistics" class="statistics">
        <div class="container">
            <h2 class="section-title">System Statistics</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" data-target="15000">0</div>
                    <div class="stat-label">Criminal Records</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" data-target="2500">0</div>
                    <div class="stat-label">Active Cases</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" data-target="500">0</div>
                    <div class="stat-label">Police Officers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" data-target="95">0</div>
                    <div class="stat-label">Success Rate (%)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" data-target="1000">0</div>
                    <div class="stat-label">Investigations</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" data-target="24">0</div>
                    <div class="stat-label">Hours Support</div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section id="about" class="about-us">
        <div class="container">
            <h2 class="section-title">About Us</h2>
            <div class="about-content">
                <div class="about-text">
                    <h3>Leading Law Enforcement Technology</h3>
                    <p>The Criminal Management System is a state-of-the-art platform designed to revolutionize law enforcement operations. Our system provides comprehensive tools for managing criminal records, investigations, and police operations.</p>
                    <p>With years of experience in law enforcement technology, we understand the critical needs of police departments and investigative units. Our platform is built with security, efficiency, and user experience in mind.</p>
                    <ul class="about-features">
                        <li>Advanced security protocols</li>
                        <li>Real-time data synchronization</li>
                        <li>Comprehensive audit trails</li>
                        <li>24/7 technical support</li>
                        <li>Regular system updates</li>
                        <li>Compliance with law enforcement standards</li>
                    </ul>
                </div>
                <div class="about-text">
                    <h3>Our Mission</h3>
                    <p>To provide law enforcement agencies with cutting-edge technology that enhances their ability to protect and serve communities effectively.</p>
                    <h3>Our Vision</h3>
                    <p>To be the leading provider of law enforcement management solutions, contributing to safer communities through innovative technology.</p>
                    <h3>Core Values</h3>
                    <ul class="about-features">
                        <li>Integrity and transparency</li>
                        <li>Innovation and excellence</li>
                        <li>Security and reliability</li>
                        <li>Customer satisfaction</li>
                        <li>Continuous improvement</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <h2 class="section-title">Contact Us</h2>
            <div class="contact-grid">
                <div class="contact-info">
                    <h3>Get in Touch</h3>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>123 Law Enforcement Ave, Police District, City, State 12345</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>+1 (555) 123-4567</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>support@criminalmanagement.com</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <span>24/7 Emergency Support Available</span>
                    </div>
                </div>
                <div class="form-card">
                    <h3>Send us a Message</h3>
                    <form action="contact.php" method="post">
                        <div class="form-group">
                            <label for="contact-name">Name</label>
                            <input type="text" id="contact-name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="contact-email">Email</label>
                            <input type="email" id="contact-email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="contact-subject">Subject</label>
                            <input type="text" id="contact-subject" name="subject" required>
                        </div>
                        <div class="form-group">
                            <label for="contact-message">Message</label>
                            <textarea id="contact-message" name="message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Criminal Management System. All rights reserved. | Secure • Reliable • Efficient</p>
        </div>
    </footer>

    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Scroll progress indicator
        window.addEventListener('scroll', () => {
            const scrollTop = window.pageYOffset;
            const docHeight = document.body.offsetHeight - window.innerHeight;
            const scrollPercent = (scrollTop / docHeight) * 100;
            document.getElementById('scrollProgress').style.width = scrollPercent + '%';
        });

        // Animate statistics numbers
        function animateNumbers() {
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const target = parseInt(stat.getAttribute('data-target'));
                const duration = 2000; // 2 seconds
                const increment = target / (duration / 16); // 60fps
                let current = 0;

                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    stat.textContent = Math.floor(current).toLocaleString();
                }, 16);
            });
        }

        // Trigger animation when statistics section is visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateNumbers();
                    observer.unobserve(entry.target);
                }
            });
        });

        const statisticsSection = document.getElementById('statistics');
        if (statisticsSection) {
            observer.observe(statisticsSection);
        }

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const password = this.querySelector('input[type="password"]');
                const confirmPassword = this.querySelector('#reg-confirm-password');
                
                if (password && confirmPassword && password.value !== confirmPassword.value) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return;
                }

                // Add loading state
                const button = this.querySelector('button[type="submit"]');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                button.disabled = true;
                
                // Reset button after form submission
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 2000);
            });
        });

        // Add animation on scroll
        const fadeObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        // Observe all elements for fade-in animation
        document.querySelectorAll('.form-card, .overview-card, .stat-card, .about-text, .contact-info').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            fadeObserver.observe(el);
        });
    </script>
</body>
</html> 