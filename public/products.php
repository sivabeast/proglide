<?php
session_start();
require "includes/db.php";

$user_id = $_SESSION['user_id'] ?? null;

/* =========================
   INPUTS (FILTERS ONLY)
========================= */
$productType = $_GET['ptype'] ?? '';
$protectorTypes = $_GET['protector_type'] ?? [];
$backcaseTypes = $_GET['backcase_type'] ?? [];
$designCat = $_GET['design_cat'] ?? '';
$sort = $_GET['sort'] ?? 'new';

/* =========================
   FILTER ACTIVE (keep sidebar open after reload if any filter set)
========================= */
$filterActive = (
    $productType !== '' ||
    !empty($protectorTypes) ||
    !empty($backcaseTypes) ||
    $designCat !== '' ||
    $sort !== 'new'
);

/* =========================
   SORT
========================= */
$orderSql = "p.created_at DESC";
if ($sort === 'low')
    $orderSql = "p.price ASC";
if ($sort === 'high')
    $orderSql = "p.price DESC";

/* =========================
   BASE SQL (NO SEARCH HERE)
========================= */
$sql = "
SELECT 
    p.*,
    mc.name AS main_category,
    ct.type_name
FROM products p
JOIN main_categories mc ON mc.id = p.main_category_id
LEFT JOIN category_types ct ON ct.id = p.category_type_id
LEFT JOIN design_categories dc ON dc.id = p.design_category_id
WHERE 1
";

$params = [];
$types = "";

/* =========================
   PRODUCT TYPE
========================= */
if ($productType === "protector") {
    $sql .= " AND mc.name='Protector'";
}
if ($productType === "backcase") {
    $sql .= " AND mc.name='Back Case'";
}

/* =========================
   PROTECTOR TYPES
========================= */
if ($productType === "protector" && !empty($protectorTypes)) {
    $in = implode(',', array_fill(0, count($protectorTypes), '?'));
    $sql .= " AND ct.type_name IN ($in)";
    $params = array_merge($params, $protectorTypes);
    $types .= str_repeat("s", count($protectorTypes));
}

/* =========================
   BACK CASE TYPES
========================= */
if ($productType === "backcase" && !empty($backcaseTypes)) {
    $in = implode(',', array_fill(0, count($backcaseTypes), '?'));
    $sql .= " AND ct.type_name IN ($in)";
    $params = array_merge($params, $backcaseTypes);
    $types .= str_repeat("s", count($backcaseTypes));
}

/* =========================
   DESIGN CATEGORY
========================= */
if ($productType === "backcase" && $designCat !== '') {
    $sql .= " AND dc.id=?";
    $params[] = (int) $designCat;
    $types .= "i";
}

$sql .= " ORDER BY $orderSql";

/* =========================
   EXECUTE
========================= */
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

/* =========================
   FETCH PRODUCTS
========================= */
$products = [];

while ($row = $res->fetch_assoc()) {

    /* Wishlist */
    $row['in_wishlist'] = false;
    if ($user_id) {
        $w = $conn->prepare(
            "SELECT id FROM wishlist WHERE user_id=? AND product_id=?"
        );
        $w->bind_param("ii", $user_id, $row['id']);
        $w->execute();
        $row['in_wishlist'] = $w->get_result()->num_rows > 0;
    }

    /* Cart (Protector only) */
    $row['in_cart'] = false;
    if ($user_id && strtolower($row['main_category']) === 'protector') {
        $c = $conn->prepare(
            "SELECT id FROM cart
             WHERE user_id=? AND product_id=? AND phone_model_id IS NULL"
        );
        $c->bind_param("ii", $user_id, $row['id']);
        $c->execute();
        $row['in_cart'] = $c->get_result()->num_rows > 0;
    }

    $products[] = $row;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Products</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="products.css">
</head>

<body>

    <?php include "includes/header.php"; ?>

    <div class="pcontainer">

        <!-- ================= SEARCH (AJAX) ================= -->
        <form class="search-bar" onsubmit="return false;">
            <input type="text" id="searchInput" placeholder="Search products...">
        </form>

        <!-- FILTER TOGGLE -->
        <div class="filter-toggle">
            <button id="filterToggle" type="button">
                ☰ Filters
            </button>
        </div>

        <div class="page-layout">

            <!-- ================= FILTER ================= -->
            <form class="filter-box <?= $filterActive ? 'active' : '' ?>" id="filterBox">
                <div class="filter-group">
                    <b>Product Type</b>
                    <select name="ptype" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="protector" <?= $productType === 'protector' ? 'selected' : '' ?>>Protector</option>
                        <option value="backcase" <?= $productType === 'backcase' ? 'selected' : '' ?>>Back Case</option>
                    </select>
                </div>

                <?php if ($productType === 'protector'): ?>
                    <div class="filter-group">
                        <b>Protector Type</b>
                        <?php
                        $r = $conn->query("SELECT type_name FROM category_types WHERE main_category_id=1");
                        while ($c = $r->fetch_assoc()):
                            ?>
                            <label>
                                <input type="checkbox" name="protector_type[]" value="<?= $c['type_name'] ?>"
                                    <?= in_array($c['type_name'], $protectorTypes) ? 'checked' : '' ?>>
                                <?= ucfirst($c['type_name']) ?>
                            </label>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>

                <?php if ($productType === 'backcase'): ?>
                    <div class="filter-group">
                        <b>Back Case Type</b>
                        <?php
                        $r = $conn->query("SELECT type_name FROM category_types WHERE main_category_id=2");
                        while ($c = $r->fetch_assoc()):
                            ?>
                            <label>
                                <input type="checkbox" name="backcase_type[]" value="<?= $c['type_name'] ?>"
                                    <?= in_array($c['type_name'], $backcaseTypes) ? 'checked' : '' ?>>
                                <?= ucfirst($c['type_name']) ?>
                            </label>
                        <?php endwhile; ?>
                    </div>

                    <div class="filter-group">
                        <b>Design Category</b>
                        <select name="design_cat">
                            <option value="">All</option>
                            <?php
                            $r = $conn->query("SELECT id,name FROM design_categories");
                            while ($d = $r->fetch_assoc()):
                                ?>
                                <option value="<?= $d['id'] ?>" <?= $designCat == $d['id'] ? 'selected' : '' ?>>
                                    <?= $d['name'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="filter-group">
                    <b>Price</b>
                    <select name="sort">
                        <option value="new">Newest</option>
                        <option value="low" <?= $sort === 'low' ? 'selected' : '' ?>>Low → High</option>
                        <option value="high" <?= $sort === 'high' ? 'selected' : '' ?>>High → Low</option>
                    </select>
                </div>

                <button class="btn">Apply Filters</button>
            </form>

            <!-- ================= PRODUCTS ================= -->
            <div class="grid" id="productGrid">
                <?php foreach ($products as $p):
                    $isBackCase = strtolower($p['main_category']) === 'back case';
                    $folder = $isBackCase ? 'backcases' : 'protectors';
                    ?>
                    <div class="card">
                        <a href="productdetails.php?id=<?= $p['id'] ?>" class="product-link">
                            <img src="../uploads/products/<?= $folder ?>/<?= htmlspecialchars($p['image']) ?>">
                            
                                <div class="name">
                                    <?= htmlspecialchars($isBackCase ? ($p['design_name'] ?? 'Design') : $p['model_name']) ?>
                                
                                <?php if (!$isBackCase): ?>
                                    ( <?= ucfirst($p['type_name']) ?> )
                                <?php endif; ?></div>
                                <div class="body">
                                <div class="price">₹<?= number_format($p['price'], 2) ?></div>
                            
                        </a>

                        <div class="buttons">
                            <a class="btn-love <?= $p['in_wishlist'] ? 'active' : '' ?>" href="<?= $p['in_wishlist']
                                  ? 'ajax/remove_wishlist.php?id=' . $p['id']
                                  : 'ajax/add_to_wishlist.php?id=' . $p['id'] ?>">
                            <i class="fa <?= $p['in_wishlist'] ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                        </a>

                            <?php if ($isBackCase): ?>
                                <a href="productdetails.php?id=<?= $p['id'] ?>" class="btn">Select Model</a>
                            <?php else: ?>
                                <a class="btn-cart <?= $p['in_cart'] ? 'added' : '' ?>" href="<?= $p['in_cart']
                                  ? 'ajax/remove_cart.php?id=' . $p['id']
                                  : 'ajax/add_to_cart.php?id=' . $p['id'] ?>">
                            <?= $p['in_cart'] ? 'ADDED' : 'ADD TO CART' ?>
                        </a>
                            <?php endif; ?>
                        </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <?php include "includes/footer.php"; ?>
    <?php include "includes/mobile_bottom_nav.php"; ?>


    <!-- ================= AJAX SEARCH SCRIPT ================= -->
    <script>
        const searchInput = document.getElementById("searchInput");
        const productGrid = document.getElementById("productGrid");

        // 🔥 Save initial products HTML
        const originalHTML = productGrid.innerHTML;

        let timer = null;

        searchInput.addEventListener("keyup", function () {

            clearTimeout(timer);
            const q = this.value.trim();

            timer = setTimeout(() => {

                // ✅ EMPTY SEARCH → restore original products
                if (q === "") {
                    productGrid.innerHTML = originalHTML;
                    return;
                }

                fetch("ajax/search_products.php?q=" + encodeURIComponent(q))
                    .then(res => res.text())
                    .then(html => {
                        productGrid.innerHTML = html;
                    })
                    .catch(err => console.error("Search error:", err));

            }, 300);
        });

        // ================= FILTER TOGGLE + OUTSIDE CLICK =================
        const filterToggle = document.getElementById('filterToggle');
        const filterBox = document.getElementById('filterBox');

        filterToggle.addEventListener('click', () => {
            filterBox.classList.toggle('active');
        });

        // Click outside the filter box closes it (useful on small screens)
        document.addEventListener('click', (e) => {
            if (!filterBox.classList.contains('active')) return;
            // ignore clicks inside filterBox or on the toggle button
            if (filterBox.contains(e.target) || filterToggle.contains(e.target)) return;
            filterBox.classList.remove('active');
        });
    </script>
</body>

</html>