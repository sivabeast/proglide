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

// Get all brands for dropdown
$brands = [];
$brand_sql = "SELECT id, name FROM brands WHERE status = 1 ORDER BY name";
$brand_result = $conn->query($brand_sql);
while ($row = $brand_result->fetch_assoc()) {
    $brands[] = $row;
}

// Get similar products (same category)
$similar_sql = "SELECT 
                    p.*,
                    c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.category_id = ? AND p.id != ? AND p.status = 1
                ORDER BY p.is_popular DESC, p.created_at DESC
                LIMIT 4";
$similar_stmt = $conn->prepare($similar_sql);
$similar_stmt->bind_param("ii", $product['category_id'], $product_id);
$similar_stmt->execute();
$similar_products = $similar_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$categoryFolderMap = [
    1 => 'protectors',
    2 => 'backcases',
    5 => 'battery',
    6 => 'airpods',
    7 => 'watch'
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
    <title><?php echo htmlspecialchars($product['model_name'] ?? $product['design_name'] ?? 'Product Details'); ?> | PROGLIDE</title>

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
            margin-bottom: 40px;
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
            background: #f8f8f8;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
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
            justify-content: center;
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

        /* Quantity Selector */
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 20px 0;
        }

        .qty-label {
            font-weight: 600;
            color: #555;
        }

        .qty-controls {
            display: flex;
            align-items: center;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            width: fit-content;
        }

        .qty-btn {
            width: 40px;
            height: 40px;
            background: #f8f8f8;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .qty-btn:hover {
            background: #e9e9e9;
        }

        .qty-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .qty-input {
            width: 60px;
            height: 40px;
            border: none;
            border-left: 2px solid #e0e0e0;
            border-right: 2px solid #e0e0e0;
            text-align: center;
            font-size: 1rem;
            font-weight: 600;
            -moz-appearance: textfield;
        }

        .qty-input::-webkit-outer-spin-button,
        .qty-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
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

        /* Model Selection Section */
        .model-selection-section {
            margin: 25px 0;
            padding: 25px;
            background: #f9f9f9;
            border-radius: 12px;
            border-left: 4px solid #ff6b35;
        }

        .model-selection-section h3 {
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .model-select-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .select-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            position: relative;
        }

        .select-label {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }

        .model-select {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.95rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #333;
        }

        .model-select:focus {
            outline: none;
            border-color: #ff6b35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .search-container {
            position: relative;
        }

        .model-search {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.95rem;
            background: white;
            transition: all 0.3s ease;
        }

        .model-search:focus {
            outline: none;
            border-color: #ff6b35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .models-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            background: white;
            margin-top: 10px;
            display: none;
            position: absolute;
            width: 100%;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .models-list.active {
            display: block;
        }

        .model-option {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .model-option:hover {
            background: #f8f8f8;
        }

        .model-option.selected {
            background: #fff5f2;
            border-left: 4px solid #ff6b35;
        }

        .model-option:last-child {
            border-bottom: none;
        }

        .model-icon {
            color: #ff6b35;
        }

        .selected-model-display {
            padding: 15px;
            background: white;
            border: 2px solid #ff6b35;
            border-radius: 10px;
            margin-top: 15px;
            display: none;
        }

        .selected-model-display.show {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .selected-model-info h4 {
            color: #333;
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .selected-model-info p {
            color: #666;
            font-size: 0.9rem;
        }

        .change-model-btn {
            background: #ff6b35;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .change-model-btn:hover {
            background: #e55a2b;
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

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #218838 0%, #1da88c 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        /* Similar Products */
        .similar-products {
            margin-top: 50px;
        }

        .similar-products h3 {
            margin-bottom: 25px;
            color: #333;
            font-size: 1.5rem;
        }

        .similar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .similar-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .similar-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .similar-image-container {
            width: 100%;
            height: 200px;
            background: #f8f8f8;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }

        .similar-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .similar-info {
            padding: 15px;
        }

        .similar-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
            font-size: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 2.8em;
        }

        .similar-price {
            color: #ff6b35;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .similar-old-price {
            color: #999;
            text-decoration: line-through;
            font-size: 0.9rem;
            margin-left: 8px;
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

        /* No Models Message */
        .no-models {
            padding: 20px;
            text-align: center;
            color: #666;
            font-style: italic;
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

            .model-select-grid {
                grid-template-columns: 1fr;
            }

            .similar-grid {
                grid-template-columns: repeat(2, 1fr);
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

            .similar-grid {
                grid-template-columns: 1fr;
            }

            .models-list {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 90%;
                max-width: 400px;
                max-height: 60vh;
            }

            .selected-model-display {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .change-model-btn {
                width: 100%;
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

            .model-select-grid {
                grid-template-columns: 1fr;
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
                        <?php echo htmlspecialchars($product['design_name'] ?? $product['model_name'] ?? 'Product'); ?>
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

                    <!-- Quantity Selector -->
                    <div class="quantity-selector">
                        <span class="qty-label">Quantity:</span>
                        <div class="qty-controls">
                            <button class="qty-btn" id="decreaseQty">−</button>
                            <input type="number" class="qty-input" id="quantity" value="1" min="1" max="10">
                            <button class="qty-btn" id="increaseQty">+</button>
                        </div>
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

                    <!-- Model Selection for Back Cases -->
                    <?php if ($is_back_case): ?>
                        <div class="model-selection-section">
                            <h3><i class="fas fa-mobile-alt"></i> Select Your Phone Model</h3>
                            
                            <div class="model-select-grid">
                                <!-- Brand Selection -->
                                <div class="select-group">
                                    <label class="select-label">Select Brand</label>
                                    <select class="model-select" id="brandSelect">
                                        <option value="">-- Select Brand --</option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?php echo $brand['id']; ?>">
                                                <?php echo htmlspecialchars($brand['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Model Search -->
                                <div class="select-group search-container">
                                    <label class="select-label">Search Model</label>
                                    <div class="search-container">
                                        <input type="text" class="model-search" id="modelSearch" 
                                               placeholder="Type to search models..." disabled>
                                        <i class="fas fa-search search-icon"></i>
                                        <div class="models-list" id="modelsList"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Model Display -->
                            <div class="selected-model-display" id="selectedModelDisplay">
                                <div class="selected-model-info">
                                    <h4>Selected Model</h4>
                                    <p id="selectedModelText"></p>
                                </div>
                                <button class="change-model-btn" id="changeModelBtn">
                                    <i class="fas fa-edit"></i> Change
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($product['description'])): ?>
                        <div class="description">
                            <h3>Product Description</h3>
                            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button class="btn btn-primary" id="addToCartBtn" 
                                <?php echo ($is_back_case) ? 'disabled' : ''; ?>>
                            <i class="fas fa-shopping-cart"></i> ADD TO CART
                        </button>
                        
                        <button class="btn btn-success" id="buyNowBtn"
                                <?php echo ($is_back_case) ? 'disabled' : ''; ?>>
                            <i class="fas fa-bolt"></i> BUY NOW
                        </button>
                        
                        <button class="btn btn-secondary" id="wishlistBtn">
                            <i class="far fa-heart"></i> WISHLIST
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Similar Products -->
        <?php if (!empty($similar_products)): ?>
        <div class="similar-products">
            <h3>You May Also Like</h3>
            <div class="similar-grid">
                <?php foreach ($similar_products as $similar): 
                    $similar_folder = $categoryFolderMap[$similar['category_id']] ?? 'others';
                    $similar_image = !empty($similar['image1']) ? "../uploads/products/$similar_folder/" . $similar['image1'] : '../assets/no-image.png';
                    $similar_name = $similar['design_name'] ?? $similar['model_name'] ?? 'Product';
                ?>
                <div class="similar-card" onclick="window.location.href='productdetails.php?id=<?php echo $similar['id']; ?>'">
                    <div class="similar-image-container">
                        <img src="<?php echo htmlspecialchars($similar_image); ?>" 
                             alt="<?php echo htmlspecialchars($similar_name); ?>" 
                             class="similar-image"
                             onerror="this.onerror=null; this.src='../assets/no-image.png';">
                    </div>
                    <div class="similar-info">
                        <h4 class="similar-title"><?php echo htmlspecialchars($similar_name); ?></h4>
                        <div class="similar-price">
                            ₹<?php echo number_format($similar['price'], 2); ?>
                            <?php if (!empty($similar['original_price']) && $similar['original_price'] > $similar['price']): ?>
                                <span class="similar-old-price">₹<?php echo number_format($similar['original_price'], 2); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
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
            thumbnails.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            mainImage.src = this.dataset.image;
        });
    });

    // Quantity Controls
    const quantityInput = document.getElementById('quantity');
    const decreaseBtn = document.getElementById('decreaseQty');
    const increaseBtn = document.getElementById('increaseQty');

    function updateQuantityButtons() {
        const currentQty = parseInt(quantityInput.value);
        decreaseBtn.disabled = currentQty <= 1;
        increaseBtn.disabled = currentQty >= 10;
    }

    decreaseBtn.addEventListener('click', function() {
        let current = parseInt(quantityInput.value);
        if (current > 1) {
            quantityInput.value = current - 1;
            updateQuantityButtons();
        }
    });

    increaseBtn.addEventListener('click', function() {
        let current = parseInt(quantityInput.value);
        if (current < 10) {
            quantityInput.value = current + 1;
            updateQuantityButtons();
        }
    });

    quantityInput.addEventListener('change', function() {
        let value = parseInt(this.value);
        if (isNaN(value) || value < 1) value = 1;
        if (value > 10) value = 10;
        this.value = value;
        updateQuantityButtons();
    });

    updateQuantityButtons(); // Initialize

    // Initialize variables
    let selectedBrandId = null;
    let selectedModelId = null;
    let selectedModelName = null;
    let selectedBrandName = null;
    const addToCartBtn = document.getElementById('addToCartBtn');
    const buyNowBtn = document.getElementById('buyNowBtn');
    const wishlistBtn = document.getElementById('wishlistBtn');

    <?php if ($is_back_case): ?>
        // Brand Selection
        const brandSelect = document.getElementById('brandSelect');
        const modelSearch = document.getElementById('modelSearch');
        const modelsList = document.getElementById('modelsList');
        const selectedModelDisplay = document.getElementById('selectedModelDisplay');
        const selectedModelText = document.getElementById('selectedModelText');
        const changeModelBtn = document.getElementById('changeModelBtn');

        brandSelect.addEventListener('change', function() {
            selectedBrandId = this.value;
            selectedModelId = null;
            selectedModelName = null;
            selectedBrandName = this.options[this.selectedIndex].text;
            
            if (selectedBrandId) {
                modelSearch.disabled = false;
                modelSearch.placeholder = "Type to search models...";
                modelSearch.value = '';
                modelsList.innerHTML = '';
                modelsList.classList.remove('active');
                selectedModelDisplay.classList.remove('show');
                addToCartBtn.disabled = true;
                buyNowBtn.disabled = true;
                
                // Load models for selected brand
                loadModels(selectedBrandId);
            } else {
                modelSearch.disabled = true;
                modelSearch.value = '';
                modelSearch.placeholder = "Select brand first";
                modelsList.innerHTML = '';
                modelsList.classList.remove('active');
                selectedModelDisplay.classList.remove('show');
                addToCartBtn.disabled = true;
                buyNowBtn.disabled = true;
            }
        });

        // Model Search
        let searchTimeout;
        modelSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.trim();
            
            if (searchTerm.length >= 1) {
                searchTimeout = setTimeout(() => {
                    searchModels(searchTerm);
                }, 300);
            } else if (searchTerm.length === 0 && window.modelsCache) {
                displayModels(window.modelsCache);
            } else {
                modelsList.innerHTML = '';
                modelsList.classList.remove('active');
            }
        });

        modelSearch.addEventListener('focus', function() {
            if (selectedBrandId && window.modelsCache) {
                if (this.value.length === 0) {
                    displayModels(window.modelsCache);
                }
            }
        });

        // Click outside to close models list
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-container')) {
                modelsList.classList.remove('active');
            }
        });

        // Change Model Button
        changeModelBtn.addEventListener('click', function() {
            selectedModelDisplay.classList.remove('show');
            selectedModelId = null;
            selectedModelName = null;
            addToCartBtn.disabled = true;
            buyNowBtn.disabled = true;
            modelSearch.value = '';
            modelSearch.focus();
            
            if (window.modelsCache) {
                displayModels(window.modelsCache);
            }
        });

        // Load models for brand
        function loadModels(brandId) {
            console.log('Loading models for brand:', brandId);
            
            window.modelsCache = [];
            modelsList.innerHTML = '<div class="no-models">Loading models...</div>';
            modelsList.classList.add('active');
            
            // IMPORTANT: Use correct path based on your folder structure
            // If productdetails.php is in root, use: 'ajax/get_models.php'
            // If productdetails.php is in a subfolder, use: '../ajax/get_models.php'
            const path = 'ajax/get_models.php'; // Change this if needed
            
            fetch(`${path}?brand_id=${brandId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Models data:', data);
                    if (data.success && data.models && data.models.length > 0) {
                        window.modelsCache = data.models;
                        console.log('Loaded', data.models.length, 'models');
                        
                        displayModels(window.modelsCache);
                        
                        showNotification(`Loaded ${data.models.length} models for ${selectedBrandName}`, 'success');
                    } else {
                        window.modelsCache = [];
                        modelsList.innerHTML = '<div class="no-models">No models found for this brand</div>';
                        showNotification('No models found for ' + selectedBrandName, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading models:', error);
                    modelsList.innerHTML = `<div class="no-models">Error: ${error.message}</div>`;
                    showNotification('Error loading models. Please check console.', 'error');
                });
        }

        // Search models
        function searchModels(searchTerm) {
            if (!window.modelsCache || !selectedBrandId) {
                modelsList.innerHTML = '<div class="no-models">Please select a brand first</div>';
                modelsList.classList.add('active');
                return;
            }
            
            const filteredModels = window.modelsCache.filter(model => 
                model.model_name.toLowerCase().includes(searchTerm.toLowerCase())
            );
            
            displayModels(filteredModels);
        }

        // Display models in dropdown
        function displayModels(models) {
            modelsList.innerHTML = '';
            
            if (models.length === 0) {
                modelsList.innerHTML = '<div class="no-models">No models found</div>';
            } else {
                models.forEach(model => {
                    const option = document.createElement('div');
                    option.className = 'model-option';
                    if (selectedModelId == model.id) {
                        option.classList.add('selected');
                    }
                    option.innerHTML = `
                        <i class="fas fa-mobile-alt model-icon"></i>
                        <span>${model.model_name}</span>
                    `;
                    option.dataset.modelId = model.id;
                    option.dataset.modelName = model.model_name;
                    
                    option.addEventListener('click', function() {
                        document.querySelectorAll('.model-option').forEach(opt => {
                            opt.classList.remove('selected');
                        });
                        
                        this.classList.add('selected');
                        selectedModelId = this.dataset.modelId;
                        selectedModelName = this.dataset.modelName;
                        modelSearch.value = selectedModelName;
                        selectedModelText.textContent = `${selectedBrandName} - ${selectedModelName}`;
                        selectedModelDisplay.classList.add('show');
                        addToCartBtn.disabled = false;
                        buyNowBtn.disabled = false;
                        modelsList.classList.remove('active');
                        showNotification(`Selected: ${selectedModelName}`, 'success');
                    });
                    
                    modelsList.appendChild(option);
                });
            }
            
            modelsList.classList.add('active');
        }
    <?php endif; ?>

    // Check if product is in wishlist on page load
    <?php if (isset($_SESSION['user_id'])): ?>
        // Check wishlist status
        fetch('ajax/check_wishlist.php?product_id=<?php echo $product_id; ?>')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.in_wishlist) {
                    wishlistBtn.classList.add('active');
                    wishlistBtn.innerHTML = '<i class="fas fa-heart"></i> IN WISHLIST';
                    wishlistBtn.style.color = '#ff6b35';
                    wishlistBtn.style.borderColor = '#ff6b35';
                }
            })
            .catch(error => console.error('Error checking wishlist:', error));
    <?php endif; ?>

    // Add to Cart Function - CORRECTED
    function addToCart(isBuyNow = false) {
        const quantity = parseInt(document.getElementById('quantity').value) || 1;
        
        // Validate quantity
        if (quantity < 1 || quantity > 10) {
            showNotification('Quantity must be between 1 and 10', 'error');
            return;
        }
        
        // Prepare URL parameters
        let url = `ajax/add_to_cart.php?id=<?php echo $product_id; ?>&qty=${quantity}`;
        
        <?php if ($is_back_case): ?>
            if (!selectedModelId) {
                showNotification('Please select a phone model first', 'error');
                return;
            }
            url += `&model_id=${selectedModelId}`;
        <?php endif; ?>

        console.log('Add to Cart URL:', url);
        
        // Disable buttons during request
        const originalCartText = addToCartBtn.innerHTML;
        const originalBuyText = buyNowBtn.innerHTML;
        addToCartBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        addToCartBtn.disabled = true;
        buyNowBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        buyNowBtn.disabled = true;

        // Make AJAX request
        fetch(url)
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Add to cart response:', data);
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    
                    // Update Add to Cart button
                    addToCartBtn.innerHTML = '<i class="fas fa-check"></i> ADDED';
                    addToCartBtn.classList.add('added');
                    addToCartBtn.disabled = true;
                    
                    // Update cart count in header
                    updateCartCount(data.cart_count);
                    
                    if (isBuyNow) {
                        // Redirect to checkout after 1 second with buy_now flag
                        setTimeout(() => {
                            let checkoutUrl = `checkout.php?buy_now=1&id=<?php echo $product_id; ?>&qty=${quantity}`;
                            <?php if ($is_back_case): ?>
                                if (selectedModelId) {
                                    checkoutUrl += `&model_id=${selectedModelId}`;
                                }
                            <?php endif; ?>
                            window.location.href = checkoutUrl;
                        }, 1000);
                    } else {
                        // Re-enable Add to Cart button after 3 seconds
                        setTimeout(() => {
                            addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> ADD TO CART';
                            addToCartBtn.classList.remove('added');
                            addToCartBtn.disabled = false;
                        }, 3000);
                    }
                    
                    // Reset buy now button
                    buyNowBtn.innerHTML = '<i class="fas fa-bolt"></i> BUY NOW';
                    buyNowBtn.disabled = false;
                } else {
                    // Reset buttons
                    addToCartBtn.innerHTML = originalCartText;
                    addToCartBtn.disabled = false;
                    buyNowBtn.innerHTML = originalBuyText;
                    buyNowBtn.disabled = false;
                    
                    if (data.redirect) {
                        if (confirm('Please login to add items to cart.\n\nDo you want to login?')) {
                            window.location.href = data.redirect;
                        }
                    } else {
                        showNotification(data.message || 'Failed to add to cart', 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Reset buttons
                addToCartBtn.innerHTML = originalCartText;
                addToCartBtn.disabled = false;
                buyNowBtn.innerHTML = originalBuyText;
                buyNowBtn.disabled = false;
                
                showNotification('Failed to connect to server. Please check your internet connection.', 'error');
            });
    }

    // Add to Cart Button
    addToCartBtn.addEventListener('click', function (e) {
        e.preventDefault();
        console.log('Add to Cart clicked');
        addToCart(false);
    });

    // Buy Now Button
    buyNowBtn.addEventListener('click', function (e) {
        e.preventDefault();
        console.log('Buy Now clicked');
        addToCart(true);
    });

    // Wishlist Button - CORRECTED
    wishlistBtn.addEventListener('click', function () {
        <?php if (!isset($_SESSION['user_id'])): ?>
            if (confirm('Please login to add items to wishlist.\n\nDo you want to login?')) {
                window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
            }
            return;
        <?php endif; ?>

        const button = this;
        const isActive = button.classList.contains('active');

        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;

        if (isActive) {
            // Remove from wishlist
            fetch('ajax/remove_wishlist.php?product_id=<?php echo $product_id; ?>')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        button.classList.remove('active');
                        button.innerHTML = '<i class="far fa-heart"></i> WISHLIST';
                        button.style.color = '';
                        button.style.borderColor = '';
                        showNotification('Removed from wishlist', 'info');
                    } else {
                        showNotification(data.message || 'Failed to remove from wishlist', 'error');
                        button.innerHTML = '<i class="fas fa-heart"></i> IN WISHLIST';
                    }
                    button.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Failed to connect to server. Please try again.', 'error');
                    button.innerHTML = '<i class="fas fa-heart"></i> IN WISHLIST';
                    button.disabled = false;
                });
        } else {
            // Add to wishlist - Use FormData for POST request
            const formData = new FormData();
            formData.append('product_id', '<?php echo $product_id; ?>');

            fetch('ajax/add_to_wishlist.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        button.classList.add('active');
                        button.innerHTML = '<i class="fas fa-heart"></i> IN WISHLIST';
                        button.style.color = '#ff6b35';
                        button.style.borderColor = '#ff6b35';
                        showNotification('Added to wishlist!', 'success');
                    } else {
                        showNotification(data.message || 'Failed to add to wishlist', 'error');
                        button.innerHTML = '<i class="far fa-heart"></i> WISHLIST';
                    }
                    button.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Failed to connect to server. Please try again.', 'error');
                    button.innerHTML = '<i class="far fa-heart"></i> WISHLIST';
                    button.disabled = false;
                });
        }
    });

    // Show notification
    function showNotification(message, type = 'success') {
        const notification = document.getElementById('notification');
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}" 
               style="color: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'}"></i>
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
        // Try multiple selectors for cart count
        const cartSelectors = ['.cart-count', '.action-badge', '.badge'];
        let cartCountElement = null;
        
        cartSelectors.forEach(selector => {
            const element = document.querySelector(selector);
            if (element && !cartCountElement) {
                cartCountElement = element;
            }
        });
        
        if (cartCountElement) {
            if (count !== null) {
                cartCountElement.textContent = count;
            } else {
                const currentCount = parseInt(cartCountElement.textContent) || 0;
                cartCountElement.textContent = currentCount + 1;
            }
            cartCountElement.classList.add('updated');
            setTimeout(() => {
                if (cartCountElement) {
                    cartCountElement.classList.remove('updated');
                }
            }, 500);
        }
    }

    // Debug function
    window.debugCart = function() {
        console.log('=== DEBUG CART ===');
        console.log('Product ID:', <?php echo $product_id; ?>);
        console.log('Is Back Case:', <?php echo $is_back_case ? 'true' : 'false'; ?>);
        console.log('Selected Model ID:', selectedModelId);
        console.log('Selected Model Name:', selectedModelName);
        console.log('Quantity:', document.getElementById('quantity').value);
        
        // Test the add_to_cart endpoint
        fetch('ajax/add_to_cart.php?id=<?php echo $product_id; ?>&qty=1')
            .then(r => {
                console.log('Response status:', r.status);
                return r.json();
            })
            .then(data => console.log('Test response:', data))
            .catch(err => console.error('Test error:', err));
    };
});
</script>
</body>
</html>
<?php $conn->close(); ?>