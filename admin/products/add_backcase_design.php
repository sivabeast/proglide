<?php
ob_start();

include "../includes/admin_auth.php";
include "../includes/admin_db.php";
include "../includes/sidebar.php";
include "../includes/header.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$success = "";
$error = "";

/* MAIN CATEGORY = BACK CASE */
$main_category_id = 2;

/* ===========================
   FETCH CASE TYPES
=========================== */
$caseTypes = [];
$res = $conn->query("
    SELECT id,type_name 
    FROM category_types 
    WHERE main_category_id=2
    ORDER BY type_name
");
while ($r = $res->fetch_assoc()) {
    $caseTypes[] = $r;
}

/* ===========================
   FETCH DESIGN CATEGORIES
=========================== */
$designCats = [];
$res = $conn->query("SELECT * FROM design_categories ORDER BY name");
while ($r = $res->fetch_assoc()) {
    $designCats[] = $r;
}

/* ===========================
   ADD DESIGN CATEGORY
=========================== */
if (isset($_POST['add_design_category'])) {
    $name = trim($_POST['new_design_category']);
    if ($name != "") {
        $stmt = $conn->prepare("INSERT IGNORE INTO design_categories(name) VALUES(?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
    }
    header("Location: add_backcase_design.php");
    exit;
}

/* ===========================
   ADD CASE TYPE
=========================== */
if (isset($_POST['add_case_type'])) {
    $name = trim($_POST['new_case_type']);
    if ($name != "") {
        $stmt = $conn->prepare("
            INSERT IGNORE INTO category_types(main_category_id,type_name)
            VALUES(2,?)
        ");
        $stmt->bind_param("s", $name);
        $stmt->execute();
    }
    header("Location: add_backcase_design.php");
    exit;
}

/* ===========================
   ADD BACK CASE
=========================== */
if (isset($_POST['add_backcase'])) {

    $design = $_POST['design_name'];
    $designCat = $_POST['design_category'];
    $caseType = $_POST['case_type'];
    $mrp = $_POST['original_price'];
    $price = $_POST['price'];
    $desc = $_POST['description'];

    if ($design == "" || $price == "" || empty($_FILES['image']['name'])) {
        $error = "Please fill required fields";
    } else {

        $folder = "../../uploads/products/backcases/";
        if (!is_dir($folder))
            mkdir($folder, 0777, true);

        $img = time() . "_" . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], $folder . $img);

        $stmt = $conn->prepare("
            INSERT INTO products
            (main_category_id,category_type_id,design_name,
             original_price,price,description,image,design_category_id)
            VALUES(?,?,?,?,?,?,?,?)
        ");
        $stmt->bind_param(
            "iisdsssi",
            $main_category_id,
            $caseType,
            $design,
            $mrp,
            $price,
            $desc,
            $img,
            $designCat
        );
        $stmt->execute();

        $success = "Back Case added successfully!";
    }
}

/* ===========================
   FETCH PRODUCTS
=========================== */
$products = [];
$res = $conn->query("
    SELECT p.*,ct.type_name,dc.name design_cat
    FROM products p
    JOIN category_types ct ON p.category_type_id=ct.id
    LEFT JOIN design_categories dc ON p.design_category_id=dc.id
    WHERE p.main_category_id=2
    ORDER BY p.created_at DESC
");
while ($r = $res->fetch_assoc()) {
    $products[] = $r;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Back Case Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* ===============================
   ROOT VARIABLES
================================ */
        :root {
            --bg: #020617;
            --card: #111827;
            --border: #374151;

            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --danger: #dc2626;
            --warning: #f59e0b;

            --text: #f9fafb;
            --muted: #9ca3af;
        }

        /* ===============================
   RESET
================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
        }

        /* ===============================
   CONTAINER (SIDEBAR SAFE)
================================ */
        .container {
            max-width: 100%;
            padding-left: 280px;
        }

        @media(max-width:992px) {
            .container {
                padding-left: 16px;
                padding-right: 16px;
            }
        }

        /* ===============================
   PAGE TITLE
================================ */
        h3 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        @media(max-width:576px) {
            h3 {
                font-size: 20px;
                text-align: center;
            }
        }

        /* ===============================
   CARD
================================ */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
        }

        /* ===============================
   TOP BAR (FILTER + SEARCH + BUTTON)
================================ */
        .d-flex.gap-2 {
            width: 100%;
            align-items: center;
        }

        /* Select + Input */
        .form-control,
        .form-select {
            background: #020617;
            border: 1px solid var(--border);
            color: var(--text);
            font-size: 14px;
            height: 42px;
        }

        .form-control::placeholder {
            color: var(--muted);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: none;
            background: #020617;
            color: var(--text);
        }

        /* Add Button */
        .btn-primary {
            background: var(--primary);
            border: none;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        /* ===============================
   TABLE
================================ */
        .table-dark {
            --bs-table-bg: transparent;
            --bs-table-border-color: var(--border);
        }

        .table-dark th {
            background: #020617;
            color: var(--muted);
            font-size: 13px;
            white-space: nowrap;
        }

        .table-dark td {
            font-size: 14px;
            vertical-align: middle;
            white-space: nowrap;
        }

        /* Image column */
        .table td:first-child {
            width: 90px;
            text-align: center;
        }

        .table img {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border);
            display: block;
            margin: auto;
        }

        .modal-dialog {
            max-width: 700px;

            margin: 5%;
            /* Center align the modal horizontally */
        }
.type-badge {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 12px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .5px;
            border-radius: 999px;
            color: #e0e7ff;
            background: linear-gradient(135deg, #1f2937, #020617);
            border: 1px solid var(--border);
            text-transform: uppercase;
        }
        /* ===============================
   ACTION BUTTONS
================================ */
        .btn-warning {
            background: var(--warning);
            border: none;
            color: #000;
        }

        .btn-danger {
            background: var(--danger);
            border: none;
        }

        /* ===============================
   MOBILE GRID VIEW (LIKE PRODUCT LIST)
================================ */
        @media(max-width:576px) {

            .table thead {
                display: none;
            }

            .table tbody {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .table tbody tr {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                background: #020617;
                border: 1px solid var(--border);
                border-radius: 16px;
                padding: 14px 10px;
                text-align: center;
                min-height: 150px;
            }

            .table tbody td:nth-child(1),
            .table tbody td:nth-child(3),
            .table tbody td:nth-child(5) {
                display: none;
            }

            .table tbody td:nth-child(2) {
                font-size: 16px;
                font-weight: 600;
                margin-bottom: 6px;
            }

            .table tbody td:nth-child(4) {
                font-size: 12px;
                color: var(--muted);
                text-transform: uppercase;
                margin-bottom: 10px;
            }

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
            }
        }

        /* ===============================
   MODAL (ADD BACK CASE)
================================ */
        .modal-content {
            background: #020617;
            border: 1px solid var(--border);
            border-radius: 14px;
        }

        .modal-body {
            padding: 20px;
        }

        /* ===============================
   ALERTS
================================ */
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

        /* ===============================
   SCROLLBAR
================================ */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-thumb {
            background: #374151;
            border-radius: 10px;
        }
    </style>
</head>

<body>

    <div class="container py-4">

        <h3>Back Case Manager</h3>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

        <!-- TOP BAR -->
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <select id="catFilter" class="form-select w-auto">
                <option value="all">ALL</option>
                <?php foreach ($designCats as $d): ?>
                    <option value="<?= $d['name'] ?>"><?= strtoupper($d['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <input type="text" id="searchBox" class="form-control w-25" placeholder="Search design">

            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                + Add Back Case
            </button>
        </div>

        <!-- PRODUCT TABLE -->
        <div class="card p-3">
            <table class="table table-dark table-hover align-middle">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Design</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>MRP</th>
                        <th>Price</th>
                        <th>Action</th>

                    </tr>
                </thead>
                <tbody id="productTable">
                    <?php foreach ($products as $p): ?>
                        <tr data-cat="<?= $p['design_cat'] ?>">
                            <td><img src="../../uploads/products/backcases/<?= $p['image'] ?>"></td>
                            <td><?= $p['design_name'] ?></td>
                            <td><?= $p['design_cat'] ?></td>
                            <td><span class="type-badge"><?= strtoupper($p['type_name']) ?></span></td>
                            <td><s>₹<?= $p['original_price'] ?></s></td>
                            <td>₹<?= $p['price'] ?></td>
                            <td>
                                <a href="product_edit.php?id=<?= $p['id'] ?>&from=backcase"
                                    class="btn btn-sm btn-warning">Edit</a>


                                <a href="?delete=<?= $p['id'] ?>" onclick="return confirm('Delete this protector?')"
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

    <!-- ADD MODAL -->
    <div class="modal fade" id="addModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Protector</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <!-- ADD DESIGN CATEGORY -->
                    <form method="post" class="d-flex gap-2 mb-3">
                        <input name="new_design_category" class="form-control" placeholder="New Design Category">
                        <button name="add_design_category" class="btn btn-outline-primary">Add</button>
                    </form>

                    <!-- ADD CASE TYPE -->
                    <form method="post" class="d-flex gap-2 mb-3">
                        <input name="new_case_type" class="form-control" placeholder="New Case Type">
                        <button name="add_case_type" class="btn btn-outline-warning">Add</button>
                    </form>
                    <hr>

                    <form method="post" enctype="multipart/form-data">

                        <input name="design_name" class="form-control mb-2" placeholder="Design Name" required>

                        <select name="design_category" class="form-select mb-2" required>
                            <option value="">Select Design Category</option>
                            <?php foreach ($designCats as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= $d['name'] ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select name="case_type" class="form-select mb-2" required>
                            <option value="">Select Case Type</option>
                            <?php foreach ($caseTypes as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= strtoupper($t['type_name']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <input name="original_price" type="number" class="form-control mb-2"
                            placeholder="Original Price">
                        <input name="price" type="number" class="form-control mb-2" placeholder="Selling Price"
                            required>

                        <textarea name="description" class="form-control mb-2" placeholder="Description"></textarea>

                        <input type="file" name="image" class="form-control mb-3" required>

                        <button name="add_backcase" class="btn btn-primary w-100">Save</button>

                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        const rows = document.querySelectorAll("#productTable tr");
        const cat = document.getElementById("catFilter");
        const search = document.getElementById("searchBox");

        function filter() {
            let c = cat.value;
            let q = search.value.toLowerCase();
            rows.forEach(r => {
                let okCat = (c === "all" || r.dataset.cat === c);
                let okText = r.children[1].innerText.toLowerCase().includes(q);
                r.style.display = (okCat && okText) ? "" : "none";
            });
        }
        cat.onchange = filter;
        search.onkeyup = filter;
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php ob_end_flush(); ?>