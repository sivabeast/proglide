<?php
include "includes/admin_auth.php";
include "includes/admin_db.php";

/* TOGGLE USER STATUS */
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];

    $u = $conn->query("SELECT status FROM users WHERE id=$id")->fetch_assoc();
    $newStatus = ($u['status'] == 'active') ? 'blocked' : 'active';

    $stmt = $conn->prepare("UPDATE users SET status=? WHERE id=?");
    $stmt->bind_param("si", $newStatus, $id);
    $stmt->execute();

    header("Location: users.php");
    exit;
}

/* FETCH USERS */
$users = $conn->query("
    SELECT id, name, email, status, created_at
    FROM users
    ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
<title>Users | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* =================================================
   ROOT VARIABLES
================================================= */
:root{
    --bg:#020617;
    --sidebar:#020617;
    --content:#020617;
    --card:#0f172a;
    --border:#1e293b;

    --primary:#7c3aed;
    --primary-soft:#8b5cf6;
    --danger:#ef4444;

    --text:#e5e7eb;
    --muted:#94a3b8;
}

/* =================================================
   RESET
================================================= */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

html,body{
    width:100%;
    height:100%;
}

body{
    font-family:'Poppins',sans-serif;
    background:var(--bg);
    color:var(--text);
    overflow-x:hidden;
}

/* =================================================
   MAIN LAYOUT
================================================= */
.admin-wrapper{
    display:flex;
    min-height:100vh;
    width:100%;
}

/* =================================================
   SIDEBAR
================================================= */
.sidebar{
    width:260px;
    min-width:260px;
    background:linear-gradient(180deg,#020617,#020617);
    border-right:1px solid var(--border);
    position:fixed;
    top:0;
    left:0;
    bottom:0;
    padding:24px 16px;
    z-index:1000;
}

/* Sidebar menu items */
.sidebar a{
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px 14px;
    margin-bottom:6px;
    border-radius:12px;
    color:var(--text);
    text-decoration:none;
    font-size:14px;
    transition:.25s ease;
}

.sidebar a:hover{
    background:rgba(124,58,237,.15);
}

.sidebar a.active{
    background:linear-gradient(135deg,var(--primary),var(--primary-soft));
    color:#fff;
    box-shadow:0 10px 25px rgba(124,58,237,.45);
}

/* =================================================
   CONTENT AREA
================================================= */
.content{
    margin-left:260px;
    padding:28px 28px 40px;
    width:calc(100% - 260px);
    min-height:100vh;
    transition:.3s ease;
}

/* =================================================
   HEADER / TOPBAR
================================================= */
.topbar{
    height:64px;
    display:flex;
    align-items:center;
    padding:0 28px;
    margin:-28px -28px 28px;
    border-bottom:1px solid var(--border);
    background:linear-gradient(180deg,#020617,#020617);
}

.topbar h4{
    font-size:18px;
    font-weight:600;
    color:var(--primary-soft);
}

/* =================================================
   CARD
================================================= */
.card{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:16px;
    padding:20px;
    box-shadow:0 20px 40px rgba(0,0,0,.45);
}

/* =================================================
   TABLE
================================================= */
.table{
    margin:0;
}

.table-dark{
    --bs-table-bg:transparent;
    --bs-table-border-color:var(--border);
    color:var(--text);
}

.table-dark th{
    font-size:12px;
    color:var(--muted);
    text-transform:uppercase;
    background:#020617;
    border-bottom:1px solid var(--border);
    white-space:nowrap;
}

.table-dark td{
    font-size:14px;
    vertical-align:middle;
    white-space:nowrap;
}

.table-dark tbody tr:hover{
    background:rgba(124,58,237,.08);
}

/* =================================================
   TABLE IMAGE FIX
================================================= */
.table img{
    width:52px;
    height:52px;
    object-fit:cover;
    border-radius:10px;
    border:1px solid var(--border);
}

/* =================================================
   BUTTONS
================================================= */
.btn-edit{
    background:linear-gradient(135deg,var(--primary),var(--primary-soft));
    border:none;
    color:#fff;
}

.btn-edit:hover{
    opacity:.9;
}

.btn-danger{
    background:var(--danger);
    border:none;
}

/* =================================================
   ALERT
================================================= */
.alert-success{
    background:#052e16;
    border:1px solid #14532d;
    color:#86efac;
    border-radius:12px;
}

/* =================================================
   TABLET RESPONSIVE
================================================= */
@media(max-width:1024px){

    .content{
        padding:24px 18px;
    }

    .topbar{
        padding:0 18px;
        margin:-24px -18px 24px;
    }
}
@media(max-width:992px){

    .content{
        margin-left:0;
        width:100%;
        padding:20px 14px;
    }

    .topbar{
        margin:-20px -14px 20px;
        padding:0 14px;
    }
}

/* =================================================
   MOBILE RESPONSIVE
================================================= */
@media(max-width:768px){

    /* Sidebar becomes overlay */
    .sidebar{
        position:fixed;
        left:-260px;
        transition:.3s ease;
    }

    .sidebar.open{
        left:0;
    }


    /* Table scroll */
    .card{
        overflow-x:auto;
    }

    .table{
        min-width:720px;
    }
}
@media (prefers-reduced-motion: reduce){
    *{
        transition:none !important;
        animation:none !important;
    }
}
/* =================================================
   SMALL MOBILE
================================================= */
@media(max-width:480px){

    .topbar h4{
        font-size:16px;
    }

    .table img{
        width:44px;
        height:44px;
    }

    .btn{
        font-size:12px;
        padding:6px 10px;
    }
}

/* =================================================
   SCROLLBAR
================================================= */
::-webkit-scrollbar{
    width:6px;
    height:6px;
}
::-webkit-scrollbar-thumb{
    background:#334155;
    border-radius:10px;
}

</style>
</head>

<body>

<?php include "includes/sidebar.php"; ?>
<?php include "includes/header.php"; ?>

<main class="content">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Users</h4>
</div>

<div class="card p-4">

<div class="table-responsive">
<table class="table table-dark table-hover align-middle">
<thead>
<tr>
    <th>#</th>
    <th>Name</th>
    <th>Email</th>
    <th>Status</th>
    <th>Joined</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php if ($users->num_rows > 0): ?>
<?php while ($u = $users->fetch_assoc()): ?>
<tr>
    <td><?= $u['id'] ?></td>
    <td><?= htmlspecialchars($u['name']) ?></td>
    <td><?= htmlspecialchars($u['email']) ?></td>

    <td>
        <?php if ($u['status'] == 'active'): ?>
            <span class="badge badge-active">Active</span>
        <?php else: ?>
            <span class="badge badge-blocked">Blocked</span>
        <?php endif; ?>
    </td>

    <td><?= date("d M Y", strtotime($u['created_at'])) ?></td>

    <td>
        <?php if ($u['status'] == 'active'): ?>
            <a href="?toggle=<?= $u['id'] ?>"
               class="btn btn-sm btn-danger"
               onclick="return confirm('Block this user?')">
               Block
            </a>
        <?php else: ?>
            <a href="?toggle=<?= $u['id'] ?>"
               class="btn btn-sm btn-success"
               onclick="return confirm('Unblock this user?')">
               Unblock
            </a>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="6" class="text-center text-muted">
        No users found
    </td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>

</div>

</main>

</body>
</html>
