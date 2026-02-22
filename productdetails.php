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
<link rel="stylesheet" href="style/productdetails.css">
    <style>

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