<?php
// ajax/load_products.php
session_start();
require "../includes/db.php";

// Get filters from POST
$category_id = $_POST['category_id'] ?? 'all';
$material_type_id = $_POST['material_type_id'] ?? 'all';
$variant_type_id = $_POST['variant_type_id'] ?? 'all';
$price_min = $_POST['price_min'] ?? '';
$price_max = $_POST['price_max'] ?? '';
$sort = $_POST['sort'] ?? 'new';
$page = $_POST['page'] ?? 1;
$limit = 10; // Products per page
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where = "WHERE p.status = 1";
$params = [];
$types = "";

if (!empty($category_id) && $category_id !== 'all') {
    $where .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

if (!empty($material_type_id) && $material_type_id !== 'all') {
    $where .= " AND p.material_type_id = ?";
    $params[] = $material_type_id;
    $types .= "i";
}

if (!empty($variant_type_id) && $variant_type_id !== 'all') {
    $where .= " AND p.variant_type_id = ?";
    $params[] = $variant_type_id;
    $types .= "i";
}

if (!empty($price_min)) {
    $where .= " AND p.price >= ?";
    $params[] = $price_min;
    $types .= "d";
}

if (!empty($price_max)) {
    $where .= " AND p.price <= ?";
    $params[] = $price_max;
    $types .= "d";
}

// Build ORDER BY
$order_by = "";
switch ($sort) {
    case 'popular':
        $order_by = "ORDER BY p.is_popular DESC, p.created_at DESC";
        break;
    case 'low':
        $order_by = "ORDER BY p.price ASC";
        break;
    case 'high':
        $order_by = "ORDER BY p.price DESC";
        break;
    default:
        $order_by = "ORDER BY p.created_at DESC";
        break;
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM products p $where";
$count_stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Get products with pagination
$sql = "SELECT 
    p.*,
    c.name AS category_name,
    c.slug AS category_slug,
    mt.name AS material_name,
    vt.name AS variant_name
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN material_types mt ON p.material_type_id = mt.id
LEFT JOIN variant_types vt ON p.variant_type_id = vt.id
$where
$order_by
LIMIT ? OFFSET ?";

// Add pagination parameters
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Generate HTML with detailed product cards
$html = '';
if ($result->num_rows > 0) {
    while ($product = $result->fetch_assoc()) {
        // Determine if it's a back case category
        $is_back_case = ($product['category_id'] == 2);
        
        // Get correct category slug for image path
    
        $image = $product['image1'] ?? '';
        $categorySlug = $product['category_slug'] ?? 'others';
        
        $baseDir = dirname(__DIR__);
$fsPath = $baseDir . "../uploads/products/$categorySlug/$image";

if (!empty($image) && file_exists($fsPath)) {
    $imagePath = "../uploads/products/$categorySlug/$image";
} else {
    $imagePath = "/assets/no-image.png";
}


        
        // Determine product display name based on category
        if ($product['category_id'] == 1) { // Protector
            $display_name = $product['model_name'] ?? 'Protector';
            $variant_info = $product['variant_name'] ? '(' . $product['variant_name'] . ')' : '';
            $product_name = trim($display_name . ' ' . $variant_info);
        } else if ($product['category_id'] == 2) { // Back Case
            $display_name = $product['design_name'] ?? 'Back Case';
            $product_name = $display_name;
        } else {
            $display_name = $product['model_name'] ?? $product['design_name'] ?? 'Product';
            $product_name = $display_name;
        }

        $html .= '<div class="product-card" data-product-id="' . $product['id'] . '">';
        $html .= '<div class="product-image-container">';

        $html .= '<img src="' . $imagePath . '"
            alt="' . htmlspecialchars($product_name) . '"
            class="product-image"
            loading="lazy"
            onerror="this.src=\'/assets/no-image.png\'">';
        
        // Wishlist button
        $html .= '<button class="wishlist-btn" data-product-id="' . $product['id'] . '">';
        $html .= '<i class="far fa-heart"></i>';
        $html .= '</button>';
        
        // Popular badge (ONLY ONCE)
        if ($product['is_popular']) {
            $html .= '<span class="popular-badge"><i class="fas fa-fire"></i> Popular</span>';
        }
        
        $html .= '</div>'; // ✅ CLOSE product-image-container ONLY ONCE
        


        // Product Info
        $html .= '<div class="product-info">';

        // Product Name with Variant
        $html .= '<h4 class="product-title">' . htmlspecialchars($product_name) . '</h4>';

        // Material Type
        if (!empty($product['material_name'])) {
            $html .= '<span class="product-material"><p class="material-text"><i class="fas fa-gem"></i> ' . htmlspecialchars($product['material_name']) . '</p></span>';
        }

        // Category and Variant
        $html .= '<span class="product-category">';
        $html .= '<p class="category-text"><i class="fas fa-tag"></i> ' . htmlspecialchars($product['category_name']);
        if ($product['category_id'] == 1 && !empty($product['variant_name'])) {
            $html .= ' • <i class="fas fa-palette"></i> ' . htmlspecialchars($product['variant_name']);
        }
        $html .= '</p>';
        $html .= '</span>';

        // Price Section
        $html .= '<div class="price-section">';
        if (!empty($product['original_price']) && $product['original_price'] > $product['price']) {
            $html .= '<span class="current-price">₹' . number_format($product['price'], 2) . '</span>';
            $html .= '<span class="original-price">₹' . number_format($product['original_price'], 2) . '</span>';
        } else {
            $html .= '<span class="current-price">₹' . number_format($product['price'], 2) . '</span>';
        }
        $html .= '</div>';

        // FIX: Always add a visible action button for all cards
        $html .= '<div class="product-action">';
        if ($is_back_case) {
            $html .= '<button class="action-btn select-model-btn" data-product-id="' . $product['id'] . '">';
            $html .= '<i class="fas fa-mobile-alt"></i> Select Model';
            $html .= '</button>';
        } else {
            $html .= '<button class="action-btn add-to-cart-btn" data-product-id="' . $product['id'] . '">';
            $html .= '<i class="fas fa-shopping-cart"></i> ADD TO CART';
            $html .= '</button>';
        }
        $html .= '</div>'; // End .product-action

        $html .= '</div>'; // Close product-info
        $html .= '</div>'; // Close product-card
    }
} else {
    $html = '<div class="no-products"><i class="fas fa-box-open"></i><h3>No Products Found</h3><p>Try changing your filters</p></div>';
}

// Generate pagination
$pagination = '';
if ($total_pages > 1) {
    $pagination .= '<div class="pagination-container">';
    if ($page > 1) {
        $pagination .= '<a href="#" class="page-link" data-page="' . ($page - 1) . '"><i class="fas fa-chevron-left"></i></a>';
    }
    
    // Show limited pagination
    $start_page = max(1, $page - 2);
    $end_page = min($total_pages, $page + 2);
    
    if ($start_page > 1) {
        $pagination .= '<a href="#" class="page-link" data-page="1">1</a>';
        if ($start_page > 2) {
            $pagination .= '<span class="page-dots">...</span>';
        }
    }
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $page) {
            $pagination .= '<span class="page-link active" data-page="' . $i . '">' . $i . '</span>';
        } else {
            $pagination .= '<a href="#" class="page-link" data-page="' . $i . '">' . $i . '</a>';
        }
    }
    
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $pagination .= '<span class="page-dots">...</span>';
        }
        $pagination .= '<a href="#" class="page-link" data-page="' . $total_pages . '">' . $total_pages . '</a>';
    }
    
    if ($page < $total_pages) {
        $pagination .= '<a href="#" class="page-link" data-page="' . ($page + 1) . '"><i class="fas fa-chevron-right"></i></a>';
    }
    $pagination .= '</div>';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'html' => $html,
    'pagination' => $pagination,
    'total' => $total_rows,
    'page' => $page,
    'total_pages' => $total_pages
]);

$conn->close();
?>