<?php
include "includes/admin_db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";

if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare(
        "SELECT id, password FROM admins WHERE username = ? LIMIT 1"
    );

    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $admin = $res->fetch_assoc();

        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];   // ✅ VERY IMPORTANT
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password";
        }
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login | PROTECTORS</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    min-height:100vh;
    background:#1f2029;
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
    max-width:420px;
    box-shadow:0 15px 40px rgba(0,0,0,.5);
}
.brand{
    font-size:26px;
    font-weight:700;
    text-align:center;
}
.brand span{color:#4a6fa5}
.form-control{
    background:#1f2029;
    border:none;
    color:#fff;
}
.form-control:focus{
    background:#1f2029;
    color:#fff;
    box-shadow:none;
    border:1px solid #4a6fa5;
}
.btn-primary{
    background:#4a6fa5;
    border:none;
}
.btn-primary:hover{background:#365a8c;}
.error{
    background:#842029;
    padding:10px;
    border-radius:6px;
    margin-bottom:15px;
}
</style>
</head>

<body>

<div class="card p-4">
    <div class="brand mb-4">
        PROTECT<span>ORS</span><br>
        <small>Admin Panel</small>
    </div>

    <?php if($error): ?>
        <div class="error text-center"><?= $error ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button name="login" class="btn btn-primary w-100">
            Login
        </button>
        
    </form>
</div>

</body>
</html>
