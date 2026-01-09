<?php
session_start();
include "includes/admin_db.php";
include "includes/admin_auth.php";

/* =========================
   ADD BRAND
========================= */
if (isset($_POST['add_brand'])) {
    $brand = trim($_POST['brand_name']);
    if ($brand !== '') {
        $stmt = $conn->prepare("INSERT IGNORE INTO brands (name) VALUES (?)");
        $stmt->bind_param("s", $brand);
        $stmt->execute();
    }
    header("Location: manage_brands_models.php");
    exit;
}

/* =========================
   ADD MODELS
========================= */
if (isset($_POST['add_models'])) {
    $brand_id = (int) $_POST['brand_id'];
    $models = trim($_POST['models']);

    if ($brand_id && $models !== '') {
        $stmt = $conn->prepare(
            "INSERT IGNORE INTO phone_models (brand_id, model_name)
             VALUES (?, ?)"
        );

        foreach (explode("\n", $models) as $m) {
            $m = trim($m);
            if ($m !== '') {
                $stmt->bind_param("is", $brand_id, $m);
                $stmt->execute();
            }
        }
    }
    header("Location: manage_brands_models.php?brand_id=$brand_id");
    exit;
}

/* =========================
   FETCH BRANDS
========================= */
$brands = $conn->query("
    SELECT * FROM brands ORDER BY name
")->fetch_all(MYSQLI_ASSOC);

/* =========================
   FETCH MODELS (WITH BRAND FILTER)
========================= */
$where = "";
if (!empty($_GET['brand_id'])) {
    $bid = (int) $_GET['brand_id'];
    $where = "WHERE pm.brand_id = $bid";
}

$models = $conn->query("
    SELECT pm.*, b.name AS brand_name
    FROM phone_models pm
    JOIN brands b ON b.id = pm.brand_id
    $where
    ORDER BY b.name, pm.model_name
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Brand & Model Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
/* ===============================
   GLOBAL
================================ */
*{
    box-sizing:border-box;
}

body{
    background:#f1f3f8;
    font-family:'Segoe UI',system-ui,sans-serif;
    color:#1f2937;
}

/* ===============================
   PAGE TITLE
================================ */
h3{
    font-weight:700;
    color:#111827;
}

/* ===============================
   CARDS (same as add_protector)
================================ */
.card{
    background:#ffffff;
    border:none;
    border-radius:18px;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
}

/* ===============================
   FORMS
================================ */
.form-control,
.form-select{
    border-radius:14px;
    padding:12px 16px;
    font-size:15px;
    border:1px solid #dbe1ea;
}

.form-control:focus,
.form-select:focus{
    border-color:#6366f1;
    box-shadow:0 0 0 3px rgba(99,102,241,.15);
}

/* ===============================
   BUTTONS
================================ */
.btn{
    border-radius:14px;
    font-weight:600;
    padding:10px 20px;
    transition:.2s;
}

.btn-primary{
    background:#4f46e5;
    border:none;
}
.btn-primary:hover{
    background:#4338ca;
}

.btn-success{
    background:#22c55e;
    border:none;
}
.btn-success:hover{
    background:#16a34a;
}

.btn-warning{
    background:#f59e0b;
    border:none;
    color:#fff;
}
.btn-warning:hover{
    background:#d97706;
}

.btn-danger{
    background:#ef4444;
    border:none;
}
.btn-danger:hover{
    background:#dc2626;
}

/* ===============================
   SEARCH INPUT
================================ */
#searchBox{
    border-radius:16px;
    padding:14px 18px;
    font-size:15px;
    background:#ffffff;
}

/* ===============================
   MODEL GRID (MAIN PART)
================================ */
.model-col{
    transition:.2s;
}

.model-box{
    background:#ffffff;
    border-radius:18px;
    padding:20px 12px;
    text-align:center;
    font-weight:600;
    font-size:15px;
    cursor:pointer;
    border:1px solid #eef1f6;
    box-shadow:0 8px 24px rgba(0,0,0,.08);
    transition:all .25s ease;
}

.model-box:hover{
    transform:translateY(-6px) scale(1.02);
    box-shadow:0 16px 40px rgba(0,0,0,.15);
    border-color:#6366f1;
    color:#4f46e5;
}

/* ===============================
   EMPTY STATE
================================ */
.text-muted{
    font-size:15px;
    color:#6b7280 !important;
}

/* ===============================
   MODALS (same premium look)
================================ */
.modal-content{
    border-radius:22px;
    border:none;
    box-shadow:0 25px 60px rgba(0,0,0,.3);
}

.modal-header{
    border-bottom:1px solid #e5e7eb;
    padding:20px 24px;
}

.modal-title{
    font-weight:700;
    color:#111827;
}

.modal-body{
    padding:22px 24px;
    font-size:15px;
}

.modal-footer{
    border-top:1px solid #e5e7eb;
    padding:16px 24px;
}

/* ===============================
   STATUS BADGES
================================ */
.badge{
    padding:6px 14px;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
}

.bg-success{
    background:#22c55e !important;
}

.bg-danger{
    background:#ef4444 !important;
}

/* ===============================
   RESPONSIVE
================================ */
@media(max-width:768px){
    .model-box{
        font-size:14px;
        padding:16px 8px;
    }

    .btn{
        padding:8px 14px;
        font-size:14px;
    }

    #searchBox{
        padding:12px 14px;
    }
}

    </style>
</head>

<body>
    <?php include "includes/sidebar.php"; ?>
    <?php include "includes/header.php"; ?>

    <div class="container">

        <h3 class="fw-bold mb-4">Brand & Phone Model Management</h3>

        <!-- ================= ADD BRAND ================= -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="post" class="d-flex gap-3">
                    <input type="text" name="brand_name" class="form-control" placeholder="Brand name" required>
                    <button class="btn btn-primary" name="add_brand">
                        <i class="fas fa-plus"></i> Add Brand
                    </button>
                </form>
            </div>
        </div>

        <!-- ================= TOP ACTIONS ================= -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <form method="get" class="d-flex gap-3">
                <select name="brand_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Brands</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= (!empty($_GET['brand_id']) && $_GET['brand_id'] == $b['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModelsModal">
                <i class="fas fa-plus"></i> Add Models
            </button>
        </div>

        <!-- ================= SEARCH ================= -->
        <div class="mb-3">
            <input type="text" id="searchBox" class="form-control" placeholder="Search model...">
        </div>

            <!-- ================= MODELS GRID ================= -->
            <div class="row g-3">
                <?php if (count($models) > 0): ?>
                <?php foreach ($models as $m): ?>
                <div class="col-6 col-md-3 col-lg-2 model-col">
                    <div class="model-box" onclick="openModel(
                <?= $m['id'] ?>,
                '<?= htmlspecialchars($m['model_name']) ?>',
                '<?= htmlspecialchars($m['brand_name']) ?>',
                '<?= $m['status'] ?>'
             )">
                    <?= htmlspecialchars($m['model_name']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="col-12">
                    <p class="text-muted text-center">Models not found</p>
                </div>
                <?php endif; ?>
            </div>

        <!-- ================= ADD MODELS MODAL ================= -->
        <div class="modal fade" id="addModelsModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form method="post">

                        <div class="modal-header">
                            <h5 class="modal-title">Add Phone Models</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <select name="brand_id" class="form-select mb-3" required>
                                <option value="">Select Brand</option>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <textarea name="models" rows="5" class="form-control" required
                                placeholder="One model per line"></textarea>
                        </div>

                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-success" name="add_models">Save</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- ================= MODEL DETAIL MODAL ================= -->
        <div class="modal fade" id="modelModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">Model Details</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <p><b>Model:</b> <span id="mdName"></span></p>
                        <p><b>Brand:</b> <span id="mdBrand"></span></p>
                        <p>
                            <b>Status:</b>
                            <span id="mdStatus" class="badge"></span>
                        </p>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-warning" onclick="toggleStatus()">
                            <i class="fas fa-eye-slash"></i> Toggle
                        </button>
                        <button class="btn btn-danger" onclick="deleteModel()">Delete</button>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let currentId = 0;

        function openModel(id, name, brand, status) {
            currentId = id;
            mdName.innerText = name;
            mdBrand.innerText = brand;
            mdStatus.innerText = status;
            mdStatus.className = "badge " + (status === "hidden" ? "bg-danger" : "bg-success");
            new bootstrap.Modal(modelModal).show();
        }

        function toggleStatus() {
            fetch("ajax/toggle_model.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "id=" + currentId
            })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        mdStatus.innerText = d.status;
                        mdStatus.className =
                            "badge " + (d.status === "hidden" ? "bg-danger" : "bg-success");
                    }
                });
        }

        function deleteModel() {
            if (!confirm("Delete model?")) return;
            fetch("ajax/delete_model.php?id=" + currentId)
                .then(() => location.reload());
        }

        /* SEARCH – NO RELOAD */
        searchBox.addEventListener("keyup", () => {
            let v = searchBox.value.toLowerCase();
            document.querySelectorAll(".model-col").forEach(col => {
                col.style.display =
                    col.innerText.toLowerCase().includes(v) ? "" : "none";
            });
        });
    </script>

</body>

</html>