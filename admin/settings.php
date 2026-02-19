<?php
require "includes/admin_db.php";
require "includes/admin_auth.php";

// Handle settings update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // General Settings
    if (isset($_POST['update_general'])) {
        $site_name = trim($_POST['site_name']);
        $site_description = trim($_POST['site_description']);
        $site_email = trim($_POST['site_email']);
        $site_phone = trim($_POST['site_phone']);
        $site_address = trim($_POST['site_address']);
        $currency = trim($_POST['currency']);
        $tax_rate = (float)$_POST['tax_rate'];
        $shipping_charge = (float)$_POST['shipping_charge'];
        $free_shipping_min = (float)$_POST['free_shipping_min'];
        
        // Update in database (you need to create a settings table)
        // For now, we'll store in session or you can create a settings table
        
        $success_message = "General settings updated successfully!";
    }
    
    // Appearance Settings
    if (isset($_POST['update_appearance'])) {
        $theme_color = trim($_POST['theme_color']);
        $header_layout = trim($_POST['header_layout']);
        $footer_layout = trim($_POST['footer_layout']);
        $products_per_page = (int)$_POST['products_per_page'];
        $show_popular = isset($_POST['show_popular']) ? 1 : 0;
        $show_categories = isset($_POST['show_categories']) ? 1 : 0;
        
        // Handle logo upload
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = "../uploads/settings/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
            if (in_array($_FILES['site_logo']['type'], $allowed_types)) {
                $extension = pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
                $filename = 'logo_' . time() . '.' . $extension;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $filepath)) {
                    $site_logo = $filename;
                }
            }
        }
        
        // Handle favicon upload
        if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = "../uploads/settings/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'];
            if (in_array($_FILES['site_favicon']['type'], $allowed_types)) {
                $extension = pathinfo($_FILES['site_favicon']['name'], PATHINFO_EXTENSION);
                $filename = 'favicon_' . time() . '.' . $extension;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['site_favicon']['tmp_name'], $filepath)) {
                    $site_favicon = $filename;
                }
            }
        }
        
        $success_message = "Appearance settings updated successfully!";
    }
    
    // Email Settings
    if (isset($_POST['update_email'])) {
        $smtp_host = trim($_POST['smtp_host']);
        $smtp_port = (int)$_POST['smtp_port'];
        $smtp_username = trim($_POST['smtp_username']);
        $smtp_password = trim($_POST['smtp_password']);
        $smtp_encryption = trim($_POST['smtp_encryption']);
        $admin_email = trim($_POST['admin_email']);
        $order_notifications = isset($_POST['order_notifications']) ? 1 : 0;
        $user_notifications = isset($_POST['user_notifications']) ? 1 : 0;
        
        $success_message = "Email settings updated successfully!";
    }
    
    // Payment Settings
    if (isset($_POST['update_payment'])) {
        $cod_enabled = isset($_POST['cod_enabled']) ? 1 : 0;
        $razorpay_enabled = isset($_POST['razorpay_enabled']) ? 1 : 0;
        $razorpay_key = trim($_POST['razorpay_key']);
        $razorpay_secret = trim($_POST['razorpay_secret']);
        $paypal_enabled = isset($_POST['paypal_enabled']) ? 1 : 0;
        $paypal_email = trim($_POST['paypal_email']);
        $paypal_mode = trim($_POST['paypal_mode']);
        
        $success_message = "Payment settings updated successfully!";
    }
    
    // Social Media Settings
    if (isset($_POST['update_social'])) {
        $facebook_url = trim($_POST['facebook_url']);
        $instagram_url = trim($_POST['instagram_url']);
        $twitter_url = trim($_POST['twitter_url']);
        $youtube_url = trim($_POST['youtube_url']);
        $linkedin_url = trim($_POST['linkedin_url']);
        $whatsapp_number = trim($_POST['whatsapp_number']);
        
        $success_message = "Social media settings updated successfully!";
    }
    
    // Security Settings
    if (isset($_POST['update_security'])) {
        $recaptcha_enabled = isset($_POST['recaptcha_enabled']) ? 1 : 0;
        $recaptcha_site_key = trim($_POST['recaptcha_site_key']);
        $recaptcha_secret_key = trim($_POST['recaptcha_secret_key']);
        $login_attempts = (int)$_POST['login_attempts'];
        $session_timeout = (int)$_POST['session_timeout'];
        $two_factor_auth = isset($_POST['two_factor_auth']) ? 1 : 0;
        
        $success_message = "Security settings updated successfully!";
    }
    
    // Backup Settings
    if (isset($_POST['create_backup'])) {
        // Create database backup
        $backup_dir = "../backups/";
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0777, true);
        }
        
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backup_dir . $filename;
        
        // Use mysqldump to create backup
        $command = "mysqldump --user=" . DB_USER . " --password=" . DB_PASS . " --host=" . DB_HOST . " " . DB_NAME . " > " . $filepath;
        system($command, $output);
        
        if ($output === 0) {
            $success_message = "Database backup created successfully! Filename: " . $filename;
        } else {
            $error_message = "Failed to create backup. Please check permissions.";
        }
    }
}

// Get current settings (you need to fetch from database)
// For demo, using default values
$settings = [
    'site_name' => 'PROGLIDE',
    'site_description' => 'Premium Phone Accessories',
    'site_email' => 'info@proglide.com',
    'site_phone' => '+91 98765 43210',
    'site_address' => 'Chennai, Tamil Nadu, India',
    'currency' => 'INR',
    'tax_rate' => 18,
    'shipping_charge' => 49,
    'free_shipping_min' => 499,
    'theme_color' => '#FF6B35',
    'header_layout' => 'standard',
    'footer_layout' => 'standard',
    'products_per_page' => 24,
    'show_popular' => 1,
    'show_categories' => 1,
    'site_logo' => '',
    'site_favicon' => '',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',
    'admin_email' => 'admin@proglide.com',
    'order_notifications' => 1,
    'user_notifications' => 1,
    'cod_enabled' => 1,
    'razorpay_enabled' => 0,
    'razorpay_key' => '',
    'razorpay_secret' => '',
    'paypal_enabled' => 0,
    'paypal_email' => '',
    'paypal_mode' => 'sandbox',
    'facebook_url' => 'https://facebook.com/proglide',
    'instagram_url' => 'https://instagram.com/proglide',
    'twitter_url' => 'https://twitter.com/proglide',
    'youtube_url' => '',
    'linkedin_url' => '',
    'whatsapp_number' => '+919876543210',
    'recaptcha_enabled' => 0,
    'recaptcha_site_key' => '',
    'recaptcha_secret_key' => '',
    'login_attempts' => 5,
    'session_timeout' => 30,
    'two_factor_auth' => 0
];

// Get backup files list
$backup_dir = "../backups/";
$backups = [];
if (file_exists($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
            $backups[] = [
                'name' => $file,
                'size' => filesize($backup_dir . $file),
                'date' => date('Y-m-d H:i:s', filemtime($backup_dir . $file))
            ];
        }
    }
    // Sort by date descending
    usort($backups, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | PROGLIDE Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #FF6B35;
            --primary-dark: #e55a2b;
            --primary-light: rgba(255, 107, 53, 0.1);
            --dark-bg: #0a0a0a;
            --dark-card: #1a1a1a;
            --dark-border: #2a2a2a;
            --dark-hover: #252525;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --text-muted: #808080;
            --success: #4CAF50;
            --warning: #FFC107;
            --danger: #F44336;
            --info: #2196F3;
            --sidebar-width: 260px;
            --header-height: 70px;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--dark-bg);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--dark-card);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            border-right: 1px solid var(--dark-border);
            padding: 30px 0;
            z-index: 1000;
            transition: var(--transition);
            overflow-y: auto;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: var(--dark-border);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        .logo {
            padding: 0 25px;
            margin-bottom: 40px;
        }

        .logo h1 {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, #FF8E53 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .logo p {
            color: var(--text-secondary);
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 25px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
            position: relative;
            font-size: 0.95rem;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--text-primary);
            background: var(--primary-light);
            border-right: 3px solid var(--primary);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: var(--transition);
        }

        /* Header */
        .header {
            height: var(--header-height);
            background: var(--dark-card);
            border-bottom: 1px solid var(--dark-border);
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 8px;
            border-radius: var(--radius-sm);
            transition: var(--transition);
        }

        .menu-toggle:hover {
            background: var(--dark-hover);
        }

        .header-left h2 {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-left h2 i {
            color: var(--primary);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 5px 15px;
            background: var(--dark-hover);
            border-radius: 40px;
            cursor: pointer;
            transition: var(--transition);
        }

        .admin-profile:hover {
            background: var(--dark-border);
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), #FF8E53);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
        }

        .admin-info h4 {
            font-size: 0.95rem;
            font-weight: 600;
        }

        .admin-info p {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .logout-btn {
            padding: 8px 20px;
            background: transparent;
            border: 1px solid var(--dark-border);
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Content */
        .content {
            padding: 30px;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .alert-danger {
            background: rgba(244, 67, 54, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
        }

        .alert-warning {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid var(--warning);
            color: var(--warning);
        }

        .alert-info {
            background: rgba(33, 150, 243, 0.1);
            border: 1px solid var(--info);
            color: var(--info);
        }

        /* Header Actions */
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-actions h2 {
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-actions h2 i {
            color: var(--primary);
        }

        .btn {
            padding: 12px 25px;
            border-radius: var(--radius-sm);
            border: none;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }

        .btn-secondary {
            background: var(--dark-hover);
            color: var(--text-primary);
            border: 1px solid var(--dark-border);
        }

        .btn-secondary:hover {
            background: var(--dark-border);
            transform: translateY(-2px);
        }

        .btn-success {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .btn-success:hover {
            background: var(--success);
            color: white;
        }

        .btn-danger {
            background: rgba(244, 67, 54, 0.1);
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        .btn-danger:hover {
            background: var(--danger);
            color: white;
        }

        .btn-warning {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .btn-warning:hover {
            background: var(--warning);
            color: black;
        }

        /* Settings Tabs */
        .settings-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            background: var(--dark-card);
            padding: 15px;
            border-radius: var(--radius);
            border: 1px solid var(--dark-border);
        }

        .tab-btn {
            padding: 12px 20px;
            background: var(--dark-hover);
            border: 1px solid var(--dark-border);
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        .tab-btn:hover {
            background: var(--dark-border);
            color: var(--text-primary);
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .tab-btn i {
            font-size: 1rem;
        }

        /* Settings Panels */
        .settings-panel {
            display: none;
            background: var(--dark-card);
            border-radius: var(--radius);
            border: 1px solid var(--dark-border);
            padding: 30px;
            margin-bottom: 30px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .settings-panel.active {
            display: block;
        }

        .panel-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--dark-border);
        }

        .panel-header i {
            font-size: 2rem;
            color: var(--primary);
            background: var(--primary-light);
            padding: 15px;
            border-radius: 50%;
        }

        .panel-header h3 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .panel-header p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 5px;
        }

        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .form-group label i {
            color: var(--primary);
            font-size: 0.9rem;
        }

        .form-control {
            padding: 12px 15px;
            background: var(--dark-hover);
            border: 1px solid var(--dark-border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--dark-card);
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        select.form-control {
            cursor: pointer;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .form-text {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 5px;
        }

        /* Switch */
        .switch-group {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .switch-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--dark-border);
            transition: var(--transition);
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: var(--transition);
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        /* Image Upload */
        .image-upload-wrapper {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .image-preview {
            width: 100px;
            height: 100px;
            background: var(--dark-hover);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 2rem;
            border: 2px dashed var(--dark-border);
            overflow: hidden;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .image-upload-box {
            flex: 1;
            background: var(--dark-hover);
            border: 2px dashed var(--dark-border);
            border-radius: var(--radius-sm);
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .image-upload-box:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .image-upload-box i {
            font-size: 2rem;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .image-upload-box p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .image-upload-box span {
            color: var(--text-muted);
            font-size: 0.75rem;
        }

        /* Color Picker */
        .color-picker {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .color-preview {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            border: 2px solid var(--dark-border);
        }

        input[type="color"] {
            width: 100%;
            height: 45px;
            padding: 5px;
            background: var(--dark-hover);
            border: 1px solid var(--dark-border);
            border-radius: var(--radius-sm);
            cursor: pointer;
        }

        /* Backup List */
        .backup-list {
            margin-top: 30px;
        }

        .backup-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: var(--dark-hover);
            border: 1px solid var(--dark-border);
            border-radius: var(--radius-sm);
            margin-bottom: 10px;
            transition: var(--transition);
        }

        .backup-item:hover {
            border-color: var(--primary);
            background: var(--dark-card);
        }

        .backup-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .backup-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .backup-details h4 {
            font-size: 1rem;
            margin-bottom: 5px;
        }

        .backup-details p {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .backup-actions {
            display: flex;
            gap: 10px;
        }

        .backup-btn {
            padding: 8px 12px;
            border-radius: var(--radius-sm);
            border: none;
            cursor: pointer;
            transition: var(--transition);
            color: var(--text-secondary);
            background: var(--dark-border);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.8rem;
        }

        .backup-btn:hover {
            background: var(--primary);
            color: white;
        }

        .backup-btn.download:hover {
            background: var(--info);
        }

        .backup-btn.restore:hover {
            background: var(--warning);
            color: black;
        }

        .backup-btn.delete:hover {
            background: var(--danger);
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid var(--dark-border);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }

            .admin-info {
                display: none;
            }

            .logout-btn span {
                display: none;
            }

            .logout-btn {
                padding: 10px 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: span 1;
            }

            .settings-tabs {
                flex-direction: column;
            }

            .tab-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .content {
                padding: 20px;
            }

            .header {
                padding: 0 20px;
            }

            .header-left h2 {
                font-size: 1.2rem;
            }

            .settings-panel {
                padding: 20px;
            }

            .panel-header {
                flex-direction: column;
                text-align: center;
            }

            .backup-item {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .backup-info {
                flex-direction: column;
                text-align: center;
            }

            .backup-actions {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .content {
                padding: 15px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .switch-group {
                flex-direction: column;
                gap: 15px;
            }

            .image-upload-wrapper {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <h1>PROGLIDE</h1>
            <p>Admin Dashboard</p>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="products.php" class="nav-link">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="add_product.php" class="nav-link">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add Product</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="categories.php" class="nav-link">
                    <i class="fas fa-layer-group"></i>
                    <span>Categories</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="brands.php" class="nav-link">
                    <i class="fas fa-tag"></i>
                    <span>Brands</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="phone_models.php" class="nav-link">
                    <i class="fas fa-mobile-alt"></i>
                    <span>Phone Models</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="material_types.php" class="nav-link">
                    <i class="fas fa-cube"></i>
                    <span>Materials</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="variant_types.php" class="nav-link">
                    <i class="fas fa-paint-bucket"></i>
                    <span>Variants</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link active">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <button class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h2><i class="fas fa-cog"></i> Settings</h2>
            </div>
            
            <div class="header-right">
                <div class="admin-profile">
                    <div class="admin-avatar">
                        <?php echo isset($_SESSION['admin_name']) ? strtoupper(substr($_SESSION['admin_name'], 0, 1)) : 'A'; ?>
                    </div>
                    <div class="admin-info">
                        <h4><?php echo isset($_SESSION['admin_name']) ? htmlspecialchars($_SESSION['admin_name']) : 'Admin'; ?></h4>
                        <p>Administrator</p>
                    </div>
                </div>
                <button class="logout-btn" onclick="window.location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </div>
        </div>
        
        <!-- Content -->
        <div class="content">
            <!-- Alerts -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Settings Tabs -->
            <div class="settings-tabs">
                <button class="tab-btn active" onclick="showTab('general')">
                    <i class="fas fa-globe"></i>
                    General
                </button>
                <button class="tab-btn" onclick="showTab('appearance')">
                    <i class="fas fa-paint-brush"></i>
                    Appearance
                </button>
                <button class="tab-btn" onclick="showTab('email')">
                    <i class="fas fa-envelope"></i>
                    Email
                </button>
                <button class="tab-btn" onclick="showTab('payment')">
                    <i class="fas fa-credit-card"></i>
                    Payment
                </button>
                <button class="tab-btn" onclick="showTab('social')">
                    <i class="fas fa-share-alt"></i>
                    Social Media
                </button>
                <button class="tab-btn" onclick="showTab('security')">
                    <i class="fas fa-shield-alt"></i>
                    Security
                </button>
                <button class="tab-btn" onclick="showTab('backup')">
                    <i class="fas fa-database"></i>
                    Backup
                </button>
            </div>
            
            <!-- General Settings Panel -->
            <div id="general" class="settings-panel active">
                <div class="panel-header">
                    <i class="fas fa-globe"></i>
                    <div>
                        <h3>General Settings</h3>
                        <p>Configure your store的基本 information</p>
                    </div>
                </div>
                
                <form action="" method="POST">
                    <input type="hidden" name="update_general" value="1">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-store"></i> Site Name</label>
                            <input type="text" name="site_name" class="form-control" value="<?php echo $settings['site_name']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Site Email</label>
                            <input type="email" name="site_email" class="form-control" value="<?php echo $settings['site_email']; ?>" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label><i class="fas fa-align-left"></i> Site Description</label>
                            <textarea name="site_description" class="form-control" rows="3"><?php echo $settings['site_description']; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="text" name="site_phone" class="form-control" value="<?php echo $settings['site_phone']; ?>">
                        </div>
                        
                        <div class="form-group full-width">
                            <label><i class="fas fa-map-marker-alt"></i> Address</label>
                            <textarea name="site_address" class="form-control" rows="2"><?php echo $settings['site_address']; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-rupee-sign"></i> Currency</label>
                            <select name="currency" class="form-control">
                                <option value="INR" <?php echo $settings['currency'] == 'INR' ? 'selected' : ''; ?>>INR (₹)</option>
                                <option value="USD" <?php echo $settings['currency'] == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                <option value="EUR" <?php echo $settings['currency'] == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                <option value="GBP" <?php echo $settings['currency'] == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-percent"></i> Tax Rate (%)</label>
                            <input type="number" name="tax_rate" class="form-control" step="0.01" min="0" max="100" value="<?php echo $settings['tax_rate']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-truck"></i> Shipping Charge</label>
                            <input type="number" name="shipping_charge" class="form-control" step="0.01" min="0" value="<?php echo $settings['shipping_charge']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-gift"></i> Free Shipping Minimum</label>
                            <input type="number" name="free_shipping_min" class="form-control" step="0.01" min="0" value="<?php echo $settings['free_shipping_min']; ?>">
                            <div class="form-text">Orders above this amount get free shipping</div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i>
                            Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Appearance Settings Panel -->
            <div id="appearance" class="settings-panel">
                <div class="panel-header">
                    <i class="fas fa-paint-brush"></i>
                    <div>
                        <h3>Appearance Settings</h3>
                        <p>Customize the look and feel of your store</p>
                    </div>
                </div>
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="update_appearance" value="1">
                    
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label><i class="fas fa-palette"></i> Theme Color</label>
                            <div class="color-picker">
                                <div class="color-preview" style="background: <?php echo $settings['theme_color']; ?>;"></div>
                                <input type="color" name="theme_color" value="<?php echo $settings['theme_color']; ?>" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-header"></i> Header Layout</label>
                            <select name="header_layout" class="form-control">
                                <option value="standard" <?php echo $settings['header_layout'] == 'standard' ? 'selected' : ''; ?>>Standard</option>
                                <option value="compact" <?php echo $settings['header_layout'] == 'compact' ? 'selected' : ''; ?>>Compact</option>
                                <option value="expanded" <?php echo $settings['header_layout'] == 'expanded' ? 'selected' : ''; ?>>Expanded</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-shoe-prints"></i> Footer Layout</label>
                            <select name="footer_layout" class="form-control">
                                <option value="standard" <?php echo $settings['footer_layout'] == 'standard' ? 'selected' : ''; ?>>Standard</option>
                                <option value="simple" <?php echo $settings['footer_layout'] == 'simple' ? 'selected' : ''; ?>>Simple</option>
                                <option value="detailed" <?php echo $settings['footer_layout'] == 'detailed' ? 'selected' : ''; ?>>Detailed</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-images"></i> Products Per Page</label>
                            <input type="number" name="products_per_page" class="form-control" min="6" max="100" value="<?php echo $settings['products_per_page']; ?>">
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Display Options</label>
                            <div class="switch-group">
                                <div class="switch-item">
                                    <label class="switch">
                                        <input type="checkbox" name="show_popular" <?php echo $settings['show_popular'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <span class="switch-label">Show Popular Products on Homepage</span>
                                </div>
                                
                                <div class="switch-item">
                                    <label class="switch">
                                        <input type="checkbox" name="show_categories" <?php echo $settings['show_categories'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <span class="switch-label">Show Categories on Homepage</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label><i class="fas fa-image"></i> Site Logo</label>
                            <div class="image-upload-wrapper">
                                <div class="image-preview">
                                    <?php if ($settings['site_logo']): ?>
                                        <img src="../uploads/settings/<?php echo $settings['site_logo']; ?>" alt="Logo">
                                    <?php else: ?>
                                        <i class="fas fa-image"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="image-upload-box" onclick="document.getElementById('logo-upload').click();">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Upload New Logo</p>
                                    <span>Recommended: 200x50px, PNG or SVG</span>
                                    <input type="file" name="site_logo" id="logo-upload" accept="image/*" style="display: none;">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label><i class="fas fa-star"></i> Favicon</label>
                            <div class="image-upload-wrapper">
                                <div class="image-preview">
                                    <?php if ($settings['site_favicon']): ?>
                                        <img src="../uploads/settings/<?php echo $settings['site_favicon']; ?>" alt="Favicon">
                                    <?php else: ?>
                                        <i class="fas fa-star"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="image-upload-box" onclick="document.getElementById('favicon-upload').click();">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Upload New Favicon</p>
                                    <span>Recommended: 32x32px, ICO or PNG</span>
                                    <input type="file" name="site_favicon" id="favicon-upload" accept="image/*" style="display: none;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i>
                            Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Email Settings Panel -->
            <div id="email" class="settings-panel">
                <div class="panel-header">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <h3>Email Settings</h3>
                        <p>Configure email server and notifications</p>
                    </div>
                </div>
                
                <form action="" method="POST">
                    <input type="hidden" name="update_email" value="1">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-server"></i> SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-control" value="<?php echo $settings['smtp_host']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-plug"></i> SMTP Port</label>
                            <input type="number" name="smtp_port" class="form-control" value="<?php echo $settings['smtp_port']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> SMTP Username</label>
                            <input type="text" name="smtp_username" class="form-control" value="<?php echo $settings['smtp_username']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> SMTP Password</label>
                            <input type="password" name="smtp_password" class="form-control" value="<?php echo $settings['smtp_password']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-shield-alt"></i> Encryption</label>
                            <select name="smtp_encryption" class="form-control">
                                <option value="tls" <?php echo $settings['smtp_encryption'] == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo $settings['smtp_encryption'] == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                <option value="none" <?php echo $settings['smtp_encryption'] == 'none' ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-inbox"></i> Admin Email</label>
                            <input type="email" name="admin_email" class="form-control" value="<?php echo $settings['admin_email']; ?>">
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Notification Settings</label>
                            <div class="switch-group">
                                <div class="switch-item">
                                    <label class="switch">
                                        <input type="checkbox" name="order_notifications" <?php echo $settings['order_notifications'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <span class="switch-label">Order Notifications</span>
                                </div>
                                
                                <div class="switch-item">
                                    <label class="switch">
                                        <input type="checkbox" name="user_notifications" <?php echo $settings['user_notifications'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <span class="switch-label">User Registration Notifications</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-info" onclick="testEmailSettings()">
                            <i class="fas fa-paper-plane"></i>
                            Test Email
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i>
                            Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Payment Settings Panel -->
            <div id="payment" class="settings-panel">
                <div class="panel-header">
                    <i class="fas fa-credit-card"></i>
                    <div>
                        <h3>Payment Settings</h3>
                        <p>Configure payment gateways</p>
                    </div>
                </div>
                
                <form action="" method="POST">
                    <input type="hidden" name="update_payment" value="1">
                    
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <div class="switch-item">
                                <label class="switch">
                                    <input type="checkbox" name="cod_enabled" <?php echo $settings['cod_enabled'] ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                                <span class="switch-label">Cash on Delivery (COD)</span>
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <div class="switch-item" style="margin-bottom: 20px;">
                                <label class="switch">
                                    <input type="checkbox" name="razorpay_enabled" id="razorpay_enabled" <?php echo $settings['razorpay_enabled'] ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                                <span class="switch-label">Razorpay</span>
                            </div>
                            
                            <div id="razorpay_fields" style="display: <?php echo $settings['razorpay_enabled'] ? 'block' : 'none'; ?>;">
                                <div class="form-group">
                                    <label>Razorpay Key ID</label>
                                    <input type="text" name="razorpay_key" class="form-control" value="<?php echo $settings['razorpay_key']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Razorpay Key Secret</label>
                                    <input type="password" name="razorpay_secret" class="form-control" value="<?php echo $settings['razorpay_secret']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <div class="switch-item" style="margin-bottom: 20px;">
                                <label class="switch">
                                    <input type="checkbox" name="paypal_enabled" id="paypal_enabled" <?php echo $settings['paypal_enabled'] ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                                <span class="switch-label">PayPal</span>
                            </div>
                            
                            <div id="paypal_fields" style="display: <?php echo $settings['paypal_enabled'] ? 'block' : 'none'; ?>;">
                                <div class="form-group">
                                    <label>PayPal Email</label>
                                    <input type="email" name="paypal_email" class="form-control" value="<?php echo $settings['paypal_email']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>PayPal Mode</label>
                                    <select name="paypal_mode" class="form-control">
                                        <option value="sandbox" <?php echo $settings['paypal_mode'] == 'sandbox' ? 'selected' : ''; ?>>Sandbox (Testing)</option>
                                        <option value="live" <?php echo $settings['paypal_mode'] == 'live' ? 'selected' : ''; ?>>Live (Production)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i>
                            Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Social Media Settings Panel -->
            <div id="social" class="settings-panel">
                <div class="panel-header">
                    <i class="fas fa-share-alt"></i>
                    <div>
                        <h3>Social Media</h3>
                        <p>Connect your social media accounts</p>
                    </div>
                </div>
                
                <form action="" method="POST">
                    <input type="hidden" name="update_social" value="1">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fab fa-facebook" style="color: #1877f2;"></i> Facebook URL</label>
                            <input type="url" name="facebook_url" class="form-control" value="<?php echo $settings['facebook_url']; ?>" placeholder="https://facebook.com/yourpage">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-instagram" style="color: #e4405f;"></i> Instagram URL</label>
                            <input type="url" name="instagram_url" class="form-control" value="<?php echo $settings['instagram_url']; ?>" placeholder="https://instagram.com/yourpage">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-twitter" style="color: #1da1f2;"></i> Twitter URL</label>
                            <input type="url" name="twitter_url" class="form-control" value="<?php echo $settings['twitter_url']; ?>" placeholder="https://twitter.com/yourpage">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-youtube" style="color: #ff0000;"></i> YouTube URL</label>
                            <input type="url" name="youtube_url" class="form-control" value="<?php echo $settings['youtube_url']; ?>" placeholder="https://youtube.com/@yourchannel">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-linkedin" style="color: #0a66c2;"></i> LinkedIn URL</label>
                            <input type="url" name="linkedin_url" class="form-control" value="<?php echo $settings['linkedin_url']; ?>" placeholder="https://linkedin.com/company/yourcompany">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-whatsapp" style="color: #25d366;"></i> WhatsApp Number</label>
                            <input type="text" name="whatsapp_number" class="form-control" value="<?php echo $settings['whatsapp_number']; ?>" placeholder="+919876543210">
                            <div class="form-text">Include country code</div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i>
                            Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Security Settings Panel -->
            <div id="security" class="settings-panel">
                <div class="panel-header">
                    <i class="fas fa-shield-alt"></i>
                    <div>
                        <h3>Security Settings</h3>
                        <p>Configure security and protection features</p>
                    </div>
                </div>
                
                <form action="" method="POST">
                    <input type="hidden" name="update_security" value="1">
                    
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <div class="switch-item" style="margin-bottom: 20px;">
                                <label class="switch">
                                    <input type="checkbox" name="recaptcha_enabled" id="recaptcha_enabled" <?php echo $settings['recaptcha_enabled'] ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                                <span class="switch-label">Enable reCAPTCHA</span>
                            </div>
                            
                            <div id="recaptcha_fields" style="display: <?php echo $settings['recaptcha_enabled'] ? 'block' : 'none'; ?>;">
                                <div class="form-group">
                                    <label>reCAPTCHA Site Key</label>
                                    <input type="text" name="recaptcha_site_key" class="form-control" value="<?php echo $settings['recaptcha_site_key']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>reCAPTCHA Secret Key</label>
                                    <input type="password" name="recaptcha_secret_key" class="form-control" value="<?php echo $settings['recaptcha_secret_key']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-exclamation-triangle"></i> Max Login Attempts</label>
                            <input type="number" name="login_attempts" class="form-control" min="1" max="10" value="<?php echo $settings['login_attempts']; ?>">
                            <div class="form-text">Number of failed attempts before lockout</div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Session Timeout (minutes)</label>
                            <input type="number" name="session_timeout" class="form-control" min="5" max="480" value="<?php echo $settings['session_timeout']; ?>">
                        </div>
                        
                        <div class="form-group full-width">
                            <div class="switch-item">
                                <label class="switch">
                                    <input type="checkbox" name="two_factor_auth" <?php echo $settings['two_factor_auth'] ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                                <span class="switch-label">Enable Two-Factor Authentication for Admins</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i>
                            Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Backup Panel -->
            <div id="backup" class="settings-panel">
                <div class="panel-header">
                    <i class="fas fa-database"></i>
                    <div>
                        <h3>Backup & Restore</h3>
                        <p>Create and manage database backups</p>
                    </div>
                </div>
                
                <form action="" method="POST">
                    <input type="hidden" name="create_backup" value="1">
                    
                    <div style="text-align: center; padding: 40px; background: var(--dark-hover); border-radius: var(--radius); margin-bottom: 30px;">
                        <i class="fas fa-database" style="font-size: 3rem; color: var(--primary); margin-bottom: 20px;"></i>
                        <h4 style="margin-bottom: 15px;">Create New Backup</h4>
                        <p style="color: var(--text-secondary); margin-bottom: 25px;">Create a complete backup of your database including all tables and data.</p>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-download"></i>
                            Create Backup Now
                        </button>
                    </div>
                </form>
                
                <?php if (!empty($backups)): ?>
                <div class="backup-list">
                    <h4 style="margin-bottom: 20px;">Available Backups</h4>
                    
                    <?php foreach ($backups as $backup): ?>
                    <div class="backup-item">
                        <div class="backup-info">
                            <div class="backup-icon">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="backup-details">
                                <h4><?php echo $backup['name']; ?></h4>
                                <p>
                                    <i class="fas fa-calendar"></i> <?php echo $backup['date']; ?> &nbsp; | &nbsp;
                                    <i class="fas fa-hdd"></i> <?php echo round($backup['size'] / 1024, 2); ?> KB
                                </p>
                            </div>
                        </div>
                        <div class="backup-actions">
                            <a href="../backups/<?php echo $backup['name']; ?>" download class="backup-btn download">
                                <i class="fas fa-download"></i>
                                Download
                            </a>
                            <button class="backup-btn restore" onclick="restoreBackup('<?php echo $backup['name']; ?>')">
                                <i class="fas fa-undo"></i>
                                Restore
                            </button>
                            <button class="backup-btn delete" onclick="deleteBackup('<?php echo $backup['name']; ?>')">
                                <i class="fas fa-trash"></i>
                                Delete
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Mobile menu toggle
        const menuToggle = document.querySelector('.menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (menuToggle) {
            menuToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                sidebar.classList.toggle('active');
            });
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 992 && 
                !sidebar.contains(e.target) && 
                !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
        
        // Tab switching
        function showTab(tabId) {
            // Hide all panels
            document.querySelectorAll('.settings-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab-btn').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected panel
            document.getElementById(tabId).classList.add('active');
            
            // Add active class to clicked tab
            event.currentTarget.classList.add('active');
        }
        
        // Toggle payment fields
        document.getElementById('razorpay_enabled')?.addEventListener('change', function() {
            document.getElementById('razorpay_fields').style.display = this.checked ? 'block' : 'none';
        });
        
        document.getElementById('paypal_enabled')?.addEventListener('change', function() {
            document.getElementById('paypal_fields').style.display = this.checked ? 'block' : 'none';
        });
        
        document.getElementById('recaptcha_enabled')?.addEventListener('change', function() {
            document.getElementById('recaptcha_fields').style.display = this.checked ? 'block' : 'none';
        });
        
        // Image upload preview
        document.getElementById('logo-upload')?.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('#appearance .image-preview');
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Logo">';
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        document.getElementById('favicon-upload')?.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelectorAll('#appearance .image-preview')[1];
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Favicon">';
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        // Color picker preview
        document.querySelector('input[name="theme_color"]')?.addEventListener('input', function(e) {
            document.querySelector('.color-preview').style.background = e.target.value;
        });
        
        // Test email function
        function testEmailSettings() {
            alert('Test email functionality - Would send test email to configured address');
        }
        
        // Backup functions
        function restoreBackup(filename) {
            if (confirm('Are you sure you want to restore this backup? Current data will be overwritten.')) {
                alert('Restore functionality - Would restore ' + filename);
            }
        }
        
        function deleteBackup(filename) {
            if (confirm('Are you sure you want to delete this backup? This action cannot be undone.')) {
                alert('Delete functionality - Would delete ' + filename);
            }
        }
        
        // Auto-hide alerts
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.style.display = 'none', 300);
            });
        }, 5000);
        
        // Responsive handler
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>