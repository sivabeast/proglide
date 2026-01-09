<?php
include "includes/admin_db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$success = $error = "";

if (isset($_POST['change_pass'])) {
    $old = $_POST['old_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {
        $error = "New passwords do not match";
    } elseif (strlen($new) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        $stmt = $conn->prepare("SELECT password FROM admins WHERE id=?");
        $stmt->bind_param("i", $_SESSION['admin_id']);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if (!password_verify($old, $res['password'])) {
            $error = "Old password is incorrect";
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $up = $conn->prepare("UPDATE admin SET password=? WHERE id=?");
            $up->bind_param("si", $hash, $_SESSION['admin_id']);
            $up->execute();
            $success = "Password updated successfully";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Profile</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#1f2029;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    font-family:Poppins,sans-serif;
}
.card{
    background:#2a2b38;
    color:#fff;
    border-radius:12px;
    width:100%;
    max-width:450px;
}
.form-control{
    background:#1f2029;
    border:none;
    color:#fff;
}
.form-control:focus{
    background:#1f2029;
    color:#fff;
    border:1px solid #4a6fa5;
    box-shadow:none;
}
.btn-primary{
    background:#4a6fa5;
    border:none;
}
.btn-primary:hover{background:#365a8c;}
.alert{border-radius:6px;}
</style>
</head>

<body>

<div class="card p-4">
    <h4 class="text-center mb-3">Admin Profile</h4>

    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label>Old Password</label>
            <input type="password" name="old_password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>New Password</label>
            <input type="password" name="new_password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>

        <button name="change_pass" class="btn btn-primary w-100">
            Update Password
        </button>
    </form>

    <a href="dashboard.php" class="d-block text-center mt-3 text-decoration-none text-light">
        ← Back to Dashboard
    </a>
</div>

</body>
</html>
