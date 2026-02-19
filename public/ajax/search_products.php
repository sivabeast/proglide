<?php
// ajax/search_products.php
session_start();
require "../includes/db.php";

$search = $_GET['q'] ?? '';

if (strlen($search) < 2) {
    echo '<div class="no-products"><p>Please enter at least 2 characters</p></div>';
    exit;
}

// Modified SQL to include category slug
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
        WHERE p.status = 1 
        AND (p.model_name LIKE ? OR p.design_name LIKE ? OR p.description LIKE ? 
             OR c.name LIKE ? OR mt.name LIKE ? OR vt.name LIKE ?)
        ORDER BY p.created_at DESC
        LIMIT 20";
        
$searchTerm = "%" . $search . "%";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

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

$html = '';
if ($result->num_rows > 0) {
    while ($product = $result->fetch_assoc()) {
        $is_back_case = ($product['category_id'] == 2); // Assuming category_id 2 is Back Case
        
        // Determine product display name based on category
        if ($product['category_id'] == 1) { // Protector
            $display_name = $product['model_name'] ?? 'Protector';
            $variant_info = $product['variant_name'] ? '(' . $product['variant_name'] . ')' : '';
            $product_name = $display_name . ' ' . $variant_info;
        } else if ($product['category_id'] == 2) { // Back Case
            $display_name = $product['design_name'] ?? 'Back Case';
            $product_name = $display_name;
        } else {
            $display_name = $product['model_name'] ?? $product['design_name'] ?? 'Product';
            $product_name = $display_name;
        }
        
        // Get correct image path
        $category_slug = $product['category_slug'] ?? 'general';
        $image_path = getProductImagePath($category_slug, $product['image1'] ?? '');
        
        $html .= '<div class="product-card" data-product-id="' . $product['id'] . '">';
        
        // Product Image Container
        $html .= '<div class="product-image-container">';
        $html .= '<img src="' . $image_path . '" alt="' . htmlspecialchars($product_name) . '" class="product-image" loading="lazy" onerror="this.onerror=null; this.src=\'/proglide/assets/no-image.png\';">';
        
        // Wishlist Icon (Heart)
        $html .= '<button class="wishlist-btn" data-product-id="' . $product['id'] . '" aria-label="Add to wishlist">';
        $html .= '<i class="far fa-heart"></i>';
        $html .= '</button>';
        
        // Popular Badge
        if ($product['is_popular']) {
            $html .= '<span class="popular-badge"><i class="fas fa-fire"></i> Popular</span>';
        }
        
        $html .= '</div>'; // Close product-image-container
        
        // Product Info
        $html .= '<div class="product-info">';
        
        // Product Name with Variant
        $html .= '<h4 class="product-title">' . htmlspecialchars($product_name) . '</h4>';
        
        // Material Type
        if (!empty($product['material_name'])) {
            $html .= '<p class="product-material"><i class="fas fa-gem"></i> ' . htmlspecialchars($product['material_name']) . '</p>';
        }
        
        // Category and Variant
        $html .= '<p class="product-category">';
        $html .= '<i class="fas fa-tag"></i> ' . htmlspecialchars($product['category_name']);
        if ($product['category_id'] == 1 && !empty($product['variant_name'])) {
            $html .= ' • <i class="fas fa-palette"></i> ' . htmlspecialchars($product['variant_name']);
        }
        $html .= '</p>';
        
        // Price Section
        $html .= '<div class="price-section">';
        $html .= '<span class="current-price">₹' . number_format($product['price'], 2) . '</span>';
        
        if (!empty($product['original_price']) && $product['original_price'] > $product['price']) {
            $html .= '<span class="original-price">₹' . number_format($product['original_price'], 2) . '</span>';
            $discount = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
            $html .= '<span class="discount-badge">' . $discount . '% OFF</span>';
        }
        $html .= '</div>';
        
        // Action Button
        if ($is_back_case) {
            $html .= '<button class="action-btn select-model-btn" data-product-id="' . $product['id'] . '">';
            $html .= '<i class="fas fa-mobile-alt"></i> Select Model';
            $html .= '</button>';
        } else {
            $html .= '<button class="action-btn add-to-cart-btn" data-product-id="' . $product['id'] . '">';
            $html .= '<i class="fas fa-shopping-cart"></i> ADD TO CART';
            $html .= '</button>';
        }
        
        $html .= '</div>'; // Close product-info
        $html .= '</div>'; // Close product-card
    }
} else {
    $html = '<div class="no-products"><i class="fas fa-search"></i><h3>No products found</h3><p>Try different keywords</p></div>';
}

echo $html;

$conn->close();
?>