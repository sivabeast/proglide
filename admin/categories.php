<?php
require "includes/admin_db.php";
require "includes/admin_auth.php";

// Handle AJAX request for category details
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $category = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'id' => $category['id'],
            'name' => $category['name'],
            'slug' => $category['slug'],
            'image' => $category['image'],
            'status' => $category['status'],
            'show_on_home' => $category['show_on_home']
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Category not found']);
    }
    exit;
}

// Page title for header
$page_title = "Categories Management";
$page_icon = "bi bi-tags";

// Handle Add Category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $name = trim($_POST['name']);
        $slug = trim($_POST['slug']);
        $status = isset($_POST['status']) ? 1 : 0;
        $show_on_home = isset($_POST['show_on_home']) ? 1 : 0;
        
        // Handle image upload
        $image = "";
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = "../uploads/categories/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            if (in_array($_FILES['image']['type'], $allowed_types)) {
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = 'category_' . time() . '_' . uniqid() . '.' . $extension;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                    $image = $filename;
                }
            }
        }
        
        // No icon field anymore
        $icon = '';
        
        $stmt = $conn->prepare("INSERT INTO categories (name, slug, image, icon, status, show_on_home, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssii", $name, $slug, $image, $icon, $status, $show_on_home);
        
        if ($stmt->execute()) {
            $success_message = "Category added successfully!";
        } else {
            $error_message = "Error adding category: " . $conn->error;
        }
    }
    
    // Handle Edit Category
    if ($_POST['action'] == 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $slug = trim($_POST['slug']);
        $status = isset($_POST['status']) ? 1 : 0;
        $show_on_home = isset($_POST['show_on_home']) ? 1 : 0;
        
        // Get existing image
        $stmt = $conn->prepare("SELECT image FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $category = $result->fetch_assoc();
        $image = $category['image'];
        
        // Handle new image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = "../uploads/categories/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            if (in_array($_FILES['image']['type'], $allowed_types)) {
                // Delete old image
                if ($image && file_exists($upload_dir . $image)) {
                    unlink($upload_dir . $image);
                }
                
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = 'category_' . time() . '_' . uniqid() . '.' . $extension;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                    $image = $filename;
                }
            }
        }
        
        // Keep existing icon (don't change it)
        $stmt = $conn->prepare("UPDATE categories SET name = ?, slug = ?, image = ?, status = ?, show_on_home = ? WHERE id = ?");
        $stmt->bind_param("sssiii", $name, $slug, $image, $status, $show_on_home, $id);
        
        if ($stmt->execute()) {
            $success_message = "Category updated successfully!";
        } else {
            $error_message = "Error updating category: " . $conn->error;
        }
    }
}

// Handle Delete Category
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Get image before deletion
    $stmt = $conn->prepare("SELECT image FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    
    // Delete image file
    if ($category && $category['image']) {
        $image_path = "../uploads/categories/" . $category['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Delete category
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success_message = "Category deleted successfully!";
    } else {
        $error_message = "Error deleting category: " . $conn->error;
    }
}

// Handle Status Toggle
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $stmt = $conn->prepare("UPDATE categories SET status = NOT status WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success_message = "Category status updated!";
    }
}

// Handle Home Show Toggle
if (isset($_GET['toggle_home']) && is_numeric($_GET['toggle_home'])) {
    $id = $_GET['toggle_home'];
    $stmt = $conn->prepare("UPDATE categories SET show_on_home = NOT show_on_home WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success_message = "Home display status updated!";
    }
}

// Get all categories
$categories = $conn->query("SELECT * FROM categories ORDER BY id DESC");

// Generate slug from name
function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Categories Management | PROGLIDE Admin</title>
    
    <!-- Bootstrap Icons Only - No Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
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
            margin-left: var(--sidebar-width);
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

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: var(--transition);
        }

        /* Content */
        .content {
            padding: 30px;
        }

        /* Alerts */
        .alert {
            padding: 12px 18px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
            font-size: 0.9rem;
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

        /* Header Actions */
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-actions h2 {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-actions h2 i {
            color: var(--primary);
        }

        .btn {
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            border: none;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
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

        .btn-danger {
            background: rgba(244, 67, 54, 0.1);
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        .btn-danger:hover {
            background: var(--danger);
            color: white;
        }

        /* Stats Cards - 2 per row on mobile */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--dark-card);
            border-radius: var(--radius);
            padding: 20px;
            border: 1px solid var(--dark-border);
            transition: var(--transition);
        }

        .stat-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            background: var(--primary-light);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            color: var(--primary);
            font-size: 1.3rem;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            background: linear-gradient(135deg, var(--text-primary), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.2;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        /* Categories Grid - 2 per row on mobile */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .category-card {
            background: var(--dark-card);
            border-radius: var(--radius);
            border: 1px solid var(--dark-border);
            overflow: hidden;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }

        .category-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .category-image {
            height: 140px;
            background: var(--dark-hover);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .category-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .category-image i {
            font-size: 2.5rem;
            color: var(--text-muted);
        }

        .category-icon {
            position: absolute;
            bottom: 8px;
            right: 8px;
            width: 32px;
            height: 32px;
            background: var(--dark-card);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            border: 2px solid var(--primary);
            font-size: 0.9rem;
        }

        .category-content {
            padding: 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .category-title h3 {
            font-size: 1.1rem;
            margin-bottom: 4px;
            color: var(--text-primary);
        }

        .category-title p {
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        .category-slug {
            background: var(--dark-hover);
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            font-size: 0.7rem;
            font-family: monospace;
        }

        .category-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            padding: 12px 0;
            border-top: 1px solid var(--dark-border);
            border-bottom: 1px solid var(--dark-border);
            font-size: 0.85rem;
        }

        .category-stat {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-secondary);
        }

        .category-stat i {
            color: var(--primary);
            font-size: 0.9rem;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
        }

        .badge-success {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success);
        }

        .badge-warning {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .badge-danger {
            background: rgba(244, 67, 54, 0.1);
            color: var(--danger);
        }

        .badge-info {
            background: rgba(33, 150, 243, 0.1);
            color: var(--info);
        }

        .category-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
        }

        .action-btn {
            padding: 8px 12px;
            border-radius: var(--radius-sm);
            border: none;
            cursor: pointer;
            transition: var(--transition);
            color: var(--text-secondary);
            background: var(--dark-hover);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.8rem;
            flex: 1;
            justify-content: center;
        }

        .action-btn:hover {
            background: var(--dark-border);
            color: var(--text-primary);
        }

        .action-btn.edit:hover {
            background: var(--info);
            color: white;
        }

        .action-btn.delete:hover {
            background: var(--danger);
            color: white;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--dark-card);
            border-radius: var(--radius);
            padding: 25px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid var(--dark-border);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-header h3 i {
            color: var(--primary);
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.1rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-close:hover {
            color: var(--danger);
        }

        /* Form */
        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .form-group label i {
            color: var(--primary);
            margin-right: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            background: var(--dark-hover);
            border: 1px solid var(--dark-border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--dark-card);
        }

        .form-text {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* Switch */
        .switch-group {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
        }

        .switch-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 22px;
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
            border-radius: 22px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
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
            transform: translateX(22px);
        }

        .switch-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        /* Image Upload */
        .image-upload-box {
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
            font-size: 1.8rem;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .image-upload-box p {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .image-upload-box span {
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        .image-preview {
            display: none;
            position: relative;
            border-radius: var(--radius-sm);
            overflow: hidden;
        }

        .image-preview img {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }

        .image-preview .remove-image {
            position: absolute;
            top: 8px;
            right: 8px;
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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: var(--dark-card);
            border-radius: var(--radius);
            border: 1px solid var(--dark-border);
        }

        .empty-state i {
            font-size: 3.5rem;
            color: var(--text-muted);
            margin-bottom: 15px;
        }

        .empty-state h3 {
            font-size: 1.3rem;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .empty-state p {
            color: var(--text-secondary);
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .header {
                margin-left: 0;
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

            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Mobile - 2 cards per row */
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

            /* Stats - 2 cards per row */
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
                margin-bottom: 10px;
            }

            .stat-value {
                font-size: 1.5rem;
            }

            .stat-label {
                font-size: 0.8rem;
            }

            /* Categories - 2 cards per row */
            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .category-image {
                height: 110px;
            }

            .category-image i {
                font-size: 2rem;
            }

            .category-icon {
                width: 28px;
                height: 28px;
                font-size: 0.8rem;
                bottom: 5px;
                right: 5px;
            }

            .category-content {
                padding: 12px;
            }

            .category-title h3 {
                font-size: 0.95rem;
            }

            .category-title p {
                font-size: 0.7rem;
            }

            .category-slug {
                font-size: 0.6rem;
                padding: 3px 6px;
            }

            .category-stats {
                font-size: 0.75rem;
                gap: 10px;
                padding: 8px 0;
                margin-bottom: 10px;
            }

            .category-stat i {
                font-size: 0.8rem;
            }

            .badge {
                font-size: 0.6rem;
                padding: 3px 6px;
            }

            .category-actions {
                gap: 6px;
            }

            .action-btn {
                padding: 6px 8px;
                font-size: 0.7rem;
            }

            .action-btn i {
                font-size: 0.75rem;
            }
        }

        /* Small Mobile */
        @media (max-width: 480px) {
            .content {
                padding: 15px;
            }

            .header-actions h2 {
                font-size: 1.2rem;
            }

            .btn {
                padding: 8px 15px;
                font-size: 0.85rem;
            }

            .stats-grid {
                gap: 8px;
            }

            .stat-card {
                padding: 12px;
            }

            .stat-icon {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }

            .stat-value {
                font-size: 1.2rem;
            }

            .stat-label {
                font-size: 0.7rem;
            }

            .categories-grid {
                gap: 8px;
            }

            .category-image {
                height: 90px;
            }

            .category-image i {
                font-size: 1.8rem;
            }

            .category-content {
                padding: 10px;
            }

            .category-title h3 {
                font-size: 0.85rem;
            }

            .category-slug {
                font-size: 0.55rem;
            }

            .category-stats {
                font-size: 0.7rem;
                gap: 8px;
            }

            .category-actions {
                flex-direction: column;
                gap: 5px;
            }

            .action-btn {
                width: 100%;
                padding: 6px;
            }
        }

        /* Extra Small Mobile */
        @media (max-width: 360px) {
            .category-title h3 {
                font-size: 0.8rem;
            }

            .category-stats {
                font-size: 0.65rem;
            }

            .badge {
                font-size: 0.55rem;
                padding: 2px 4px;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>   
    <?php include "includes/sidebar.php"; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Content -->
        <div class="content">
            <!-- Alerts -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats -->
            <?php
            $total_categories = $conn->query("SELECT COUNT(*) as total FROM categories")->fetch_assoc()['total'];
            $active_categories = $conn->query("SELECT COUNT(*) as total FROM categories WHERE status = 1")->fetch_assoc()['total'];
            $home_categories = $conn->query("SELECT COUNT(*) as total FROM categories WHERE show_on_home = 1")->fetch_assoc()['total'];
            $total_products_in_categories = $conn->query("SELECT COUNT(DISTINCT category_id) as total FROM products")->fetch_assoc()['total'];
            ?>
            
            <!-- Stats Cards - 2 per row on mobile -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-tags"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_categories; ?></div>
                    <div class="stat-label">Total Categories</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo $active_categories; ?></div>
                    <div class="stat-label">Active</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-house"></i>
                    </div>
                    <div class="stat-value"><?php echo $home_categories; ?></div>
                    <div class="stat-label">On Home</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-box"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_products_in_categories; ?></div>
                    <div class="stat-label">With Products</div>
                </div>
            </div>
            
            <!-- Header Actions -->
            <div class="header-actions">
                <h2><i class="bi bi-tags"></i> Categories</h2>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="bi bi-plus-circle"></i>
                    Add Category
                </button>
            </div>
            
            <!-- Categories Grid - 2 per row on mobile -->
            <?php if ($categories && $categories->num_rows > 0): ?>
                <div class="categories-grid">
                    <?php while($category = $categories->fetch_assoc()): 
                        // Get product count for this category
                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE category_id = ?");
                        $stmt->bind_param("i", $category['id']);
                        $stmt->execute();
                        $product_count = $stmt->get_result()->fetch_assoc()['total'];
                    ?>
                        <div class="category-card">
                            <div class="category-image">
                                <?php if ($category['image'] && file_exists("../uploads/categories/" . $category['image'])): ?>
                                    <img src="../uploads/categories/<?php echo $category['image']; ?>" 
                                         alt="<?php echo htmlspecialchars($category['name']); ?>">
                                <?php else: ?>
                                    <i class="bi bi-tags"></i>
                                <?php endif; ?>
                                
                                <?php if ($category['icon']): ?>
                                    <div class="category-icon">
                                        <i class="<?php echo htmlspecialchars($category['icon']); ?>"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="category-content">
                                <div class="category-header">
                                    <div class="category-title">
                                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                        <p>ID: #<?php echo $category['id']; ?></p>
                                    </div>
                                    <span class="category-slug"><?php echo htmlspecialchars($category['slug']); ?></span>
                                </div>
                                
                                <div class="category-stats">
                                    <div class="category-stat">
                                        <i class="bi bi-box"></i>
                                        <?php echo $product_count; ?>
                                    </div>
                                    <div class="category-stat">
                                        <i class="bi bi-calendar"></i>
                                        <?php echo date('d M', strtotime($category['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap;">
                                    <a href="?toggle_status=<?php echo $category['id']; ?>" 
                                       class="badge <?php echo $category['status'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <i class="bi bi-<?php echo $category['status'] ? 'check-circle' : 'x-circle'; ?>"></i>
                                        <?php echo $category['status'] ? 'Active' : 'Inactive'; ?>
                                    </a>
                                    
                                    <a href="?toggle_home=<?php echo $category['id']; ?>" 
                                       class="badge <?php echo $category['show_on_home'] ? 'badge-warning' : 'badge-info'; ?>">
                                        <i class="bi bi-house"></i>
                                        <?php echo $category['show_on_home'] ? 'Show' : 'Hide'; ?>
                                    </a>
                                </div>
                                
                                <div class="category-actions">
                                    <button onclick="openEditModal(<?php echo $category['id']; ?>)" 
                                            class="action-btn edit">
                                        <i class="bi bi-pencil"></i>
                                        Edit
                                    </button>
                                    <button onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>')" 
                                            class="action-btn delete">
                                        <i class="bi bi-trash"></i>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-tags"></i>
                    <h3>No Categories Found</h3>
                    <p>Get started by creating your first product category</p>
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="bi bi-plus-circle"></i>
                        Add New Category
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Category Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="bi bi-plus-circle"></i> Add Category</h3>
                <button class="modal-close" onclick="closeModal('addModal')">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label><i class="bi bi-tag"></i> Category Name *</label>
                    <input type="text" name="name" class="form-control" required 
                           placeholder="Enter category name" onkeyup="generateSlug(this.value, 'add-slug')">
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-link"></i> Slug *</label>
                    <input type="text" name="slug" id="add-slug" class="form-control" required 
                           placeholder="enter-category-slug">
                    <div class="form-text">URL friendly name. Auto-generated from category name.</div>
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-image"></i> Category Image</label>
                    <div id="add-image-preview" class="image-preview">
                        <img src="" alt="Preview">
                        <button type="button" class="remove-image" onclick="removeCategoryImage('add')">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <div id="add-image-upload" class="image-upload-box" onclick="triggerCategoryUpload('add')">
                        <i class="bi bi-cloud-upload"></i>
                        <p>Upload Category Image</p>
                        <span>Recommended: 400x400px</span>
                        <input type="file" name="image" id="add-file" 
                               accept="image/jpeg,image/png,image/webp" 
                               style="display: none;" onchange="previewCategoryImage(this, 'add')">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Category Settings</label>
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
                                <input type="checkbox" name="show_on_home" checked>
                                <span class="slider"></span>
                            </label>
                            <span class="switch-label">Show on Homepage</span>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions" style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="bi bi-pencil"></i> Edit Category</h3>
                <button class="modal-close" onclick="closeModal('editModal')">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            
            <form action="" method="POST" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-id">
                
                <div class="form-group">
                    <label><i class="bi bi-tag"></i> Category Name *</label>
                    <input type="text" name="name" id="edit-name" class="form-control" required 
                           placeholder="Enter category name" onkeyup="generateSlug(this.value, 'edit-slug')">
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-link"></i> Slug *</label>
                    <input type="text" name="slug" id="edit-slug" class="form-control" required 
                           placeholder="enter-category-slug">
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-image"></i> Category Image</label>
                    <div id="edit-image-preview" class="image-preview">
                        <img src="" alt="Preview">
                        <button type="button" class="remove-image" onclick="removeCategoryImage('edit')">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <div id="edit-image-upload" class="image-upload-box" onclick="triggerCategoryUpload('edit')">
                        <i class="bi bi-cloud-upload"></i>
                        <p>Change Category Image</p>
                        <span>Click to upload new image</span>
                        <input type="file" name="image" id="edit-file" 
                               accept="image/jpeg,image/png,image/webp" 
                               style="display: none;" onchange="previewCategoryImage(this, 'edit')">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Category Settings</label>
                    <div class="switch-group">
                        <div class="switch-item">
                            <label class="switch">
                                <input type="checkbox" name="status" id="edit-status">
                                <span class="slider"></span>
                            </label>
                            <span class="switch-label">Active</span>
                        </div>
                        
                        <div class="switch-item">
                            <label class="switch">
                                <input type="checkbox" name="show_on_home" id="edit-show-home">
                                <span class="slider"></span>
                            </label>
                            <span class="switch-label">Show on Homepage</span>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions" style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="bi bi-exclamation-triangle" style="color: var(--danger);"></i> Delete Category</h3>
                <button class="modal-close" onclick="closeModal('deleteModal')">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteCategoryName"></strong>?</p>
                <p style="margin-top: 10px; font-size: 0.85rem; color: var(--danger);">
                    <i class="bi bi-exclamation-circle"></i>
                    This action cannot be undone. All products in this category will also be deleted.
                </p>
            </div>
            <div class="modal-footer" style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button class="btn btn-secondary" onclick="closeModal('deleteModal')">
                    Cancel
                </button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                    Delete Category
                </a>
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
                sidebar && !sidebar.contains(e.target) && 
                menuToggle && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
        
        // Add active class to current page
        const currentPage = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPage || (currentPage === 'categories.php' && href === 'categories.php')) {
                link.classList.add('active');
            }
        });
        
        // Generate slug from name
        function generateSlug(name, targetId) {
            if (!name) return;
            
            let slug = name.toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
            
            document.getElementById(targetId).value = slug;
        }
        
        // Modal functions
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
            document.getElementById('add-slug').value = '';
            
            // Reset image preview
            document.getElementById('add-image-preview').style.display = 'none';
            document.getElementById('add-image-upload').style.display = 'block';
            document.getElementById('add-file').value = '';
        }
        
        function openEditModal(categoryId) {
            // Fetch category details via AJAX - FIXED URL
            fetch(window.location.pathname + '?id=' + categoryId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit-id').value = data.id;
                        document.getElementById('edit-name').value = data.name;
                        document.getElementById('edit-slug').value = data.slug;
                        
                        // Set status checkboxes
                        document.getElementById('edit-status').checked = data.status == 1;
                        document.getElementById('edit-show-home').checked = data.show_on_home == 1;
                        
                        // Handle image preview
                        if (data.image) {
                            const preview = document.getElementById('edit-image-preview');
                            const uploadBox = document.getElementById('edit-image-upload');
                            preview.querySelector('img').src = '../uploads/categories/' + data.image;
                            preview.style.display = 'block';
                            uploadBox.style.display = 'none';
                        } else {
                            document.getElementById('edit-image-preview').style.display = 'none';
                            document.getElementById('edit-image-upload').style.display = 'block';
                        }
                        
                        document.getElementById('editModal').classList.add('active');
                    } else {
                        alert('Error loading category details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load category details. Check console for details.');
                });
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function confirmDelete(id, name) {
            document.getElementById('deleteCategoryName').textContent = name;
            document.getElementById('confirmDeleteBtn').href = '?delete=' + id;
            document.getElementById('deleteModal').classList.add('active');
        }
        
        // Image upload functions
        function triggerCategoryUpload(type) {
            document.getElementById(type + '-file').click();
        }
        
        function previewCategoryImage(input, type) {
            const preview = document.getElementById(type + '-image-preview');
            const uploadBox = document.getElementById(type + '-image-upload');
            
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
        
        function removeCategoryImage(type) {
            const preview = document.getElementById(type + '-image-preview');
            const uploadBox = document.getElementById(type + '-image-upload');
            const fileInput = document.getElementById(type + '-file');
            
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
        
        // Responsive resize handler
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992 && sidebar) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>