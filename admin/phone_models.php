<?php
require "includes/admin_db.php";
require "includes/admin_auth.php";

// Get all active brands for dropdown
$brands = $conn->query("SELECT * FROM brands WHERE status = 1 ORDER BY name");

// Handle Add Phone Model
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $brand_id = (int)$_POST['brand_id'];
        $model_name = trim($_POST['model_name']);
        $status = isset($_POST['status']) ? 1 : 0;
        
        // Check if model already exists for this brand
        $check = $conn->prepare("SELECT id FROM phone_models WHERE brand_id = ? AND model_name = ?");
        $check->bind_param("is", $brand_id, $model_name);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "This model already exists for the selected brand!";
        } else {
            $stmt = $conn->prepare("INSERT INTO phone_models (brand_id, model_name, status) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $brand_id, $model_name, $status);
            
            if ($stmt->execute()) {
                $success_message = "Phone model added successfully!";
            } else {
                $error_message = "Error adding phone model: " . $conn->error;
            }
        }
    }
    
    // Handle Edit Phone Model
    if ($_POST['action'] == 'edit') {
        $id = (int)$_POST['id'];
        $brand_id = (int)$_POST['brand_id'];
        $model_name = trim($_POST['model_name']);
        $status = isset($_POST['status']) ? 1 : 0;
        
        // Check if model already exists for this brand (excluding current)
        $check = $conn->prepare("SELECT id FROM phone_models WHERE brand_id = ? AND model_name = ? AND id != ?");
        $check->bind_param("isi", $brand_id, $model_name, $id);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "This model already exists for the selected brand!";
        } else {
            $stmt = $conn->prepare("UPDATE phone_models SET brand_id = ?, model_name = ?, status = ? WHERE id = ?");
            $stmt->bind_param("isii", $brand_id, $model_name, $status, $id);
            
            if ($stmt->execute()) {
                $success_message = "Phone model updated successfully!";
            } else {
                $error_message = "Error updating phone model: " . $conn->error;
            }
        }
    }
    
    // Handle Bulk Add
    if ($_POST['action'] == 'bulk_add') {
        $brand_id = (int)$_POST['brand_id'];
        $model_list = trim($_POST['model_list']);
        $status = isset($_POST['status']) ? 1 : 0;
        
        // Split by new line and clean up
        $models = explode("\n", $model_list);
        $success_count = 0;
        $error_count = 0;
        $existing_models = [];
        
        foreach ($models as $model) {
            $model = trim($model);
            if (empty($model)) continue;
            
            // Check if exists
            $check = $conn->prepare("SELECT id FROM phone_models WHERE brand_id = ? AND model_name = ?");
            $check->bind_param("is", $brand_id, $model);
            $check->execute();
            $result = $check->get_result();
            
            if ($result->num_rows == 0) {
                $stmt = $conn->prepare("INSERT INTO phone_models (brand_id, model_name, status) VALUES (?, ?, ?)");
                $stmt->bind_param("isi", $brand_id, $model, $status);
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            } else {
                $existing_models[] = $model;
            }
        }
        
        $success_message = "Added $success_count models successfully!";
        if ($error_count > 0) {
            $success_message .= " Failed: $error_count models.";
        }
        if (!empty($existing_models)) {
            $success_message .= " Skipped existing: " . implode(", ", array_slice($existing_models, 0, 5));
            if (count($existing_models) > 5) {
                $success_message .= " and " . (count($existing_models) - 5) . " more";
            }
        }
    }
}

// Handle Delete Phone Model
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if model is used in cart
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cart WHERE phone_model_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $cart_count = $stmt->get_result()->fetch_assoc()['total'];
    
    // Check if model is in order items
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM order_items WHERE phone_model_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $order_count = $stmt->get_result()->fetch_assoc()['total'];
    
    $total_usage = $cart_count + $order_count;
    
    if ($total_usage > 0) {
        $error_message = "Cannot delete model. It is used in $total_usage cart(s) or order(s).";
    } else {
        $stmt = $conn->prepare("DELETE FROM phone_models WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_message = "Phone model deleted successfully!";
        } else {
            $error_message = "Error deleting phone model: " . $conn->error;
        }
    }
}

// Handle Status Toggle
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $stmt = $conn->prepare("UPDATE phone_models SET status = NOT status WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success_message = "Model status updated!";
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Search and filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$brand_filter = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$where_conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "pm.model_name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if ($brand_filter > 0) {
    $where_conditions[] = "pm.brand_id = ?";
    $params[] = $brand_filter;
    $types .= "i";
}

if ($status_filter !== '') {
    $where_conditions[] = "pm.status = ?";
    $params[] = $status_filter;
    $types .= "i";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM phone_models pm $where_clause";
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_models = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_models / $limit);

// Get phone models with brand details
$query = "SELECT pm.*, b.name as brand_name, b.status as brand_status 
          FROM phone_models pm 
          JOIN brands b ON pm.brand_id = b.id 
          $where_clause 
          ORDER BY b.name ASC, pm.model_name ASC 
          LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$models = $stmt->get_result();

// Get brand for filter (reset pointer)
$brands_filter = $conn->query("SELECT * FROM brands ORDER BY name");

// Get counts for stats
$total_models_count = $conn->query("SELECT COUNT(*) as total FROM phone_models")->fetch_assoc()['total'];
$active_models_count = $conn->query("SELECT COUNT(*) as total FROM phone_models WHERE status = 1")->fetch_assoc()['total'];
$total_brands_with_models = $conn->query("SELECT COUNT(DISTINCT brand_id) as total FROM phone_models")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Models Management | PROGLIDE</title>
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

        .btn-info {
            background: rgba(33, 150, 243, 0.1);
            color: var(--info);
            border: 1px solid var(--info);
        }

        .btn-info:hover {
            background: var(--info);
            color: white;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--dark-card);
            border-radius: var(--radius);
            padding: 25px;
            border: 1px solid var(--dark-border);
            transition: var(--transition);
        }

        .stat-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-light);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
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
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Filters */
        .filters-card {
            background: var(--dark-card);
            border-radius: var(--radius);
            border: 1px solid var(--dark-border);
            padding: 25px;
            margin-bottom: 30px;
        }

        .filters-form {
            display: grid;
            grid-template-columns: 1fr 200px 150px auto;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group label i {
            color: var(--primary);
            margin-right: 5px;
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

        /* Table */
        .table-card {
            background: var(--dark-card);
            border-radius: var(--radius);
            border: 1px solid var(--dark-border);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 20px;
            background: var(--dark-hover);
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--dark-border);
            color: var(--text-secondary);
            font-size: 0.95rem;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: var(--dark-hover);
        }

        .brand-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-icon {
            width: 35px;
            height: 35px;
            background: var(--primary-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .brand-details h4 {
            color: var(--text-primary);
            font-size: 1rem;
            margin-bottom: 3px;
        }

        .brand-details p {
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        .model-name {
            font-weight: 600;
            color: var(--text-primary);
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-success {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success);
        }

        .badge-danger {
            background: rgba(244, 67, 54, 0.1);
            color: var(--danger);
        }

        .badge-warning {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .badge-info {
            background: rgba(33, 150, 243, 0.1);
            color: var(--info);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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
            font-size: 0.85rem;
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

        .action-btn.status:hover {
            background: var(--success);
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
            overflow-y: auto;
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--dark-card);
            border-radius: var(--radius);
            padding: 30px;
            max-width: 600px;
            width: 100%;
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
            margin-bottom: 25px;
        }

        .modal-header h3 {
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header h3 i {
            color: var(--primary);
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.2rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-close:hover {
            color: var(--danger);
        }

        .modal-body {
            margin-bottom: 25px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .form-group label i {
            color: var(--primary);
            margin-right: 8px;
        }

        .form-group label.required::after {
            content: "*";
            color: var(--danger);
            margin-left: 5px;
        }

        .form-control {
            width: 100%;
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

        textarea.form-control {
            resize: vertical;
            min-height: 150px;
            font-family: monospace;
        }

        .form-text {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 5px;
        }

        /* Switch */
        .switch-group {
            display: flex;
            gap: 20px;
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

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .page-item {
            list-style: none;
        }

        .page-link {
            padding: 10px 15px;
            background: var(--dark-card);
            border: 1px solid var(--dark-border);
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .page-link:hover,
        .page-item.active .page-link {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .page-item.disabled .page-link {
            opacity: 0.5;
            pointer-events: none;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--dark-card);
            border-radius: var(--radius);
            border: 1px solid var(--dark-border);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--text-primary);
        }

        .empty-state p {
            color: var(--text-secondary);
            margin-bottom: 25px;
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

            .filters-form {
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .content {
                padding: 15px;
            }

            .brand-info {
                flex-direction: column;
                text-align: center;
            }

            .switch-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
   <?php include "includes/header.php"; ?>   
<?php include "includes/sidebar.php"; ?>
         
        <!-- Content -->
         <div class="main-content">
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
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_models_count; ?></div>
                    <div class="stat-label">Total Models</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo $active_models_count; ?></div>
                    <div class="stat-label">Active Models</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tag"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_brands_with_models; ?></div>
                    <div class="stat-label">Brands with Models</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_models_count > 0 ? round(($active_models_count / $total_models_count) * 100) : 0; ?>%</div>
                    <div class="stat-label">Active Rate</div>
                </div>
            </div>
            
            <!-- Header Actions -->
            <div class="header-actions">
                <h2><i class="fas fa-mobile-alt"></i> Manage Phone Models</h2>
                <div style="display: flex; gap: 15px;">
                    <button class="btn btn-success" onclick="openBulkAddModal()">
                        <i class="fas fa-tasks"></i>
                        Bulk Add
                    </button>
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus-circle"></i>
                        Add New Model
                    </button>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters-card">
                <form method="GET" action="" class="filters-form">
                    <div class="form-group">
                        <label><i class="fas fa-search"></i> Search Model</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by model name..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Brand</label>
                        <select name="brand_id" class="form-control">
                            <option value="">All Brands</option>
                            <?php 
                            $brands_filter->data_seek(0);
                            while($brand = $brands_filter->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $brand['id']; ?>" 
                                    <?php echo $brand_filter == $brand['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($brand['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-flag"></i> Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="display: flex; flex-direction: row; gap: 10px;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-filter"></i>
                            Apply
                        </button>
                        <a href="phone_models.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Clear
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Models Table -->
            <div class="table-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Brand</th>
                                <th>Model Name</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($models && $models->num_rows > 0): ?>
                                <?php while($model = $models->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="brand-info">
                                                <div class="brand-icon">
                                                    <i class="fas fa-mobile-alt"></i>
                                                </div>
                                                <div class="brand-details">
                                                    <h4><?php echo htmlspecialchars($model['brand_name']); ?></h4>
                                                    <p>ID: #<?php echo $model['brand_id']; ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="model-name"><?php echo htmlspecialchars($model['model_name']); ?></span>
                                        </td>
                                        <td>
                                            <a href="?toggle_status=<?php echo $model['id']; ?>&<?php echo http_build_query($_GET); ?>" 
                                               class="badge <?php echo $model['status'] ? 'badge-success' : 'badge-danger'; ?>">
                                                <i class="fas fa-<?php echo $model['status'] ? 'check-circle' : 'times-circle'; ?>"></i>
                                                <?php echo $model['status'] ? 'Active' : 'Inactive'; ?>
                                            </a>
                                            <?php if (!$model['brand_status']): ?>
                                                <span class="badge badge-warning" style="margin-left: 5px;">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    Brand Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="openEditModal(<?php echo $model['id']; ?>, <?php echo $model['brand_id']; ?>, '<?php echo addslashes($model['model_name']); ?>', <?php echo $model['status']; ?>)" 
                                                        class="action-btn edit">
                                                    <i class="fas fa-edit"></i>
                                                    Edit
                                                </button>
                                                <button onclick="confirmDelete(<?php echo $model['id']; ?>, '<?php echo addslashes($model['model_name']); ?>')" 
                                                        class="action-btn delete">
                                                    <i class="fas fa-trash"></i>
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 60px;">
                                        <i class="fas fa-mobile-alt" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 15px;"></i>
                                        <h3 style="color: var(--text-primary); margin-bottom: 10px;">No Phone Models Found</h3>
                                        <p style="color: var(--text-muted); margin-bottom: 20px;">Add your first phone model to get started</p>
                                        <button class="btn btn-primary" onclick="openAddModal()">
                                            <i class="fas fa-plus-circle"></i>
                                            Add New Model
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <ul class="pagination">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a href="?<?php 
                            $params = $_GET;
                            $params['page'] = $page - 1;
                            echo http_build_query($params);
                        ?>" class="page-link">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a href="?<?php 
                                $params = $_GET;
                                $params['page'] = $i;
                                echo http_build_query($params);
                            ?>" class="page-link"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a href="?<?php 
                            $params = $_GET;
                            $params['page'] = $page + 1;
                            echo http_build_query($params);
                        ?>" class="page-link">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Model Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Add Phone Model</h3>
                <button class="modal-close" onclick="closeModal('addModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form action="" method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label class="required"><i class="fas fa-tag"></i> Brand</label>
                    <select name="brand_id" class="form-control" required>
                        <option value="">Select Brand</option>
                        <?php 
                        $brands->data_seek(0);
                        while($brand = $brands->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $brand['id']; ?>" 
                                <?php echo isset($_GET['brand_id']) && $_GET['brand_id'] == $brand['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($brand['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="required"><i class="fas fa-mobile-alt"></i> Model Name</label>
                    <input type="text" name="model_name" class="form-control" required 
                           placeholder="e.g., iPhone 14 Pro Max, Galaxy S23 Ultra">
                    <div class="form-text">Enter the exact model name as it should appear</div>
                </div>
                
                <div class="form-group">
                    <label>Model Status</label>
                    <div class="switch-group">
                        <div class="switch-item">
                            <label class="switch">
                                <input type="checkbox" name="status" checked>
                                <span class="slider"></span>
                            </label>
                            <span class="switch-label">Active</span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save Model
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Model Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Phone Model</h3>
                <button class="modal-close" onclick="closeModal('editModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form action="" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-id">
                
                <div class="form-group">
                    <label class="required"><i class="fas fa-tag"></i> Brand</label>
                    <select name="brand_id" id="edit-brand-id" class="form-control" required>
                        <option value="">Select Brand</option>
                        <?php 
                        $brands->data_seek(0);
                        while($brand = $brands->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $brand['id']; ?>">
                                <?php echo htmlspecialchars($brand['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="required"><i class="fas fa-mobile-alt"></i> Model Name</label>
                    <input type="text" name="model_name" id="edit-model-name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Model Status</label>
                    <div class="switch-group">
                        <div class="switch-item">
                            <label class="switch">
                                <input type="checkbox" name="status" id="edit-status">
                                <span class="slider"></span>
                            </label>
                            <span class="switch-label">Active</span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Update Model
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bulk Add Modal -->
    <div id="bulkAddModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-tasks"></i> Bulk Add Phone Models</h3>
                <button class="modal-close" onclick="closeModal('bulkAddModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form action="" method="POST">
                <input type="hidden" name="action" value="bulk_add">
                
                <div class="form-group">
                    <label class="required"><i class="fas fa-tag"></i> Brand</label>
                    <select name="brand_id" class="form-control" required>
                        <option value="">Select Brand</option>
                        <?php 
                        $brands->data_seek(0);
                        while($brand = $brands->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $brand['id']; ?>">
                                <?php echo htmlspecialchars($brand['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="required"><i class="fas fa-list"></i> Model Names</label>
                    <textarea name="model_list" class="form-control" required 
                              placeholder="iPhone 14&#10;iPhone 14 Plus&#10;iPhone 14 Pro&#10;iPhone 14 Pro Max&#10;iPhone 13&#10;iPhone 13 Mini"></textarea>
                    <div class="form-text">Enter one model per line. Duplicate models will be skipped.</div>
                </div>
                
                <div class="form-group">
                    <label>Default Status</label>
                    <div class="switch-group">
                        <div class="switch-item">
                            <label class="switch">
                                <input type="checkbox" name="status" checked>
                                <span class="slider"></span>
                            </label>
                            <span class="switch-label">Active</span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('bulkAddModal')">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-tasks"></i>
                        Add All Models
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle" style="color: var(--danger);"></i> Delete Phone Model</h3>
                <button class="modal-close" onclick="closeModal('deleteModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteModelName"></strong>?</p>
                <p style="margin-top: 15px; color: var(--danger);">
                    <i class="fas fa-exclamation-circle"></i>
                    This action cannot be undone. The model will be removed from carts and orders.
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('deleteModal')">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                    <i class="fas fa-trash"></i>
                    Delete Model
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
        
        // Modal functions
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }
        
        function openEditModal(id, brandId, modelName, status) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-brand-id').value = brandId;
            document.getElementById('edit-model-name').value = modelName;
            document.getElementById('edit-status').checked = status == 1;
            document.getElementById('editModal').classList.add('active');
        }
        
        function openBulkAddModal() {
            document.getElementById('bulkAddModal').classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function confirmDelete(id, modelName) {
            document.getElementById('deleteModelName').textContent = modelName;
            document.getElementById('confirmDeleteBtn').href = '?delete=' + id;
            document.getElementById('deleteModal').classList.add('active');
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
            if (window.innerWidth > 992) {
                sidebar.classList.remove('active');
            }
        });
        
        // Set brand filter from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const brandId = urlParams.get('brand_id');
        if (brandId) {
            // Auto open add modal with brand selected
            setTimeout(function() {
                const brandSelect = document.querySelector('#addModal select[name="brand_id"]');
                if (brandSelect) {
                    brandSelect.value = brandId;
                }
            }, 100);
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>