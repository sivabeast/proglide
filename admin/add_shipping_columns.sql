-- Add shipping details columns to orders table
ALTER TABLE orders ADD COLUMN full_name VARCHAR(100) NULL;
ALTER TABLE orders ADD COLUMN phone VARCHAR(20) NULL;
ALTER TABLE orders ADD COLUMN address TEXT NULL;
ALTER TABLE orders ADD COLUMN pincode VARCHAR(10) NULL;
ALTER TABLE orders ADD COLUMN notes TEXT NULL;
