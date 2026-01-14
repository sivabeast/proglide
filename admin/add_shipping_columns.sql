-- Add shipping details columns to orders table
ALTER TABLE orders ADD COLUMN full_name VARCHAR(100) NULL;
ALTER TABLE orders ADD COLUMN phone VARCHAR(20) NULL;
ALTER TABLE orders ADD COLUMN address TEXT NULL;
ALTER TABLE orders ADD COLUMN pincode VARCHAR(10) NULL;
ALTER TABLE orders ADD COLUMN notes TEXT NULL;




CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    slug VARCHAR(100),
    icon VARCHAR(100),
    image VARCHAR(255),
    status TINYINT DEFAULT 1,
    show_on_home TINYINT DEFAULT 1
);

ALTER TABLE products
ADD status TINYINT(1) DEFAULT 1,
ADD is_popular TINYINT(1) DEFAULT 0;
