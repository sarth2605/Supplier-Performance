-- ==============================================================================
-- Supplier Performance Analysis System (SPAS)
-- Product Transfer & Multi-Tier Inventory Schema Migration
-- ==============================================================================

USE `supplier_performance_db`;

-- 1. Ensure manufacturer_id column exists on products table
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'supplier_performance_db' 
    AND TABLE_NAME = 'products' 
    AND COLUMN_NAME = 'manufacturer_id');

SET @query := IF(@exist = 0, 
    'ALTER TABLE `products` ADD COLUMN `manufacturer_id` INT DEFAULT NULL AFTER `supplier_id`, ADD CONSTRAINT `fk_products_manufacturer` FOREIGN KEY (`manufacturer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;', 
    'SELECT "Column manufacturer_id already exists"');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Create User Inventory Table (Tracks stock per user location)
CREATE TABLE IF NOT EXISTS `user_inventory` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `role` ENUM('manufacturer', 'supplier', 'shopkeeper') NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 0,
    `batch_number` VARCHAR(80) DEFAULT NULL,
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_user_product` (`user_id`, `product_id`),
    INDEX `idx_user_role` (`user_id`, `role`),
    INDEX `idx_product` (`product_id`),
    CONSTRAINT `fk_inventory_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create Product Transfers Table (Complete Supply Chain: Manufacturer -> Supplier -> Shopkeeper)
CREATE TABLE IF NOT EXISTS `product_transfers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transfer_ref` VARCHAR(50) NOT NULL UNIQUE,
    `product_id` INT NOT NULL,
    `sender_id` INT NOT NULL,
    `sender_role` ENUM('manufacturer', 'supplier') NOT NULL,
    `receiver_id` INT NOT NULL,
    `receiver_role` ENUM('supplier', 'shopkeeper') NOT NULL,
    `stage` ENUM('manufacturer_to_supplier', 'supplier_to_shopkeeper') NOT NULL,
    `quantity` INT NOT NULL,
    `batch_number` VARCHAR(80) DEFAULT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('Pending', 'In Transit', 'Received', 'Cancelled', 'Rejected') NOT NULL DEFAULT 'In Transit',
    `transfer_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `received_date` DATETIME DEFAULT NULL,
    `parent_transfer_id` INT DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_sender` (`sender_id`, `sender_role`),
    INDEX `idx_receiver` (`receiver_id`, `receiver_role`),
    INDEX `idx_product` (`product_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_stage` (`stage`),
    CONSTRAINT `fk_transfer_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_transfer_sender` FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_transfer_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_transfer_parent` FOREIGN KEY (`parent_transfer_id`) REFERENCES `product_transfers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Clean up legacy demo accounts with dummy passwords
-- (Any user must register their own account with secure hashed password)
DELETE FROM `users` WHERE `email` IN (
    'admin@example.com',
    'manufacturer@example.com',
    'supplier@example.com',
    'shopkeeper@example.com',
    'manager@example.com',
    'staff@example.com'
);
