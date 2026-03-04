<?php
require "includes/db.php";
require "includes/auth.php";

if (!isset($_SESSION['user_id'])) {
    // User not logged in
    $user_id = null;
} else {
    $user_id = (int) $_SESSION['user_id'];
}

/* =========================
   FETCH CART ITEMS (DB SAFE)
========================= */
$sql = "
SELECT
    c.id AS cart_id,
    c.quantity,
    p.id AS product_id,
    p.price,
    p.original_price,
    p.image1 AS image,
    p.model_name,
    p.design_name,
    p.is_popular,
    cat.name AS main_category,
    cat.slug AS category_slug,
    mt.name AS type_name,
    vt.name AS variant_name,
    pm.model_name AS phone_model
FROM cart c
JOIN products p ON p.id = c.product_id
JOIN categories cat ON cat.id = p.category_id
LEFT JOIN material_types mt ON mt.id = p.material_type_id
LEFT JOIN variant_types vt ON vt.id = p.variant_type_id
LEFT JOIN phone_models pm ON pm.id = c.phone_model_id
WHERE c.user_id = ?
ORDER BY c.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
$subtotal = 0;
$total_items = 0;
$total_savings = 0;

while ($row = $res->fetch_assoc()) {
    $row['total'] = $row['price'] * $row['quantity'];
    $subtotal += $row['total'];
    $total_items += $row['quantity'];
    
    if (!empty($row['original_price']) && $row['original_price'] > $row['price']) {
        $saving = ($row['original_price'] - $row['price']) * $row['quantity'];
        $total_savings += $saving;
    }
    
    $items[] = $row;
}

/* DELIVERY RULE */
$delivery = ($subtotal > 0 && $subtotal < 299) ? 0 : 0;
$grand_total = $subtotal + $delivery;

// Helper function to get product image path
function getCartImagePath($category_slug, $image_name) {
    if (empty($image_name)) {
        return "/proglide/assets/no-image.png";
    }
    
    $new_path = "/proglide/uploads/products/" . $category_slug . "/" . $image_name;
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $new_path)) {
        return $new_path;
    }
    
    $old_path = "/proglide/uploads/products/" . $image_name;
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $old_path)) {
        return $old_path;
    }
    
    return "/proglide/assets/no-image.png";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Shopping Cart | PROGLIDE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="/proglide/image/logo.png">
<link rel="stylesheet" href="style/cart.css">
    <style>

    </style>
</head>

<body>

    <?php include "includes/header.php"; ?>

    <div class="container">
        <!-- Back Button -->
        <div class="back-btn" onclick="history.back()">
            <i class="fas fa-arrow-left"></i> Back to Shopping
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h3>
                <i class="fas fa-shopping-cart"></i>
                My Cart
                <?php if (isset($_SESSION['user_id']) && $total_items > 0): ?>
                    <span class="cart-count-badge"><?= $total_items ?> <?= $total_items > 1 ? 'Items' : 'Item' ?></span>
                <?php endif; ?>
            </h3>
            
            <?php if (isset($_SESSION['user_id']) && !empty($items)): ?>
                <button class="clear-cart-btn" onclick="clearCart()">
                    <i class="fas fa-trash-alt"></i> Empty Cart
                </button>
            <?php endif; ?>
        </div>

        <div class="cart-layout">
            <?php if (isset($_SESSION['user_id'])): ?>

                <!-- ================= CART ITEMS ================= -->
                <div class="cart-items-container">
                    <div class="cart-header">
                        <i class="fas fa-shopping-bag"></i> My Cart Items (<?= $total_items ?>)
                    </div>
                    
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $it):
                            $isBackCase = strtolower($it['main_category']) === 'back case';
                            
                            $title = $isBackCase
                                ? ($it['design_name'] ?? 'Back Case')
                                : ($it['model_name'] ?? 'Product');
                            
                            if (!$isBackCase && !empty($it['type_name'])) {
                                $title .= " (" . $it['type_name'] . ")";
                            }
                            
                            if (!empty($it['variant_name'])) {
                                $title .= " - " . $it['variant_name'];
                            }
                            
                            if (!empty($it['phone_model'])) {
                                $title .= " (" . $it['phone_model'] . ")";
                            }
                            
                            $item_total = $it['price'] * $it['quantity'];
                            $discount = 0;
                            if (!empty($it['original_price']) && $it['original_price'] > $it['price']) {
                                $discount = round((($it['original_price'] - $it['price']) / $it['original_price']) * 100);
                            }
                            
                            $image_path = getCartImagePath($it['category_slug'] ?? 'general', $it['image']);
                            
                            // Calculate delivery date (3-5 days from now)
                            $delivery_date = date('d M', strtotime('+3 days')) . ' - ' . date('d M', strtotime('+5 days'));
                        ?>
                            <div class="cart-item" data-cart-id="<?= $it['cart_id'] ?>" data-product-id="<?= $it['product_id'] ?>">
                                <div class="item-image">
                                    <?php if ($it['is_popular']): ?>
                                        <span class="popular-badge">
                                            <i class="fas fa-crown"></i> POPULAR
                                        </span>
                                    <?php endif; ?>
                                    <img src="<?= $image_path ?>" 
                                         alt="<?= htmlspecialchars($title) ?>"
                                         onerror="this.onerror=null; this.src='/proglide/assets/no-image.png';">
                                </div>
                                
                                <div class="item-details">
                                    <div class="item-title">
                                        <a href="productdetails.php?id=<?= $it['product_id'] ?>"><?= htmlspecialchars($title) ?></a>
                                    </div>
                                    
                                    <div class="item-meta">
                                        <span><i class="fas fa-tag"></i> <?= htmlspecialchars($it['main_category']) ?></span>
                                        <?php if ($isBackCase && !empty($it['phone_model'])): ?>
                                            <span><i class="fas fa-mobile-alt"></i> <?= htmlspecialchars($it['phone_model']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="item-price-section">
                                        <span class="current-price">₹<?= number_format($it['price'], 2) ?></span>
                                        <?php if ($discount > 0): ?>
                                            <span class="original-price">₹<?= number_format($it['original_price'], 2) ?></span>
                                            <span class="discount-badge"><?= $discount ?>% OFF</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="quantity-controls">
                                        <span class="qty-label">Quantity:</span>
                                        <div class="qty-box">
                                            <button class="qty-btn" onclick="updateQty(<?= $it['cart_id'] ?>, -1, this)" 
                                                    <?= $it['quantity'] <= 1 ? 'disabled' : '' ?>>
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="text" class="qty-input" id="qty-<?= $it['cart_id'] ?>" value="<?= $it['quantity'] ?>" readonly>
                                            <button class="qty-btn" onclick="updateQty(<?= $it['cart_id'] ?>, 1, this)"
                                                    <?= $it['quantity'] >= 10 ? 'disabled' : '' ?>>
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="item-total" id="item-total-<?= $it['cart_id'] ?>">
                                        Total: ₹<?= number_format($item_total, 2) ?>
                                    </div>
                                    
                                    <div class="delivery-date">
                                        <i class="fas fa-truck"></i> Delivery by <?= $delivery_date ?>
                                    </div>
                                    
                                    <button class="remove-item" onclick="removeItem(<?= $it['product_id'] ?>, this)">
                                        <i class="fas fa-trash-alt"></i> Remove
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-cart">
                            <i class="fas fa-shopping-cart"></i>
                            <h4>Your cart is empty!</h4>
                            <p>Looks like you haven't added any items to your cart yet.</p>
                            <a href="products.php" class="shop-now-btn">
                                <i class="fas fa-shopping-bag"></i> Shop Now
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ================= PRICE SUMMARY ================= -->
                <?php if (!empty($items)): ?>
                <div class="price-summary">
                    <div class="summary-header">
                        <h5><i class="fas fa-receipt"></i> Price Summary</h5>
                    </div>
                    
                    <div class="summary-row">
                        <span>Subtotal (<span id="total-items"><?= $total_items ?></span> items)</span>
                        <span id="summary-subtotal">₹<?= number_format($subtotal, 2) ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Delivery Charges</span>
                        <span class="delivery-fee-free" id="summary-delivery">FREE</span>
                    </div>
                    
                    <?php if ($total_savings > 0): ?>
                        <div class="savings-badge" id="savings-badge">
                            <i class="fas fa-tag"></i> You save ₹<?= number_format($total_savings, 2) ?> on this order!
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-row total">
                        <span>Total Amount</span>
                        <span id="summary-total">₹<?= number_format($grand_total, 2) ?></span>
                    </div>
                    
                    <a href="checkout.php" class="checkout-btn">
                        <i class="fas fa-lock"></i> Proceed to Checkout
                    </a>
                    
                    <a href="products.php" class="continue-shopping">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                    
                    <div class="text-center mt-3" style="color: var(--text-muted); font-size: 0.85rem;">
                        <i class="fas fa-shield-alt" style="color: var(--primary);"></i> Safe and Secure Payments
                    </div>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- ================= LOGIN REQUIRED ================= -->
                <div class="col-12">
                    <div class="login-required">
                        <i class="fas fa-lock"></i>
                        <h4>Login Required</h4>
                        <p>Please login to view your cart and proceed with checkout.</p>
                        <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn-login">
                            <i class="fas fa-sign-in-alt"></i> Login Now
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notification -->
    <div class="notification" id="notification"></div>

    <?php include "includes/footer.php"; ?>
    <?php include "includes/mobile_bottom_nav.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function updateQty(cartId, delta, button) {
            // Store original button content
            const originalHtml = button.innerHTML;
            
            // Show loading
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            // Disable the other button for this row
            const row = document.querySelector(`.cart-item[data-cart-id="${cartId}"]`);
            if (row) {
                const otherBtns = row.querySelectorAll('.qty-btn');
                otherBtns.forEach(btn => btn.disabled = true);
            }

            fetch("ajax/update_cart_qty.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ cart_id: cartId, delta: delta })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || "Error updating quantity");
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                    if (row) {
                        const otherBtns = row.querySelectorAll('.qty-btn');
                        otherBtns.forEach(btn => btn.disabled = false);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to connect to server');
                button.innerHTML = originalHtml;
                button.disabled = false;
                if (row) {
                    const otherBtns = row.querySelectorAll('.qty-btn');
                    otherBtns.forEach(btn => btn.disabled = false);
                }
            });
        }

        function removeItem(productId, button) {
            if (!confirm('Remove this item from cart?')) return;
            
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
            button.disabled = true;

            const formData = new FormData();
            formData.append('product_id', productId);

            fetch('ajax/remove_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Item removed from cart', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert(data.message || 'Failed to remove item');
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to connect to server');
                button.innerHTML = originalHtml;
                button.disabled = false;
            });
        }

        function clearCart() {
            if (!confirm('Are you sure you want to empty your cart?')) return;
            
            const clearBtn = document.querySelector('.clear-cart-btn');
            const originalHtml = clearBtn.innerHTML;
            clearBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Clearing...';
            clearBtn.disabled = true;
            
            fetch('ajax/clear_cart.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Cart cleared successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert(data.message || 'Failed to clear cart');
                    clearBtn.innerHTML = originalHtml;
                    clearBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to connect to server');
                clearBtn.innerHTML = originalHtml;
                clearBtn.disabled = false;
            });
        }

        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <div>${message}</div>
            `;
            notification.className = `notification ${type}`;
            notification.classList.add('show');

            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }
    </script>

</body>

</html>
<?php $conn->close(); ?>