<?php
require "includes/admin_db.php";
require "includes/admin_auth.php";

// Handle user status update
if (isset($_POST['update_status'])) {
    $user_id = (int)$_POST['user_id'];
    $status = (int)$_POST['status'];
    
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $user_id);
    
    if ($stmt->execute()) {
        $success_message = "User #$user_id status updated successfully!";
    } else {
        $error_message = "Error updating user status: " . $conn->error;
    }
}

// Handle delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // Check if user has orders
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $orders_count = $stmt->get_result()->fetch_assoc()['total'];
    
    // Check if user has cart items
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_count = $stmt->get_result()->fetch_assoc()['total'];
    
    // Check if user has wishlist items
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $wishlist_count = $stmt->get_result()->fetch_assoc()['total'];
    
    $total_usage = $orders_count + $cart_count + $wishlist_count;
    
    if ($total_usage > 0) {
        $error_message = "Cannot delete user. They have $orders_count order(s), $cart_count cart item(s), and $wishlist_count wishlist item(s).";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $success_message = "User deleted successfully!";
        } else {
            $error_message = "Error deleting user: " . $conn->error;
        }
    }
}

// Handle add/edit user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $status = isset($_POST['status']) ? 1 : 0;
        
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error_message = "Email already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssi", $name, $email, $phone, $password, $status);
            
            if ($stmt->execute()) {
                $success_message = "User added successfully!";
            } else {
                $error_message = "Error adding user: " . $conn->error;
            }
        }
    }
    
    if ($_POST['action'] == 'edit') {
        $user_id = (int)$_POST['user_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $status = isset($_POST['status']) ? 1 : 0;
        
        // Check if email already exists for another user
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->bind_param("si", $email, $user_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error_message = "Email already exists for another user!";
        } else {
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssssii", $name, $email, $phone, $password, $status, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, status = ? WHERE id = ?");
                $stmt->bind_param("sssii", $name, $email, $phone, $status, $user_id);
            }
            
            if ($stmt->execute()) {
                $success_message = "User updated successfully!";
            } else {
                $error_message = "Error updating user: " . $conn->error;
            }
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Search and filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$where_conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if ($status_filter !== '') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= "i";
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total users count for pagination
$count_query = "SELECT COUNT(*) as total FROM users $where_clause";
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_users = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);

// Get users with statistics
$query = "SELECT u.*,
          (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
          (SELECT SUM(total_amount) FROM orders WHERE user_id = u.id AND payment_status = 'paid') as total_spent,
          (SELECT COUNT(*) FROM cart WHERE user_id = u.id) as cart_count,
          (SELECT COUNT(*) FROM wishlist WHERE user_id = u.id) as wishlist_count,
          (SELECT created_at FROM orders WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as last_order
          FROM users u 
          $where_clause 
          ORDER BY u.id DESC 
          LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();

// Get statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'],
    'active' => $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 1")->fetch_assoc()['total'],
    'inactive' => $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 0")->fetch_assoc()['total'],
    'with_orders' => $conn->query("SELECT COUNT(DISTINCT user_id) as total FROM orders")->fetch_assoc()['total'],
    'total_orders' => $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'],
    'total_revenue' => $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'")->fetch_assoc()['total'] ?? 0,
    'new_today' => $conn->query("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['total'],
    'new_this_month' => $conn->query("SELECT COUNT(*) as total FROM users WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetch_assoc()['total'],
];

// Get user for edit modal
if (isset($_GET['get_user']) && is_numeric($_GET['get_user'])) {
    $user_id = $_GET['get_user'];
    $stmt = $conn->prepare("SELECT id, name, email, phone, status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    header('Content-Type: application/json');
    echo json_encode($user);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management | PROGLIDE</title>
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--dark-card);
            border-radius: var(--radius);
            padding: 20px;
            border: 1px solid var(--dark-border);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
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

        .stat-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .stat-info {
            flex: 1;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            background: linear-gradient(135deg, var(--text-primary), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            background: var(--dark-hover);
            color: var(--text-secondary);
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

        /* Filters */
        .filters-card {
            background: var(--dark-card);
            border-radius: var(--radius);
            border: 1px solid var(--dark-border);
            padding: 25px;
            margin-bottom: 30px;
        }

        .filters-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .filters-header h3 {
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filters-header h3 i {
            color: var(--primary);
        }

        .clear-filters {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }

        .clear-filters:hover {
            color: var(--danger);
        }

        .filters-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
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

        .date-input {
            color-scheme: dark;
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
            padding: 20px;
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            text-transform: uppercase;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 1rem;
            margin-bottom: 3px;
        }

        .user-email {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 3px;
        }

        .user-phone {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-success {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .badge-danger {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }

        .badge-warning {
            background: rgba(255, 193, 7, 0.1);
            color: #FFC107;
        }

        .badge-info {
            background: rgba(33, 150, 243, 0.1);
            color: #2196F3;
        }

        .stat-value-small {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .stat-label-small {
            font-size: 0.7rem;
            color: var(--text-muted);
            display: block;
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

        .action-btn.view:hover {
            background: var(--success);
            color: white;
        }

        /* Status Update Form */
        .status-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .status-select {
            padding: 8px 12px;
            background: var(--dark-hover);
            border: 1px solid var(--dark-border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 0.85rem;
            cursor: pointer;
        }

        .status-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .update-btn {
            padding: 8px 12px;
            background: var(--primary);
            border: none;
            border-radius: var(--radius-sm);
            color: white;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.85rem;
        }

        .update-btn:hover {
            background: var(--primary-dark);
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
            max-width: 500px;
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
            padding-bottom: 15px;
            border-bottom: 1px solid var(--dark-border);
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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
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

        /* User Details Modal */
        .user-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .detail-item {
            background: var(--dark-hover);
            border-radius: var(--radius-sm);
            padding: 15px;
        }

        .detail-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .detail-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .activity-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            border-bottom: 1px solid var(--dark-border);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 35px;
            height: 35px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 3px;
        }

        .activity-time {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .filters-form {
                grid-template-columns: 1fr 1fr;
            }
        }

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

            .status-form {
                flex-direction: column;
                align-items: stretch;
            }

            .user-info {
                flex-direction: column;
                text-align: center;
            }

            .user-details-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .content {
                padding: 15px;
            }

            .modal-content {
                padding: 20px;
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
                <a href="users.php" class="nav-link active">
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
                <h2><i class="fas fa-users"></i> Users Management</h2>
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
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $stats['total']; ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                        <div class="stat-badge">All Time</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $stats['active']; ?></div>
                            <div class="stat-label">Active</div>
                        </div>
                        <div class="stat-badge badge-success"><?php echo $stats['total'] > 0 ? round(($stats['active'] / $stats['total']) * 100) : 0; ?>%</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $stats['inactive']; ?></div>
                            <div class="stat-label">Inactive</div>
                        </div>
                        <div class="stat-badge badge-danger"><?php echo $stats['total'] > 0 ? round(($stats['inactive'] / $stats['total']) * 100) : 0; ?>%</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $stats['with_orders']; ?></div>
                            <div class="stat-label">With Orders</div>
                        </div>
                        <div class="stat-badge badge-info">Customers</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $stats['new_today']; ?></div>
                            <div class="stat-label">New Today</div>
                        </div>
                        <div class="stat-badge badge-warning"><?php echo $stats['new_this_month']; ?> this month</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-info">
                            <div class="stat-value">₹<?php echo number_format($stats['total_revenue'], 2); ?></div>
                            <div class="stat-label">Revenue</div>
                        </div>
                        <div class="stat-badge badge-success"><?php echo $stats['total_orders']; ?> orders</div>
                    </div>
                </div>
            </div>
            
            <!-- Header Actions -->
            <div class="header-actions">
                <h2><i class="fas fa-users"></i> Manage Users</h2>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus-circle"></i>
                    Add New User
                </button>
            </div>
            
            <!-- Filters -->
            <div class="filters-card">
                <div class="filters-header">
                    <h3><i class="fas fa-filter"></i> Filter Users</h3>
                    <a href="users.php" class="clear-filters">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                </div>
                
                <form method="GET" action="" class="filters-form">
                    <div class="form-group">
                        <label><i class="fas fa-search"></i> Search</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Name, Email, Phone..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-flag"></i> Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> From Date</label>
                        <input type="date" name="date_from" class="form-control date-input" 
                               value="<?php echo $date_from; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> To Date</label>
                        <input type="date" name="date_to" class="form-control date-input" 
                               value="<?php echo $date_to; ?>">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-search"></i>
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Users Table -->
            <div class="table-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Orders</th>
                                <th>Spent</th>
                                <th>Joined</th>
                                <th>Last Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users && $users->num_rows > 0): ?>
                                <?php while($user = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                                                </div>
                                                <div class="user-details">
                                                    <span class="user-name"><?php echo htmlspecialchars($user['name'] ?? 'Unknown'); ?></span>
                                                    <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div><i class="fas fa-phone" style="color: var(--primary); width: 20px;"></i> <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></div>
                                                <div style="margin-top: 5px;"><i class="fas fa-envelope" style="color: var(--primary); width: 20px;"></i> <?php echo htmlspecialchars($user['email']); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <form method="POST" class="status-form">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <select name="status" class="status-select">
                                                    <option value="1" <?php echo $user['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                                                    <option value="0" <?php echo $user['status'] == 0 ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                                <button type="submit" name="update_status" class="update-btn">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <span class="stat-value-small"><?php echo $user['order_count']; ?></span>
                                            <span class="stat-label-small">orders</span>
                                            <?php if ($user['cart_count'] > 0): ?>
                                                <div><span class="badge badge-warning"><?php echo $user['cart_count']; ?> in cart</span></div>
                                            <?php endif; ?>
                                            <?php if ($user['wishlist_count'] > 0): ?>
                                                <div><span class="badge badge-info"><?php echo $user['wishlist_count']; ?> in wishlist</span></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="stat-value-small">₹<?php echo number_format($user['total_spent'] ?? 0, 2); ?></span>
                                        </td>
                                        <td>
                                            <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                                            <div class="stat-label-small"><?php echo timeAgo($user['created_at']); ?></div>
                                        </td>
                                        <td>
                                            <?php if ($user['last_order']): ?>
                                                <?php echo date('d M Y', strtotime($user['last_order'])); ?>
                                                <div class="stat-label-small"><?php echo timeAgo($user['last_order']); ?></div>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Never</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="viewUser(<?php echo $user['id']; ?>)" 
                                                        class="action-btn view">
                                                    <i class="fas fa-eye"></i>
                                                    View
                                                </button>
                                                <button onclick="openEditModal(<?php echo $user['id']; ?>)" 
                                                        class="action-btn edit">
                                                    <i class="fas fa-edit"></i>
                                                    Edit
                                                </button>
                                                <button onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo addslashes($user['name'] ?? 'User'); ?>')" 
                                                        class="action-btn delete"
                                                        <?php echo ($user['order_count'] > 0 || $user['cart_count'] > 0 || $user['wishlist_count'] > 0) ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-trash"></i>
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="empty-state">
                                        <i class="fas fa-users"></i>
                                        <h3>No Users Found</h3>
                                        <p>There are no users matching your criteria.</p>
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
    
    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Add New User</h3>
                <button class="modal-close" onclick="closeModal('addUserModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form action="" method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label class="required"><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="required"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="e.g., 9876543210">
                </div>
                
                <div class="form-group">
                    <label class="required"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" class="form-control" required>
                    <div class="form-text">Minimum 6 characters</div>
                </div>
                
                <div class="form-group">
                    <label>Account Status</label>
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
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit User</h3>
                <button class="modal-close" onclick="closeModal('editUserModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form action="" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label class="required"><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="required"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="text" name="phone" id="edit_phone" class="form-control">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> New Password (leave blank to keep current)</label>
                    <input type="password" name="password" class="form-control">
                    <div class="form-text">Minimum 6 characters if changing</div>
                </div>
                
                <div class="form-group">
                    <label>Account Status</label>
                    <div class="switch-group">
                        <div class="switch-item">
                            <label class="switch">
                                <input type="checkbox" name="status" id="edit_status">
                                <span class="slider"></span>
                            </label>
                            <span class="switch-label">Active</span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- View User Modal -->
    <div id="viewUserModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3><i class="fas fa-user"></i> User Details</h3>
                <button class="modal-close" onclick="closeModal('viewUserModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <!-- User details will be loaded here via AJAX -->
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary);"></i>
                    <p style="margin-top: 15px;">Loading user details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('viewUserModal')">
                    <i class="fas fa-times"></i>
                    Close
                </button>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle" style="color: var(--danger);"></i> Delete User</h3>
                <button class="modal-close" onclick="closeModal('deleteModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteUserName"></strong>?</p>
                <p style="margin-top: 15px; color: var(--danger);">
                    <i class="fas fa-exclamation-circle"></i>
                    This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('deleteModal')">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                    <i class="fas fa-trash"></i>
                    Delete User
                </a>
            </div>
        </div>
    </div>
    
    <?php
    // Helper function for time ago
    function timeAgo($timestamp) {
        $time_ago = strtotime($timestamp);
        $current_time = time();
        $time_difference = $current_time - $time_ago;
        $seconds = $time_difference;
        
        $minutes = round($seconds / 60);
        $hours = round($seconds / 3600);
        $days = round($seconds / 86400);
        $weeks = round($seconds / 604800);
        $months = round($seconds / 2629440);
        $years = round($seconds / 31553280);
        
        if ($seconds <= 60) {
            return "Just now";
        } else if ($minutes <= 60) {
            return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
        } else if ($hours <= 24) {
            return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
        } else if ($days <= 7) {
            return ($days == 1) ? "yesterday" : "$days days ago";
        } else if ($weeks <= 4.3) {
            return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
        } else if ($months <= 12) {
            return ($months == 1) ? "1 month ago" : "$months months ago";
        } else {
            return ($years == 1) ? "1 year ago" : "$years years ago";
        }
    }
    ?>
    
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
            document.getElementById('addUserModal').classList.add('active');
        }
        
        function openEditModal(userId) {
            fetch('users.php?get_user=' + userId)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_user_id').value = data.id;
                    document.getElementById('edit_name').value = data.name;
                    document.getElementById('edit_email').value = data.email;
                    document.getElementById('edit_phone').value = data.phone || '';
                    document.getElementById('edit_status').checked = data.status == 1;
                    document.getElementById('editUserModal').classList.add('active');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading user data');
                });
        }
        
        function viewUser(userId) {
            const modal = document.getElementById('viewUserModal');
            const content = document.getElementById('userDetailsContent');
            
            modal.classList.add('active');
            
            // Load user details via AJAX
            fetch('get_user_details.php?id=' + userId)
                .then(response => response.text())
                .then(html => {
                    content.innerHTML = html;
                })
                .catch(error => {
                    content.innerHTML = '<div class="alert alert-danger">Error loading user details.</div>';
                });
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function confirmDelete(userId, userName) {
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('confirmDeleteBtn').href = '?delete=' + userId;
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
        
        // Disable delete buttons for users with activity
        document.querySelectorAll('.action-btn.delete[disabled]').forEach(btn => {
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
            btn.title = 'Cannot delete: User has orders, cart items, or wishlist items';
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>