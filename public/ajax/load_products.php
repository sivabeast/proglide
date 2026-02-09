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

$limit  = 12;
$offset = ($page - 1) * $limit;

/* ===============================
   WHERE
================================ */
$where  = "WHERE p.status = 1";
$params = [];
$types  = "";

// URL category parameter (from products.php?cat=)
if (isset($_POST['category_id']) && $_POST['category_id'] !== 'all') {
    $category_id = $_POST['category_id'];
}

if ($category_id !== 'all') {
    $where .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

if ($material_type_id !== 'all') {
    $where .= " AND p.material_type_id = ?";
    $params[] = $material_type_id;
    $types .= "i";
}

if ($variant_type_id !== 'all') {
    $where .= " AND p.variant_type_id = ?";
    $params[] = $variant_type_id;
    $types .= "i";
}

if ($price_min !== '') {
    $where .= " AND p.price >= ?";
    $params[] = $price_min;
    $types .= "d";
}

if ($price_max !== '') {
    $where .= " AND p.price <= ?";
    $params[] = $price_max;
    $types .= "d";
}

if ($search !== '') {
    $where .= " AND (p.model_name LIKE ? OR p.design_name LIKE ? 
              OR mt.name LIKE ? OR vt.name LIKE ? OR c.name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sssss";
}

/* ===============================
   ORDER BY
================================ */
$order_by = match ($sort) {
    'popular' => "ORDER BY p.is_popular DESC, p.created_at DESC",
    'low'     => "ORDER BY p.price ASC",
    'high'    => "ORDER BY p.price DESC",
    default   => "ORDER BY p.created_at DESC",
};

/* ===============================
   QUERY
================================ */
$sql = "
SELECT p.*,
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
LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;
$types   .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

/* ===============================
   BASE PATHS
================================ */
$PROJECT_ROOT = dirname(dirname(__DIR__));
$WEB_ROOT     = "/proglide";

$html = "";
$total_products = 0;

/* ===============================
   LOOP
================================ */
while ($p = $result->fetch_assoc()) {
    $total_products++;
    
    /* Dynamic folder using slug */
    $folder = strtolower($p['category_slug'] ?? 'others');
    $image  = $p['image1'] ?? '';
    
    /* filesystem check */
    $fsPath = "$PROJECT_ROOT/uploads/products/$folder/$image";
    
    /* browser path */
    if ($image && file_exists($fsPath)) {
        $img = "$WEB_ROOT/uploads/products/$folder/$image";
    } else {
        $img = "$WEB_ROOT/assets/no-image.png";
    }
    
    /* product name */
    if ($p['category_id'] == 13) {
        $product_name = trim(($p['model_name'] ?? 'Protector') .
            ($p['variant_name'] ? " ({$p['variant_name']})" : ""));
    } elseif ($p['category_id'] == 14) {
        $product_name = $p['design_name'] ?? 'Back Cases';
    } else {
        $product_name = $p['model_name'] ?? 'Product';
    }
    
    // Check if in wishlist for current user
    $wishlist_active = '';
    if (isset($_SESSION['user_id'])) {
        $check_wish = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check_wish->bind_param("ii", $_SESSION['user_id'], $p['id']);
        $check_wish->execute();
        $wishlist_active = $check_wish->get_result()->num_rows > 0 ? 'active' : '';
    }
    
    // Check if in cart for current user
    $cart_active = '';
    if (isset($_SESSION['user_id'])) {
        $check_cart = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
        $check_cart->bind_param("ii", $_SESSION['user_id'], $p['id']);
        $check_cart->execute();
        $cart_active = $check_cart->get_result()->num_rows > 0 ? 'added' : '';
    }
    
    // Determine button text and class
    $button_text = $cart_active ? 'ADDED' : 'Add to Cart';
    $button_class = $cart_active ? 'added' : '';
    
    $html .= '
    <div class="product-card" data-product-id="'.$p['id'].'">
        <div class="product-image-container">
            <img src="'.$img.'" class="product-image" loading="lazy"
                 onerror="this.src=\''.$WEB_ROOT.'/assets/no-image.png\'">
            <button class="wishlist-btn '.$wishlist_active.'" data-product-id="'.$p['id'].'">
                '.($wishlist_active ? '<i class="fas fa-heart"></i>' : '<i class="far fa-heart"></i>').'
            </button>
            '.($p['is_popular'] ? '<span class="popular-badge">Popular</span>' : '').'
        </div>

        <div class="product-info">
            <h4 class="product-title">'.htmlspecialchars($product_name).'</h4>

            '.($p['material_name'] ? '<p class="product-material">'.$p['material_name'].'</p>' : '').'

            <p class="product-category">'.$p['category_name'].'</p>

            <div class="price-section">
                <span class="current-price">₹'.number_format($p['price'], 2).'</span>
                '.($p['original_price'] > $p['price']
                    ? '<span class="original-price">₹'.number_format($p['original_price'], 2).'</span>'
                    : '').'
            </div>

            <button class="action-btn '.($p['category_id']==2?'select-model-btn':'add-to-cart-btn').' '.$button_class.'" 
                    data-product-id="'.$p['id'].'"
                    style="'.($cart_active ? 'background: linear-gradient(135deg, #00e676, #00c853) !important; color: #fff !important;' : '').'">
                '.($p['category_id']==2?'Select Model':$button_text).'
            </button>
        </div>
    </div>';
}

if ($html === '') {
    $html = '<div class="no-products">
                <i class="fas fa-box-open" style="font-size: 3rem; opacity: 0.3; margin-bottom: 20px;"></i>
                <h3>No products found</h3>
                <p>Try adjusting your filters or search terms</p>
            </div>';
}

// Generate pagination
$pagination_html = '';
if ($total_products > 0) {
    $total_pages = ceil($total_products / $limit);
    
    if ($total_pages > 1) {
        $pagination_html .= '<div class="pagination">';
        
        // Previous
        if ($page > 1) {
            $pagination_html .= '<span class="page-link" data-page="'.($page-1).'">Previous</span>';
        }
        
        // Pages
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == $page) {
                $pagination_html .= '<span class="page-link active">'.$i.'</span>';
            } else {
                $pagination_html .= '<span class="page-link" data-page="'.$i.'">'.$i.'</span>';
            }
        }
        
        // Next
        if ($page < $total_pages) {
            $pagination_html .= '<span class="page-link" data-page="'.($page+1).'">Next</span>';
        }
        
        $pagination_html .= '</div>';
    }
}

echo json_encode([
    'html' => $html,
    'pagination' => $pagination_html,
    'total' => $total_products,
    'page' => $page
]);

$conn->close();
?>