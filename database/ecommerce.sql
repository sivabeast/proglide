CREATE DATABASE IF NOT EXISTS protections;
USE protections;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active','blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO admins (username,email,password)
VALUES (
  'admin',
  'admin@protectors.com',
  '$2y$10$Qp1RZlQZx8F9YwZkYF5Kse8n9Q9kW8h7YyY0q0E3Yp2j9JZ6e'
);
-- password = admin123
CREATE TABLE main_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);
INSERT INTO main_categories (name) VALUES
('Protector'),
('Back Case');
CREATE TABLE category_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    main_category_id INT NOT NULL,
    type_name VARCHAR(50) NOT NULL,
    FOREIGN KEY (main_category_id)
        REFERENCES main_categories(id)
        ON DELETE CASCADE
);
-- Protector types
INSERT INTO category_types (main_category_id, type_name) VALUES
(1,'clear'),
(1,'matte'),
(1,'privacy'),
(1,'mirror');

-- Back case types
INSERT INTO category_types (main_category_id, type_name) VALUES
(2,'plastic'),
(2,'hard');
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,

    main_category_id INT NOT NULL,   -- protector / backcase
    category_type_id INT NOT NULL,   -- clear / matte / plastic / hard

    model_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    image VARCHAR(255),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (main_category_id)
        REFERENCES main_categories(id)
        ON DELETE CASCADE,

    FOREIGN KEY (category_type_id)
        REFERENCES category_types(id)
        ON DELETE CASCADE
);
INSERT INTO products (main_category_id, category_type_id, model_name, price, description, image) VALUES
(1, 1, 'iPhone 13 Screen Protector', 19.99, 'Clear screen protector for iPhone 13', 'iphone13_clear.jpg'),
(1, 2, 'Samsung Galaxy S21 Matte Protector', 21.99, 'Matte screen protector for Samsung Galaxy S21', 'galaxy_s21_matte.jpg'),
(2, 5, 'iPhone 13 Plastic Back Case', 15.99, 'Durable plastic back case for iPhone 13', 'iphone13_plastic.jpg'),
(2, 6, 'Samsung Galaxy S21 Hard Back Case', 17.99, 'Hard back case for Samsung Galaxy S21', 'galaxy_s21_hard.jpg');

CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE CASCADE
);
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
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON DELETE CASCADE,

    FOREIGN KEY (product_id)
        REFERENCES products(id)
);
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,

    FOREIGN KEY (user_id)
        REFERENCES users(id),

    FOREIGN KEY (product_id)
        REFERENCES products(id)
);
ALTER TABLE wishlist
ADD UNIQUE KEY unique_wishlist (user_id, product_id);
ALTER TABLE cart
ADD UNIQUE KEY unique_cart (user_id, product_id);

CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE CASCADE
);