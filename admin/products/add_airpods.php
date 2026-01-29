<?php
ob_start();

include "../includes/admin_auth.php";
include "../includes/admin_db.php";
include "../includes/sidebar.php";
include "../includes/header.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$success = "";
$error = "";

// Get AirPods category ID
$airpodsCategory = null;
$catRes = $conn->query("SELECT id FROM categories WHERE slug = 'airpods' LIMIT 1");
if ($catRes->num_rows > 0) {
    $airpodsCategory = $catRes->fetch_assoc()['id'];
} else {
    $error = "AirPods category not found!";
}

/* ===========================
   FETCH AIRPODS MATERIAL TYPES
=========================== */
$materialTypes = [];
if ($airpodsCategory) {
    $res = $conn->query("
        SELECT id, name 
        FROM material_types 
        WHERE category_id = $airpodsCategory AND status = 1
        ORDER BY name
    ");
    while ($r = $res->fetch_assoc()) {
        $materialTypes[] = $r;
    }
}

/* ===========================
   FETCH AIRPODS VARIANT TYPES
=========================== */
$variantTypes = [];
if ($airpodsCategory) {
    $res = $conn->query("
        SELECT id, name 
        FROM variant_types 
        WHERE category_id = $airpodsCategory AND status = 1
        ORDER BY name
    ");
    while ($r = $res->fetch_assoc()) {
        $variantTypes[] = $r;
    }
}

/* ===========================
   ADD MATERIAL TYPE FOR AIRPODS
=========================== */
if (isset($_POST['add_material_type'])) {
    $name = trim($_POST['new_material_type']);
    if ($name != "" && $airpodsCategory) {
        $stmt = $conn->prepare("
            INSERT INTO material_types (category_id, name)
            VALUES (?, ?)
        ");
        $stmt->bind_param("is", $airpodsCategory, $name);
        $stmt->execute();
        $success = "Material type added!";
        header("Refresh:1");
        exit;
    }
}

/* ===========================
   ADD VARIANT TYPE FOR AIRPODS
=========================== */
if (isset($_POST['add_variant_type'])) {
    $name = trim($_POST['new_variant_type']);
    if ($name != "" && $airpodsCategory) {
        $stmt = $conn->prepare("
            INSERT INTO variant_types (category_id, name)
            VALUES (?, ?)
        ");
        $stmt->bind_param("is", $airpodsCategory, $name);
        $stmt->execute();
        $success = "Variant type added!";
        header("Refresh:1");
        exit;
    }
}

/* ===========================
   ADD AIRPODS
=========================== */
if (isset($_POST['add_airpods'])) {
    $model = trim($_POST['model_name']);
    $material_type_id = $_POST['material_type'];
    $variant_type_id = $_POST['variant_type'];
    $original_price = $_POST['original_price'];
    $price = $_POST['price'];
    $description = trim($_POST['description']);
    $is_popular = isset($_POST['is_popular']) ? 1 : 0;

    if ($model == "" || $price == "" || empty($_FILES['image1']['name'])) {
        $error = "Please fill all required fields (Model Name, Price, and Main Image)";
    } elseif (!$airpodsCategory) {
        $error = "AirPods category not found!";
    } else {
        $folder = "../../uploads/products/airpods/";
        if (!is_dir($folder))
            mkdir($folder, 0777, true);

        // Handle image uploads
        $image1 = "";
        $image2 = "";
        $image3 = "";
        
        if (!empty($_FILES['image1']['name'])) {
            $image1 = time() . "_1_" . $_FILES['image1']['name'];
            move_uploaded_file($_FILES['image1']['tmp_name'], $folder . $image1);
        }
        
        if (!empty($_FILES['image2']['name'])) {
            $image2 = time() . "_2_" . $_FILES['image2']['name'];
            move_uploaded_file($_FILES['image2']['tmp_name'], $folder . $image2);
        }
        
        if (!empty($_FILES['image3']['name'])) {
            $image3 = time() . "_3_" . $_FILES['image3']['name'];
            move_uploaded_file($_FILES['image3']['tmp_name'], $folder . $image3);
        }

        $stmt = $conn->prepare("
            INSERT INTO products
            (category_id, material_type_id, variant_type_id, model_name,
             original_price, price, description, image1, image2, image3, is_popular)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iiisdsssssi",
            $airpodsCategory,
            $material_type_id,
            $variant_type_id,
            $model,
            $original_price,
            $price,
            $description,
            $image1,
            $image2,
            $image3,
            $is_popular
        );
        
        if ($stmt->execute()) {
            $success = "AirPods added successfully!";
        } else {
            $error = "Error adding AirPods: " . $conn->error;
        }
        $stmt->close();
    }
}

/* ===========================
   FETCH AIRPODS PRODUCTS
=========================== */
$products = [];
if ($airpodsCategory) {
    $res = $conn->query("
        SELECT p.*, 
               mt.name as material_name,
               vt.name as variant_name,
               c.name as category_name
        FROM products p
        LEFT JOIN material_types mt ON p.material_type_id = mt.id
        LEFT JOIN variant_types vt ON p.variant_type_id = vt.id
        JOIN categories c ON p.category_id = c.id
        WHERE p.category_id = $airpodsCategory
        ORDER BY p.created_at DESC
    ");
    while ($row = $res->fetch_assoc()) {
        $products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>AirPods Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* =========================================================
   ADMIN AIRPODS MANAGER – FULL CSS
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

        .alert-warning {
            background: #451a03;
            border: 1px solid #7c2d12;
            color: #fed7aa;
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

        /* BADGES */
        .material-badge {
            display: inline-block;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .5px;
            border-radius: 6px;
            background: linear-gradient(135deg, #1e40af, #1e3a8a);
            border: 1px solid #3b82f6;
            text-transform: uppercase;
            margin-right: 5px;
        }

        .variant-badge {
            display: inline-block;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .5px;
            border-radius: 6px;
            background: linear-gradient(135deg, #059669, #047857);
            border: 1px solid #10b981;
            text-transform: uppercase;
        }

        /* POPULAR CHECKBOX */
        .popular-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .modal-dialog {
            max-width: 700px;
            margin: 5%;
        }

        /* IMAGE PREVIEW */
        .image-preview {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px dashed var(--border);
            margin: 5px;
            display: none;
        }
        
        .image-preview-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 10px 0;
        }

        /* ===============================
   MOBILE CARD VIEW
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
                min-height: 200px;
            }

            .table tbody td:nth-child(1) {
                display: none;
            }

            .table tbody td:nth-child(2) {
                font-size: 15px;
                font-weight: 600;
                margin-bottom: 4px;
            }

            .table tbody td:nth-child(3) {
                display: flex;
                flex-direction: column;
                gap: 4px;
                margin-bottom: 8px;
            }

            .table tbody td:nth-child(4) {
                display: none;
            }

            .table tbody td:nth-child(5) {
                font-size: 14px;
                font-weight: 600;
                margin-bottom: 10px;
            }

            .table tbody td:nth-child(6) {
                margin-bottom: 8px;
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

        /* FORM GROUP STYLES */
        .form-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid var(--border);
        }

        .form-section h6 {
            color: var(--primary);
            margin-bottom: 10px;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="container py-4">

        <h3>AirPods Manager</h3>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <!-- ADD MATERIAL AND VARIANT TYPES -->
        <div class="row mb-3">
            <div class="col-md-6">
                <form method="post" class="d-flex gap-2">
                    <input type="text" name="new_material_type" class="form-control" placeholder="New Material Type (e.g., Silicon, Plastic, Leather)" required>
                    <button name="add_material_type" class="btn btn-success">Add Material</button>
                </form>
            </div>
            <div class="col-md-6">
                <form method="post" class="d-flex gap-2">
                    <input type="text" name="new_variant_type" class="form-control" placeholder="New Variant Type (e.g., Pro 2, 3rd Gen, Case Cover)" required>
                    <button name="add_variant_type" class="btn btn-warning">Add Variant</button>
                </form>
            </div>
        </div>

        <!-- FILTER BAR -->
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <select id="materialFilter" class="form-select w-auto">
                <option value="all">ALL MATERIALS</option>
                <?php foreach ($materialTypes as $mt): ?>
                    <option value="<?= $mt['name'] ?>"><?= strtoupper($mt['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <select id="variantFilter" class="form-select w-auto">
                <option value="all">ALL VARIANTS</option>
                <?php foreach ($variantTypes as $vt): ?>
                    <option value="<?= $vt['name'] ?>"><?= strtoupper($vt['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <input type="text" id="searchBox" class="form-control w-25" placeholder="Search model">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ Add AirPods</button>
        </div>

        <!-- PRODUCT TABLE -->
        <div class="card p-3">
            <div class="table-responsive">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Model</th>
                            <th>Type</th>
                            <th>MRP</th>
                            <th>Price</th>
                            <th>Popular</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productTable">
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No AirPods found. Add your first AirPods product.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $p): 
                                $imageSrc = !empty($p['image1']) ? "../../uploads/products/airpods/" . $p['image1'] : 'https://via.placeholder.com/55x55?text=No+Image';
                            ?>
                                <tr data-material="<?= $p['material_name'] ?>" data-variant="<?= $p['variant_name'] ?>">
                                    <td>
                                        <img src="<?= $imageSrc ?>" 
                                             alt="<?= htmlspecialchars($p['model_name']) ?>"
                                             onerror="this.src='https://via.placeholder.com/55x55?text=No+Image'">
                                    </td>
                                    <td><?= htmlspecialchars($p['model_name']) ?></td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="material-badge"><?= $p['material_name'] ?></span>
                                            <span class="variant-badge"><?= $p['variant_name'] ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($p['original_price'])): ?>
                                            <s>₹<?= number_format($p['original_price'], 2) ?></s>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>₹<?= number_format($p['price'], 2) ?></td>
                                    <td>
                                        <input type="checkbox" class="form-check-input popular-checkbox popular-toggle" 
                                               data-id="<?= $p['id'] ?>" 
                                               <?= $p['is_popular'] ? 'checked' : '' ?>>
                                    </td>
                                    <td>
                                        <a href="product_edit.php?id=<?= $p['id'] ?>&from=airpods"
                                            class="btn btn-sm btn-warning">Edit</a>
                                        <a href="product_delete.php?id=<?= $p['id'] ?>" 
                                           onclick="return confirm('Delete this AirPods product?')"
                                           class="btn btn-sm btn-danger">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
                    <h5 class="modal-title">Add New AirPods Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" enctype="multipart/form-data" id="airpodsForm">
                    <div class="modal-body">
                        <?php if (!$airpodsCategory): ?>
                            <div class="alert alert-danger">
                                AirPods category not found! Please check your database.
                            </div>
                        <?php elseif (empty($materialTypes) || empty($variantTypes)): ?>
                            <div class="alert alert-warning">
                                Please add at least one Material Type and one Variant Type first!
                            </div>
                        <?php else: ?>
                            <div class="form-section">
                                <h6>Basic Information</h6>
                                <div class="mb-3">
                                    <label class="form-label">Product Name *</label>
                                    <input type="text" name="model_name" class="form-control" 
                                           placeholder="e.g., AirPods Pro 2 Case, AirPods 3rd Gen Cover" required>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Material Type *</label>
                                        <select name="material_type" class="form-select" required>
                                            <option value="">Select Material</option>
                                            <?php foreach ($materialTypes as $mt): ?>
                                                <option value="<?= $mt['id'] ?>"><?= $mt['name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Variant Type *</label>
                                        <select name="variant_type" class="form-select" required>
                                            <option value="">Select Variant</option>
                                            <?php foreach ($variantTypes as $vt): ?>
                                                <option value="<?= $vt['id'] ?>"><?= $vt['name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h6>Pricing</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Original Price (MRP)</label>
                                        <input type="number" name="original_price" class="form-control" 
                                               placeholder="Original Price" step="0.01" min="0">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Selling Price *</label>
                                        <input type="number" name="price" class="form-control" 
                                               placeholder="Selling Price" step="0.01" min="0" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h6>Description</h6>
                                <div class="mb-3">
                                    <textarea name="description" class="form-control" 
                                              placeholder="Description of the AirPods product (features, compatibility, etc.)" 
                                              rows="3"></textarea>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h6>Images</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Main Image (Required) *</label>
                                        <input type="file" name="image1" class="form-control" accept="image/*" required
                                               onchange="previewImage(this, 'preview1')">
                                        <div class="image-preview-container">
                                            <img id="preview1" class="image-preview" alt="Image 1 Preview">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Image 2 (Optional)</label>
                                        <input type="file" name="image2" class="form-control" accept="image/*"
                                               onchange="previewImage(this, 'preview2')">
                                        <div class="image-preview-container">
                                            <img id="preview2" class="image-preview" alt="Image 2 Preview">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Image 3 (Optional)</label>
                                        <input type="file" name="image3" class="form-control" accept="image/*"
                                               onchange="previewImage(this, 'preview3')">
                                        <div class="image-preview-container">
                                            <img id="preview3" class="image-preview" alt="Image 3 Preview">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h6>Settings</h6>
                                <div class="form-check">
                                    <input type="checkbox" name="is_popular" class="form-check-input" id="isPopular">
                                    <label class="form-check-label" for="isPopular">
                                        Mark as Popular Product
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button name="add_airpods" class="btn btn-primary" 
                                <?= (!$airpodsCategory || empty($materialTypes) || empty($variantTypes)) ? 'disabled' : '' ?>>
                            Save AirPods Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Filter functionality
        const rows = document.querySelectorAll("#productTable tr");
        const materialFilter = document.getElementById("materialFilter");
        const variantFilter = document.getElementById("variantFilter");
        const searchBox = document.getElementById("searchBox");

        function applyFilter() {
            const material = materialFilter.value;
            const variant = variantFilter.value;
            const query = searchBox.value.toLowerCase();

            rows.forEach(row => {
                if (row.cells.length <= 1) return; // Skip empty row
                
                const matchMaterial = (material === "all" || row.dataset.material === material);
                const matchVariant = (variant === "all" || row.dataset.variant === variant);
                const matchText = row.cells[1].innerText.toLowerCase().includes(query);
                
                row.style.display = (matchMaterial && matchVariant && matchText) ? "" : "none";
            });
        }

        if (materialFilter) materialFilter.addEventListener("change", applyFilter);
        if (variantFilter) variantFilter.addEventListener("change", applyFilter);
        if (searchBox) searchBox.addEventListener("keyup", applyFilter);

        // Image preview function
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        }

        // Toggle popular status
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
                    .then(response => {
                        console.log('Server response:', response);
                        if (response.trim() === 'success') {
                            showNotification('Popular status updated!', 'success');
                        } else {
                            showNotification('Error updating status', 'error');
                            this.checked = !this.checked; // Revert checkbox
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Network error', 'error');
                        this.checked = !this.checked; // Revert checkbox
                    });
                });
            });
        });

        // Notification function
        function showNotification(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const notification = document.createElement('div');
            notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
            notification.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
            `;
            notification.innerHTML = `
                <strong>${type === 'success' ? '✓' : '✗'}</strong> ${message}
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        }

        // Reset form after modal is closed
        document.getElementById('addModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('airpodsForm').reset();
            // Clear previews
            ['preview1', 'preview2', 'preview3'].forEach(id => {
                const preview = document.getElementById(id);
                preview.style.display = 'none';
                preview.src = '';
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php ob_end_flush(); ?>