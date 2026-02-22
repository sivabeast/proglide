<?php
include "includes/auth.php";
include "includes/db.php";
include "includes/header.php";

/*
 Fetch categories added by admin
 + count total products in each category
*/
$categories = $conn->query("
    SELECT 
        c.id,
        c.name,
        c.image,
        COUNT(p.id) AS total_products
    FROM categories c
    LEFT JOIN products p 
        ON p.category_id = c.id AND p.status = 1
    WHERE c.status = 1
    GROUP BY c.id
    ORDER BY c.name ASC
");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Categories | Proglide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: #0a0a0a;
            color: #fff;
        }

        /* CATEGORY CARD */
       
.category-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(255, 107, 53, 0.2);
}

.category-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

        /* IMAGE */
        .category-img {
            height: 180px;
            background: #000;
        }

        .category-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* CONTENT */
        .category-body {
            padding: 16px;
            text-align: center;
        }

        .category-name {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .product-count {
            font-size: .9rem;
            color: #ff6b35;
            margin-top: 6px;
        }
    </style>
</head>

<body>

    <div class="container py-4">

        <!-- PAGE TITLE -->
        <div class="text-center mb-4">
            <h2 class="fw-bold">Browse Categories</h2>
            <p class="text-muted">Admin added categories only</p>
        </div>

        <!-- CATEGORIES GRID -->
        <div class="row g-3">

            <!-- categories.php இல் இந்த பகுதியை மாற்றவும் -->
<?php if($categories->num_rows > 0): ?>
    <?php while($cat = $categories->fetch_assoc()): ?>
    <div class="col-6 col-md-4 col-lg-3">
        <!-- இங்கு <a> tag சேர்க்கவும் -->
        <a href="products.php?cat=<?= $cat['id'] ?>" style="text-decoration: none; color: inherit;">
            <div class="category-card">
                <!-- IMAGE -->
                <?php
                $imagePath = "../uploads/categories/" . $cat['image'];
                ?>
                <div class="category-img">
                    <?php if (!empty($cat['image']) && file_exists($imagePath)): ?>
                        <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($cat['name']) ?>">
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center h-100 text-secondary">
                            <i class="fas fa-folder fa-2x"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- CONTENT -->
                <div class="category-body">
                    <div class="category-name">
                        <?= htmlspecialchars($cat['name']) ?>
                    </div>
                    <div class="product-count">
                        <?= $cat['total_products'] ?> Products
                    </div>
                </div>
            </div>
        </a>
    </div>
    <?php endwhile; ?>
<?php endif; ?>

        </div>
    </div>
    <?php include "includes/footer.php"; ?>
    <?php include "includes/mobile_bottom_nav.php"; ?>
</body>

</html>

<?php $conn->close(); ?>