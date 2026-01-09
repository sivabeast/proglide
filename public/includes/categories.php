<?php
/* =========================
   CATEGORY PARAM
========================= */
$category = $_GET['cat'] ?? 'all';
?>

<style>
/* =========================
   RESET
========================= */
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',system-ui,sans-serif;}
a{text-decoration:none;color:inherit}

/* =========================
   CATEGORY BAR
========================= */
.categories-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:#0b0724;
    padding:10px 5%;
    border-top:0.5px solid #09072e;
    border-bottom:0.5px solid #0d123c;
    position:fixed;
    top:78px;
    width:100%;
    height:55px;
    z-index:5000;
}

/* MENU BUTTON */
.menu-btn{
    display:none;
    font-size:26px;
    color:#fff;
    cursor:pointer;
}

/* DESKTOP CATEGORY LIST */
.category-list{
    display:flex;
    gap:25px;
    list-style:none;
    white-space:nowrap;
}
.category-list li{
    padding:8px 18px;
    border-radius:20px;
}
.category-list li a{
    color:#b7b7b7;
}
.category-list li.active{
    background:#4a6fa5;
}
.category-list li.active a{
    color:#fff;
}

/* SEARCH */
.search-box{
    position:relative;
    width:230px;
}
.search-box input{
    width:100%;
    padding:8px 35px;
    border:1px solid #ccc;
    border-radius:8px;
}
.search-box i{
    position:absolute;
    top:50%;
    left:12px;
    transform:translateY(-50%);
}

/* =========================
   DRAWER (MOBILE)
========================= */
.drawer{
    position:fixed;
    top:0;
    left:-260px;
    width:260px;
    height:100vh;
    background:#0b0724;
    padding:25px;
    padding-top:40px;
    transition:.35s;
    z-index:6000;
}
.drawer ul{list-style:none;padding:0;}
.drawer li{
    padding:12px;
    border-radius:6px;
    margin-bottom:10px;
}
.drawer li a{
    color:#e7e7e7;
    display:block;
}
.drawer li.active{
    background:#4a6fa5;
}

/* OVERLAY */
.overlay{
    position:fixed;
    inset:0;
    backdrop-filter:blur(5px);
    background:rgba(0,0,0,.25);
    display:none;
    z-index:5500;
}

/* RESPONSIVE */
@media(max-width:768px){
    .menu-btn{display:block;}
    .category-list{display:none;}
    .search-box{width:320px;}
}
</style>

<!-- OVERLAY -->
<div class="overlay" id="cat-overlay" onclick="closeCatDrawer()"></div>

<!-- CATEGORY BAR -->
<div class="categories-bar">

    <i class="fas fa-bars menu-btn" onclick="openCatDrawer()"></i>

    <ul class="category-list">
        <li class="<?= $category=='all'?'active':'' ?>">
            <a href="index.php">ALL</a>
        </li>
        <li class="<?= $category=='clear'?'active':'' ?>">
            <a href="index.php?cat=clear">CLEAR MATE</a>
        </li>
        <li class="<?= $category=='privacy'?'active':'' ?>">
            <a href="index.php?cat=privacy">PRIVACY</a>
        </li>
        <li class="<?= $category=='mirror'?'active':'' ?>">
            <a href="index.php?cat=mirror">MIRROR</a>
        </li>
        <li class="<?= $category=='plastic'?'active':'' ?>">
            <a href="index.php?cat=plastic">PLASTIC CASE</a>
        </li>
        <li class="<?= $category=='hard'?'active':'' ?>">
            <a href="index.php?cat=hard">HARD CASE</a>
        </li>
    </ul>

    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="search" placeholder="Search products...">
    </div>

</div>

<!-- DRAWER MENU -->
<div class="drawer" id="catDrawer">
    <ul>
        <li class="<?= $category=='all'?'active':'' ?>">
            <a href="../index.php">ALL</a>
        </li>
        <li class="<?= $category=='clear'?'active':'' ?>">
            <a href="../index.php?cat=clear">CLEAR MATE</a>
        </li>
        <li class="<?= $category=='privacy'?'active':'' ?>">
            <a href="../index.php?cat=privacy">PRIVACY</a>
        </li>
        <li class="<?= $category=='mirror'?'active':'' ?>">
            <a href="../index.php?cat=mirror">MIRROR</a>
        </li>
        <li class="<?= $category=='plastic'?'active':'' ?>">
            <a href="../index.php?cat=plastic">PLASTIC CASE</a>
        </li>
        <li class="<?= $category=='hard'?'active':'' ?>">
            <a href="../index.php?cat=hard">HARD CASE</a>
        </li>
    </ul>
</div>

<!-- JS (ONLY FOR DRAWER OPEN/CLOSE) -->
<script>
function openCatDrawer(){
    document.getElementById("catDrawer").style.left="0";
    document.getElementById("cat-overlay").style.display="block";
}
function closeCatDrawer(){
    document.getElementById("catDrawer").style.left="-260px";
    document.getElementById("cat-overlay").style.display="none";
}
</script>
