<?php
// productdetails.php
session_start();
require "includes/db.php";

$product_id = $_GET['id'] ?? 0;
$select_model = isset($_GET['select_model']);

if (!$product_id) {
    header("Location: products.php");
    exit;
}

// Get product details
$sql = "SELECT 
            p.*,
            c.name as category_name,
            c.id as category_id,
            mt.name as material_name,
            vt.name as variant_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN material_types mt ON p.material_type_id = mt.id
        LEFT JOIN variant_types vt ON p.variant_type_id = vt.id
        WHERE p.id = ? AND p.status = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: products.php");
    exit;
}

$is_back_case = ($product['category_id'] == 2);

// Get phone models for back cases
$phone_models = [];
if ($is_back_case) {
    $model_sql = "SELECT pm.*, b.name as brand_name 
                  FROM phone_models pm
                  LEFT JOIN brands b ON pm.brand_id = b.id
                  WHERE pm.status = 1
                  ORDER BY b.name, pm.model_name";
    $model_result = $conn->query($model_sql);
    while ($row = $model_result->fetch_assoc()) {
        $phone_models[] = $row;
    }
}

$categoryFolderMap = [
    1 => 'protectors',
    2 => 'backcases',
    3 => 'airpods'
];

$folder = $categoryFolderMap[$product['category_id']] ?? 'others';
$basePath = "../uploads/products/$folder/";

$images = [];

if (!empty($product['image1']) && file_exists($basePath . $product['image1'])) {
    $images[] = $basePath . $product['image1'];
}
if (!empty($product['image2']) && file_exists($basePath . $product['image2'])) {
    $images[] = $basePath . $product['image2'];
}
if (!empty($product['image3']) && file_exists($basePath . $product['image3'])) {
    $images[] = $basePath . $product['image3'];
}

if (empty($images)) {
    $images[] = "../assets/no-image.png";
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['model_name'] ?? $product['design_name'] ?? 'Product Details'); ?> |
        PROGLIDE</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
            padding-top: 80px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .product-details {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 30px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #666;
            text-decoration: none;
            margin-bottom: 20px;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: #f0f0f0;
            color: #333;
        }

        .product-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .product-images {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .main-image {
            width: 100%;
            height: 400px;
            border-radius: 12px;
            overflow: hidden;
            background: linear-gradient(135deg, #1a1a1a 0%, #000 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
        }

        .main-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .thumbnail-images {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .thumbnail {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .thumbnail:hover,
        .thumbnail.active {
            border-color: #ff6b35;
        }

        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .product-title {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .product-category {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f0f0f0;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #666;
        }

        .price-section {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 15px 0;
        }

        .current-price {
            font-size: 2rem;
            font-weight: 700;
            color: #ff6b35;
        }

        .original-price {
            font-size: 1.2rem;
            color: #999;
            text-decoration: line-through;
        }

        .discount-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .product-specs {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin: 20px 0;
        }

        .spec-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .spec-item:last-child {
            border-bottom: none;
        }

        .spec-icon {
            width: 24px;
            text-align: center;
            color: #ff6b35;
        }

        .spec-label {
            font-weight: 600;
            min-width: 120px;
            color: #666;
        }

        .spec-value {
            flex: 1;
            color: #333;
        }

        .description {
            margin: 25px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 12px;
        }

        .description h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .description p {
            color: #666;
            line-height: 1.8;
        }

        /* Model Selection Modal */
        .model-selection {
            margin-top: 30px;
            padding: 25px;
            background: #f9f9f9;
            border-radius: 12px;
            border-left: 4px solid #ff6b35;
        }

        .model-selection h3 {
            margin-bottom: 20px;
            color: #333;
        }

        .model-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
        }

        .model-item {
            padding: 15px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .model-item:hover {
            border-color: #ff6b35;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.1);
        }

        .model-item.selected {
            border-color: #ff6b35;
            background: #fff5f2;
        }

        .brand-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .model-name {
            color: #666;
            font-size: 0.9rem;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff6b35 0%, #e55a2b 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #e55a2b 0%, #cc4a22 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #333;
            border: 2px solid #e0e0e0;
        }

        .btn-secondary:hover {
            background: #f5f5f5;
            border-color: #ccc;
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 100px;
            right: 20px;
            background: white;
            border-left: 4px solid #28a745;
            border-radius: 12px;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: #333;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s ease;
            max-width: 350px;
        }

        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }

        .notification.error {
            border-left-color: #dc3545;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .product-layout {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .main-image {
                height: 350px;
            }
        }

        @media (max-width: 767px) {
            body {
                padding-top: 70px;
            }

            .container {
                padding: 15px;
            }

            .product-details {
                padding: 20px;
            }

            .product-title {
                font-size: 1.6rem;
            }

            .current-price {
                font-size: 1.6rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .model-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .main-image {
                height: 280px;
            }

            .product-title {
                font-size: 1.4rem;
            }

            .current-price {
                font-size: 1.4rem;
            }

            .spec-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <?php if (file_exists("includes/header.php"))
        include "includes/header.php"; ?>

    <div class="container">
        <!-- Back Link -->
        <a href="products.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>

        <div class="product-details">
            <div class="product-layout">
                <!-- Product Images -->
                <div class="product-images">
                    <div class="main-image" id="mainImage">
                        <img id="currentImage" src="<?= htmlspecialchars($images[0]) ?>" alt="Product Image">


                    </div>

                    <div class="thumbnail-images">
                        <?php foreach ($images as $i => $img): ?>
                            <div class="thumbnail <?= $i === 0 ? 'active' : '' ?>"
                                data-image="<?= htmlspecialchars($img) ?>">
                                <img src="<?= htmlspecialchars($img) ?>" alt="Thumbnail">
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>

                <!-- Product Information -->
                <div class="product-info">
                    <h1 class="product-title">
                        <?php echo htmlspecialchars($product['model_name'] ?? $product['design_name'] ?? 'Product'); ?>
                    </h1>

                    <div class="product-category">
                        <i class="fas fa-tag"></i>
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </div>

                    <div class="price-section">
                        <span class="current-price">₹<?php echo number_format($product['price'], 2); ?></span>
                        <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                            <span class="original-price">₹<?php echo number_format($product['original_price'], 2); ?></span>
                            <?php
                            $discount = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
                            ?>
                            <span class="discount-badge"><?php echo $discount; ?>% OFF</span>
                        <?php endif; ?>
                    </div>

                    <div class="product-specs">
                        <?php if (!empty($product['material_name'])): ?>
                            <div class="spec-item">
                                <div class="spec-icon"><i class="fas fa-gem"></i></div>
                                <div class="spec-label">Material:</div>
                                <div class="spec-value"><?php echo htmlspecialchars($product['material_name']); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($product['variant_name'])): ?>
                            <div class="spec-item">
                                <div class="spec-icon"><i class="fas fa-palette"></i></div>
                                <div class="spec-label">Variant:</div>
                                <div class="spec-value"><?php echo htmlspecialchars($product['variant_name']); ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="spec-item">
                            <div class="spec-icon"><i class="fas fa-box"></i></div>
                            <div class="spec-label">Availability:</div>
                            <div class="spec-value">In Stock</div>
                        </div>

                        <div class="spec-item">
                            <div class="spec-icon"><i class="fas fa-shipping-fast"></i></div>
                            <div class="spec-label">Delivery:</div>
                            <div class="spec-value">3-5 Business Days</div>
                        </div>
                    </div>

                    <?php if (!empty($product['description'])): ?>
                        <div class="description">
                            <h3>Product Description</h3>
                            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Model Selection for Back Cases -->
                    <?php if ($is_back_case && $select_model): ?>
                        <div class="model-selection">
                            <h3><i class="fas fa-mobile-alt"></i> Select Your Phone Model</h3>
                            <p>Please select your phone model to ensure perfect fit:</p>

                            <div class="model-grid" id="modelGrid">
                                <?php if (!empty($phone_models)): ?>
                                    <?php foreach ($phone_models as $model): ?>
                                        <div class="model-item" data-model-id="<?php echo $model['id']; ?>">
                                            <div class="brand-name"><?php echo htmlspecialchars($model['brand_name']); ?></div>
                                            <div class="model-name"><?php echo htmlspecialchars($model['model_name']); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p>No phone models available.</p>
                                <?php endif; ?>
                            </div>

                            <p id="selectedModelText" style="display: none;">
                                Selected: <strong id="selectedModelName"></strong>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <?php if ($is_back_case): ?>
                            <?php if ($select_model): ?>
                                <button class="btn btn-primary" id="addToCartBtn" disabled>
                                    <i class="fas fa-shopping-cart"></i> ADD TO CART
                                </button>
                            <?php else: ?>
                                <a href="productdetails.php?id=<?php echo $product_id; ?>&select_model=1"
                                    class="btn btn-primary">
                                    <i class="fas fa-mobile-alt"></i> SELECT MODEL
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn btn-primary" id="addToCartBtn">
                                <i class="fas fa-shopping-cart"></i> ADD TO CART
                            </button>
                        <?php endif; ?>

                        <button class="btn btn-secondary" id="wishlistBtn">
                            <i class="far fa-heart"></i> ADD TO WISHLIST
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification -->
    <div class="notification" id="notification"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Image Thumbnail Selection
            const thumbnails = document.querySelectorAll('.thumbnail');
            const mainImage = document.getElementById('currentImage');

            thumbnails.forEach(thumbnail => {
                thumbnail.addEventListener('click', function () {
                    // Remove active class from all thumbnails
                    thumbnails.forEach(t => t.classList.remove('active'));

                    // Add active class to clicked thumbnail
                    this.classList.add('active');

                    // Change main image
                    const imageSrc = this.dataset.image;
                    mainImage.src = imageSrc;
                });
            });

            <?php if ($is_back_case && $select_model): ?>
                // Model Selection
                const modelItems = document.querySelectorAll('.model-item');
                const addToCartBtn = document.getElementById('addToCartBtn');
                const selectedModelText = document.getElementById('selectedModelText');
                const selectedModelName = document.getElementById('selectedModelName');
                let selectedModelId = null;

                modelItems.forEach(item => {
                    item.addEventListener('click', function () {
                        // Remove selected class from all items
                        modelItems.forEach(m => m.classList.remove('selected'));

                        // Add selected class to clicked item
                        this.classList.add('selected');

                        // Store selected model
                        selectedModelId = this.dataset.modelId;
                        const brand = this.querySelector('.brand-name').textContent;
                        const model = this.querySelector('.model-name').textContent;
                        selectedModelName.textContent = brand + ' ' + model;

                        // Show selected model text
                        selectedModelText.style.display = 'block';

                        // Enable add to cart button
                        addToCartBtn.disabled = false;
                    });
                });
            <?php endif; ?>

            // Add to Cart
            const addToCartBtn = document.getElementById('addToCartBtn');
            if (addToCartBtn) {
                addToCartBtn.addEventListener('click', function () {
                    const button = this;
                    const originalHTML = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    button.disabled = true;

                    // Prepare form data
                    const formData = new FormData();
                    formData.append('product_id', <?php echo $product_id; ?>);
                    formData.append('quantity', 1);

                    <?php if ($is_back_case && $select_model): ?>
                        if (selectedModelId) {
                            formData.append('phone_model_id', selectedModelId);
                        } else {
                            showNotification('Please select a phone model', 'error');
                            button.innerHTML = originalHTML;
                            button.disabled = false;
                            return;
                        }
                    <?php endif; ?>

                    // Make AJAX request
                    fetch('add_to_cart.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification(data.message, 'success');
                                button.innerHTML = '<i class="fas fa-check"></i> ADDED TO CART';

                                // Update cart count
                                updateCartCount(data.cart_count);

                                // Reset button after 2 seconds
                                setTimeout(() => {
                                    button.innerHTML = originalHTML;
                                    button.disabled = false;
                                }, 2000);
                            } else {
                                showNotification(data.message, 'error');
                                button.innerHTML = originalHTML;
                                button.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('Failed to add to cart. Please try again.', 'error');
                            button.innerHTML = originalHTML;
                            button.disabled = false;
                        });
                });
            }

            // Wishlist Button
            const wishlistBtn = document.getElementById('wishlistBtn');
            if (wishlistBtn) {
                wishlistBtn.addEventListener('click', function () {
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        if (confirm('Please login to add items to wishlist.\n\nDo you want to login?')) {
                            window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                        }
                        return;
                    <?php endif; ?>

                    const button = this;
                    const originalHTML = button.innerHTML;
                    const isActive = button.classList.contains('active');

                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    button.disabled = true;

                    // Simulate API call
                    setTimeout(() => {
                        if (isActive) {
                            button.classList.remove('active');
                            button.innerHTML = '<i class="far fa-heart"></i> ADD TO WISHLIST';
                            showNotification('Removed from wishlist', 'info');
                        } else {
                            button.classList.add('active');
                            button.innerHTML = '<i class="fas fa-heart"></i> IN WISHLIST';
                            showNotification('Added to wishlist!', 'success');
                        }
                        button.disabled = false;
                    }, 500);
                });
            }

            // Show notification
            function showNotification(message, type = 'success') {
                const notification = document.getElementById('notification');
                notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}" 
                   style="color: ${type === 'success' ? '#28a745' : '#dc3545'}"></i>
                <div>${message}</div>
            `;
                notification.className = `notification ${type}`;

                notification.classList.add('show');

                setTimeout(() => {
                    notification.classList.remove('show');
                }, 3000);
            }

            // Update cart count
            function updateCartCount(count = null) {
                const cartCountElement = document.querySelector('.cart-count');
                if (cartCountElement) {
                    if (count !== null) {
                        cartCountElement.textContent = count;
                    } else {
                        const currentCount = parseInt(cartCountElement.textContent) || 0;
                        cartCountElement.textContent = currentCount + 1;
                    }
                    cartCountElement.classList.add('updated');
                    setTimeout(() => cartCountElement.classList.remove('updated'), 500);
                }
            }
        });
    </script>
</body>

</html>
<?php $conn->close(); ?>