/* =========================================
   DATABASE
========================================= */
CREATE DATABASE IF NOT EXISTS proglide;
USE proglide;

/* =========================================
   USERS
========================================= */

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL
);

/* =========================================
   ADMINS
========================================= */
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

/* =========================================
   CATEGORIES (MAIN LEVEL)
   Protector, Back Case, AirPods, Watch
========================================= */
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) UNIQUE NOT NULL,
    image VARCHAR(255),
    icon VARCHAR(100),
    status TINYINT DEFAULT 1,
    show_on_home TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO categories (name, slug) VALUES
('Protector','protector'),
('Back Case','back-case'),
('AirPods','airpods'),
('Smart Watch','smart-watch');

/* =========================================
   MATERIAL TYPES
   ─ Protector → 9H, 10H, 9H+
   ─ Back Case → Plastic, Hard, Silicone
========================================= */
CREATE TABLE material_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    status TINYINT DEFAULT 1,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

INSERT INTO material_types (category_id, name) VALUES
-- Protector materials
(1, '9H Glass'),
(1, '9H+ Glass'),
(1, '10H Glass'),

-- Back Case materials
(2, 'Plastic Case'),
(2, 'Hard Case'),
(2, 'Silicone Case');

/* =========================================
   VARIANT TYPES
   SAME TABLE – DIFFERENT USAGE
   ─ Protector → Clear, Matte, Privacy, Mirror
   ─ Back Case → Anime, Cartoon, Marvel
========================================= */
CREATE TABLE variant_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    status TINYINT DEFAULT 1,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

INSERT INTO variant_types (category_id, name) VALUES
-- Protector variants
(1, 'Clear'),
(1, 'Matte'),
(1, 'Privacy'),
(1, 'Mirror'),

-- Back Case design categories
(2, 'Anime'),
(2, 'Cartoon'),
(2, 'Marvel'),
(2, 'Minimal');

/* =========================================
   BRANDS
========================================= */
CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    status TINYINT DEFAULT 1
);

/* =========================================
   PHONE MODELS
========================================= */
CREATE TABLE phone_models (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,
    model_name VARCHAR(100) NOT NULL,
    status TINYINT DEFAULT 1,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
);

/* =========================================
   PRODUCTS
========================================= */
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,

    category_id INT NOT NULL,          -- Protector / Back Case
    material_type_id INT NOT NULL,     -- 9H / Plastic / Hard
    variant_type_id INT NULL,          -- Clear / Matte / Anime

    model_name VARCHAR(150),           -- Protector name
    design_name VARCHAR(150),          -- Back case design name

    description TEXT,

    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2),

    image1 VARCHAR(255),
    image2 VARCHAR(255),
    image3 VARCHAR(255),

    status TINYINT DEFAULT 1,
    is_popular TINYINT DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (material_type_id) REFERENCES material_types(id),
    FOREIGN KEY (variant_type_id) REFERENCES variant_types(id)
);



/* =========================================
   CART
========================================= */
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    phone_model_id INT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

/* =========================================
   WISHLIST
========================================= */
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    UNIQUE (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

/* =========================================
   ORDERS
========================================= */
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2),
    status ENUM('Pending','Processing','Delivered','Cancelled') DEFAULT 'Pending',
    payment_status ENUM('pending','paid','failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

/* =========================================
   ORDER ITEMS
========================================= */
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    phone_model_id INT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);
