<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
:root{
    --primary: #8b5cf6;
    --secondary: #38bdf8;
    --dark: #000000ff;
    --glass:rgba(255,255,255,0.08);
}

/* ===== TOPBAR ===== */
.topbar{
    height:64px;
    background: #000;
    /* backdrop-filter:blur(16px); */
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 22px;
    margin-left:250px;
    margin-bottom:25px;
    position:sticky;
    top:0;
    z-index:900;
    /* box-shadow:0 8px 30px rgba(0,0,0,.45); */
    animation:slideDown .6s ease;
    border-bottom-left-radius:10px;
    border-bottom-right-radius:10px;
    border-bottom:1px solid rgba(0, 0, 0, 0.1);
}

/* Slide animation */
@keyframes slideDown{
    from{opacity:0;transform:translateY(-20px)}
    to{opacity:1;transform:translateY(0)}
}

/* Left section */
.topbar-center{
    padding-left:80px ;
    
    align-items:right;
    justify-content:center;
    gap:16px;
}

/* Menu Button */
.menu-btn{
    background:linear-gradient(135deg,var(--primary),#7c3aed);
    border:none;
    width:44px;
    height:44px;
    border-radius:12px;
    color: #fff;
    font-size:22px;
    display:none;
    align-items:center;
    justify-content:center;
    box-shadow:0 10px 25px rgba(139,92,246,.45);
    transition:.3s;
}
.menu-btn:hover{
    transform:translateY(-3px);
    box-shadow:0 15px 35px rgba(139,92,246,.6);
}
.menu-btn:active{
    transform:scale(.95);
}

/* Title */
.topbar-title{
    font-weight:700;
    font-size:18px;
    letter-spacing:.5px;
    background:linear-gradient(90deg,var(--primary),var(--secondary));
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

/* Right section */
.topbar-right{
    display:flex;
    align-items:center;
    gap:16px;
}

/* Icon buttons */
.topbar-icon{
    width:40px;
    height:40px;
    border-radius:10px;
    background:rgba(255,255,255,.08);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    transition:.3s;
}
.topbar-icon i{
    font-size:18px;
    color:#fff;
}
.topbar-icon:hover{
    background:rgba(139,92,246,.25);
    transform:translateY(-2px);
}

/* ===== MOBILE ===== */
@media(max-width:992px){
    .topbar{
        margin-left:0;
    }
    .menu-btn{
        display:flex;
    }
}
</style>

<!-- TOPBAR -->
<div class="topbar">

    <div class="topbar-center">
        <div class="topbar-title">Admin Panel</div>
    </div>

    <div class="topbar-right">
        <div class="topbar-icon" title="Notifications">
            <i class="bi bi-bell"></i>
        </div>
        <div class="topbar-icon" title="Profile">
            <a href="/project/admin/profile.php" class="<?= $page=='profile.php'?'active':'' ?>">
<i class="bi bi-person-circle"></i></a>
        </div>
    </div>

</div>
