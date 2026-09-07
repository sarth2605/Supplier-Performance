-- =======================================================
-- Supplier Performance Analysis System
-- B.Sc. Computer Science Final Year Project Database Schema
-- Database Management System: MySQL 8.0+ / MariaDB
-- =======================================================

CREATE DATABASE IF NOT EXISTS `supplier_performance_db` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `supplier_performance_db`;

-- -------------------------------------------------------
-- Drop existing tables in reverse foreign key order
-- -------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `complaints`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `performance`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `system_settings`;

-- -------------------------------------------------------
-- 1. Table: users
-- Roles: Admin, Manager, Analyst
-- -------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('Admin', 'Manager', 'Analyst') DEFAULT 'Analyst',
    `department` VARCHAR(100) DEFAULT 'Procurement',
    `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- 2. Table: suppliers
-- Core supplier directory
-- -------------------------------------------------------
CREATE TABLE `suppliers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `supplier_code` VARCHAR(30) NOT NULL UNIQUE,
    `supplier_name` VARCHAR(150) NOT NULL,
    `company_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(120) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `address` TEXT NOT NULL,
    `city` VARCHAR(80) NOT NULL,
    `state` VARCHAR(80) NOT NULL,
    `country` VARCHAR(80) NOT NULL DEFAULT 'India',
    `category` ENUM('Raw Materials', 'Electronics', 'Packaging', 'Logistics', 'IT Services', 'Machinery', 'Chemicals', 'Other') NOT NULL,
    `products_supplied` TEXT,
    `registration_number` VARCHAR(60) NOT NULL,
    `tax_id` VARCHAR(60) DEFAULT NULL,
    `contract_start_date` DATE NOT NULL,
    `contract_end_date` DATE NOT NULL,
    `status` ENUM('Active', 'Under Review', 'Inactive') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- 3. Table: performance
-- Historical & current performance evaluations
-- -------------------------------------------------------
CREATE TABLE `performance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `supplier_id` INT NOT NULL,
    `evaluation_date` DATE NOT NULL,
    `evaluator_id` INT DEFAULT NULL,
    `quality_score` DECIMAL(5, 2) NOT NULL COMMENT 'Weight 30%',
    `delivery_score` DECIMAL(5, 2) NOT NULL COMMENT 'Weight 25%',
    `cost_score` DECIMAL(5, 2) NOT NULL COMMENT 'Weight 20%',
    `reliability_score` DECIMAL(5, 2) NOT NULL COMMENT 'Weight 15%',
    `service_score` DECIMAL(5, 2) NOT NULL COMMENT 'Weight 10%',
    `defect_rate` DECIMAL(5, 2) NOT NULL DEFAULT 0.00 COMMENT 'Percentage %',
    `on_time_delivery_rate` DECIMAL(5, 2) NOT NULL DEFAULT 100.00 COMMENT 'Percentage %',
    `overall_score` DECIMAL(5, 2) NOT NULL,
    `rating` ENUM('Excellent', 'Good', 'Average', 'Poor', 'Critical') NOT NULL,
    `strengths` TEXT DEFAULT NULL,
    `weaknesses` TEXT DEFAULT NULL,
    `recommendation` TEXT DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`evaluator_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_supplier_eval` (`supplier_id`, `evaluation_date`),
    INDEX `idx_rating` (`rating`)
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- 4. Table: products
-- Products or components supplied by suppliers
-- -------------------------------------------------------
CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `supplier_id` INT NOT NULL,
    `product_name` VARCHAR(150) NOT NULL,
    `product_code` VARCHAR(50) DEFAULT NULL,
    `category` VARCHAR(100) NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `currency` VARCHAR(10) DEFAULT 'INR',
    `quantity_in_stock` INT DEFAULT 0,
    `lead_time_days` INT DEFAULT 7,
    `status` ENUM('Available', 'Out of Stock', 'Discontinued') DEFAULT 'Available',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE CASCADE,
    INDEX `idx_product_supplier` (`supplier_id`)
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- 5. Table: orders
-- Purchase and fulfillment orders tracking
-- -------------------------------------------------------
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `supplier_id` INT NOT NULL,
    `order_number` VARCHAR(50) NOT NULL UNIQUE,
    `order_date` DATE NOT NULL,
    `expected_date` DATE NOT NULL,
    `actual_date` DATE DEFAULT NULL,
    `quantity` INT NOT NULL,
    `total_amount` DECIMAL(12, 2) NOT NULL,
    `status` ENUM('Pending', 'In Transit', 'Delivered On-Time', 'Delivered Late', 'Cancelled') DEFAULT 'Pending',
    `defect_count` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE CASCADE,
    INDEX `idx_order_supplier` (`supplier_id`),
    INDEX `idx_order_status` (`status`)
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- 6. Table: complaints
-- Quality and operational complaints log
-- -------------------------------------------------------
CREATE TABLE `complaints` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `supplier_id` INT NOT NULL,
    `complaint_type` ENUM('Quality Defect', 'Delayed Shipment', 'Pricing Discrepancy', 'Packaging Damage', 'Customer Service', 'Other') NOT NULL,
    `severity` ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    `description` TEXT NOT NULL,
    `date` DATE NOT NULL,
    `status` ENUM('Open', 'In Investigation', 'Resolved', 'Closed') DEFAULT 'Open',
    `resolution` TEXT DEFAULT NULL,
    `resolved_at` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE CASCADE,
    INDEX `idx_complaint_supplier` (`supplier_id`)
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- 7. Table: notifications
-- System alerts and performance warning triggers
-- -------------------------------------------------------
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `supplier_id` INT DEFAULT NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('Critical', 'Warning', 'Info', 'Success') DEFAULT 'Info',
    `status` ENUM('Unread', 'Read') DEFAULT 'Unread',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL,
    INDEX `idx_notif_status` (`status`)
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- 8. Table: system_settings
-- Configurable formula weights and scoring tiers
-- -------------------------------------------------------
CREATE TABLE `system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(60) NOT NULL UNIQUE,
    `setting_value` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =======================================================
-- SEED DATA INITIALIZATION
-- =======================================================

-- Users
INSERT INTO `users` (`name`, `email`, `password`, `role`, `department`, `status`) VALUES
('Admin User', 'admin@supplierflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Executive Operations', 'Active'),
('Rajesh Kumar', 'rajesh.manager@supplierflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager', 'Procurement & Logistics', 'Active'),
('Priya Sharma', 'priya.analyst@supplierflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Analyst', 'Quality Assurance', 'Active');

-- Settings (Default weights matching specification)
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('weight_quality', '0.30', 'Weight for Quality Score (30%)'),
('weight_delivery', '0.25', 'Weight for Delivery Score (25%)'),
('weight_cost', '0.20', 'Weight for Cost Score (20%)'),
('weight_reliability', '0.15', 'Weight for Reliability Score (15%)'),
('weight_service', '0.10', 'Weight for Service Score (10%)'),
('tier_excellent', '90', 'Score threshold for Excellent rating'),
('tier_good', '80', 'Score threshold for Good rating'),
('tier_average', '70', 'Score threshold for Average rating'),
('tier_poor', '60', 'Score threshold for Poor rating');

-- Sample Suppliers
INSERT INTO `suppliers` (`supplier_code`, `supplier_name`, `company_name`, `email`, `phone`, `address`, `city`, `state`, `country`, `category`, `products_supplied`, `registration_number`, `contract_start_date`, `contract_end_date`, `status`) VALUES
('SUP-101', 'Apex Microtech Ltd', 'Apex Microtech Solutions Pvt Ltd', 'contact@apexmicro.com', '+91 98234 56789', 'Plot 42, Electronic City Phase 1', 'Bengaluru', 'Karnataka', 'India', 'Electronics', 'Semiconductors, Microcontrollers, PCB Assemblies', 'REG-KA-2021-9982', '2023-01-15', '2025-01-14', 'Active'),
('SUP-102', 'Tata Steel & Alloy Works', 'Tata Steel Industrial Division', 'sales@tatasteelalloy.com', '+91 94123 45678', 'Industrial Growth Center, Jamshedpur', 'Jamshedpur', 'Jharkhand', 'India', 'Raw Materials', 'Hot Rolled Coils, Stainless Steel Tubes, Billets', 'REG-JH-2019-4501', '2022-06-01', '2024-12-31', 'Active'),
('SUP-103', 'Speedline Logistics Hub', 'Speedline Freight & Cargo Express', 'support@speedlinelogistics.com', '+91 98987 65432', 'Warehouse Complex B4, Nhava Sheva', 'Navi Mumbai', 'Maharashtra', 'India', 'Logistics', 'Cold Chain Transport, Heavy Freight, Express Air Cargo', 'REG-MH-2020-7712', '2023-04-01', '2025-03-31', 'Active'),
('SUP-104', 'EcoPack Solutions', 'EcoPack Biodegradable Containers LLP', 'info@ecopack.co.in', '+91 97654 32109', 'Sector 18, Udyog Vihar', 'Gurugram', 'Haryana', 'India', 'Packaging', 'Corrugated Boxes, Bubble Wrap, Biodegradable Tape', 'REG-HR-2022-3341', '2023-08-10', '2024-09-15', 'Active'),
('SUP-105', 'Nova Precision Tools', 'Nova Heavy Engineering & Tools Ltd', 'orders@novatools.com', '+91 93210 98765', 'GIDC Industrial Estate, Makarpura', 'Vadodara', 'Gujarat', 'India', 'Machinery', 'CNC Cutters, Lathe Toolbits, Hydraulic Press Valves', 'REG-GJ-2020-1190', '2022-11-01', '2024-10-31', 'Active'),
('SUP-106', 'Zenith Cloud & IT Systems', 'Zenith Information Technologies Ltd', 'enterprise@zenithit.com', '+91 91234 56780', 'Infopark Campus, Kakkanad', 'Kochi', 'Kerala', 'India', 'IT Services', 'ERP Integration, Cloud Storage, EDI Gateways', 'REG-KL-2021-8823', '2023-02-01', '2026-01-31', 'Active'),
('SUP-107', 'Vortex Chemical Refineries', 'Vortex Specialty Polymers Pvt Ltd', 'sales@vortexchem.com', '+91 98450 12345', 'SIPCOT Industrial Park', 'Ranipet', 'Tamil Nadu', 'India', 'Chemicals', 'Industrial Adhesives, Resin Pellets, Degreasing Solvents', 'REG-TN-2018-0912', '2021-05-15', '2024-05-14', 'Under Review'),
('SUP-108', 'RapidFast Courier Co', 'RapidFast Express Courier Services', 'dispatch@rapidfast.in', '+91 99001 22334', 'Transport Nagar, Ring Road', 'Indore', 'Madhya Pradesh', 'India', 'Logistics', 'Last-mile Parcel Delivery, Courier Services', 'REG-MP-2022-6789', '2023-09-01', '2024-08-31', 'Inactive');

-- Sample Performance Evaluations
INSERT INTO `performance` (`supplier_id`, `evaluation_date`, `evaluator_id`, `quality_score`, `delivery_score`, `cost_score`, `reliability_score`, `service_score`, `defect_rate`, `on_time_delivery_rate`, `overall_score`, `rating`, `strengths`, `weaknesses`, `recommendation`, `remarks`) VALUES
(1, '2024-05-15', 2, 94.00, 92.00, 88.00, 95.00, 90.00, 0.80, 98.50, 92.05, 'Excellent', 'Consistently zero-defect microcontrollers, state of the art automated testing', 'Slightly higher lead time for custom batches', 'Preferred primary vendor for all electronics components. Consider renewing multi-year contract.', 'Outstanding performance across all quarters.'),
(2, '2024-05-18', 2, 88.00, 85.00, 92.00, 90.00, 84.00, 1.40, 93.00, 87.95, 'Good', 'Competitive raw metal pricing, high bulk volume supply capability', 'Minor surface oxidation noted on one batch', 'Solid reliable tier-1 raw materials supplier.', 'Maintains high compliance standard.'),
(3, '2024-05-20', 3, 82.00, 96.00, 78.00, 88.00, 86.00, 0.50, 99.00, 86.00, 'Good', 'Superb dispatch speed and real-time GPS tracking', 'Fuel surcharge negotiations are rigid', 'Best logistics provider for urgent and perishable shipments.', 'Delivery metrics exceeded target.'),
(4, '2024-05-22', 3, 76.00, 80.00, 85.00, 74.00, 82.00, 3.20, 88.00, 79.10, 'Average', 'Eco-friendly sustainable materials, budget-friendly', 'Inconsistent corrugated cardboard thickness in monsoon', 'Request supplier to improve humidity protection testing in packaging.', 'Quality audit recommended in next quarter.'),
(5, '2024-05-25', 2, 91.00, 86.00, 82.00, 89.00, 85.00, 1.10, 94.00, 87.05, 'Good', 'Extremely durable tooling, customized CNC toolbits', 'Lead times extended during peak industrial demands', 'Maintain good standing. Place advance tool orders.', 'Reliable tooling partner.'),
(6, '2024-05-28', 1, 95.00, 94.00, 84.00, 92.00, 96.00, 0.20, 99.50, 92.20, 'Excellent', '99.99% EDI uptime, responsive support team within 15 minutes', 'Consultancy rates for bespoke add-ons are premium', 'Top rated IT vendor. Contract should be expanded to international hubs.', 'Remarkable system reliability.'),
(7, '2024-05-30', 3, 62.00, 68.00, 72.00, 58.00, 60.00, 6.80, 74.00, 64.60, 'Poor', 'Low upfront unit costs for bulk chemicals', 'High impurity rate in batch #204, delayed shipments twice', 'Issue formal quality warning notice. Suspend future purchase orders pending audit.', 'High risk of production bottlenecks.'),
(8, '2024-04-10', 2, 54.00, 50.00, 65.00, 48.00, 52.00, 8.50, 62.00, 53.60, 'Critical', 'Low base cost per parcel', 'Frequent lost shipments, delayed deliveries, poor customer support desk', 'Decommission supplier. Transition volume to Speedline Logistics.', 'Contract marked for termination.');

-- Sample Notifications
INSERT INTO `notifications` (`user_id`, `supplier_id`, `title`, `message`, `type`, `status`) VALUES
(1, 7, 'High Defect Rate Alert', 'Vortex Chemical Refineries reported a 6.8% defect rate in May evaluation (Threshold: 3.0%).', 'Critical', 'Unread'),
(1, 8, 'Critical Performance Warning', 'RapidFast Courier Co overall score dropped to 53.60% (Below 60% Critical threshold).', 'Critical', 'Unread'),
(2, 2, 'Contract Expiry Notice', 'Tata Steel & Alloy Works contract ends in less than 60 days (2024-12-31).', 'Warning', 'Unread'),
(3, 4, 'Quality Deviation Flagged', 'EcoPack Solutions average score (79.10%) dropped below target 80.00%.', 'Warning', 'Read'),
(1, 1, 'Top Performance Commendation', 'Apex Microtech Ltd achieved 92.05% Excellent rating for consecutive evaluations.', 'Success', 'Read');
