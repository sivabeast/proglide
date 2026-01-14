<?php
include "../includes/admin_auth.php";
include "../includes/admin_db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$success = "";
$error = "";



/* MAIN CATEGORY */
$main_category_id = 1;

/* ADD CATEGORY TYPE */
if (isset($_POST['add_type'])) {
    $newType = trim($_POST['new_type']);
    if ($newType != "") {
        $stmt = $conn->prepare("
            INSERT INTO category_types (main_category_id, type_name)
            VALUES (?,?)
        ");
        $stmt->bind_param("is", $main_category_id, $newType);
        $stmt->execute();
        $success = "Category type added!";
    }
}

/* FETCH TYPES */
$types = [];
$typeRes = $conn->query("
    SELECT id, type_name
    FROM category_types
    WHERE main_category_id = 1
");
while ($r = $typeRes->fetch_assoc()) {
    $types[] = $r;
}

/* ADD PROTECTOR */
if (isset($_POST['add_protector'])) {

    $model = trim($_POST['model_name']);

    if ($model === "") {
        $error = "Model name required";
    } else {

        $folder = "../../uploads/products/protectors/";
        if (!is_dir($folder))
            mkdir($folder, 0777, true);

        foreach ($types as $t) {
            $type = strtolower($t['type_name']);
            $imgField = $type . "_image";

            if (!empty($_FILES[$imgField]['name'])) {

                $mrp = $_POST[$type . "_mrp"];
                $price = $_POST[$type . "_price"];
                $desc = $_POST[$type . "_desc"];

                if ($price == "")
                    continue;

                $imgName = time() . "_" . $type . "_" . $_FILES[$imgField]['name'];
                move_uploaded_file($_FILES[$imgField]['tmp_name'], $folder . $imgName);

                $stmt = $conn->prepare("
                    INSERT INTO products
                    (main_category_id, category_type_id, model_name,
                     original_price, price, description, image)
                    VALUES (?,?,?,?,?,?,?)
                ");
                $stmt->bind_param(
                    "iissdss",
                    $main_category_id,
                    $t['id'],
                    $model,
                    $mrp,
                    $price,
                    $desc,
                    $imgName
                );
                $stmt->execute();
            }
        }
        $success = "Protector added successfully!";
    }
}

/* FETCH PRODUCTS */
$products = [];
$res = $conn->query("
    SELECT p.*, ct.type_name
    FROM products p
    JOIN category_types ct ON p.category_type_id = ct.id
    WHERE p.main_category_id = 1
    ORDER BY p.created_at DESC
");
while ($row = $res->fetch_assoc()) {
    $products[] = $row;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Protector Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* =========================================================
   ADMIN PRODUCT MANAGER – FULL CSS (FIXED)
========================================================= */

        :root {
            --bg: #020617;
            --card: #111827;
            --border: #374151;

            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #16a34a;
            --danger: #dc2626;
            --warning: #f59e0b;

            --text: #f9fafb;
            --muted: #9ca3af;
        }

        /* RESET */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box
        }

        /* BODY */
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
        }

        /* CONTAINER */
        .container {
            max-width: 100%;
            padding-left: 280px;
        }

        @media(max-width:992px) {
            .container {
                padding-left: 16px;
                padding-right: 16px
            }
        }

        /* TITLE */
        h3 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 18px;
        }

        @media(max-width:576px) {
            h3 {
                text-align: center;
                font-size: 20px
            }
        }

        /* ALERTS */
        .alert-success {
            background: #052e16;
            border: 1px solid #14532d;
            color: #86efac;
            border-radius: 10px;
        }

        .alert-danger {
            background: #450a0a;
            border: 1px solid #7f1d1d;
            color: #fecaca;
            border-radius: 10px;
        }

        /* CARD */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px;
        }

        /* INPUTS */
        .form-control,
        .form-select {
            background: #020617;
            border: 1px solid var(--border);
            color: var(--text);
            height: 42px;
        }

        .form-control::placeholder {
            color: var(--muted)
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: none;
            background: #020617;
        }

        /* BUTTONS */
        .btn-primary {
            background: var(--primary);
            border: none
        }

        .btn-primary:hover {
            background: var(--primary-dark)
        }

        .btn-success {
            background: var(--success);
            border: none
        }

        .btn-warning {
            background: var(--warning);
            border: none;
            color: #000
        }

        .btn-danger {
            background: var(--danger);
            border: none
        }

        /* TABLE */
        .table-dark {
            --bs-table-bg: transparent;
            --bs-table-border-color: var(--border);
        }

        .table-dark th {
            background: #020617;
            color: var(--muted);
            font-size: 13px;
        }

        .table-dark td {
            font-size: 14px;
            vertical-align: middle;
        }

        /* IMAGE */
        .table td:first-child {
            text-align: center;
            width: 90px
        }

        .table img {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid var(--border);
        }

        /* TYPE BADGE */
        .type-badge {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 12px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .5px;
            border-radius: 999px;
            background: linear-gradient(135deg, #1f2937, #020617);
            border: 1px solid var(--border);
            text-transform: uppercase;
        }
        .modal-dialog {
            max-width: 700px;
            
            margin: 5%;
            /* Center align the modal horizontally */
        }

        /* ===============================
   MOBILE CARD VIEW (FIXED)
================================ */
        @media(max-width:576px) {

            .table thead {
                display: none
            }

            .table tbody {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 14px;
            }

            .table tbody tr {
                display: flex;
                flex-direction: column;
                align-items: center;
                background: radial-gradient(circle at top, #020617, #000);
                border: 1px solid var(--border);
                border-radius: 18px;
                padding: 16px 12px;
                text-align: center;
                min-height: 180px;
            }

            /* HIDE IMAGE ONLY */
            .table tbody td:nth-child(1) {
                display: none;
            }

            /* MODEL NAME */
            .table tbody td:nth-child(2) {
                font-size: 15px;
                font-weight: 600;
                margin-bottom: 4px;
            }

            /* TYPE (VISIBLE!) */
            .table tbody td:nth-child(3) {
                display: block;
                margin-bottom: 8px;
            }

            /* HIDE MRP */
            .table tbody td:nth-child(4) {
                display: none;
            }

            /* PRICE */
            .table tbody td:nth-child(5) {
                font-size: 14px;
                font-weight: 600;
                margin-bottom: 10px;
            }

            /* ACTION BUTTONS */
            .table tbody td:last-child {
                display: flex;
                gap: 8px;
                width: 100%;
                margin-top: auto;
            }

            .table tbody td:last-child .btn {
                flex: 1;
                font-size: 12px;
                padding: 6px 0;
                border-radius: 8px;
            }
        }

        
        .modal-content {
            background: #020617;
            border: 1px solid var(--border);
            border-radius: 16px;
            color: var(--text);
        }

        .modal-content input,
        .modal-content textarea,
        .modal-content select {
            color: white !important;
        }

        .modal-content input::placeholder,
        .modal-content textarea::placeholder {
            color: #9ca3af !important;
        }

        .modal-body {
            padding: 20px
        }

        .modal-footer {
            border-top: 1px solid var(--border)
        }

        /* SCROLLBAR */
        ::-webkit-scrollbar {
            width: 6px
        }

        ::-webkit-scrollbar-thumb {
            background: #374151;
            border-radius: 10px
        }
    </style>
</head>

<body>

    <?php include "../includes/sidebar.php"; ?>
    <?php include "../includes/header.php"; ?>

    <div class="container py-4">

        <h3>Protector Manager</h3>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

        <!-- ADD CATEGORY TYPE -->
        <form method="post" class="d-flex gap-2 mb-3">
            <input type="text" name="new_type" class="form-control w-25" placeholder="New category (Clear, Matte)" required>
            <button name="add_type" class="btn btn-success">Add Type</button>
        </form>

        <!-- FILTER BAR -->
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <select id="typeSelect" class="form-select w-auto">
                <option value="all">ALL</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= $t['type_name'] ?>"><?= strtoupper($t['type_name']) ?></option>
                <?php endforeach; ?>
            </select>

            <input type="text" id="searchBox" class="form-control w-25" placeholder="Search model">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ Add</button>
        </div>

        <!-- PRODUCT TABLE -->
        <div class="product-card-container"></div>
        <div class="card p-3">
            <table class="table table-dark table-hover">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Model</th>
                        <th>Type</th>
                        <th>MRP</th>
                        <th>Price</th>
                        <th>popular</th>
                        <th>Action</th>

                    </tr>
                </thead>
                <tbody id="productTable">
                    <?php foreach ($products as $p): ?>
                        <tr data-type="<?= $p['type_name'] ?>">
                            <td><img src="../../uploads/products/protectors/<?= $p['image'] ?>"></td>
                            <td><?= $p['model_name'] ?></td>
                            <td>
                                <span class="type-badge">
                                    <?= strtoupper($p['type_name']) ?>
                                </span>
                            </td>
                            
                            <td><s>₹<?= $p['original_price'] ?></s></td>
                            <td>₹<?= $p['price'] ?></td>
                            <td><input type="checkbox" class="form-check-input popular-toggle" data-id="<?= $p['id'] ?>" <?= $p['is_popular'] ? 'checked' : '' ?>></td>

                            <td>
                                <a href="product_edit.php?id=<?= $p['id'] ?>&from=protector"
                                    class="btn btn-sm btn-warning">Edit</a>


                                <a href="product_delete.php?id=<?= $p['id'] ?>" onclick="return confirm('Delete this protector?')"
                                    class="btn btn-sm btn-danger">
                                    Delete
                                </a>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>

    <!-- ADD MODAL -->
   
    <div class="modal fade" id="addModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Protector</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-body" style="color: #fff">

                        <input type="text" name="model_name" class="form-control mb-3" placeholder="Model Name"
                            required>

                        <?php foreach ($types as $t):
                            $type = strtolower($t['type_name']); ?>
                            <hr>
                            <h6><?= strtoupper($t['type_name']) ?></h6>

                            <input type="number" name="<?= $type ?>_mrp" class="form-control mb-2"
                                placeholder="Original Price">
                            <input type="number" name="<?= $type ?>_price" class="form-control mb-2"
                                placeholder="Selling Price">
                            <textarea name="<?= $type ?>_desc" class="form-control mb-2"
                                placeholder="Description"></textarea>
                            <input type="file" name="<?= $type ?>_image" class="form-control">
                        <?php endforeach; ?>

                    </div>
                    <div class="modal-footer">
                        <button name="add_protector" class="btn btn-primary w-100">Save</button>

                    </div>
                </form>

            </div>
        </div>
    </div>
    </div>
    <script>
        const rows = document.querySelectorAll("#productTable tr");
        const typeSelect = document.getElementById("typeSelect");
        const searchBox = document.getElementById("searchBox");

        function applyFilter() {
            const type = typeSelect.value;
            const q = searchBox.value.toLowerCase();

            rows.forEach(r => {
                const matchType = (type === "all" || r.dataset.type === type);
                const matchText = r.children[1].innerText.toLowerCase().includes(q);
                r.style.display = (matchType && matchText) ? "" : "none";
            });
        }

        typeSelect.addEventListener("change", applyFilter);
        searchBox.addEventListener("keyup", applyFilter);
    </script>
 
 <script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.popular-toggle').forEach(toggle => {

        toggle.addEventListener('change', function () {

            const productId = this.dataset.id;
            const status = this.checked ? 1 : 0;

            fetch('../ajax/toggle_popular.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'id=' + productId + '&status=' + status
            })
            .then(res => res.text())
            .then(t => console.log('Server:', t));
        });

    });

});
</script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>