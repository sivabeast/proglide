<?php
include "../includes/admin_db.php";

$brand_id = $_GET['brand_id'] ?? '';

$where = "";
if ($brand_id !== '') {
    $bid = (int)$brand_id;
    $where = "WHERE pm.brand_id = $bid";
}

$sql = "
SELECT 
    pm.id,
    pm.model_name,
    pm.status,
    b.name AS brand_name
FROM phone_models pm
JOIN brands b ON b.id = pm.brand_id
$where
ORDER BY b.name, pm.model_name
";

$res = $conn->query($sql);

if (!$res || $res->num_rows === 0) {
    echo "<p class='text-muted'>No models found.</p>";
    exit;
}

while ($row = $res->fetch_assoc()):
?>
<div class="model-card">
    <div>
        <strong><?= htmlspecialchars($row['model_name']) ?></strong><br>
        <small class="text-muted"><?= htmlspecialchars($row['brand_name']) ?></small>

        <?php if ($row['status'] === 'hidden'): ?>
            <span class="badge hidden-badge ms-2">Hidden</span>
        <?php else: ?>
            <span class="badge active-badge ms-2">Active</span>
        <?php endif; ?>
    </div>

    <div class="model-actions">
        <button class="btn btn-sm btn-outline-secondary"
                onclick="toggleModel(<?= $row['id'] ?>)">
            <?= $row['status']==='hidden' ? 'Show' : 'Hide' ?>
        </button>

        <button class="btn btn-sm btn-outline-danger"
                onclick="deleteModel(<?= $row['id'] ?>)">
            Delete
        </button>
    </div>
</div>
<?php endwhile; ?>
