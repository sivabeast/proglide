<?php
require "includes/admin_db.php";
require "includes/admin_auth.php";

// Get all categories for dropdown
$categories = $conn->query("SELECT * FROM categories WHERE status = 1 ORDER BY name");

// Get all brands for dropdown
$brands = $conn->query("SELECT * FROM brands WHERE status = 1 ORDER BY name");

// Initialize variables
$design_name = $model_name = $description = $price = $original_price = "";
$category_id = $material_type_id = $variant_type_id = 0;
$errors = [];
$success = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate and sanitize inputs
    $category_id = (int)$_POST['category_id'];
    $material_type_id = (int)$_POST['material_type_id'];
    $variant_type_id = !empty($_POST['variant_type_id']) ? (int)$_POST['variant_type_id'] : null;
    $design_name = trim($_POST['design_name']);
    $model_name = trim($_POST['model_name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $original_price = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
    $status = isset($_POST['status']) ? 1 : 0;
    $is_popular = isset($_POST['is_popular']) ? 1 : 0;
    
    // Validation
    if ($category_id <= 0) {
        $errors[] = "Please select a category";
    }
    
    if ($material_type_id <= 0) {
        $errors[] = "Please select a material type";
    }
    
    if (empty($design_name) && empty($model_name)) {
        $errors[] = "Please enter either design name or model name";
    }
    
    if ($price <= 0) {
        $errors[] = "Please enter a valid price";
    }
    
    // Get category slug for folder name
    $cat_query = $conn->prepare("SELECT slug FROM categories WHERE id = ?");
    $cat_query->bind_param("i", $category_id);
    $cat_query->execute();
    $cat_result = $cat_query->get_result();
    $category_data = $cat_result->fetch_assoc();
    $category_slug = $category_data['slug'] ?? 'general';
    
    // Create category-specific upload directory
    $upload_dir = "../uploads/products/" . $category_slug . "/";
    
    // Create directory if not exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $image1 = $image2 = $image3 = "";
    
    // Function to handle image upload
    function uploadImage($file, $upload_dir) {
        if ($file['error'] == UPLOAD_ERR_NO_FILE) {
            return "";
        }
        
        if ($file['error'] != UPLOAD_ERR_OK) {
            return ["error" => "Upload failed with error code: " . $file['error']];
        }
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        if (!in_array($file['type'], $allowed_types)) {
            return ["error" => "Only JPG, PNG and WEBP files are allowed"];
        }
        
        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return ["error" => "File size must be less than 5MB"];
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $filename;
        } else {
            return ["error" => "Failed to move uploaded file"];
        }
    }
    
    // Upload images
    if (isset($_FILES['image1'])) {
        $result = uploadImage($_FILES['image1'], $upload_dir);
        if (is_array($result)) {
            $errors[] = "Image 1: " . $result['error'];
        } else {
            $image1 = $result;
        }
    }
    
    if (isset($_FILES['image2'])) {
        $result = uploadImage($_FILES['image2'], $upload_dir);
        if (is_array($result)) {
            $errors[] = "Image 2: " . $result['error'];
        } else {
            $image2 = $result;
        }
    }
    
    if (isset($_FILES['image3'])) {
        $result = uploadImage($_FILES['image3'], $upload_dir);
        if (is_array($result)) {
            $errors[] = "Image 3: " . $result['error'];
        } else {
            $image3 = $result;
        }
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        // Store only filename in database, not the full path
        $stmt = $conn->prepare("
            INSERT INTO products (
                category_id, material_type_id, variant_type_id, 
                model_name, design_name, description, 
                price, original_price, 
                image1, image2, image3, 
                status, is_popular, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        // Handle null values properly
        $variant_type_id = ($variant_type_id === 0 || $variant_type_id === null) ? null : $variant_type_id;
        $original_price = ($original_price === 0.0 || $original_price === null) ? null : $original_price;
        $model_name = empty($model_name) ? null : $model_name;
        $design_name = empty($design_name) ? null : $design_name;
        $description = empty($description) ? null : $description;
        $image2 = empty($image2) ? null : $image2;
        $image3 = empty($image3) ? null : $image3;
        
        $stmt->bind_param(
            "iiisssddsssii",
            $category_id,
            $material_type_id,
            $variant_type_id,
            $model_name,
            $design_name,
            $description,
            $price,
            $original_price,
            $image1,
            $image2,
            $image3,
            $status,
            $is_popular
        );
        
        if ($stmt->execute()) {
            $success = "Product added successfully! Images saved in: " . $category_slug . " folder";
            // Clear form
            $design_name = $model_name = $description = $price = $original_price = "";
            $category_id = $material_type_id = $variant_type_id = 0;
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
    }
}

// AJAX handlers for dynamic dropdowns
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_materials') {
    $cat_id = (int)$_GET['category_id'];
    $query = "SELECT id, name FROM material_types WHERE category_id = ? AND status = 1 ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $cat_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $options = '<option value="">Select Material Type</option>';
    while ($row = $result->fetch_assoc()) {
        $options .= '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
    }
    echo $options;
    exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_variants') {
    $cat_id = (int)$_GET['category_id'];
    $query = "SELECT id, name FROM variant_types WHERE category_id = ? AND status = 1 ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $cat_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $options = '<option value="">Select Variant Type (Optional)</option>';
    while ($row = $result->fetch_assoc()) {
        $options .= '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
    }
    echo $options;
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | PROGLIDE</title>
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

        /* Form */
        .form-card {
            background: var(--dark-card);
            border-radius: var(--radius);
            border: 1px solid var(--dark-border);
            padding: 30px;
            margin-bottom: 30px;
        }

        .form-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
            font-size: 1.3rem;
            color: var(--text-primary);
        }

        .form-title i {
            color: var(--primary);
        }

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

        .required::after {
            content: "*";
            color: var(--danger);
            margin-left: 5px;
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
            min-height: 120px;
        }

        .form-text {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 5px;
        }

        /* Image Upload */
        .image-upload-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 10px;
        }

        .image-upload-wrapper {
            position: relative;
        }

        .image-upload-box {
            background: var(--dark-hover);
            border: 2px dashed var(--dark-border);
            border-radius: var(--radius-sm);
            padding: 20px;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
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
            margin-bottom: 5px;
        }

        .image-upload-box span {
            color: var(--text-muted);
            font-size: 0.75rem;
        }

        .image-preview {
            display: none;
            position: relative;
            border-radius: var(--radius-sm);
            overflow: hidden;
        }

        .image-preview img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .image-preview .remove-image {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .image-preview .remove-image:hover {
            background: var(--danger);
        }

        /* Switch Toggle */
        .switch-group {
            display: flex;
            align-items: center;
            gap: 20px;
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

        .switch-label {
            font-size: 0.95rem;
            color: var(--text-secondary);
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

        .btn {
            padding: 12px 30px;
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

        /* Loading Spinner */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid var(--dark-border);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Category Folder Info */
        .folder-info {
            background: var(--primary-light);
            border: 1px solid var(--primary);
            border-radius: var(--radius-sm);
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary);
        }

        .folder-info i {
            font-size: 1.5rem;
        }

        .folder-info span {
            color: var(--text-primary);
            font-weight: 600;
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

            .image-upload-grid {
                grid-template-columns: 1fr;
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

            .form-card {
                padding: 20px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .content {
                padding: 15px;
            }

            .form-card {
                padding: 15px;
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
                <a href="add_product.php" class="nav-link active">
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
                <a href="settings.php" class="nav-link">
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
                <h2><i class="fas fa-plus-circle"></i> Add Product</h2>
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
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Please fix the following errors:</strong>
                        <ul style="margin-top: 10px; margin-left: 20px;">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <!-- Category Folder Info -->
            <div class="folder-info" id="folderInfo" style="display: none;">
                <i class="fas fa-folder-open"></i>
                <div>
                    Images will be saved in: <span id="folderPath">uploads/products/</span>
                </div>
            </div>
            
            <!-- Add Product Form -->
            <div class="form-card">
                <div class="form-title">
                    <i class="fas fa-box"></i>
                    <h2>Product Information</h2>
                </div>
                
                <form action="" method="POST" enctype="multipart/form-data" id="addProductForm">
                    <div class="form-grid">
                        <!-- Category -->
                        <div class="form-group">
                            <label class="required">
                                <i class="fas fa-layer-group"></i>
                                Category
                            </label>
                            <select name="category_id" id="category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php 
                                $categories->data_seek(0);
                                while($cat = $categories->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                        data-slug="<?php echo $cat['slug']; ?>"
                                        <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <!-- Material Type -->
                        <div class="form-group">
                            <label class="required">
                                <i class="fas fa-cube"></i>
                                Material Type
                            </label>
                            <select name="material_type_id" id="material_type_id" class="form-control" required>
                                <option value="">Select Category First</option>
                            </select>
                        </div>
                        
                        <!-- Variant Type -->
                        <div class="form-group">
                            <label>
                                <i class="fas fa-paint-bucket"></i>
                                Variant Type (Optional)
                            </label>
                            <select name="variant_type_id" id="variant_type_id" class="form-control">
                                <option value="">Select Category First</option>
                            </select>
                        </div>
                        
                        <!-- Design Name -->
                        <div class="form-group">
                            <label>
                                <i class="fas fa-pencil-alt"></i>
                                Design Name
                            </label>
                            <input type="text" name="design_name" class="form-control" 
                                   placeholder="Enter design name" 
                                   value="<?php echo htmlspecialchars($design_name); ?>">
                            <div class="form-text">Leave blank if using model name only</div>
                        </div>
                        
                        <!-- Model Name -->
                        <div class="form-group">
                            <label>
                                <i class="fas fa-tag"></i>
                                Model Name
                            </label>
                            <input type="text" name="model_name" class="form-control" 
                                   placeholder="Enter model name" 
                                   value="<?php echo htmlspecialchars($model_name); ?>">
                            <div class="form-text">Leave blank if using design name only</div>
                        </div>
                        
                        <!-- Price -->
                        <div class="form-group">
                            <label class="required">
                                <i class="fas fa-rupee-sign"></i>
                                Selling Price
                            </label>
                            <input type="number" name="price" class="form-control" 
                                   placeholder="0.00" step="0.01" min="0" 
                                   value="<?php echo $price > 0 ? $price : ''; ?>" required>
                        </div>
                        
                        <!-- Original Price -->
                        <div class="form-group">
                            <label>
                                <i class="fas fa-tag"></i>
                                Original Price (MRP)
                            </label>
                            <input type="number" name="original_price" class="form-control" 
                                   placeholder="0.00" step="0.01" min="0" 
                                   value="<?php echo $original_price > 0 ? $original_price : ''; ?>">
                            <div class="form-text">Show discount by setting higher than selling price</div>
                        </div>
                        
                        <!-- Full width for description -->
                        <div class="form-group full-width">
                            <label>
                                <i class="fas fa-align-left"></i>
                                Description
                            </label>
                            <textarea name="description" class="form-control" 
                                      placeholder="Enter product description..."><?php echo htmlspecialchars($description); ?></textarea>
                        </div>
                        
                        <!-- Image Upload Section -->
                        <div class="form-group full-width">
                            <label>
                                <i class="fas fa-images"></i>
                                Product Images
                            </label>
                            <div class="image-upload-grid">
                                <!-- Image 1 -->
                                <div class="image-upload-wrapper">
                                    <div id="image1-preview" class="image-preview">
                                        <img src="" alt="Preview">
                                        <button type="button" class="remove-image" onclick="removeImage(1)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div id="image1-upload" class="image-upload-box" onclick="triggerUpload(1)">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Upload Main Image</p>
                                        <span>Recommended: 800x800px</span>
                                        <input type="file" name="image1" id="file1" 
                                               accept="image/jpeg,image/png,image/webp" 
                                               style="display: none;" onchange="previewImage(this, 1)">
                                    </div>
                                </div>
                                
                                <!-- Image 2 -->
                                <div class="image-upload-wrapper">
                                    <div id="image2-preview" class="image-preview">
                                        <img src="" alt="Preview">
                                        <button type="button" class="remove-image" onclick="removeImage(2)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div id="image2-upload" class="image-upload-box" onclick="triggerUpload(2)">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Upload Image 2</p>
                                        <span>Optional</span>
                                        <input type="file" name="image2" id="file2" 
                                               accept="image/jpeg,image/png,image/webp" 
                                               style="display: none;" onchange="previewImage(this, 2)">
                                    </div>
                                </div>
                                
                                <!-- Image 3 -->
                                <div class="image-upload-wrapper">
                                    <div id="image3-preview" class="image-preview">
                                        <img src="" alt="Preview">
                                        <button type="button" class="remove-image" onclick="removeImage(3)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div id="image3-upload" class="image-upload-box" onclick="triggerUpload(3)">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Upload Image 3</p>
                                        <span>Optional</span>
                                        <input type="file" name="image3" id="file3" 
                                               accept="image/jpeg,image/png,image/webp" 
                                               style="display: none;" onchange="previewImage(this, 3)">
                                    </div>
                                </div>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i>
                                Supported formats: JPG, PNG, WEBP. Max size: 5MB per image.
                                <br>
                                <i class="fas fa-folder"></i>
                                Images will be automatically organized by category folder.
                            </div>
                        </div>
                        
                        <!-- Status and Popular -->
                        <div class="form-group full-width">
                            <label>Product Settings</label>
                            <div class="switch-group">
                                <div class="switch-item">
                                    <label class="switch">
                                        <input type="checkbox" name="status" checked>
                                        <span class="slider"></span>
                                    </label>
                                    <span class="switch-label">Active</span>
                                </div>
                                
                                <div class="switch-item">
                                    <label class="switch">
                                        <input type="checkbox" name="is_popular">
                                        <span class="slider"></span>
                                    </label>
                                    <span class="switch-label">Mark as Popular</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="products.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Product
                        </button>
                    </div>
                </form>
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
        
        // Add active class to current page
        const currentPage = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPage) {
                link.classList.add('active');
            }
        });
        
        // Category change - load materials and variants
        document.getElementById('category_id').addEventListener('change', function() {
            const categoryId = this.value;
            const materialSelect = document.getElementById('material_type_id');
            const variantSelect = document.getElementById('variant_type_id');
            const folderInfo = document.getElementById('folderInfo');
            const folderPath = document.getElementById('folderPath');
            
            // Get selected option's slug
            const selectedOption = this.options[this.selectedIndex];
            const slug = selectedOption.getAttribute('data-slug');
            
            if (categoryId) {
                // Show folder info
                folderInfo.style.display = 'flex';
                folderPath.textContent = 'uploads/products/' + slug + '/';
                
                // Show loading
                materialSelect.innerHTML = '<option value="">Loading...</option>';
                variantSelect.innerHTML = '<option value="">Loading...</option>';
                
                // Load materials
                fetch('add_product.php?ajax=get_materials&category_id=' + categoryId)
                    .then(response => response.text())
                    .then(html => {
                        materialSelect.innerHTML = html;
                    });
                
                // Load variants
                fetch('add_product.php?ajax=get_variants&category_id=' + categoryId)
                    .then(response => response.text())
                    .then(html => {
                        variantSelect.innerHTML = html;
                    });
            } else {
                folderInfo.style.display = 'none';
                materialSelect.innerHTML = '<option value="">Select Category First</option>';
                variantSelect.innerHTML = '<option value="">Select Category First</option>';
            }
        });
        
        // Trigger category change on page load if category is selected
        window.addEventListener('load', function() {
            const categorySelect = document.getElementById('category_id');
            if (categorySelect.value) {
                categorySelect.dispatchEvent(new Event('change'));
            }
        });
        
        // Image upload preview
        function triggerUpload(imageNumber) {
            document.getElementById('file' + imageNumber).click();
        }
        
        function previewImage(input, imageNumber) {
            const preview = document.getElementById('image' + imageNumber + '-preview');
            const uploadBox = document.getElementById('image' + imageNumber + '-upload');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.querySelector('img').src = e.target.result;
                    preview.style.display = 'block';
                    uploadBox.style.display = 'none';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function removeImage(imageNumber) {
            const preview = document.getElementById('image' + imageNumber + '-preview');
            const uploadBox = document.getElementById('image' + imageNumber + '-upload');
            const fileInput = document.getElementById('file' + imageNumber);
            
            preview.style.display = 'none';
            uploadBox.style.display = 'block';
            fileInput.value = '';
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 300);
            });
        }, 5000);
        
        // Form validation
        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            const category = document.getElementById('category_id').value;
            const material = document.getElementById('material_type_id').value;
            const designName = document.querySelector('input[name="design_name"]').value.trim();
            const modelName = document.querySelector('input[name="model_name"]').value.trim();
            const price = document.querySelector('input[name="price"]').value;
            
            if (!category) {
                alert('Please select a category');
                e.preventDefault();
                return;
            }
            
            if (!material || material === '') {
                alert('Please select a material type');
                e.preventDefault();
                return;
            }
            
            if (!designName && !modelName) {
                alert('Please enter either design name or model name');
                e.preventDefault();
                return;
            }
            
            if (!price || price <= 0) {
                alert('Please enter a valid price');
                e.preventDefault();
                return;
            }
        });
        
        // Responsive resize handler
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>