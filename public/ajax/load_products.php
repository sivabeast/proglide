<?php
session_start();
require "../includes/db.php";

/* ===============================
   INPUTS
================================ */
$category_id       = $_POST['category_id'] ?? 'all';
$material_type_id  = $_POST['material_type_id'] ?? 'all';
$variant_type_id   = $_POST['variant_type_id'] ?? 'all';
$price_min         = $_POST['price_min'] ?? '';
$price_max         = $_POST['price_max'] ?? '';
$sort              = $_POST['sort'] ?? 'new';
$search            = $_POST['search'] ?? '';
$page              = max(1, (int)($_POST['page'] ?? 1));

$limit  = 8;
$offset = ($page - 1) * $limit;

/* ===============================
   WHERE CLAUSE
================================ */
$where  = "WHERE p.status = 1";
$params = [];
$types  = "";

// Category filter
if ($category_id !== 'all' && $category_id !== '') {
    $where .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

// Material type filter
if ($material_type_id !== 'all' && $material_type_id !== '') {
    $where .= " AND p.material_type_id = ?";
    $params[] = $material_type_id;
    $types .= "i";
}

// Variant type filter
if ($variant_type_id !== 'all' && $variant_type_id !== '') {
    $where .= " AND p.variant_type_id = ?";
    $params[] = $variant_type_id;
    $types .= "i";
}

// Price range filters
if ($price_min !== '' && is_numeric($price_min)) {
    $where .= " AND p.price >= ?";
    $params[] = (float)$price_min;
    $types .= "d";
}

if ($price_max !== '' && is_numeric($price_max)) {
    $where .= " AND p.price <= ?";
    $params[] = (float)$price_max;
    $types .= "d";
}

/* ===============================
   ORDER BY
================================ */
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
}

/* ===============================
   COUNT TOTAL PRODUCTS
================================ */
$count_sql = "
SELECT COUNT(*) as total
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN material_types mt ON p.material_type_id = mt.id
LEFT JOIN variant_types vt ON p.variant_type_id = vt.id
$where
";

$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_products = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_products / $limit);

/* ===============================
   MAIN QUERY
================================ */
$sql = "
SELECT p.*,
       c.name AS category_name,
       c.id AS category_id,
       c.slug AS category_slug,
       mt.name AS material_name,
       vt.name AS variant_name
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN material_types mt ON p.material_type_id = mt.id
LEFT JOIN variant_types vt ON p.variant_type_id = vt.id
$where
$order_by
LIMIT ? OFFSET ?
";

// Add limit and offset to params
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

/* ===============================
   IMAGE PATH HELPER FUNCTION
================================ */
function getProductImagePath($category_slug, $image_name) {
    if (empty($image_name)) {
        return "/proglide/assets/no-image.png";
    }
    
    // Try new path with category slug
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

$html = "";

/* ===============================
   LOOP THROUGH PRODUCTS
================================ */
while ($p = $result->fetch_assoc()) {
    /* Get correct image path */
    $category_slug = $p['category_slug'] ?? 'general';
    $img = getProductImagePath($category_slug, $p['image1'] ?? '');
    
    /* Product name based on category */
    if ($p['category_id'] == 1) { // Protectors
        $product_name = trim(($p['model_name'] ?? 'Protector') .
            ($p['variant_name'] ? " (" . $p['variant_name'] . ")" : ""));
    } elseif ($p['category_id'] == 2) { // Back Cases
        $product_name = $p['design_name'] ?? 'Back Case';
    } elseif ($p['category_id'] == 5) { // Battery
        $product_name = ($p['model_name'] ?? 'Battery') . 
                       ($p['material_name'] ? " " . $p['material_name'] : "");
    } else { // Other categories
        $product_name = $p['model_name'] ?? $p['design_name'] ?? 'Product';
    }
    
    // Check if in wishlist
    $wishlist_active = '';
    $wishlist_icon = 'far fa-heart';
    if (isset($_SESSION['user_id'])) {
        $check_wish = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check_wish->bind_param("ii", $_SESSION['user_id'], $p['id']);
        $check_wish->execute();
        if ($check_wish->get_result()->num_rows > 0) {
            $wishlist_active = ' active';
            $wishlist_icon = 'fas fa-heart';
        }
    }
    
    // Check if in cart
    $in_cart = '';
    $cart_button_text = '<i class="fas fa-shopping-cart"></i> <span>Add</span>';
    if (isset($_SESSION['user_id'])) {
        $check_cart = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
        $check_cart->bind_param("ii", $_SESSION['user_id'], $p['id']);
        $check_cart->execute();
        if ($check_cart->get_result()->num_rows > 0) {
            $in_cart = ' added';
            $cart_button_text = '<i class="fas fa-check"></i> <span>Added</span>';
        }
    }
    
    // Calculate discount percentage
    $discount_html = '';
    if (!empty($p['original_price']) && $p['original_price'] > $p['price']) {
        $discount = round((($p['original_price'] - $p['price']) / $p['original_price']) * 100);
        $discount_html = '<span class="discount-badge">' . $discount . '% OFF</span>';
    }
    
    // Build HTML
    $html .= '
    <div class="product-card" data-product-id="' . $p['id'] . '">
        <div class="product-image-container">
            <img src="' . $img . '" alt="' . htmlspecialchars($product_name) . '" class="product-image" loading="lazy"
                 onerror="this.onerror=null; this.src=\'/proglide/assets/no-image.png\'">
            <button class="wishlist-btn' . $wishlist_active . '" data-product-id="' . $p['id'] . '">
                <i class="' . $wishlist_icon . '"></i>
            </button>';
    
    if ($p['is_popular']) {
        $html .= '<span class="popular-badge"><i class="fas fa-fire"></i> Popular</span>';
    }
    
    $html .= '</div>

        <div class="product-info">
            <h4 class="product-title">' . htmlspecialchars($product_name) . '</h4>';

    if (!empty($p['material_name'])) {
        $html .= '<p class="product-material"><i class="fas fa-gem"></i> ' . htmlspecialchars($p['material_name']) . '</p>';
    }

    $html .= '<div class="price-section">
                <span class="current-price">₹' . number_format($p['price'], 2) . '</span>';
    
    if (!empty($p['original_price']) && $p['original_price'] > $p['price']) {
        $html .= '<span class="original-price">₹' . number_format($p['original_price'], 2) . '</span>';
    }
    
    $html .= $discount_html . '
            </div>
            
            <div class="action-buttons">';

    // Button based on category - FIXED: Back Cases use category_id = 2
    if ($p['category_id'] == 2) { // Back Cases
        $html .= '<button class="action-btn select-model-btn" data-product-id="' . $p['id'] . '">
                    <i class="fas fa-mobile-alt"></i> <span>Select</span>
                  </button>';
    } else {
        $html .= '<button class="action-btn add-to-cart-btn' . $in_cart . '" data-product-id="' . $p['id'] . '">
                    ' . $cart_button_text . '
                  </button>';
    }
    
    $html .= '</div>
        </div>
    </div>';
}

if ($html === '') {
    $html = '<div class="no-products">
                <i class="fas fa-box-open"></i>
                <h3>No products found</h3>
                <p>Try adjusting your filters or search terms</p>
            </div>';
}

// Generate pagination
$pagination_html = '';
if ($total_products > 0 && $total_pages > 1) {
    $pagination_html .= '<div class="pagination">';
    
    // Previous button
    if ($page > 1) {
        $pagination_html .= '<span class="page-link" data-page="' . ($page-1) . '"><i class="fas fa-chevron-left"></i> Prev</span>';
    } else {
        $pagination_html .= '<span class="page-link disabled"><i class="fas fa-chevron-left"></i> Prev</span>';
    }
    
    // Page numbers
    $start = max(1, $page - 2);
    $end = min($total_pages, $page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $page) {
            $pagination_html .= '<span class="page-link active">' . $i . '</span>';
        } else {
            $pagination_html .= '<span class="page-link" data-page="' . $i . '">' . $i . '</span>';
        }
    }
    
    // Next button
    if ($page < $total_pages) {
        $pagination_html .= '<span class="page-link" data-page="' . ($page+1) . '">Next <i class="fas fa-chevron-right"></i></span>';
    } else {
        $pagination_html .= '<span class="page-link disabled">Next <i class="fas fa-chevron-right"></i></span>';
    }
    
    $pagination_html .= '</div>';
}

echo json_encode([
    'html' => $html,
    'pagination' => $pagination_html,
    'total' => $total_products,
    'page' => $page,
    'total_pages' => $total_pages
]);

$conn->close();
?>