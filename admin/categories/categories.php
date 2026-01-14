<?php
include "../includes/admin_auth.php";
include "../includes/admin_db.php";

$success = "";

/* ADD CATEGORY */
if (isset($_POST['add_category'])) {

    $name = trim($_POST['name']);
    $slug = strtolower(trim($_POST['slug']));
    $show = isset($_POST['show_on_home']) ? 1 : 0;

    if ($name && $slug) {

        $imgName = null;

        if (!empty($_FILES['image']['name'])) {
            $folder = "../../uploads/categories/";
            if (!is_dir($folder)) mkdir($folder, 0777, true);

            $imgName = time() . "_" . $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], $folder . $imgName);
        }

        $stmt = $conn->prepare("
            INSERT INTO categories (name, slug, image, show_on_home)
            VALUES (?,?,?,?)
        ");
        $stmt->bind_param("sssi", $name, $slug, $imgName, $show);
        $stmt->execute();

        $success = "Category added successfully!";
    }
}

/* FETCH CATEGORIES */
$cats = $conn->query("SELECT * FROM categories ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
<title>Category Manager</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<?php include "../includes/sidebar.php"; ?>
<?php include "../includes/header.php"; ?>

<div class="container py-4" style="padding-left:280px">

<h3>Category Manager</h3>

<?php if($success): ?>
<div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<!-- ADD CATEGORY -->
<div class="card p-3 mb-4">
<form method="post" enctype="multipart/form-data" class="row g-2">
    <div class="col-md-3">
        <input type="text" name="name" class="form-control" placeholder="Category Name" required>
    </div>
    <div class="col-md-3">
        <input type="text" name="slug" class="form-control" placeholder="slug-example" required>
    </div>
    <div class="col-md-3">
        <input type="file" name="image" class="form-control">
    </div>
    <div class="col-md-2">
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="show_on_home" checked>
            <label class="form-check-label">Show on Home</label>
        </div>
    </div>
    <div class="col-md-1">
        <button name="add_category" class="btn btn-primary w-100">Add</button>
    </div>
</form>
</div>

<!-- CATEGORY LIST -->
<div class="card p-3">
<table class="table table-bordered">
<thead>
<tr>
    <th>Image</th>
    <th>Name</th>
    <th>Slug</th>
    <th>Home</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php while($c = $cats->fetch_assoc()): ?>
<tr>
    <td>
        <?php if($c['image']): ?>
            <img src="../../uploads/categories/<?= $c['image'] ?>" width="50">
        <?php endif; ?>
    </td>
    <td><?= htmlspecialchars($c['name']) ?></td>
    <td><?= $c['slug'] ?></td>

    <td>
        <input type="checkbox" <?= $c['show_on_home']?'checked':'' ?>
               onclick="toggle('show_on_home',<?= $c['id'] ?>)">
    </td>

    <td>
        <input type="checkbox" <?= $c['status']?'checked':'' ?>
               onclick="toggle('status',<?= $c['id'] ?>)">
    </td>

    <td>
        <a href="category_delete.php?id=<?= $c['id'] ?>"
           onclick="return confirm('Delete this category?')"
           class="btn btn-sm btn-danger">Delete</a>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

</div>

<script>
function toggle(field,id){
    fetch('category_toggle.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'field='+field+'&id='+id
    });
}
</script>

</body>
</html>
