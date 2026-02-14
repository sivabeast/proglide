<?php
session_start();
require "includes/db.php";

$category_id = $_GET['cat'] ?? '';
$category_name = '';

// Get category name for display
if ($category_id && $category_id !== 'all') {
    $cat_query = $conn->prepare("SELECT name FROM categories WHERE id = ? AND status = 1");
    $cat_query->bind_param("i", $category_id);
    $cat_query->execute();
    $cat_result = $cat_query->get_result();
    if ($cat_result->num_rows > 0) {
        $category_data = $cat_result->fetch_assoc();
        $category_name = $category_data['name'];
    }
}

$user_id = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title><?php echo $category_name ? htmlspecialchars($category_name) . ' | ' : '' ?>Products | PROGLIDE</title>
    <meta name="description" content="Premium phone protectors, cases, and accessories. 9H hardness, privacy screens, and stylish back cases.">
    <link rel="icon" type="image/x-icon" href="/proglide/image/logo.png">

    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="style/products.css">
    <style>
        
    </style>
</head>
<body>
    <!-- Header -->
    <?php if(file_exists("includes/header.php")) include "includes/header.php"; ?>

    <!-- Page Header -->
    <section class="page-header-section">
        <div class="container text-center">
            <div class="page-badge">
                <i class="fas fa-bolt"></i>
                Premium Quality • 9H Hardness • Free Shipping
            </div>
            <h1 class="page-header-title">
                <?php 
                if ($category_name && $category_id !== 'all') {
                    echo htmlspecialchars($category_name);
                } else {
                    echo 'All Products';
                }
                ?>
            </h1>
            <p class="page-header-subtitle">
                <?php 
                if ($category_name && $category_id !== 'all') {
                    echo 'Browse our collection of ' . htmlspecialchars($category_name);
                } else {
                    echo 'Discover our premium collection of protectors and cases';
                }
                ?>
            </p>
        </div>
    </section>

    <div class="container mb-5">
        <!-- Search Section -->
        <div class="search-section">
            <div class="search-container">
                <input type="text" id="searchInput" class="search-bar" 
                       placeholder="Search products by name, material, or category..." 
                       autocomplete="off" aria-label="Search products">
                <div class="search-icon">
                    <i class="fas fa-search"></i>
                </div>
            </div>
        </div>

        <!-- Filter Toggle (Mobile Only) -->
        <div class="filter-toggle-container">
            <button id="filterToggle" class="filter-toggle-btn" type="button" aria-label="Toggle filters">
                <i class="fas fa-filter"></i> Filters
            </button>
        </div>

        <!-- Filter Overlay (Mobile) -->
        <div class="filter-overlay" id="filterOverlay"></div>

        <div class="page-layout">
            <!-- Filter Sidebar -->
            <form id="filterForm" class="filter-sidebar">
                <div class="filter-group">
                    <h3 class="filter-title">
                        <i class="fas fa-layer-group"></i>
                        Category
                    </h3>
                    <select name="category_id" id="categoryFilter" class="filter-select" aria-label="Select category">
                        <option value="all">All Categories</option>
                        <?php
                        $cat_query = $conn->query("SELECT id, name FROM categories WHERE status = 1 ORDER BY name");
                        while ($cat = $cat_query->fetch_assoc()) {
                            $selected = ($cat['id'] == $category_id) ? 'selected' : '';
                            echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . htmlspecialchars($cat['name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-group">
                    <h3 class="filter-title">
                        <i class="fas fa-gem"></i>
                        Material Type
                    </h3>
                    <div id="materialFilterContainer">
                        <select name="material_type_id" id="materialFilter" class="filter-select" 
                                <?php echo $category_id && $category_id !== 'all' ? '' : 'disabled'; ?> 
                                aria-label="Select material type">
                            <option value="all"><?php echo ($category_id && $category_id !== 'all') ? 'All Materials' : 'Select Category First'; ?></option>
                        </select>
                        <div class="loading-indicator" id="materialLoading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Loading materials...
                        </div>
                    </div>
                </div>

                <div class="filter-group">
                    <h3 class="filter-title">
                        <i class="fas fa-palette"></i>
                        Design/Variant
                    </h3>
                    <div id="variantFilterContainer">
                        <select name="variant_type_id" id="variantFilter" class="filter-select" 
                                <?php echo $category_id && $category_id !== 'all' ? '' : 'disabled'; ?> 
                                aria-label="Select design/variant">
                            <option value="all"><?php echo ($category_id && $category_id !== 'all') ? 'All Variants' : 'Select Category First'; ?></option>
                        </select>
                        <div class="loading-indicator" id="variantLoading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Loading variants...
                        </div>
                    </div>
                </div>

                <div class="filter-group">
                    <h3 class="filter-title">
                        <i class="fas fa-tag"></i>
                        Price Range
                    </h3>
                    <div class="price-inputs">
                        <input type="number" name="price_min" placeholder="Min" min="0" step="0.01" 
                               class="price-input" aria-label="Minimum price">
                        <input type="number" name="price_max" placeholder="Max" min="0" step="0.01" 
                               class="price-input" aria-label="Maximum price">
                    </div>
                </div>

                <div class="filter-group">
                    <h3 class="filter-title">
                        <i class="fas fa-sort"></i>
                        Sort By
                    </h3>
                    <select name="sort" id="sortFilter" class="filter-select" aria-label="Sort products by">
                        <option value="new">Newest First</option>
                        <option value="popular">Most Popular</option>
                        <option value="low">Price: Low to High</option>
                        <option value="high">Price: High to Low</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="filter-btn filter-btn-primary" id="applyFiltersBtn">
                        <i class="fas fa-check"></i> Apply Filters
                    </button>
                    <button type="button" onclick="resetFilters()" class="filter-btn filter-btn-secondary" id="resetFiltersBtn">
                        <i class="fas fa-undo"></i> Reset Filters
                    </button>
                    
                    <button type="button" class="close-filter-btn" id="closeFiltersBtn">
                        <i class="fas fa-times"></i> Close Filters
                    </button>
                </div>
            </form>

            <!-- Products Section -->
            <div class="products-section">
                <div id="loadingIndicator" class="loading-state" style="display: none;" aria-live="polite" aria-busy="true">
                    <div class="loading-spinner" aria-hidden="true"></div>
                    <p>Loading products...</p>
                </div>

                <div id="searchResultsInfo" class="search-results-info" style="display: none;"></div>

                <div class="products-grid" id="productGrid" role="list" aria-label="Products list">
                    <!-- Products will be loaded here via AJAX -->
                </div>

                <div id="pagination" class="pagination-container" role="navigation" aria-label="Pagination"></div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php if(file_exists("includes/footer.php")) include "includes/footer.php"; ?>
    <?php if(file_exists("includes/mobile_bottom_nav.php")) include "includes/mobile_bottom_nav.php"; ?>

    <script>
    // ============================================
    // PRODUCTS PAGE JAVASCRIPT - INDEX.PHP STYLE
    // ============================================
    
    const state = {
        currentPage: 1,
        currentSearch: '',
        isLoading: false,
        currentCategoryId: '<?= $category_id ?>',
        isMobileView: window.innerWidth <= 991,
        totalProducts: 0,
        searchTimeout: null,
        userId: <?= $user_id ? 'true' : 'false' ?>
    };
    
    const elements = {
        searchInput: document.getElementById('searchInput'),
        filterForm: document.getElementById('filterForm'),
        categoryFilter: document.getElementById('categoryFilter'),
        materialFilter: document.getElementById('materialFilter'),
        variantFilter: document.getElementById('variantFilter'),
        productGrid: document.getElementById('productGrid'),
        pagination: document.getElementById('pagination'),
        loadingIndicator: document.getElementById('loadingIndicator'),
        searchResultsInfo: document.getElementById('searchResultsInfo'),
        filterToggle: document.getElementById('filterToggle'),
        filterSidebar: document.querySelector('.filter-sidebar'),
        filterOverlay: document.getElementById('filterOverlay'),
        closeFiltersBtn: document.getElementById('closeFiltersBtn')
    };
    
    document.addEventListener('DOMContentLoaded', function() {
        initializePage();
        setupEventListeners();
        handleResize();
        window.addEventListener('resize', handleResize);
    });
    
    function initializePage() {
        const urlParams = new URLSearchParams(window.location.search);
        const categoryId = urlParams.get('cat');
        
        if (categoryId && categoryId !== 'all') {
            elements.categoryFilter.value = categoryId;
            loadCategoryFilters(categoryId);
            loadProducts(1);
        } else {
            loadCategoryFilters('all');
            loadProducts(1);
        }
    }
    
    function setupEventListeners() {
        elements.searchInput.addEventListener('input', handleSearchInput);
        elements.filterForm.addEventListener('submit', handleFilterSubmit);
        elements.categoryFilter.addEventListener('change', handleCategoryChange);
        
        elements.filterToggle.addEventListener('click', toggleFilterSidebar);
        elements.filterOverlay.addEventListener('click', closeFilterSidebar);
        elements.closeFiltersBtn.addEventListener('click', closeFilterSidebar);
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && state.isMobileView && elements.filterSidebar.classList.contains('active')) {
                closeFilterSidebar();
            }
        });
        
        window.addEventListener('popstate', handlePopState);
        
        elements.searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
    }
    
    function handleResize() {
        state.isMobileView = window.innerWidth <= 991;
        if (!state.isMobileView && elements.filterSidebar.classList.contains('active')) {
            closeFilterSidebar();
        }
    }
    
    function handleSearchInput(e) {
        clearTimeout(state.searchTimeout);
        const query = e.target.value.trim();
        
        state.searchTimeout = setTimeout(() => {
            if (query.length >= 2 || query === '') {
                state.currentSearch = query;
                state.currentPage = 1;
                
                if (query) {
                    if (state.isMobileView) {
                        closeFilterSidebar();
                    }
                    performSearch(query);
                } else {
                    elements.searchResultsInfo.style.display = 'none';
                    loadProducts(1);
                }
            }
        }, 500);
    }
    
    function performSearch(query) {
        showLoading(true);
        
        fetch(`ajax/search_products.php?q=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) throw new Error('Search failed');
                return response.text();
            })
            .then(html => {
                elements.productGrid.innerHTML = html;
                elements.pagination.innerHTML = '';
                elements.searchResultsInfo.style.display = 'flex';
                elements.searchResultsInfo.innerHTML = `
                    <i class="fas fa-search"></i>
                    Found ${countProductsInGrid()} products matching "${query}"
                `;
                showLoading(false);
                setupProductInteractions();
            })
            .catch(error => {
                console.error('Search error:', error);
                showError('Search failed. Please try again.');
                showLoading(false);
            });
    }
    
    function countProductsInGrid() {
        return elements.productGrid.querySelectorAll('.product-card').length;
    }
    
    function handleFilterSubmit(e) {
        e.preventDefault();
        
        if (state.isMobileView) {
            closeFilterSidebar();
        }
        
        state.currentSearch = '';
        elements.searchInput.value = '';
        elements.searchResultsInfo.style.display = 'none';
        
        loadProducts(1);
    }
    
    function handleCategoryChange() {
        const categoryId = this.value;
        state.currentCategoryId = categoryId;
        
        loadCategoryFilters(categoryId);
        
        const url = new URL(window.location);
        if (categoryId === 'all') {
            url.searchParams.delete('cat');
        } else {
            url.searchParams.set('cat', categoryId);
        }
        window.history.pushState({ categoryId }, '', url);
        
        elements.materialFilter.value = 'all';
        elements.variantFilter.value = 'all';
        
        updatePageTitle(categoryId);
        loadProducts(1);
    }
    
    function updatePageTitle(categoryId) {
        const pageTitle = document.querySelector('.page-header-title');
        const pageSubtitle = document.querySelector('.page-header-subtitle');
        const categoryName = elements.categoryFilter.options[elements.categoryFilter.selectedIndex].text;
        
        if (categoryId === 'all') {
            pageTitle.textContent = 'All Products';
            pageSubtitle.textContent = 'Discover our premium collection of protectors and cases';
            document.title = 'Products | PROGLIDE';
        } else {
            pageTitle.textContent = categoryName;
            pageSubtitle.textContent = `Browse our collection of ${categoryName}`;
            document.title = `${categoryName} | PROGLIDE`;
        }
    }
    
    function loadCategoryFilters(categoryId) {
        if (!categoryId || categoryId === 'all') {
            elements.materialFilter.innerHTML = '<option value="all">All Materials</option>';
            elements.materialFilter.disabled = false;
            elements.variantFilter.innerHTML = '<option value="all">All Variants</option>';
            elements.variantFilter.disabled = false;
            return;
        }
        
        document.getElementById('materialLoading').style.display = 'flex';
        elements.materialFilter.disabled = true;
        document.getElementById('variantLoading').style.display = 'flex';
        elements.variantFilter.disabled = true;
        
        Promise.all([
            fetch(`ajax/get_materials.php?category_id=${encodeURIComponent(categoryId)}`),
            fetch(`ajax/get_variants.php?category_id=${encodeURIComponent(categoryId)}`)
        ])
        .then(responses => Promise.all(responses.map(r => {
            if (!r.ok) throw new Error('Network error');
            return r.json();
        })))
        .then(([materials, variants]) => {
            updateMaterialFilter(materials);
            updateVariantFilter(variants);
        })
        .catch(error => {
            console.error('Error loading filters:', error);
            elements.materialFilter.innerHTML = '<option value="all">Error loading</option>';
            elements.materialFilter.disabled = false;
            elements.variantFilter.innerHTML = '<option value="all">Error loading</option>';
            elements.variantFilter.disabled = false;
        })
        .finally(() => {
            document.getElementById('materialLoading').style.display = 'none';
            document.getElementById('variantLoading').style.display = 'none';
        });
    }
    
    function updateMaterialFilter(materials) {
        elements.materialFilter.innerHTML = '<option value="all">All Materials</option>';
        
        if (materials && materials.length > 0) {
            materials.forEach(material => {
                const option = document.createElement('option');
                option.value = material.id;
                option.textContent = material.name;
                elements.materialFilter.appendChild(option);
            });
        } else {
            elements.materialFilter.innerHTML = '<option value="all">No materials available</option>';
        }
        
        elements.materialFilter.disabled = false;
    }
    
    function updateVariantFilter(variants) {
        elements.variantFilter.innerHTML = '<option value="all">All Variants</option>';
        
        if (variants && variants.length > 0) {
            variants.forEach(variant => {
                const option = document.createElement('option');
                option.value = variant.id;
                option.textContent = variant.name;
                elements.variantFilter.appendChild(option);
            });
        } else {
            elements.variantFilter.innerHTML = '<option value="all">No variants available</option>';
        }
        
        elements.variantFilter.disabled = false;
    }
    
    function loadProducts(page = 1) {
        if (state.isLoading) return;
        
        state.isLoading = true;
        state.currentPage = page;
        
        showLoading(true);
        elements.productGrid.innerHTML = '';
        
        const formData = new FormData(elements.filterForm);
        formData.append('page', page);
        
        if (state.currentSearch) {
            ['category_id', 'material_type_id', 'variant_type_id', 'price_min', 'price_max', 'sort'].forEach(field => {
                formData.delete(field);
            });
        }
        
        fetch('ajax/load_products.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            elements.productGrid.innerHTML = data.html;
            elements.pagination.innerHTML = data.pagination;
            state.totalProducts = data.total;
            
            setupProductInteractions();
            setupPaginationHandlers();
            announceResults(data.total);
            
            showLoading(false);
            state.isLoading = false;
        })
        .catch(error => {
            console.error('Error loading products:', error);
            showError('Failed to load products. Please check your connection and try again.');
            showLoading(false);
            state.isLoading = false;
        });
    }
    
    function setupProductInteractions() {
        // Product card click
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('click', function(e) {
                if (!e.target.closest('.wishlist-btn') && !e.target.closest('.action-btn')) {
                    const productId = this.dataset.productId;
                    window.location.href = 'productdetails.php?id=' + productId;
                }
            });
        });
        
        // Wishlist buttons
        document.querySelectorAll('.wishlist-btn').forEach(btn => {
            btn.addEventListener('click', handleWishlistClick);
        });
        
        // Add to Cart buttons
        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', handleAddToCartClick);
        });
        
        // Select Model buttons
        document.querySelectorAll('.select-model-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const productId = this.dataset.productId;
                window.location.href = 'productdetails.php?id=' + productId;
            });
        });
    }
    
    function setupPaginationHandlers() {
        document.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (this.classList.contains('disabled') || this.classList.contains('active')) {
                    return;
                }
                
                const page = this.dataset.page;
                if (page) {
                    loadProducts(page);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        });
    }
    
    function handleWishlistClick(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const btn = e.currentTarget;
        const productId = btn.dataset.productId;
        const heartIcon = btn.querySelector('i');
        
        if (!state.userId) {
            if (confirm('Please login to add items to wishlist.\n\nDo you want to login?')) {
                window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
            }
            return;
        }
        
        const isActive = btn.classList.contains('active');
        
        if (isActive) {
            fetch('ajax/remove_wishlist.php?product_id=' + productId)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        btn.classList.remove('active');
                        heartIcon.className = 'far fa-heart';
                        showToast('Removed from wishlist', 'success');
                    } else {
                        showToast(data.message || 'Failed to remove from wishlist', 'error');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    showToast('Failed to remove from wishlist. Please check your connection.', 'error');
                });
        } else {
            const formData = new FormData();
            formData.append('product_id', productId);
            
            fetch('ajax/add_to_wishlist.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    btn.classList.add('active');
                    heartIcon.className = 'fas fa-heart';
                    showToast('Added to wishlist!', 'success');
                } else {
                    showToast(data.message || 'Failed to add to wishlist', 'error');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                showToast('Failed to add to wishlist. Please check your connection.', 'error');
            });
        }
    }
    
    function handleAddToCartClick(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const btn = e.currentTarget;
        const productId = btn.dataset.productId;
        
        if (!state.userId) {
            if (confirm('Please login to add items to cart.\n\nDo you want to login?')) {
                window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
            }
            return;
        }
        
        if (btn.classList.contains('added')) {
            removeFromCart(productId, btn);
        } else {
            addToCart(productId, btn);
        }
    }
    
    function addToCart(productId, button) {
        const originalText = button.innerHTML;
        
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch(`ajax/add_to_cart.php?id=${productId}&qty=1`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                button.classList.add('added');
                button.innerHTML = '<i class="fas fa-check"></i> Added';
                button.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                button.style.color = '#fff';
                updateCartCount(data.cart_count);
                showToast(data.message || 'Product added to cart!', 'success');
            } else {
                showToast(data.message || 'Failed to add to cart', 'error');
                button.innerHTML = originalText;
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showToast('Add to cart failed. Please check your connection.', 'error');
            button.innerHTML = originalText;
        })
        .finally(() => {
            button.disabled = false;
        });
    }
    
    function removeFromCart(productId, button) {
        const originalHTML = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        const formData = new FormData();
        formData.append('product_id', productId);
        
        fetch('ajax/remove_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                button.classList.remove('added');
                button.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                button.style.background = '';
                button.style.color = '';
                updateCartCount(data.cart_count);
                showToast(data.message || 'Removed from cart', 'success');
            } else {
                showToast(data.message || 'Failed to remove from cart', 'error');
                button.innerHTML = originalHTML;
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showToast('Failed to remove from cart. Please check your connection.', 'error');
            button.innerHTML = originalHTML;
        })
        .finally(() => {
            button.disabled = false;
        });
    }
    
    function resetFilters() {
        elements.filterForm.reset();
        elements.searchInput.value = '';
        state.currentSearch = '';
        state.currentCategoryId = '';
        
        loadCategoryFilters('all');
        
        const url = new URL(window.location);
        url.searchParams.delete('cat');
        window.history.pushState({}, '', url);
        
        updatePageTitle('all');
        
        if (state.isMobileView) {
            closeFilterSidebar();
        }
        
        loadProducts(1);
    }
    
    function toggleFilterSidebar() {
        if (elements.filterSidebar.classList.contains('active')) {
            closeFilterSidebar();
        } else {
            openFilterSidebar();
        }
    }
    
    function openFilterSidebar() {
        elements.filterSidebar.classList.add('active');
        elements.filterOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeFilterSidebar() {
        elements.filterSidebar.classList.remove('active');
        elements.filterOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    function handlePopState() {
        const urlParams = new URLSearchParams(window.location.search);
        const categoryId = urlParams.get('cat') || '';
        
        if (categoryId && categoryId !== 'all') {
            elements.categoryFilter.value = categoryId;
            loadCategoryFilters(categoryId);
        } else {
            elements.categoryFilter.value = 'all';
            loadCategoryFilters('all');
        }
        
        loadProducts(1);
    }
    
    function showLoading(show) {
        elements.loadingIndicator.style.display = show ? 'block' : 'none';
    }
    
    function showError(message) {
        elements.productGrid.innerHTML = `
            <div class="no-products">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Error</h3>
                <p>${message}</p>
            </div>
        `;
    }
    
    function showToast(message, type = 'info') {
        document.querySelectorAll('.toast-notification').forEach(toast => toast.remove());
        
        const toast = document.createElement('div');
        toast.className = `toast-notification alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
        
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        toast.innerHTML = `
            <i class="fas ${icon} me-2"></i>
            <strong>${type === 'success' ? 'Success!' : 'Error!'}</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 3000);
    }
    
    function updateCartCount(count = null) {
        const cartCountElements = document.querySelectorAll('.cart-count, .action-badge');
        cartCountElements.forEach(el => {
            if (count !== null) {
                el.textContent = count;
            } else {
                const current = parseInt(el.textContent) || 0;
                el.textContent = current + 1;
            }
            el.style.display = 'flex';
            el.classList.add('updated');
            setTimeout(() => el.classList.remove('updated'), 500);
        });
    }
    
    function announceResults(count) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.style.position = 'absolute';
        announcement.style.left = '-9999px';
        announcement.textContent = `Loaded ${count} products`;
        document.body.appendChild(announcement);
        setTimeout(() => announcement.remove(), 1000);
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
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>