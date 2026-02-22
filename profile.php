<?php
// profile.php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$current_password_error = '';

// Get user details
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Get user orders
$orders_query = "
    SELECT o.*, COUNT(oi.id) as item_count 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC
    LIMIT 5
";
$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();

// Get wishlist items
$wishlist_query = "
    SELECT p.*, c.name as category_name, c.slug as category_slug,
           mt.name as material_name, vt.name as variant_name
    FROM wishlist w 
    JOIN products p ON w.product_id = p.id 
    JOIN categories c ON p.category_id = c.id 
    LEFT JOIN material_types mt ON p.material_type_id = mt.id
    LEFT JOIN variant_types vt ON p.variant_type_id = vt.id
    WHERE w.user_id = ?
    LIMIT 6
";
$wishlist_stmt = $conn->prepare($wishlist_query);
$wishlist_stmt->bind_param("i", $user_id);
$wishlist_stmt->execute();
$wishlist_result = $wishlist_stmt->get_result();

// Get order stats
$stats_query = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN status = 'Processing' THEN 1 ELSE 0 END) as processing_orders,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(total_amount) as total_spent
    FROM orders 
    WHERE user_id = ?
";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update name and email
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        
        if (empty($name) || empty($email)) {
            $error = "Name and email are required";
        } else {
            $check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
            $check_stmt = $conn->prepare($check_email);
            $check_stmt->bind_param("si", $email, $user_id);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = "Email already exists";
            } else {
                $update_query = "UPDATE users SET name = ?, email = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ssi", $name, $email, $user_id);
                
                if ($update_stmt->execute()) {
                    $success = "Profile updated successfully!";
                    // Refresh user data
                    $stmt->execute();
                    $user_result = $stmt->get_result();
                    $user = $user_result->fetch_assoc();
                } else {
                    $error = "Failed to update profile";
                }
            }
        }
    }
    
    if (isset($_POST['change_password'])) {
        // Change password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $current_password_error = "Current password is incorrect";
        } elseif (strlen($new_password) < 6) {
            $current_password_error = "New password must be at least 6 characters";
        } elseif ($new_password !== $confirm_password) {
            $current_password_error = "New passwords do not match";
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_pw_query = "UPDATE users SET password = ? WHERE id = ?";
            $update_pw_stmt = $conn->prepare($update_pw_query);
            $update_pw_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_pw_stmt->execute()) {
                $success = "Password changed successfully!";
            } else {
                $error = "Failed to change password";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - ProGlide</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF6B6B;
            --primary-dark: #FF4757;
            --secondary: #1E90FF;
            --accent: #FFA502;
            --dark: #2F3542;
            --light: #F8F9FA;
            --gray: #747D8C;
            --success: #2ED573;
            --warning: #FFA502;
            --danger: #FF4757;
            --radius: 12px;
            --radius-lg: 20px;
            --shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 20px 60px rgba(0, 0, 0, 0.15);
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            --gradient: linear-gradient(135deg, #FF6B6B 0%, #FFA502 100%);
            --gradient-secondary: linear-gradient(135deg, #1E90FF 0%, #3742FA 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            color: var(--dark);
            overflow-x: hidden;
        }
        
        .profile-wrapper {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
            opacity: 0;
            animation: fadeIn 0.8s forwards 0.3s;
        }
        
        @keyframes fadeIn {
            to { opacity: 1; }
        }
        
        /* Header Styles */
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 25px 40px;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }
        
        .header-bg {
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.05) 0%, rgba(255, 165, 2, 0.05) 100%);
            clip-path: polygon(100% 0, 100% 100%, 0 100%, 100px 0);
        }
        
        .logo {
            font-family: 'Montserrat', sans-serif;
            font-size: 28px;
            font-weight: 900;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .logo span {
            color: var(--secondary);
        }
        
        .user-quick-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .notification-bell {
            position: relative;
            cursor: pointer;
        }
        
        .notification-bell i {
            font-size: 22px;
            color: var(--gray);
            transition: var(--transition);
        }
        
        .notification-bell:hover i {
            color: var(--primary);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .avatar-dropdown {
            position: relative;
        }
        
        .avatar {
            width: 50px;
            height: 50px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 20px;
            cursor: pointer;
            transition: var(--transition);
            border: 3px solid white;
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.2);
        }
        
        .avatar:hover {
            transform: scale(1.1);
        }
        
        /* Main Layout */
        .profile-main {
            display: grid;
            grid-template-columns: 280px 1fr 350px;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        /* Sidebar Navigation */
        .sidebar {
            background: white;
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: var(--shadow);
            height: fit-content;
            position: sticky;
            top: 30px;
        }
        
        .user-sidebar-info {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f1f2f6;
        }
        
        .sidebar-avatar {
            width: 80px;
            height: 80px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: bold;
            margin: 0 auto 15px;
            border: 5px solid white;
            box-shadow: 0 10px 20px rgba(255, 107, 107, 0.2);
        }
        
        .sidebar-user-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .sidebar-user-email {
            color: var(--gray);
            font-size: 14px;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin-bottom: 8px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: var(--dark);
            text-decoration: none;
            border-radius: var(--radius);
            transition: var(--transition);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: var(--gradient);
            transition: width 0.3s ease;
            z-index: 1;
            opacity: 0.1;
        }
        
        .nav-link:hover::before,
        .nav-link.active::before {
            width: 100%;
        }
        
        .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 15px;
            font-size: 18px;
            position: relative;
            z-index: 2;
        }
        
        .nav-link:hover,
        .nav-link.active {
            color: var(--primary);
            background: linear-gradient(to right, rgba(255, 107, 107, 0.05), transparent);
        }
        
        .nav-link.active {
            font-weight: 600;
            box-shadow: inset 3px 0 0 var(--primary);
        }
        
        .nav-badge {
            margin-left: auto;
            background: var(--primary);
            color: white;
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Main Content */
        .main-content {
            background: white;
            border-radius: var(--radius-lg);
            padding: 40px;
            box-shadow: var(--shadow);
        }
        
        .section {
            display: none;
            animation: slideIn 0.5s forwards;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .section.active {
            display: block;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f1f2f6;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -22px;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--gradient);
            border-radius: 2px;
        }
        
        /* Profile Info Card */
        .profile-card {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        .card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-5px);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.1) 0%, rgba(255, 165, 2, 0.1) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .card-icon i {
            font-size: 24px;
            color: var(--primary);
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e8e9ed;
            border-radius: var(--radius);
            font-size: 15px;
            transition: var(--transition);
            background: #fafbfc;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }
        
        .btn {
            background: var(--gradient);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: var(--radius);
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.3);
        }
        
        .btn-secondary {
            background: var(--gradient-secondary);
        }
        
        .btn-secondary:hover {
            box-shadow: 0 10px 25px rgba(30, 144, 255, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background: var(--gradient);
            color: white;
        }
        
        /* Orders Section */
        .orders-grid {
            display: grid;
            gap: 20px;
            margin-top: 30px;
        }
        
        .order-card {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--primary);
            transition: var(--transition);
        }
        
        .order-card:hover {
            transform: translateX(10px);
            box-shadow: var(--shadow);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .order-id {
            font-weight: 700;
            color: var(--dark);
            font-size: 16px;
        }
        
        .order-date {
            color: var(--gray);
            font-size: 14px;
        }
        
        .order-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }
        
        .order-amount {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
        }
        
        /* Wishlist Section */
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .product-card {
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-hover);
        }
        
        .product-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--danger);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .product-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            transition: var(--transition);
        }
        
        .product-card:hover .product-img {
            transform: scale(1.05);
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-name {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .product-price {
            color: var(--primary);
            font-weight: 700;
            font-size: 18px;
        }
        
        /* Stats Sidebar */
        .stats-sidebar {
            background: white;
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: var(--shadow);
            height: fit-content;
            position: sticky;
            top: 30px;
        }
        
        .stats-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .stats-icon {
            width: 70px;
            height: 70px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 28px;
        }
        
        .stats-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .stats-list {
            list-style: none;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f1f2f6;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 14px;
        }
        
        .stat-value {
            font-weight: 600;
            color: var(--dark);
        }
        
        /* Help & Support */
        .help-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .help-card {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            border-top: 4px solid var(--primary);
        }
        
        .help-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }
        
        .help-card i {
            font-size: 40px;
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .help-card h4 {
            font-size: 18px;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .help-card p {
            color: var(--gray);
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        /* Alerts */
        .alert {
            padding: 20px;
            border-radius: var(--radius);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideInRight 0.5s forwards;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(46, 213, 115, 0.1) 0%, rgba(46, 213, 115, 0.05) 100%);
            border-left: 4px solid var(--success);
            color: #218c74;
        }
        
        .alert-error {
            background: linear-gradient(135deg, rgba(255, 71, 87, 0.1) 0%, rgba(255, 71, 87, 0.05) 100%);
            border-left: 4px solid var(--danger);
            color: #c44569;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, rgba(255, 165, 2, 0.1) 0%, rgba(255, 165, 2, 0.05) 100%);
            border-left: 4px solid var(--warning);
            color: #cc8e35;
        }
        
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 60px 40px;
        }
        
        .empty-state i {
            font-size: 80px;
            color: #e0e0e0;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            color: var(--gray);
            margin-bottom: 10px;
            font-size: 20px;
        }
        
        .empty-state p {
            color: #a5b1c2;
            margin-bottom: 30px;
        }
        
        /* Footer Navigation */
        .profile-footer {
            background: white;
            border-radius: var(--radius-lg);
            padding: 30px 40px;
            box-shadow: var(--shadow);
            margin-top: 40px;
        }
        
        .footer-nav {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
        }
        
        .footer-section h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dark);
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 12px;
        }
        
        .footer-links a {
            color: var(--gray);
            text-decoration: none;
            transition: var(--transition);
            font-size: 14px;
        }
        
        .footer-links a:hover {
            color: var(--primary);
            padding-left: 5px;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .profile-main {
                grid-template-columns: 250px 1fr;
            }
            
            .stats-sidebar {
                grid-column: 1 / -1;
                margin-top: 30px;
            }
        }
        
        @media (max-width: 992px) {
            .profile-main {
                grid-template-columns: 1fr;
            }
            
            .sidebar, .stats-sidebar {
                position: static;
            }
            
            .profile-card {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
                padding: 20px;
            }
            
            .header-bg {
                display: none;
            }
            
            .main-content {
                padding: 25px;
            }
            
            .wishlist-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .wishlist-grid {
                grid-template-columns: 1fr;
            }
            
            .help-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-nav {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="profile-wrapper">
        <!-- Header -->
        <div class="profile-header">
            <div class="logo">Pro<span>Glide</span></div>
            <div class="user-quick-info">
                <div class="notification-bell">
                    <i class="far fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="avatar-dropdown">
                    <div class="avatar">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                </div>
            </div>
            <div class="header-bg"></div>
        </div>
        
        <!-- Main Content -->
        <div class="profile-main">
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="user-sidebar-info">
                    <div class="sidebar-avatar">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <h3 class="sidebar-user-name"><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p class="sidebar-user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="#" class="nav-link active" onclick="showSection('profile')">
                            <i class="fas fa-user-circle"></i>
                            <span>Profile Info</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" onclick="showSection('wishlist')">
                            <i class="fas fa-heart"></i>
                            <span>My Wishlist</span>
                            <span class="nav-badge"><?php echo $wishlist_result->num_rows; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" onclick="showSection('orders')">
                            <i class="fas fa-shopping-bag"></i>
                            <span>My Orders</span>
                            <span class="nav-badge"><?php echo $orders_result->num_rows; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" onclick="showSection('security')">
                            <i class="fas fa-shield-alt"></i>
                            <span>Security</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" onclick="showSection('help')">
                            <i class="fas fa-question-circle"></i>
                            <span>Help & Support</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" onclick="showSection('terms')">
                            <i class="fas fa-file-contract"></i>
                            <span>Terms & Conditions</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" onclick="showSection('privacy')">
                            <i class="fas fa-lock"></i>
                            <span>Privacy Policy</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" onclick="showSection('contact')">
                            <i class="fas fa-envelope"></i>
                            <span>Contact Us</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content Area -->
            <div class="main-content">
                <?php if($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Profile Info Section -->
                <div id="profile-section" class="section active">
                    <div class="section-header">
                        <h2 class="section-title">Profile Information</h2>
                        <button class="btn" onclick="showEditForm()">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                    </div>
                    
                    <div class="profile-card">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <h3 class="card-title">Personal Information</h3>
                            </div>
                            <form method="POST" action="">
                                <input type="hidden" name="update_profile" value="1">
                                <div class="form-group">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="name" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <button type="submit" class="btn">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </form>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <div class="card-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <h3 class="card-title">Account Details</h3>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Member Since</label>
                                <input type="text" class="form-input" 
                                       value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Last Login</label>
                                <input type="text" class="form-input" 
                                       value="<?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'First Login'; ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Account Status</label>
                                <input type="text" class="form-input" 
                                       value="<?php echo $user['status'] ? 'Active' : 'Inactive'; ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Security Section -->
                <div id="security-section" class="section">
                    <div class="section-header">
                        <h2 class="section-title">Security Settings</h2>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-key"></i>
                            </div>
                            <h3 class="card-title">Change Password</h3>
                        </div>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="change_password" value="1">
                            
                            <?php if($current_password_error): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <?php echo $current_password_error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-input" required minlength="6">
                                <small style="color: var(--gray); font-size: 12px;">Must be at least 6 characters</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-input" required>
                            </div>
                            
                            <button type="submit" class="btn">
                                <i class="fas fa-key"></i> Update Password
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Wishlist Section -->
                <div id="wishlist-section" class="section">
                    <div class="section-header">
                        <h2 class="section-title">My Wishlist</h2>
                        <button class="btn btn-secondary">
                            <i class="fas fa-shopping-cart"></i> Add All to Cart
                        </button>
                    </div>
                    
                    <?php if($wishlist_result->num_rows > 0): ?>
                        <div class="wishlist-grid">
                            <?php while($item = $wishlist_result->fetch_assoc()): ?>
                                <div class="product-card">
                                    <span class="product-badge">Wishlist</span>
                                    <img src="<?php echo $item['image1'] ?: 'https://via.placeholder.com/300x180/FF6B6B/ffffff?text=ProGlide'; ?>" 
                                         class="product-img" alt="<?php echo htmlspecialchars($item['model_name'] ?: $item['design_name']); ?>">
                                    <div class="product-info">
                                        <h4 class="product-name">
                                            <?php echo htmlspecialchars($item['model_name'] ?: $item['design_name']); ?>
                                        </h4>
                                        <p class="product-price">
                                            ₹<?php echo number_format($item['price'], 2); ?>
                                            <?php if($item['original_price']): ?>
                                                <span style="text-decoration: line-through; color: var(--gray); font-size: 14px;">
                                                    ₹<?php echo number_format($item['original_price'], 2); ?>
                                                </span>
                                            <?php endif; ?>
                                        </p>
                                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                                            <button class="btn" style="padding: 8px 15px; font-size: 14px; flex: 1;">
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            </button>
                                            <button class="btn btn-outline" style="padding: 8px 15px; font-size: 14px;" 
                                                    onclick="removeFromWishlist(<?php echo $item['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-heart"></i>
                            <h3>Your Wishlist is Empty</h3>
                            <p>Save your favorite products here!</p>
                            <a href="products.php" class="btn" style="margin-top: 20px;">
                                <i class="fas fa-plus"></i> Browse Products
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Orders Section -->
                <div id="orders-section" class="section">
                    <div class="section-header">
                        <h2 class="section-title">Recent Orders</h2>
                        <button class="btn btn-secondary">
                            <i class="fas fa-history"></i> View All Orders
                        </button>
                    </div>
                    
                    <?php if($orders_result->num_rows > 0): ?>
                        <div class="orders-grid">
                            <?php while($order = $orders_result->fetch_assoc()): ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <span class="order-id">Order #<?php echo $order['id']; ?></span>
                                        <span class="order-date"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                                        <span style="background: <?php 
                                            echo $order['status'] == 'Delivered' ? '#2ED573' : 
                                                  ($order['status'] == 'Processing' ? '#1E90FF' : 
                                                  ($order['status'] == 'Pending' ? '#FFA502' : '#FF4757')); 
                                        ?>; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                            <?php echo $order['status']; ?>
                                        </span>
                                        <span style="color: var(--gray); font-size: 14px;">
                                            <?php echo $order['item_count']; ?> items
                                        </span>
                                    </div>
                                    <div class="order-details">
                                        <span class="order-amount">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                                        <button class="btn" style="padding: 8px 20px; font-size: 14px;">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-bag"></i>
                            <h3>No Orders Yet</h3>
                            <p>You haven't placed any orders yet.</p>
                            <a href="products.php" class="btn" style="margin-top: 20px;">
                                <i class="fas fa-shopping-cart"></i> Start Shopping
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Help & Support Section -->
                <div id="help-section" class="section">
                    <div class="section-header">
                        <h2 class="section-title">Help & Support</h2>
                    </div>
                    
                    <div class="help-grid">
                        <div class="help-card">
                            <i class="fas fa-headset"></i>
                            <h4>24/7 Customer Support</h4>
                            <p>Get help anytime with our dedicated support team</p>
                            <button class="btn" style="width: 100%;">
                                <i class="fas fa-phone-alt"></i> Contact Support
                            </button>
                        </div>
                        
                        <div class="help-card">
                            <i class="fas fa-question-circle"></i>
                            <h4>FAQ Center</h4>
                            <p>Find answers to frequently asked questions</p>
                            <button class="btn" style="width: 100%;">
                                <i class="fas fa-search"></i> Browse FAQ
                            </button>
                        </div>
                        
                        <div class="help-card">
                            <i class="fas fa-comments"></i>
                            <h4>Live Chat</h4>
                            <p>Chat with our representatives in real-time</p>
                            <button class="btn" style="width: 100%;">
                                <i class="fas fa-comment-dots"></i> Start Chat
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Terms & Conditions -->
                <div id="terms-section" class="section">
                    <div class="section-header">
                        <h2 class="section-title">Terms & Conditions</h2>
                    </div>
                    <div class="card">
                        <h3 style="margin-bottom: 20px; color: var(--dark);">ProGlide Terms of Service</h3>
                        <div style="color: var(--gray); line-height: 1.6; max-height: 400px; overflow-y: auto; padding-right: 10px;">
                            <p>Welcome to ProGlide! These terms and conditions outline the rules and regulations for the use of ProGlide's Website.</p>
                            <h4 style="margin: 20px 0 10px; color: var(--dark);">1. Acceptance of Terms</h4>
                            <p>By accessing this website we assume you accept these terms and conditions in full.</p>
                            
                            <h4 style="margin: 20px 0 10px; color: var(--dark);">2. License</h4>
                            <p>Unless otherwise stated, ProGlide and/or its licensors own the intellectual property rights for all material on ProGlide.</p>
                            
                            <h4 style="margin: 20px 0 10px; color: var(--dark);">3. User Account</h4>
                            <p>You are responsible for maintaining the confidentiality of your account and password.</p>
                            
                            <h4 style="margin: 20px 0 10px; color: var(--dark);">4. Products and Pricing</h4>
                            <p>All products are subject to availability. We reserve the right to discontinue any product at any time.</p>
                            
                            <h4 style="margin: 20px 0 10px; color: var(--dark);">5. Returns and Refunds</h4>
                            <p>Our return policy lasts 30 days from the date of purchase.</p>
                            
                            <h4 style="margin: 20px 0 10px; color: var(--dark);">6. Shipping</h4>
                            <p>We ship to locations within India. Delivery times may vary.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Privacy Policy -->
                <div id="privacy-section" class="section">
                    <div class="section-header">
                        <h2 class="section-title">Privacy Policy</h2>
                    </div>
                    <div class="card">
                        <h3 style="margin-bottom: 20px; color: var(--dark);">Privacy Policy</h3>
                        <div style="color: var(--gray); line-height: 1.6; max-height: 400px; overflow-y: auto; padding-right: 10px;">
                            <p>Your privacy is important to us. This privacy policy explains what personal data we collect and how we use it.</p>
                            
                            <h4 style="margin: 20px 0 10px; color: var(--dark);">1. Information We Collect</h4>
                            <p>We collect information you provide directly to us, such as name, email address, and payment information.</p>
                            
                            <h4 style="margin: 20px 0 10px; color: var(--dark);">2. How We Use Information</h4>
                            <p>We use the information we collect to provide, maintain, and improve our services.</p>
                            
                            <h4 style="margin: 20px 0 10px; color: var(--dark);">3. Information Sharing</h4>
                            <p>We do not share your personal information with third parties except as described in this policy.</p>
                            
                            <h4 style="margin: 20px 0 10px; color: var(--dark);">4. Data Security</h4>
                            <p>We implement appropriate technical and organizational security measures to protect your data.</p>
                            
                            <h4 style="margin: 20px 0 10px; color: var(--dark);">5. Your Rights</h4>
                            <p>You have the right to access, correct, or delete your personal information.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Us -->
                <div id="contact-section" class="section">
                    <div class="section-header">
                        <h2 class="section-title">Contact Us</h2>
                    </div>
                    <div class="profile-card">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <h3 class="card-title">Send us a Message</h3>
                            </div>
                            <form>
                                <div class="form-group">
                                    <label class="form-label">Your Name</label>
                                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($user['name']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Your Email</label>
                                    <input type="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Subject</label>
                                    <input type="text" class="form-input" placeholder="How can we help you?">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Message</label>
                                    <textarea class="form-input" rows="5" placeholder="Type your message here..."></textarea>
                                </div>
                                
                                <button type="submit" class="btn" style="width: 100%;">
                                    <i class="fas fa-paper-plane"></i> Send Message
                                </button>
                            </form>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <div class="card-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <h3 class="card-title">Contact Information</h3>
                            </div>
                            
                            <div style="margin-bottom: 25px;">
                                <h4 style="color: var(--dark); margin-bottom: 10px; font-size: 16px;">
                                    <i class="fas fa-phone" style="color: var(--primary); margin-right: 10px;"></i>
                                    Phone Number
                                </h4>
                                <p style="color: var(--gray);">+91 9876543210</p>
                            </div>
                            
                            <div style="margin-bottom: 25px;">
                                <h4 style="color: var(--dark); margin-bottom: 10px; font-size: 16px;">
                                    <i class="fas fa-envelope" style="color: var(--primary); margin-right: 10px;"></i>
                                    Email Address
                                </h4>
                                <p style="color: var(--gray);">support@proglide.com</p>
                            </div>
                            
                            <div style="margin-bottom: 25px;">
                                <h4 style="color: var(--dark); margin-bottom: 10px; font-size: 16px;">
                                    <i class="fas fa-clock" style="color: var(--primary); margin-right: 10px;"></i>
                                    Business Hours
                                </h4>
                                <p style="color: var(--gray);">Monday - Friday: 9:00 AM - 6:00 PM</p>
                                <p style="color: var(--gray);">Saturday: 10:00 AM - 4:00 PM</p>
                            </div>
                            
                            <div>
                                <h4 style="color: var(--dark); margin-bottom: 10px; font-size: 16px;">
                                    <i class="fas fa-map-marker-alt" style="color: var(--primary); margin-right: 10px;"></i>
                                    Office Address
                                </h4>
                                <p style="color: var(--gray);">123 Tech Street, Chennai<br>Tamil Nadu 600001, India</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Sidebar -->
            <div class="stats-sidebar">
                <div class="stats-header">
                    <div class="stats-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="stats-title">Your Stats</h3>
                </div>
                
                <ul class="stats-list">
                    <li class="stat-item">
                        <span class="stat-label">Total Orders</span>
                        <span class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></span>
                    </li>
                    <li class="stat-item">
                        <span class="stat-label">Total Spent</span>
                        <span class="stat-value">₹<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></span>
                    </li>
                    <li class="stat-item">
                        <span class="stat-label">Wishlist Items</span>
                        <span class="stat-value"><?php echo $wishlist_result->num_rows; ?></span>
                    </li>
                    <li class="stat-item">
                        <span class="stat-label">Pending Orders</span>
                        <span class="stat-value"><?php echo $stats['pending_orders'] ?? 0; ?></span>
                    </li>
                    <li class="stat-item">
                        <span class="stat-label">Processing</span>
                        <span class="stat-value"><?php echo $stats['processing_orders'] ?? 0; ?></span>
                    </li>
                    <li class="stat-item">
                        <span class="stat-label">Delivered</span>
                        <span class="stat-value"><?php echo $stats['delivered_orders'] ?? 0; ?></span>
                    </li>
                </ul>
                
                <div style="margin-top: 30px; padding: 20px; background: linear-gradient(135deg, rgba(255, 107, 107, 0.05) 0%, rgba(30, 144, 255, 0.05) 100%); border-radius: var(--radius);">
                    <h4 style="color: var(--dark); margin-bottom: 10px; font-size: 16px;">Account Status</h4>
                    <p style="color: var(--success); font-weight: 600;">
                        <i class="fas fa-check-circle"></i> Verified Account
                    </p>
                    <p style="color: var(--gray); font-size: 14px; margin-top: 5px;">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="profile-footer">
            <div class="footer-nav">
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="categories.php">Categories</a></li>
                        <li><a href="deals.php">Today's Deals</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Customer Service</h4>
                    <ul class="footer-links">
                        <li><a href="#" onclick="showSection('help')">Help Center</a></li>
                        <li><a href="#" onclick="showSection('contact')">Contact Us</a></li>
                        <li><a href="#">Shipping Policy</a></li>
                        <li><a href="#">Return Policy</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Legal</h4>
                    <ul class="footer-links">
                        <li><a href="#" onclick="showSection('privacy')">Privacy Policy</a></li>
                        <li><a href="#" onclick="showSection('terms')">Terms of Service</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                        <li><a href="#">Disclaimer</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Connect With Us</h4>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fab fa-facebook" style="margin-right: 8px;"></i> Facebook</a></li>
                        <li><a href="#"><i class="fab fa-instagram" style="margin-right: 8px;"></i> Instagram</a></li>
                        <li><a href="#"><i class="fab fa-twitter" style="margin-right: 8px;"></i> Twitter</a></li>
                        <li><a href="#"><i class="fab fa-youtube" style="margin-right: 8px;"></i> YouTube</a></li>
                    </ul>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f2f6;">
                <p style="color: var(--gray); font-size: 14px;">
                    &copy; <?php echo date('Y'); ?> ProGlide. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <script>
        // Section switching
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
                section.style.display = 'none';
            });
            
            // Remove active class from all nav links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Show selected section
            const section = document.getElementById(sectionId + '-section');
            section.style.display = 'block';
            setTimeout(() => {
                section.classList.add('active');
            }, 10);
            
            // Add active class to clicked nav link
            event.target.closest('.nav-link').classList.add('active');
        }
        
        // Remove from wishlist
        function removeFromWishlist(productId) {
            if(confirm('Remove this item from wishlist?')) {
                fetch('remove_wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ product_id: productId })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const card = event.target.closest('.product-card');
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.8)';
                        setTimeout(() => {
                            card.remove();
                            // Update badge count
                            const badge = document.querySelector('.nav-link[onclick*="wishlist"] .nav-badge');
                            if(badge) {
                                let count = parseInt(badge.textContent);
                                if(count > 1) {
                                    badge.textContent = count - 1;
                                } else {
                                    badge.remove();
                                }
                            }
                        }, 300);
                    }
                });
            }
        }
        
        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[$@#&!]+/)) strength++;
            
            const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'][strength];
            const strengthColor = ['#FF4757', '#FF6B6B', '#FFA502', '#1E90FF', '#2ED573'][strength];
            
            return { strength: strengthText, color: strengthColor };
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation to cards on scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            // Observe cards
            document.querySelectorAll('.card, .product-card, .order-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s ease';
                observer.observe(card);
            });
        });
    </script>
</body>
</html>