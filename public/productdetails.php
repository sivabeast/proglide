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
            c.slug as category_slug,
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
                    c.name as category_name,
                    c.slug as category_slug
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.category_id = ? AND p.id != ? AND p.status = 1
                ORDER BY p.is_popular DESC, p.created_at DESC
                LIMIT 10";
$similar_stmt = $conn->prepare($similar_sql);
$similar_stmt->bind_param("ii", $product['category_id'], $product_id);
$similar_stmt->execute();
$similar_products = $similar_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Helper function to get product image path
function getProductImagePath($category_slug, $image_name) {
    if (empty($image_name)) {
        return "/proglide/assets/no-image.png";
    }
    
    // Try path with category slug
    $new_path = "/proglide/uploads/products/" . $category_slug . "/" . $image_name;
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $new_path)) {
        return $new_path;
    }
    
    // Try old path as fallback
    $old_path = "/proglide/uploads/products/" . $image_name;
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $old_path)) {
        return $old_path;
    }
    
    return "/proglide/assets/no-image.png";
}

// Get images for current product
$images = [];
$category_slug = $product['category_slug'] ?? 'general';

if (!empty($product['image1'])) {
    $images[] = getProductImagePath($category_slug, $product['image1']);
}
if (!empty($product['image2'])) {
    $images[] = getProductImagePath($category_slug, $product['image2']);
}
if (!empty($product['image3'])) {
    $images[] = getProductImagePath($category_slug, $product['image3']);
}

if (empty($images)) {
    $images[] = "/proglide/assets/no-image.png";
}

$user_id = $_SESSION['user_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title><?php echo htmlspecialchars($product['model_name'] ?? $product['design_name'] ?? 'Product Details'); ?> | PROGLIDE</title>
    <link rel="icon" type="image/x-icon" href="/proglide/image/logo.png">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* ============================================
           PRODUCT DETAILS PAGE - MODERN TRENDING DESIGN
           ============================================ */

        :root {
            --primary: #FF6B35;
            --primary-dark: #e55a2b;
            --primary-light: rgba(255, 107, 53, 0.1);
            --secondary: #FF8E53;
            --dark-bg: #0a0a0a;
            --dark-card: #1a1a1a;
            --dark-border: #2a2a2a;
            --dark-hover: #252525;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --text-muted: #808080;
            --success: #4CAF50;
            --warning: #FFC107;
            --danger: #F44336;
            --info: #2196F3;
            --radius: 16px;
            --radius-sm: 10px;
            --radius-lg: 20px;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            --shadow-sm: 0 4px 12px rgba(0,0,0,0.1);
            --shadow-hover: 0 15px 40px rgba(255, 107, 53, 0.25);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--dark-bg);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
            padding-top: 90px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--text-secondary);
            text-decoration: none;
            margin-bottom: 25px;
            padding: 10px 20px;
            border-radius: 40px;
            background: var(--dark-card);
            border: 1px solid var(--dark-border);
            transition: var(--transition);
            font-weight: 500;
        }

        .back-link:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateX(-5px);
        }

        .back-link i {
            font-size: 0.9rem;
        }

        /* Product Details Card */
        .product-details {
            background: var(--dark-card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--dark-border);
            overflow: hidden;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: var(--shadow);
        }

        .product-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
        }

        /* ============================================
           IMAGE GALLERY - MODERN
           ============================================ */
        .product-images {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .main-image {
            width: 100%;
            height: 450px;
            border-radius: var(--radius);
            overflow: hidden;
            background: linear-gradient(145deg, var(--dark-hover), var(--dark-card));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            border: 1px solid var(--dark-border);
        }

        .main-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.5s ease;
        }

        .main-image:hover img {
            transform: scale(1.05);
        }

        /* Thumbnail Images */
        .thumbnail-images {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .thumbnail {
            width: 90px;
            height: 90px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: var(--transition);
            background: var(--dark-hover);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }

        .thumbnail:hover,
        .thumbnail.active {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 107, 53, 0.2);
        }

        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* ============================================
           PRODUCT INFO - MODERN
           ============================================ */
        .product-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .product-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 10px;
            line-height: 1.2;
        }

        .product-category {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary-light);
            padding: 8px 16px;
            border-radius: 40px;
            font-size: 0.9rem;
            color: var(--primary);
            font-weight: 600;
            width: fit-content;
        }

        /* Price Section */
        .price-section {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 15px 0;
            padding: 15px 0;
            border-bottom: 1px solid var(--dark-border);
        }

        .current-price {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
        }

        .original-price {
            font-size: 1.3rem;
            color: var(--text-muted);
            text-decoration: line-through;
        }

        .discount-badge {
            background: linear-gradient(135deg, var(--success), #45a049);
            color: white;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
        }

        /* Quantity Selector - Modern */
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 20px 0;
        }

        .qty-label {
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .qty-controls {
            display: flex;
            align-items: center;
            border: 2px solid var(--dark-border);
            border-radius: 50px;
            overflow: hidden;
            background: var(--dark-hover);
        }

        .qty-btn {
            width: 45px;
            height: 45px;
            background: var(--dark-hover);
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            color: var(--text-primary);
        }

        .qty-btn:hover:not(:disabled) {
            background: var(--primary);
            color: white;
        }

        .qty-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .qty-input {
            width: 70px;
            height: 45px;
            border: none;
            border-left: 2px solid var(--dark-border);
            border-right: 2px solid var(--dark-border);
            text-align: center;
            font-size: 1rem;
            font-weight: 600;
            background: var(--dark-card);
            color: var(--text-primary);
        }

        /* Product Specs */
        .product-specs {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin: 20px 0;
            padding: 20px;
            background: var(--dark-hover);
            border-radius: var(--radius);
            border: 1px solid var(--dark-border);
        }

        .spec-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .spec-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .spec-label {
            font-weight: 600;
            min-width: 100px;
            color: var(--text-secondary);
        }

        .spec-value {
            flex: 1;
            color: var(--text-primary);
            font-weight: 500;
        }

        /* Model Selection */
        .model-selection-section {
            margin: 25px 0;
            padding: 25px;
            background: var(--dark-hover);
            border-radius: var(--radius);
            border-left: 4px solid var(--primary);
        }

        .model-selection-section h3 {
            margin-bottom: 20px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
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
        }

        .select-label {
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .model-select {
            padding: 12px 15px;
            background: var(--dark-card);
            border: 2px solid var(--dark-border);
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            color: var(--text-primary);
            cursor: pointer;
            transition: var(--transition);
        }

        .model-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .search-container {
            position: relative;
        }

        .model-search {
            width: 100%;
            padding: 12px 15px 12px 45px;
            background: var(--dark-card);
            border: 2px solid var(--dark-border);
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            color: var(--text-primary);
            transition: var(--transition);
        }

        .model-search:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .models-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid var(--dark-border);
            border-radius: var(--radius-sm);
            background: var(--dark-card);
            margin-top: 10px;
            display: none;
            position: absolute;
            width: 100%;
            z-index: 100;
            box-shadow: var(--shadow);
        }

        .models-list.active {
            display: block;
        }

        .model-option {
            padding: 12px 15px;
            border-bottom: 1px solid var(--dark-border);
            cursor: pointer;
            transition: var(--transition);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .model-option:hover {
            background: var(--dark-hover);
        }

        .model-option.selected {
            background: var(--primary-light);
            border-left: 4px solid var(--primary);
        }

        .model-icon {
            color: var(--primary);
        }

        .selected-model-display {
            padding: 15px 20px;
            background: var(--dark-card);
            border: 2px solid var(--primary);
            border-radius: var(--radius-sm);
            margin-top: 15px;
            display: none;
        }

        .selected-model-display.show {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .selected-model-info h4 {
            color: var(--text-primary);
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .selected-model-info p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .change-model-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: var(--transition);
        }

        .change-model-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Description */
        .description {
            margin: 25px 0;
            padding: 25px;
            background: var(--dark-hover);
            border-radius: var(--radius);
        }

        .description h3 {
            margin-bottom: 15px;
            color: var(--text-primary);
            font-size: 1.2rem;
        }

        .description p {
            color: var(--text-secondary);
            line-height: 1.8;
        }

        /* ============================================
           ACTION BUTTONS - MODERN TRENDING DESIGN
           ============================================ */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 16px 24px;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 8px 20px rgba(255, 107, 53, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(255, 107, 53, 0.5);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-success {
            background: linear-gradient(135deg, #00e676, #00c853);
            color: white;
            box-shadow: 0 8px 20px rgba(0, 230, 118, 0.4);
        }

        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 230, 118, 0.5);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.05);
            color: var(--text-primary);
            border: 2px solid var(--dark-border);
            backdrop-filter: blur(10px);
        }

        .btn-secondary:hover {
            background: rgba(255,77,77,0.1);
            border-color: #ff4d4d;
            color: #ff4d4d;
            transform: translateY(-3px);
        }

        .btn-secondary.active {
            background: #ff4d4d;
            color: white;
            border-color: #ff4d4d;
        }

        /* ============================================
           SIMILAR PRODUCTS - HORIZONTAL SCROLL
           ============================================ */
        .similar-products {
            margin-top: 50px;
            background: var(--dark-card);
            border-radius: var(--radius);
            padding: 25px 20px;
            border: 1px solid var(--dark-border);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 0 5px;
        }

        .section-header h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h3 i {
            color: var(--primary);
        }

        .view-all-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 8px 16px;
            background: var(--primary-light);
            border-radius: 30px;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .view-all-link:hover {
            background: var(--primary);
            color: white;
            transform: translateX(5px);
        }

        .similar-scroll-container {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) var(--dark-border);
            padding: 5px 0 15px 0;
            scroll-behavior: smooth;
        }

        .similar-scroll-container::-webkit-scrollbar {
            height: 6px;
        }

        .similar-scroll-container::-webkit-scrollbar-track {
            background: var(--dark-border);
            border-radius: 10px;
        }

        .similar-scroll-container::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        .similar-scroll-wrapper {
            display: flex;
            gap: 20px;
            width: max-content;
            padding: 5px 0;
        }

        .similar-card {
            width: 240px;
            min-width: 240px;
            background: var(--dark-hover);
            border-radius: var(--radius);
            border: 1px solid var(--dark-border);
            overflow: hidden;
            transition: var(--transition);
            cursor: pointer;
            position: relative;
        }

        .similar-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: var(--shadow-hover);
        }

        .similar-image-container {
            position: relative;
            width: 100%;
            height: 180px;
            background: var(--dark-card);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            overflow: hidden;
        }

        .similar-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.5s ease;
        }

        .similar-card:hover .similar-image {
            transform: scale(1.08);
        }

        .similar-popular-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: linear-gradient(135deg, var(--warning), #ff9800);
            color: #000;
            padding: 4px 8px;
            border-radius: 16px;
            font-size: 0.65rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 4px;
            z-index: 5;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
        }

        .similar-info {
            padding: 15px;
        }

        .similar-title {
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text-primary);
            font-size: 0.9rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 42px;
            line-height: 1.4;
        }

        .similar-material {
            font-size: 0.7rem;
            color: var(--text-secondary);
            background: var(--dark-card);
            padding: 3px 8px;
            border-radius: 16px;
            display: inline-block;
            margin-bottom: 8px;
            border: 1px solid var(--dark-border);
        }

        .similar-price-section {
            display: flex;
            align-items: baseline;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .similar-current-price {
            color: var(--primary);
            font-weight: 700;
            font-size: 1rem;
        }

        .similar-old-price {
            color: var(--text-muted);
            text-decoration: line-through;
            font-size: 0.8rem;
        }

        .similar-discount {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--success);
            background: rgba(76, 175, 80, 0.1);
            padding: 2px 6px;
            border-radius: 12px;
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 100px;
            right: 20px;
            background: var(--dark-card);
            border-left: 4px solid var(--success);
            border-radius: var(--radius);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--text-primary);
            box-shadow: var(--shadow);
            z-index: 9999;
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s ease;
            max-width: 350px;
            border: 1px solid var(--dark-border);
        }

        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }

        .notification.error {
            border-left-color: var(--danger);
        }

        .notification i {
            font-size: 1.2rem;
        }

        /* ============================================
           RESPONSIVE DESIGN
           ============================================ */

        /* Tablet */
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

            .product-title {
                font-size: 1.8rem;
            }

            .current-price {
                font-size: 2rem;
            }
        }

        /* Mobile */
        @media (max-width: 767px) {
            body {
                padding-top: 70px;
            }

            .container {
                padding: 0 15px;
            }

            .product-details {
                padding: 20px;
            }

            .product-title {
                font-size: 1.5rem;
            }

            .current-price {
                font-size: 1.8rem;
            }

            .original-price {
                font-size: 1.1rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 12px;
            }

            .btn {
                width: 100%;
                padding: 14px 20px;
                font-size: 0.95rem;
            }

            .quantity-selector {
                flex-wrap: wrap;
                gap: 15px;
            }

            .qty-controls {
                width: 100%;
            }

            .qty-btn {
                width: 50px;
                height: 50px;
            }

            .qty-input {
                flex: 1;
            }

            .spec-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .spec-icon {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }

            .spec-label {
                min-width: auto;
            }

            .model-selection-section {
                padding: 20px;
            }

            .selected-model-display {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }

            .change-model-btn {
                width: 100%;
            }

            /* Similar Products Mobile */
            .similar-products {
                padding: 20px 15px;
                margin-top: 30px;
            }

            .section-header h3 {
                font-size: 1.2rem;
            }

            .view-all-link {
                font-size: 0.8rem;
                padding: 6px 12px;
            }

            .similar-card {
                width: 200px;
                min-width: 200px;
            }

            .similar-image-container {
                height: 150px;
                padding: 12px;
            }

            .similar-info {
                padding: 12px;
            }

            .similar-title {
                font-size: 0.85rem;
                min-height: 38px;
            }

            .notification {
                left: 15px;
                right: 15px;
                max-width: none;
            }
        }

        /* Small Mobile */
        @media (max-width: 375px) {
            .product-title {
                font-size: 1.3rem;
            }

            .current-price {
                font-size: 1.5rem;
            }

            .similar-card {
                width: 170px;
                min-width: 170px;
            }

            .similar-image-container {
                height: 130px;
                padding: 10px;
            }

            .similar-info {
                padding: 10px;
            }

            .similar-title {
                font-size: 0.8rem;
                min-height: 36px;
            }

            .similar-current-price {
                font-size: 0.9rem;
            }

            .btn span {
                display: none;
            }

            .btn i {
                font-size: 1.2rem;
                margin: 0;
            }

            .btn {
                padding: 12px;
            }
        }

        /* Touch Device Optimizations */
        @media (hover: none) and (pointer: coarse) {
            .product-card:hover,
            .similar-card:hover {
                transform: none;
            }

            .btn:hover {
                transform: none;
            }

            .btn:active {
                transform: scale(0.98);
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .product-details,
        .similar-card {
            animation: fadeIn 0.6s ease;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .fa-spinner {
            animation: spin 1s linear infinite;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <?php if (file_exists("includes/header.php")) include "includes/header.php"; ?>

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
                        <img id="currentImage" src="<?= htmlspecialchars($images[0]) ?>" alt="Product Image"
                             onerror="this.onerror=null; this.src='/proglide/assets/no-image.png';">
                    </div>

                    <?php if (count($images) > 1): ?>
                    <div class="thumbnail-images">
                        <?php foreach ($images as $i => $img): ?>
                            <div class="thumbnail <?= $i === 0 ? 'active' : '' ?>"
                                data-image="<?= htmlspecialchars($img) ?>">
                                <img src="<?= htmlspecialchars($img) ?>" alt="Thumbnail"
                                     onerror="this.onerror=null; this.src='/proglide/assets/no-image.png';">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
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
                            <i class="fas fa-shopping-cart"></i> <span>ADD TO CART</span>
                        </button>
                        
                        <button class="btn btn-success" id="buyNowBtn"
                                <?php echo ($is_back_case) ? 'disabled' : ''; ?>>
                            <i class="fas fa-bolt"></i> <span>BUY NOW</span>
                        </button>
                        
                        <button class="btn btn-secondary" id="wishlistBtn">
                            <i class="far fa-heart"></i> <span>WISHLIST</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Similar Products - Horizontal Scroll -->
        <?php if (!empty($similar_products)): ?>
        <div class="similar-products">
            <div class="section-header">
                <h3><i class="fas fa-layer-group"></i> You May Also Like</h3>
                <a href="products.php?cat=<?php echo $product['category_id']; ?>" class="view-all-link">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <div class="similar-scroll-container">
                <div class="similar-scroll-wrapper">
                    <?php foreach ($similar_products as $similar): 
                        $similar_name = $similar['design_name'] ?? $similar['model_name'] ?? 'Product';
                        $similar_image = getProductImagePath($similar['category_slug'] ?? 'general', $similar['image1'] ?? '');
                        
                        $similar_discount = '';
                        if (!empty($similar['original_price']) && $similar['original_price'] > $similar['price']){
                            $discount_percent = round((($similar['original_price'] - $similar['price']) / $similar['original_price']) * 100);
                            $similar_discount = '<span class="similar-discount">-' . $discount_percent . '%</span>';
                        }
                    ?>
                    <div class="similar-card" onclick="window.location.href='productdetails.php?id=<?php echo $similar['id']; ?>'">
                        <div class="similar-image-container">
                            <img src="<?php echo htmlspecialchars($similar_image); ?>" 
                                 alt="<?php echo htmlspecialchars($similar_name); ?>" 
                                 class="similar-image"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='/proglide/assets/no-image.png';">
                            
                            <?php if ($similar['is_popular']): ?>
                                <span class="similar-popular-badge">
                                    <i class="fas fa-crown"></i> Popular
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="similar-info">
                            <h4 class="similar-title"><?php echo htmlspecialchars($similar_name); ?></h4>
                            
                            <?php if (!empty($similar['material_name'])): ?>
                                <p class="similar-material">
                                    <i class="fas fa-gem"></i> <?php echo htmlspecialchars($similar['material_name']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="similar-price-section">
                                <span class="similar-current-price">₹<?php echo number_format($similar['price'], 2); ?></span>
                                <?php if (!empty($similar['original_price']) && $similar['original_price'] > $similar['price']): ?>
                                    <span class="similar-old-price">₹<?php echo number_format($similar['original_price'], 2); ?></span>
                                    <?php echo $similar_discount; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Notification -->
    <div class="notification" id="notification"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
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

        updateQuantityButtons();

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

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.search-container')) {
                    modelsList.classList.remove('active');
                }
            });

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

            function loadModels(brandId) {
                window.modelsCache = [];
                modelsList.innerHTML = '<div class="no-models">Loading models...</div>';
                modelsList.classList.add('active');
                
                fetch(`ajax/get_models.php?brand_id=${brandId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.models && data.models.length > 0) {
                            window.modelsCache = data.models;
                            displayModels(window.modelsCache);
                        } else {
                            window.modelsCache = [];
                            modelsList.innerHTML = '<div class="no-models">No models found for this brand</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading models:', error);
                        modelsList.innerHTML = '<div class="no-models">Error loading models</div>';
                    });
            }

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
                        });
                        
                        modelsList.appendChild(option);
                    });
                }
                
                modelsList.classList.add('active');
            }
        <?php endif; ?>

        // Check wishlist on page load
        <?php if (isset($_SESSION['user_id'])): ?>
            fetch('ajax/check_wishlist.php?product_id=<?php echo $product_id; ?>')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network error');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.in_wishlist) {
                        wishlistBtn.classList.add('active');
                        wishlistBtn.innerHTML = '<i class="fas fa-heart"></i> <span>IN WISHLIST</span>';
                    }
                })
                .catch(error => {
                    console.log('Wishlist check skipped');
                });
        <?php endif; ?>

        // Add to Cart Function
        function addToCart(isBuyNow = false) {
            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            
            if (quantity < 1 || quantity > 10) {
                showNotification('Quantity must be between 1 and 10', 'error');
                return;
            }
            
            let url = `ajax/add_to_cart.php?id=<?php echo $product_id; ?>&qty=${quantity}`;
            
            <?php if ($is_back_case): ?>
                if (!selectedModelId) {
                    showNotification('Please select a phone model first', 'error');
                    return;
                }
                url += `&model_id=${selectedModelId}`;
            <?php endif; ?>

            const originalCartText = addToCartBtn.innerHTML;
            const originalBuyText = buyNowBtn.innerHTML;
            
            addToCartBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Processing...</span>';
            addToCartBtn.disabled = true;
            buyNowBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Processing...</span>';
            buyNowBtn.disabled = true;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        
                        addToCartBtn.innerHTML = '<i class="fas fa-check"></i> <span>ADDED</span>';
                        addToCartBtn.classList.add('added');
                        addToCartBtn.disabled = true;
                        
                        updateCartCount(data.cart_count);
                        
                        if (isBuyNow) {
                            setTimeout(() => {
                                window.location.href = 'checkout.php';
                            }, 1000);
                        } else {
                            setTimeout(() => {
                                addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> <span>ADD TO CART</span>';
                                addToCartBtn.classList.remove('added');
                                addToCartBtn.disabled = false;
                            }, 3000);
                        }
                        
                        buyNowBtn.innerHTML = '<i class="fas fa-bolt"></i> <span>BUY NOW</span>';
                        buyNowBtn.disabled = false;
                    } else {
                        addToCartBtn.innerHTML = originalCartText;
                        addToCartBtn.disabled = false;
                        buyNowBtn.innerHTML = originalBuyText;
                        buyNowBtn.disabled = false;
                        
                        if (data.redirect) {
                            if (confirm('Please login to add items to cart.')) {
                                window.location.href = data.redirect;
                            }
                        } else {
                            showNotification(data.message || 'Failed to add to cart', 'error');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    addToCartBtn.innerHTML = originalCartText;
                    addToCartBtn.disabled = false;
                    buyNowBtn.innerHTML = originalBuyText;
                    buyNowBtn.disabled = false;
                    showNotification('Failed to connect to server', 'error');
                });
        }

        addToCartBtn.addEventListener('click', function (e) {
            e.preventDefault();
            addToCart(false);
        });

        buyNowBtn.addEventListener('click', function (e) {
            e.preventDefault();
            addToCart(true);
        });

        wishlistBtn.addEventListener('click', function () {
            <?php if (!isset($_SESSION['user_id'])): ?>
                if (confirm('Please login to add items to wishlist.')) {
                    window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                }
                return;
            <?php endif; ?>

            const button = this;
            const isActive = button.classList.contains('active');
            const originalText = button.innerHTML;

            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Processing...</span>';
            button.disabled = true;

            if (isActive) {
                fetch('ajax/remove_wishlist.php?product_id=<?php echo $product_id; ?>')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            button.classList.remove('active');
                            button.innerHTML = '<i class="far fa-heart"></i> <span>WISHLIST</span>';
                            showNotification('Removed from wishlist', 'success');
                        } else {
                            showNotification(data.message || 'Failed to remove', 'error');
                            button.innerHTML = originalText;
                        }
                        button.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Failed to connect to server', 'error');
                        button.innerHTML = originalText;
                        button.disabled = false;
                    });
            } else {
                const formData = new FormData();
                formData.append('product_id', '<?php echo $product_id; ?>');

                fetch('ajax/add_to_wishlist.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            button.classList.add('active');
                            button.innerHTML = '<i class="fas fa-heart"></i> <span>IN WISHLIST</span>';
                            showNotification('Added to wishlist!', 'success');
                        } else {
                            showNotification(data.message || 'Failed to add', 'error');
                            button.innerHTML = originalText;
                        }
                        button.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Failed to connect to server', 'error');
                        button.innerHTML = originalText;
                        button.disabled = false;
                    });
            }
        });

        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}" 
                   style="color: ${type === 'success' ? '#4CAF50' : '#F44336'}"></i>
                <div>${message}</div>
            `;
            notification.className = `notification ${type}`;
            notification.classList.add('show');

            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        function updateCartCount(count = null) {
            const cartCountElements = document.querySelectorAll('.cart-count');
            cartCountElements.forEach(el => {
                if (count !== null) {
                    el.textContent = count;
                }
                el.style.display = 'inline-flex';
            });
        }

        // Image error handling
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('error', function() {
                if (!this.hasAttribute('data-error-handled')) {
                    this.setAttribute('data-error-handled', 'true');
                    this.src = '/proglide/assets/no-image.png';
                }
            });
        });
    });
    </script>
</body>
</html>
<?php $conn->close(); ?>