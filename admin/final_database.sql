/* ================================
   DATABASE
================================ */
CREATE DATABASE IF NOT EXISTS fin;
USE fin;

/* ================================
   USERS
================================ */
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active','blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE users 
ADD phone VARCHAR(20),
ADD address TEXT;

/* ================================
   ADMINS
================================ */
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

/* ================================
   MAIN CATEGORIES
================================ */
CREATE TABLE main_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

INSERT INTO main_categories (name) VALUES
('Protector'),
('Back Case');

/* ================================
   CATEGORY TYPES
================================ */
CREATE TABLE category_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    main_category_id INT NOT NULL,
    type_name VARCHAR(50) NOT NULL,
    FOREIGN KEY (main_category_id)
        REFERENCES main_categories(id)
        ON DELETE CASCADE
);

/* Protector types */
INSERT INTO category_types (main_category_id, type_name) VALUES
(1,'clear'),
(1,'matte'),
(1,'privacy'),
(1,'mirror');

/* Back case types */
INSERT INTO category_types (main_category_id, type_name) VALUES
(2,'plastic'),
(2,'hard');

/* ================================
   BRANDS
================================ */
CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

ALTER TABLE brands 
ADD status ENUM('active','hidden') DEFAULT 'active';


INSERT INTO brands (name) VALUES
('Apple'),('Samsung'),('OnePlus'),('Vivo'),('Redmi');

/* ================================
   PHONE MODELS
================================ */
CREATE TABLE phone_models (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,
    model_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (brand_id)
        REFERENCES brands(id)
        ON DELETE CASCADE
);

ALTER TABLE phone_models 
ADD status ENUM('active','hidden') DEFAULT 'active';

/* ================================
   DESIGN CATEGORIES (Back Case)
================================ */
CREATE TABLE design_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

/* ================================
   PRODUCTS (MASTER)
================================ */
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,

    main_category_id INT NOT NULL,      -- Protector / Back Case
    category_type_id INT NOT NULL,      -- Matte / Hard / Plastic

    model_name VARCHAR(150) NULL,   -- Protector name
    design_name VARCHAR(150),           -- Back Case design

    design_category_id INT NULL,
    original_price DECIMAL(10,2),
    price DECIMAL(10,2) NOT NULL,

    description TEXT,
    image VARCHAR(255),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (main_category_id)
        REFERENCES main_categories(id)
        ON DELETE CASCADE,

    FOREIGN KEY (category_type_id)
        REFERENCES category_types(id)
        ON DELETE CASCADE,

    FOREIGN KEY (design_category_id)
        REFERENCES design_categories(id)
        ON DELETE SET NULL
);

/* ================================
   PRODUCT ↔ PHONE MODELS (MANY-TO-MANY)
================================ */
CREATE TABLE product_models (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    phone_model_id INT NOT NULL,
    extra_price DECIMAL(10,2) DEFAULT 0,

    FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE CASCADE,

    FOREIGN KEY (phone_model_id)
        REFERENCES phone_models(id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_product_model (product_id, phone_model_id)
);

/* ================================
   CART
================================ */
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    phone_model_id INT NULL,
    quantity INT DEFAULT 1,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE CASCADE,

    FOREIGN KEY (phone_model_id)
        REFERENCES phone_models(id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_cart (user_id, product_id, phone_model_id)
);

/* ================================
   ORDERS
================================ */
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2),
    status ENUM('Pending','Processing','Shipped','Delivered','Cancelled')
           DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
);

-- Add shipping details columns to orders table
ALTER TABLE orders ADD COLUMN full_name VARCHAR(100) NULL;
ALTER TABLE orders ADD COLUMN phone VARCHAR(20) NULL;
ALTER TABLE orders ADD COLUMN address TEXT NULL;
ALTER TABLE orders ADD COLUMN pincode VARCHAR(10) NULL;
ALTER TABLE orders ADD COLUMN notes TEXT NULL;



ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50);
ALTER TABLE orders ADD COLUMN payment_status ENUM('pending','paid','failed') DEFAULT 'pending';
ALTER TABLE orders ADD COLUMN payment_id VARCHAR(100) NULL;


ALTER TABLE orders 
ADD payment_proof VARCHAR(255) NULL;
ALTER TABLE orders 
ADD razorpay_order_id VARCHAR(100),
ADD razorpay_payment_id VARCHAR(100),
ADD razorpay_signature VARCHAR(255);



/* ================================
   ORDER ITEMS (VERY IMPORTANT)
================================ */
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    phone_model_id INT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON DELETE CASCADE,

    FOREIGN KEY (product_id)
        REFERENCES products(id),

    FOREIGN KEY (phone_model_id)
        REFERENCES phone_models(id)
);

/* ================================
   WISHLIST
================================ */
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_wishlist (user_id, product_id)
);

/* ================================
   REVIEWS
================================ */
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE CASCADE
);


