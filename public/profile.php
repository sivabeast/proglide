<?php
session_start();
require "includes/db.php";

/* =========================
   AUTH CHECK
========================= */
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

/* =========================
   COLUMN CHECK
========================= */
function columnExists($conn, $table, $column){
    $q = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return ($q && $q->num_rows > 0);
}

$has_phone   = columnExists($conn, 'users', 'phone');
$has_address = columnExists($conn, 'users', 'address');

/* =========================
   FETCH USER
========================= */
$fields = "id, name, email";
if ($has_phone)   $fields .= ", phone";
if ($has_address) $fields .= ", address";

$stmt = $conn->prepare("SELECT $fields FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$user = array_merge([
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => ''
], $user);

/* =========================
   UPDATE PROFILE
========================= */
$success = "";
$error   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $name    = trim($_POST['name']);
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        $error = "Name is required";
    } else {

        if ($has_phone && $has_address) {
            $stmt = $conn->prepare(
                "UPDATE users SET name=?, phone=?, address=? WHERE id=?"
            );
            $stmt->bind_param("sssi", $name, $phone, $address, $user_id);

        } elseif ($has_phone) {
            $stmt = $conn->prepare(
                "UPDATE users SET name=?, phone=? WHERE id=?"
            );
            $stmt->bind_param("ssi", $name, $phone, $user_id);

        } elseif ($has_address) {
            $stmt = $conn->prepare(
                "UPDATE users SET name=?, address=? WHERE id=?"
            );
            $stmt->bind_param("ssi", $name, $address, $user_id);

        } else {
            $stmt = $conn->prepare(
                "UPDATE users SET name=? WHERE id=?"
            );
            $stmt->bind_param("si", $name, $user_id);
        }

        if ($stmt->execute()) {
            $success = "Profile updated successfully";
            $user['name']    = $name;
            $user['phone']   = $phone;
            $user['address'] = $address;
        } else {
            $error = "Profile update failed";
        }
    }
}

/* =========================
   CHANGE PASSWORD
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$current || !$new || !$confirm) {
        $error = "All password fields are required";
    } elseif ($new !== $confirm) {
        $error = "Passwords do not match";
    } elseif (strlen($new) < 6) {
        $error = "Password must be at least 6 characters";
    } else {

        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!password_verify($current, $row['password'])) {
            $error = "Current password is incorrect";
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "UPDATE users SET password=? WHERE id=?"
            );
            $stmt->bind_param("si", $hash, $user_id);

            if ($stmt->execute()) {
                $success = "Password updated successfully";
            } else {
                $error = "Password update failed";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f6f7fb;
    padding-top:110px;
}

/* PAGE */
.profile-wrapper{
    max-width:520px;
    margin:auto;
}

/* TITLE */
.profile-title{
    text-align:center;
    font-weight:700;
    margin-bottom:25px;
}

/* CARD */
.profile-card{
    background:#fff;
    border-radius:14px;
    padding:22px;
    box-shadow:0 10px 28px rgba(0,0,0,.08);
    margin-bottom:22px;
}

/* INPUT */
.form-control{
    border-radius:10px;
    height:46px;
}

/* TEXTAREA */
textarea.form-control{
    height:auto;
}

/* BUTTON */
.btn-dark{
    height:46px;
    border-radius:10px;
}

/* MOBILE */
@media(max-width:576px){
    body{
        padding-top:95px;
    }
    .profile-wrapper{
        padding:0 10px;
    }
}
</style>
</head>

<body>

<?php include "includes/header.php"; ?>

<div class="profile-wrapper">

<h3 class="profile-title">My Profile</h3>

<?php if ($success): ?>
<div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<!-- PROFILE DETAILS -->
<div class="profile-card">
<form method="post">

<label class="form-label">Name</label>
<input class="form-control mb-3"
       name="name"
       value="<?= htmlspecialchars($user['name']) ?>"
       required>

<label class="form-label">Email</label>
<input class="form-control mb-3"
       value="<?= htmlspecialchars($user['email']) ?>"
       disabled>

<?php if ($has_phone): ?>
<label class="form-label">Phone</label>
<input class="form-control mb-3"
       name="phone"
       value="<?= htmlspecialchars($user['phone']) ?>">
<?php endif; ?>

<?php if ($has_address): ?>
<label class="form-label">Address</label>
<textarea class="form-control mb-3"
          name="address"
          rows="3"><?= htmlspecialchars($user['address']) ?></textarea>
<?php endif; ?>

<button name="update_profile" class="btn btn-dark w-100">
Update Profile
</button>

</form>
</div>

<!-- PASSWORD -->
<div class="profile-card">
<form method="post">

<h6 class="fw-bold mb-3">Change Password</h6>

<input type="password"
       name="current_password"
       class="form-control mb-3"
       placeholder="Current Password"
       required>

<input type="password"
       name="new_password"
       class="form-control mb-3"
       placeholder="New Password"
       required>

<input type="password"
       name="confirm_password"
       class="form-control mb-3"
       placeholder="Confirm Password"
       required>

<button name="change_password" class="btn btn-dark w-100">
Change Password
</button>

</form>
</div>

</div>

<?php include "includes/footer.php"; ?>
<?php include "includes/mobile_bottom_nav.php"; ?>

</body>
</html>
