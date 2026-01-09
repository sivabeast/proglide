<?php
include "../includes/admin_auth.php";
include "../includes/admin_db.php";

/* ===========================
   GET SOURCE PAGE
=========================== */
$from = $_GET['from'] ?? 'products';
$returnPage = ($from === 'backcase')
    ? 'add_backcase_design.php'
    : 'add_protector.php';

if (!isset($_GET['id'])) {
    header("Location: $returnPage");
    exit;
}

$id = (int)$_GET['id'];

/* ===========================
   FETCH PRODUCT
=========================== */
$stmt = $conn->prepare("
    SELECT 
        p.*,
        mc.name AS main_category,
        ct.type_name
    FROM products p
    JOIN main_categories mc ON p.main_category_id = mc.id
    JOIN category_types ct ON p.category_type_id = ct.id
    WHERE p.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
    die("Product not found");
}

$product = $res->fetch_assoc();

$success = "";
$error   = "";

/* ===========================
   UPDATE PRODUCT
=========================== */
if (isset($_POST['update_product'])) {

    $price    = trim($_POST['price']);
    $mrp      = trim($_POST['original_price']);
    $desc     = trim($_POST['description']);
    $oldImage = $product['image'];

    if ($price === "") {
        $error = "Selling price is required";
    } else {

        /* DETECT FOLDER */
        $isBackCase = ($product['main_category'] === 'Back Case');
        $folderType = $isBackCase ? 'backcases' : 'protectors';

        $folder = "../../uploads/products/$folderType/";
        if (!is_dir($folder)) mkdir($folder, 0777, true);

        /* IMAGE UPDATE */
        if (!empty($_FILES['image']['name'])) {

            $newImage = time() . "_" . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $folder . $newImage);

            if ($oldImage && file_exists($folder . $oldImage)) {
                unlink($folder . $oldImage);
            }

        } else {
            $newImage = $oldImage;
        }

        /* UPDATE QUERY */
        $up = $conn->prepare("
            UPDATE products
            SET original_price = ?,
                price = ?,
                description = ?,
                image = ?
            WHERE id = ?
        ");
        $up->bind_param("ddssi", $mrp, $price, $desc, $newImage, $id);

        if ($up->execute()) {
            header("Location: $returnPage");
            exit;
        } else {
            $error = "Update failed";
        }
    }
}

/* NAME FIELD */
$nameValue = ($product['main_category'] === 'Back Case')
    ? $product['design_name']
    : $product['model_name'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Product | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{background:#020617;color:#f9fafb;font-family:Poppins}
.container{padding-left:280px}
@media(max-width:992px){.container{padding-left:16px}}

.card{background:#111827;border:1px solid #374151;border-radius:14px}

.form-control{
    background:#020617;
    border:1px solid #374151;
    color:#fff;
}

.form-control:focus{
    border-color:#6366f1;
    box-shadow:0 0 0 2px rgba(99,102,241,.25);
}
label{
    color: #9a9999ff;
}
.preview-img{
    width:140px;
    height:140px;
    object-fit:cover;
    border-radius:12px;
    border:1px solid #374151;
}

.btn-primary{
    background:#6366f1;
    border:none;
    border-radius:12px;
}
</style>
</head>

<body>

<div class="container py-5">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>Edit <?= htmlspecialchars($product['main_category']) ?></h3>
    <a href="<?= $returnPage ?>" class="btn btn-secondary">← Back</a>
</div>

<?php if($error): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<div class="card p-4">

<form method="post" enctype="multipart/form-data">

<div class="mb-3">
    <label><?= ($product['main_category']==='Back Case') ? 'Design Name' : 'Model Name' ?></label>
    <input type="text" class="form-control"
           value="<?= htmlspecialchars($nameValue) ?>">
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label>Category</label>
        <input type="text" class="form-control"
               value="<?= $product['main_category'] ?>" readonly>
    </div>

    <div class="col-md-6 mb-3">
        <label>Type</label>
        <input type="text" class="form-control"
               value="<?= strtoupper($product['type_name']) ?>" readonly>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label>Original Price (₹)</label>
        <input type="number" step="0.01"
               name="original_price"
               class="form-control"
               value="<?= $product['original_price'] ?>">
    </div>

    <div class="col-md-6 mb-3">
        <label>Selling Price (₹)</label>
        <input type="number" step="0.01"
               name="price"
               class="form-control"
               value="<?= $product['price'] ?>" required>
    </div>
</div>

<div class="mb-3">
    <label>Description</label>
    <textarea name="description" rows="4"
              class="form-control"><?= htmlspecialchars($product['description']) ?></textarea>
</div>

<div class="mb-3">
    <label>Current Image</label><br>
    <img src="../../uploads/products/<?= ($product['main_category']==='Back Case')?'backcases':'protectors' ?>/<?= $product['image'] ?>"
         class="preview-img">
</div>

<div class="mb-4">
    <label>Change Image</label>
    <input type="file" name="image" class="form-control">
    <small class="text-muted">Leave empty to keep existing image</small>
</div>

<button name="update_product" class="btn btn-primary w-100">
    Update Product
</button>

</form>

</div>
</div>

</body>
</html>
