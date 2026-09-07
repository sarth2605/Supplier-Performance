-- ==============================================================================
-- Supplier Performance Analysis and Management System (SPAS)
-- Domain: Beauty, Cosmetics, Skincare & Personal Care Supply Chain
-- Database Schema & Master Seed Data with Multi-Stakeholder Portals
-- (Admin, Manufacturer, Supplier, Shopkeeper)
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `supplier_performance_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `supplier_performance_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `performance_scores`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `returns`;
DROP TABLE IF EXISTS `quality_inspections`;
DROP TABLE IF EXISTS `deliveries`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `purchase_orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `suppliers`;
-- Keep foreign key checks disabled during batch import
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. USERS & RBAC TABLE (Admin, Manufacturer, Supplier, Shopkeeper)
-- ------------------------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'manufacturer', 'supplier', 'shopkeeper', 'manager', 'staff') NOT NULL DEFAULT 'shopkeeper',
    `company_name` VARCHAR(150) DEFAULT NULL,
    `shop_name` VARCHAR(150) DEFAULT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `city` VARCHAR(80) DEFAULT 'Mumbai',
    `state` VARCHAR(80) DEFAULT 'Maharashtra',
    `supplier_id` INT DEFAULT NULL,
    `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users will register through their dedicated portals (Admin, Manufacturer, Supplier, Shopkeeper)

-- ------------------------------------------------------------------------------
-- 2. SUPPLIERS TABLE (Cosmetics & Personal Care Partners)
-- ------------------------------------------------------------------------------
CREATE TABLE `suppliers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `supplier_code` VARCHAR(30) NOT NULL UNIQUE,
    `supplier_name` VARCHAR(150) NOT NULL,
    `contact_person` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `email` VARCHAR(120) NOT NULL,
    `address` VARCHAR(255) DEFAULT NULL,
    `city` VARCHAR(80) NOT NULL,
    `state` VARCHAR(80) NOT NULL,
    `category` VARCHAR(60) NOT NULL,
    `supplier_type` VARCHAR(60) DEFAULT 'Manufacturer',
    `contract_start_date` DATE DEFAULT '2025-01-01',
    `payment_terms` VARCHAR(40) DEFAULT 'Net 30',
    `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `suppliers` (`id`, `supplier_code`, `supplier_name`, `contact_person`, `phone`, `email`, `address`, `city`, `state`, `category`, `supplier_type`, `contract_start_date`, `payment_terms`, `status`) VALUES
(1, 'SUP001', 'Glow Beauty Suppliers', 'Rahul Sharma', '9876543210', 'rahul@glowbeauty.in', 'Unit 12, MIDC Industrial Area, Andheri East', 'Mumbai', 'Maharashtra', 'Face Products', 'Manufacturer', '2025-01-01', 'Net 30', 'Active'),
(2, 'SUP002', 'Luxe Skin Laboratories', 'Dr. Ananya Verma', '9822334455', 'ananya@luxeskinlab.com', 'Plot 45, Biotech Pharma Park, Genome Valley', 'Hyderabad', 'Telangana', 'Skincare Products', 'Manufacturer', '2025-02-01', 'Net 30', 'Active'),
(3, 'SUP003', 'Velvet Lip & Color Tech', 'Vikram Malhotra', '9711223344', 'sales@velvetcolor.com', 'Sector 18, Electronic City', 'Gurugram', 'Haryana', 'Lip Products', 'Manufacturer', '2025-01-15', 'Net 15', 'Active'),
(4, 'SUP004', 'Aura Beauty Tools & Accessories', 'Meera Joshi', '9633445566', 'contact@auratools.in', 'Peenya Industrial Area Stage 2', 'Bengaluru', 'Karnataka', 'Beauty Tools & Accessories', 'Importer & Supplier', '2025-03-01', 'Net 45', 'Active'),
(5, 'SUP005', 'Silk & Shine Hair Care Co.', 'Arjun Deshmukh', '9890123456', 'arjun@silkshine.in', 'Hinjewadi Phase 1, MIDC', 'Pune', 'Maharashtra', 'Hair Beauty Products', 'Manufacturer', '2025-01-10', 'Net 30', 'Active'),
(6, 'SUP006', 'Glamour Eyes Cosmetics', 'Neha Kapoor', '9819876543', 'neha@glamoureyes.com', 'Naraina Industrial Area Phase 1', 'New Delhi', 'Delhi', 'Eye Products', 'Contract Manufacturer', '2025-02-15', 'Net 30', 'Active'),
(7, 'SUP007', 'NailPro Aesthetics Ltd', 'Sanjay Rathore', '9722334455', 'sanjay@nailproaesthetics.com', 'GIDC Vatva Industrial Estate', 'Ahmedabad', 'Gujarat', 'Nail Products', 'Manufacturer', '2025-01-20', 'Net 30', 'Active'),
(8, 'SUP008', 'Blossom Botanical Body Care', 'Kavita Sundaram', '9444123456', 'kavita@blossombotanicals.com', 'Guindy Industrial Estate', 'Chennai', 'Tamil Nadu', 'Body Care Products', 'Organic Formulator', '2025-02-01', 'Net 30', 'Active'),
(9, 'SUP009', 'Pure Derma Formulation Labs', 'Dr. Manish Gupta', '9833445566', 'manish@purederma.in', 'Baddi Pharma Corridor', 'Solan', 'Himachal Pradesh', 'Skincare Products', 'R&D & OEM', '2025-03-10', 'Net 60', 'Active'),
(10, 'SUP010', 'Radiant Face Packaging & Blends', 'Sunil Agarwal', '9988776655', 'sunil@radiantface.in', 'Sitapura Industrial Area', 'Jaipur', 'Rajasthan', 'Face Products', 'Manufacturer', '2025-01-05', 'Net 30', 'Active'),
(11, 'SUP011', 'Lash & Brow Precision Works', 'Pooja Nair', '9845123456', 'pooja@lashbrowprecision.com', 'Kakkanad Infopark Zone', 'Kochi', 'Kerala', 'Eye Products', 'Contract Manufacturer', '2025-02-20', 'Net 15', 'Active'),
(12, 'SUP012', 'Herb & Herb Organic Bath Essentials', 'Deepak Ghosh', '9830123456', 'deepak@herborganics.in', 'Sector V, Salt Lake', 'Kolkata', 'West Bengal', 'Body Care Products', 'Organic Formulator', '2025-03-01', 'Net 30', 'Active');

-- ------------------------------------------------------------------------------
-- 3. PRODUCTS TABLE (8 Cosmetics Categories)
-- ------------------------------------------------------------------------------
CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_code` VARCHAR(30) NOT NULL UNIQUE,
    `product_name` VARCHAR(150) NOT NULL,
    `category` VARCHAR(60) NOT NULL,
    `sub_category` VARCHAR(60) NOT NULL,
    `brand` VARCHAR(80) NOT NULL DEFAULT 'BeautyGlow',
    `supplier_id` INT DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `unit` VARCHAR(20) NOT NULL DEFAULT 'pcs',
    `standard_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `wholesale_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `stock_quantity` INT NOT NULL DEFAULT 500,
    `reorder_level` INT NOT NULL DEFAULT 100,
    `min_order_qty` INT NOT NULL DEFAULT 10,
    `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`, `product_code`, `product_name`, `category`, `sub_category`, `brand`, `supplier_id`, `description`, `unit`, `standard_price`, `wholesale_price`, `stock_quantity`, `reorder_level`, `min_order_qty`, `status`) VALUES
-- 1. Face Products
(1, 'PRD-FAC-001', 'Matte Liquid Foundation (30ml)', 'Face Products', 'Foundation', 'BeautyGlow', 1, 'Oil-free 24-hour long stay matte liquid foundation with SPF 15.', 'pcs', 450.00, 380.00, 850, 150, 10, 'Active'),
(2, 'PRD-FAC-002', 'Pore Minimizing Primer Gel', 'Face Products', 'Primer', 'BeautyGlow', 1, 'Silicone-free pore blurring smoothing primer for flawless base application.', 'pcs', 380.00, 310.00, 600, 100, 10, 'Active'),
(3, 'PRD-FAC-003', 'Full Coverage Liquid Concealer', 'Face Products', 'Concealer', 'BeautyGlow', 1, 'Crease-proof waterproof full coverage concealer for dark circles and spots.', 'pcs', 290.00, 230.00, 750, 120, 10, 'Active'),
(4, 'PRD-FAC-004', 'Translucent Loose Setting Powder', 'Face Products', 'Setting Powder', 'RadiantFace', 10, 'Ultra-fine micro-milled oil control translucent baking powder.', 'pcs', 320.00, 260.00, 500, 100, 10, 'Active'),
(5, 'PRD-FAC-005', 'Long-Lasting Matte Setting Spray', 'Face Products', 'Setting Spray', 'RadiantFace', 10, '16-hour lock-in hydration mist setting spray.', 'pcs', 350.00, 280.00, 420, 80, 10, 'Active'),
(6, 'PRD-FAC-006', 'Soft Velvet Powder Blush (Peach)', 'Face Products', 'Blush', 'GlowTech', 1, 'Silky buildable matte pigment blush with vitamin E infusion.', 'pcs', 280.00, 220.00, 620, 100, 10, 'Active'),
(7, 'PRD-FAC-007', 'Shimmer Powder Highlighter (Golden Hour)', 'Face Products', 'Highlighter', 'BeautyGlow', 1, 'Ultra-reflective pearl luminous pressed powder highlighter.', 'pcs', 340.00, 270.00, 480, 80, 10, 'Active'),
(8, 'PRD-FAC-008', 'Hydrating Aloe Vera Face Gel (100g)', 'Face Products', 'Gel', 'PureEssence', 10, '99% pure organic soothing and skin plumping moisture gel.', 'pcs', 220.00, 170.00, 900, 150, 20, 'Active'),

-- 2. Eye Products
(9, 'PRD-EYE-001', '12-Shade Matte & Shimmer Eyeshadow Palette', 'Eye Products', 'Eyeshadow', 'GlamourEyes', 6, 'Highly pigmented blendable nude and warm metallic eyeshadow palette.', 'pcs', 650.00, 520.00, 400, 80, 10, 'Active'),
(10, 'PRD-EYE-002', 'Waterproof Felt Tip Eyeliner (Jet Black)', 'Eye Products', 'Eyeliner', 'GlamourEyes', 6, 'Precision 0.1mm micro-tip smudge-proof 24hr liquid eyeliner pen.', 'pcs', 240.00, 190.00, 1200, 200, 20, 'Active'),
(11, 'PRD-EYE-003', '24Hr Smudge-Proof Black Kajal Pencil', 'Eye Products', 'Kajal', 'GlamourEyes', 6, 'Infused with chamomile and almond oil for intense single-stroke black finish.', 'pcs', 180.00, 140.00, 1500, 300, 25, 'Active'),
(12, 'PRD-EYE-004', 'Volumizing & Curling Mascara', 'Eye Products', 'Mascara', 'GlamourEyes', 6, 'Hourglass wand fiber mascara for 3X dramatic lash volume.', 'pcs', 320.00, 250.00, 800, 150, 15, 'Active'),
(13, 'PRD-EYE-005', 'Micro Precision Eyebrow Definer Pencil', 'Eye Products', 'Eyebrow', 'LashBrow', 11, 'Retractable ultra-fine eyebrow pencil with built-in blending spoolie.', 'pcs', 210.00, 160.00, 650, 100, 15, 'Active'),
(14, 'PRD-EYE-006', '3D Silk Magnetic False Eyelashes Kit', 'Eye Products', 'False Eyelashes', 'LashBrow', 11, 'Reusable cruelty-free 3D faux mink lashes with magnetic liquid liner.', 'sets', 420.00, 330.00, 350, 60, 10, 'Active'),

-- 3. Lip Products
(15, 'PRD-LIP-001', 'Transfer-Proof Matte Liquid Lipstick', 'Lip Products', 'Lipstick', 'VelvetLip', 3, 'Weightless non-drying 12-hour transfer-resistant liquid matte lipstick.', 'pcs', 350.00, 280.00, 1100, 200, 20, 'Active'),
(16, 'PRD-LIP-002', 'Creamy Waterproof Lip Liner Pencil', 'Lip Products', 'Lip Liner', 'VelvetLip', 3, 'Rich contouring glide-on lip liner pencil in universal nude.', 'pcs', 190.00, 150.00, 950, 150, 20, 'Active'),
(17, 'PRD-LIP-003', 'High-Shine Hyaluronic Plumping Lip Gloss', 'Lip Products', 'Lip Gloss', 'VelvetLip', 3, 'Non-sticky glass shine lip gloss with peptide volumizing actives.', 'pcs', 280.00, 220.00, 700, 120, 15, 'Active'),
(18, 'PRD-LIP-004', 'Nourishing Tinted Berry Lip Balm (15g)', 'Lip Products', 'Lip Balm', 'VelvetLip', 3, 'Shea butter and SPF 20 enriched lip repair conditioning balm.', 'pcs', 160.00, 120.00, 1300, 250, 25, 'Active'),
(19, 'PRD-LIP-005', 'Hydrating Overnight Lip Sleeping Mask', 'Lip Products', 'Lip Mask', 'VelvetLip', 3, 'Berry antioxidant exfoliating night lip plumping balm.', 'pcs', 260.00, 200.00, 450, 80, 10, 'Active'),

-- 4. Skincare Products
(20, 'PRD-SKN-001', '10% Vitamin C Radiance Face Serum (30ml)', 'Skincare Products', 'Serums', 'LuxeSkin', 2, 'Stabilized ethyl ascorbic acid serum with ferulic acid for glow and spot correction.', 'pcs', 590.00, 480.00, 650, 120, 10, 'Active'),
(21, 'PRD-SKN-002', '2% Hyaluronic Acid Deep Hydration Serum', 'Skincare Products', 'Serums', 'LuxeSkin', 2, 'Multi-molecular weight hyaluronic serum with Vitamin B5 barrier booster.', 'pcs', 520.00, 420.00, 580, 100, 10, 'Active'),
(22, 'PRD-SKN-003', 'SPF 50+ PA++++ Ultra Light Gel Sunscreen', 'Skincare Products', 'Sunscreen', 'LuxeSkin', 2, 'Zero white-cast hybrid broad spectrum water-resistant sunscreen.', 'pcs', 440.00, 350.00, 950, 200, 15, 'Active'),
(23, 'PRD-SKN-004', 'Gentle Foaming Ceramide Cleanser (150ml)', 'Skincare Products', 'Cleansers', 'PureDerma', 9, 'Non-stripping hydrating barrier defense pH 5.5 daily face wash.', 'pcs', 340.00, 270.00, 780, 150, 15, 'Active'),
(24, 'PRD-SKN-005', 'Oil-Free Barrier Repair Gel Moisturizer', 'Skincare Products', 'Moisturizers', 'LuxeSkin', 2, 'Cica & squalane infused 72-hour lightweight moisture gel cream.', 'pcs', 480.00, 390.00, 620, 100, 10, 'Active'),
(25, 'PRD-SKN-006', 'Activated Charcoal Detox Clay Mask (100g)', 'Skincare Products', 'Masks', 'PureDerma', 9, 'Deep pore purifying bentonite and French green clay detox mask.', 'pcs', 360.00, 290.00, 410, 80, 10, 'Active'),
(26, 'PRD-SKN-007', 'Hydrating Rosewater & Niacinamide Toner', 'Skincare Products', 'Toner', 'PureDerma', 9, 'Alcohol-free pore tightening balancing mist toner.', 'pcs', 290.00, 230.00, 720, 120, 15, 'Active'),

-- 5. Nail Products
(27, 'PRD-NAL-001', 'Long-Wear Quick Dry Nail Lacquer (10ml)', 'Nail Products', 'Nail Polish', 'NailPro', 7, 'Chip-resistant high-gloss salon grade nail color.', 'pcs', 150.00, 110.00, 1600, 300, 25, 'Active'),
(28, 'PRD-NAL-002', 'UV LED Gel Top & Base Coat Combo', 'Nail Products', 'Nail Polish', 'NailPro', 7, '21-day chip-proof diamond shine gel manicure sealing system.', 'sets', 380.00, 300.00, 450, 80, 10, 'Active'),
(29, 'PRD-NAL-003', 'Acetone-Free Vitamin E Nail Polish Remover', 'Nail Products', 'Nail Care', 'NailPro', 7, 'Gentle strengthening conditioning nail remover fluid (120ml).', 'pcs', 120.00, 90.00, 1400, 250, 25, 'Active'),

-- 6. Hair Beauty Products
(30, 'PRD-HAR-001', 'Keratin Repair & Shine Hair Serum (50ml)', 'Hair Beauty Products', 'Hair Serum', 'SilkShine', 5, 'Argan oil infused heat protection anti-frizz gloss serum.', 'pcs', 420.00, 330.00, 550, 100, 10, 'Active'),
(31, 'PRD-HAR-002', 'Sulfate-Free Biotin & Rice Water Shampoo', 'Hair Beauty Products', 'Shampoo', 'SilkShine', 5, 'Anti-hair fall follicle strengthening daily shampoo (300ml).', 'pcs', 380.00, 300.00, 820, 150, 15, 'Active'),
(32, 'PRD-HAR-003', 'Deep Nourishing Moroccan Argan Hair Mask', 'Hair Beauty Products', 'Hair Mask', 'SilkShine', 5, 'Intensive spa reconstruction mask for dry and chemically treated hair.', 'pcs', 490.00, 390.00, 390, 70, 10, 'Active'),

-- 7. Body Care Products
(33, 'PRD-BDY-001', 'Hydrating Shea Butter Body Lotion (400ml)', 'Body Care Products', 'Body Lotion', 'BlossomCare', 8, '48-hour deep moisture cocoa and shea butter rich body moisturizer.', 'pcs', 360.00, 280.00, 750, 150, 15, 'Active'),
(34, 'PRD-BDY-002', 'Exfoliating Arabica Coffee Body Scrub', 'Body Care Products', 'Body Scrub', 'HerbOrganics', 12, 'Coconut oil infused cellulite reducing polisher scrub (200g).', 'pcs', 390.00, 310.00, 480, 90, 10, 'Active'),
(35, 'PRD-BDY-003', 'Refreshing Citrus Burst Foaming Body Wash', 'Body Care Products', 'Body Wash', 'BlossomCare', 8, 'Gentle microbiome-safe nourishing shower gel (300ml).', 'pcs', 290.00, 220.00, 900, 180, 20, 'Active'),

-- 8. Beauty Tools & Accessories
(36, 'PRD-TOL-001', '10-Piece Professional Vegan Makeup Brush Set', 'Beauty Tools & Accessories', 'Brushes', 'AuraTools', 4, 'Ultra-soft synthetic bristles with luxury marble ergonomic handles.', 'sets', 890.00, 720.00, 320, 50, 5, 'Active'),
(37, 'PRD-TOL-002', 'Microfiber Velvet Beauty Sponge Blender', 'Beauty Tools & Accessories', 'Sponges', 'AuraTools', 4, 'Dual-finish edgeless makeup blending sponge.', 'pcs', 180.00, 130.00, 1500, 300, 25, 'Active'),
(38, 'PRD-TOL-003', 'Natural Rose Quartz Facial Roller & Gua Sha Set', 'Beauty Tools & Accessories', 'Facial Tools', 'AuraTools', 4, 'Authentic gemstone lymphatic drainage and sculpting massage tool set.', 'sets', 650.00, 510.00, 280, 50, 5, 'Active'),
(39, 'PRD-TOL-004', 'Ergonomic Precision Eyelash Curler with Refills', 'Beauty Tools & Accessories', 'Eye Tools', 'AuraTools', 4, 'Spring-loaded silicone pad clamp for long-lasting lash lift.', 'pcs', 220.00, 160.00, 600, 100, 15, 'Active');

-- ------------------------------------------------------------------------------
-- 4. PURCHASE ORDERS TABLE (Supports B2B Supplier & Shopkeeper Orders)
-- ------------------------------------------------------------------------------
CREATE TABLE `purchase_orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `po_number` VARCHAR(30) NOT NULL UNIQUE,
    `user_id` INT DEFAULT NULL, -- Entity/User who placed the order (Admin, Manufacturer, Shopkeeper)
    `supplier_id` INT NOT NULL,
    `order_type` ENUM('supplier_procurement', 'wholesale_shopkeeper') NOT NULL DEFAULT 'supplier_procurement',
    `order_date` DATE NOT NULL,
    `expected_date` DATE NOT NULL,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('Pending', 'Approved', 'Dispatched', 'Partially Delivered', 'Delivered', 'Cancelled') NOT NULL DEFAULT 'Pending',
    `shipping_address` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `purchase_orders` (`id`, `po_number`, `user_id`, `supplier_id`, `order_type`, `order_date`, `expected_date`, `total_amount`, `status`) VALUES
(1, 'ORD1001', 2, 1, 'supplier_procurement', '2026-08-01', '2026-08-06', 225000.00, 'Delivered'),
(2, 'ORD1002', 2, 2, 'supplier_procurement', '2026-08-03', '2026-08-09', 295000.00, 'Delivered'),
(3, 'ORD1003', 2, 3, 'supplier_procurement', '2026-08-05', '2026-08-11', 175000.00, 'Delivered'),
(4, 'ORD1004', 2, 4, 'supplier_procurement', '2026-08-07', '2026-08-14', 178000.00, 'Delivered'),
(5, 'ORD1005', 2, 5, 'supplier_procurement', '2026-08-10', '2026-08-16', 210000.00, 'Delivered'),
(6, 'ORD1006', 2, 6, 'supplier_procurement', '2026-08-12', '2026-08-18', 195000.00, 'Delivered'),
(7, 'ORD1007', 2, 7, 'supplier_procurement', '2026-08-15', '2026-08-20', 120000.00, 'Delivered'),
(8, 'ORD1008', 2, 8, 'supplier_procurement', '2026-08-18', '2026-08-24', 180000.00, 'Delivered'),
(9, 'ORD1009', 2, 9, 'supplier_procurement', '2026-08-20', '2026-08-27', 204000.00, 'Delivered'),
(10, 'ORD1010', 2, 10, 'supplier_procurement', '2026-08-22', '2026-08-28', 160000.00, 'Delivered'),
(11, 'ORD1011', 2, 1, 'supplier_procurement', '2026-08-25', '2026-08-30', 190000.00, 'Delivered'),
(12, 'ORD1012', 2, 2, 'supplier_procurement', '2026-08-28', '2026-09-03', 260000.00, 'Delivered'),
(13, 'ORD1013', 2, 3, 'supplier_procurement', '2026-08-29', '2026-09-04', 140000.00, 'Delivered'),
(14, 'ORD1014', 2, 4, 'supplier_procurement', '2026-08-30', '2026-09-06', 130000.00, 'Delivered'),
(15, 'ORD1015', 2, 6, 'supplier_procurement', '2026-08-31', '2026-09-07', 160000.00, 'Approved'),
(16, 'ORD1016', 4, 1, 'wholesale_shopkeeper', '2026-09-01', '2026-09-06', 45000.00, 'Approved'),
(17, 'ORD1017', 4, 2, 'wholesale_shopkeeper', '2026-09-01', '2026-09-07', 59000.00, 'Pending');

-- ------------------------------------------------------------------------------
-- 5. ORDER ITEMS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `purchase_order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `total_price` DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `order_items` (`id`, `purchase_order_id`, `product_id`, `quantity`, `unit_price`, `total_price`) VALUES
(1, 1, 1, 500, 450.00, 225000.00),
(2, 2, 20, 500, 590.00, 295000.00),
(3, 3, 15, 500, 350.00, 175000.00),
(4, 4, 36, 200, 890.00, 178000.00),
(5, 5, 30, 500, 420.00, 210000.00),
(6, 6, 9, 300, 650.00, 195000.00),
(7, 7, 27, 800, 150.00, 120000.00),
(8, 8, 33, 500, 360.00, 180000.00),
(9, 9, 23, 600, 340.00, 204000.00),
(10, 10, 4, 500, 320.00, 160000.00),
(11, 11, 2, 500, 380.00, 190000.00),
(12, 12, 21, 500, 520.00, 260000.00),
(13, 13, 17, 500, 280.00, 140000.00),
(14, 14, 38, 200, 650.00, 130000.00),
(15, 15, 10, 500, 240.00, 120000.00),
(16, 16, 1, 100, 450.00, 45000.00),
(17, 17, 20, 100, 590.00, 59000.00);

-- ------------------------------------------------------------------------------
-- 6. DELIVERIES TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `deliveries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `purchase_order_id` INT NOT NULL,
    `delivery_date` DATE NOT NULL,
    `quantity_received` INT NOT NULL,
    `delivery_status` ENUM('On Time', 'Delayed', 'Early', 'Partial') NOT NULL DEFAULT 'On Time',
    `delay_days` INT NOT NULL DEFAULT 0,
    `remarks` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `deliveries` (`id`, `purchase_order_id`, `delivery_date`, `quantity_received`, `delivery_status`, `delay_days`, `remarks`) VALUES
(1, 1, '2026-08-06', 490, 'On Time', 0, 'Delivered exactly on schedule via BlueDart cargo; 1 carton corner dented.'),
(2, 2, '2026-08-08', 500, 'Early', 0, 'Arrived 1 day ahead of schedule; temperature-controlled packing intact.'),
(3, 3, '2026-08-11', 495, 'On Time', 0, 'Delivered on time in tamper-evident sealed shrink wrap.'),
(4, 4, '2026-08-17', 200, 'Delayed', 3, 'Delayed by 3 days due to regional logistics hub backlog.'),
(5, 5, '2026-08-16', 500, 'On Time', 0, 'Punctual delivery via Safexpress container.'),
(6, 6, '2026-08-20', 300, 'Delayed', 2, 'Delayed 2 days due to weather transit disruptions.'),
(7, 7, '2026-08-20', 800, 'On Time', 0, 'Full shipment delivered on schedule.'),
(8, 8, '2026-08-24', 500, 'On Time', 0, 'Organic certified shipment received with batch COA certificate.'),
(9, 9, '2026-08-27', 600, 'On Time', 0, 'Delivered on schedule with complete quality documentation.'),
(10, 10, '2026-08-30', 500, 'Delayed', 2, 'Delayed 2 days; carrier notification provided.'),
(11, 11, '2026-08-30', 500, 'On Time', 0, 'Batch on-time receipt with zero outer transit damage.'),
(12, 12, '2026-09-02', 500, 'Early', 0, 'Delivered 1 day early; excellent logistics coordination.'),
(13, 13, '2026-09-04', 492, 'On Time', 0, 'Delivered on contract expected date.'),
(14, 14, '2026-09-06', 200, 'On Time', 0, 'Delivered on schedule.');

-- ------------------------------------------------------------------------------
-- 7. QUALITY INSPECTIONS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `quality_inspections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `delivery_id` INT NOT NULL,
    `inspection_date` DATE NOT NULL,
    `quantity_received` INT NOT NULL,
    `quantity_accepted` INT NOT NULL,
    `quantity_defective` INT NOT NULL,
    `quantity_rejected` INT NOT NULL DEFAULT 0,
    `defect_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `quality_score` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    `quality_status` ENUM('Passed', 'Failed', 'Conditional') NOT NULL DEFAULT 'Passed',
    `remarks` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`delivery_id`) REFERENCES `deliveries`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `quality_inspections` (`id`, `delivery_id`, `inspection_date`, `quantity_received`, `quantity_accepted`, `quantity_defective`, `quantity_rejected`, `defect_rate`, `quality_score`, `quality_status`, `remarks`) VALUES
(1, 1, '2026-08-07', 490, 480, 10, 10, 2.04, 97.96, 'Passed', 'Minor pump dispenser leakage on 10 units; batch overall AQL compliant.'),
(2, 2, '2026-08-09', 500, 500, 0, 0, 0.00, 100.00, 'Passed', 'Zero non-conformances. Viscosity and microbiological assay passed 100%.'),
(3, 3, '2026-08-12', 495, 490, 5, 5, 1.01, 98.99, 'Passed', '5 lipstick bullet tips had slight surface smudging; returned to vendor.'),
(4, 4, '2026-08-18', 200, 196, 4, 4, 2.00, 98.00, 'Passed', '4 brush ferrules had loose crimping. Rest of batch passed tensile check.'),
(5, 5, '2026-08-17', 500, 498, 2, 2, 0.40, 99.60, 'Passed', 'Exceptional hair serum formulation; pump spring mechanism verified.'),
(6, 6, '2026-08-21', 300, 292, 8, 8, 2.67, 97.33, 'Passed', '8 eyeshadow pans showed micro-cracking during drop-test inspection.'),
(7, 7, '2026-08-21', 800, 796, 4, 4, 0.50, 99.50, 'Passed', 'Nail lacquer bottle cap seals and viscosity tested normal.'),
(8, 8, '2026-08-25', 500, 500, 0, 0, 0.00, 100.00, 'Passed', '100% compliant with natural organic body butter quality standards.'),
(9, 9, '2026-08-28', 600, 597, 3, 3, 0.50, 99.50, 'Passed', 'pH tested 5.48 (target: 5.50), foaming height test compliant.'),
(10, 10, '2026-08-31', 500, 490, 10, 10, 2.00, 98.00, 'Passed', 'Sifter seal integrity on 10 units loose; rejected for credit note.'),
(11, 11, '2026-08-31', 500, 495, 5, 5, 1.00, 99.00, 'Passed', 'Primer emulsion stability verified at 45C accelerated test.'),
(12, 12, '2026-09-03', 500, 500, 0, 0, 0.00, 100.00, 'Passed', 'Hyaluronic purity index certified; zero particulates detected.'),
(13, 13, '2026-09-05', 492, 488, 4, 4, 0.81, 99.19, 'Passed', 'Gloss wand applicator adhesion test passed.'),
(14, 14, '2026-09-07', 200, 198, 2, 2, 1.00, 99.00, 'Passed', 'Rose quartz stone smoothness and non-porous structure verified.');

-- ------------------------------------------------------------------------------
-- 8. RETURNS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `returns` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `return_code` VARCHAR(30) NOT NULL UNIQUE,
    `user_id` INT DEFAULT NULL, -- Who requested the return (Shopkeeper, Manufacturer, Admin)
    `purchase_order_id` INT NOT NULL,
    `supplier_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `return_date` DATE NOT NULL,
    `return_quantity` INT NOT NULL,
    `return_reason` ENUM('Damaged', 'Wrong Product', 'Quality Issue', 'Expired', 'Other') NOT NULL DEFAULT 'Quality Issue',
    `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `return_status` ENUM('Approved', 'Pending', 'Rejected') NOT NULL DEFAULT 'Pending',
    `remarks` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `returns` (`id`, `return_code`, `user_id`, `purchase_order_id`, `supplier_id`, `product_id`, `return_date`, `return_quantity`, `return_reason`, `refund_amount`, `return_status`, `remarks`) VALUES
(1, 'RET001', 2, 1, 1, 1, '2026-08-08', 10, 'Damaged', 4500.00, 'Approved', 'Dispenser pump nozzles cracked during transit. Full refund credited.'),
(2, 'RET002', 2, 3, 3, 15, '2026-08-13', 5, 'Wrong Product', 1750.00, 'Approved', 'Received shade Ruby Red instead of Crimson Rose. Correct batch dispatched.'),
(3, 'RET003', 2, 6, 6, 9, '2026-08-22', 8, 'Quality Issue', 5200.00, 'Approved', 'Eyeshadow pressed pigment cracked; vendor issued immediate credit note.'),
(4, 'RET004', 2, 10, 10, 4, '2026-09-01', 10, 'Damaged', 3200.00, 'Pending', 'Sifter seal loose; pending vendor quality management approval.');

-- ------------------------------------------------------------------------------
-- 9. PAYMENTS & INVOICES TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `payment_code` VARCHAR(30) NOT NULL UNIQUE,
    `supplier_id` INT NOT NULL,
    `purchase_order_id` INT NOT NULL,
    `invoice_number` VARCHAR(50) NOT NULL,
    `invoice_date` DATE NOT NULL,
    `invoice_amount` DECIMAL(12,2) NOT NULL,
    `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `pending_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_date` DATE DEFAULT NULL,
    `payment_method` ENUM('Bank Transfer', 'NEFT/RTGS', 'UPI', 'Credit Card', 'Cheque') DEFAULT 'NEFT/RTGS',
    `payment_status` ENUM('Paid', 'Pending', 'Partially Paid', 'Overdue') NOT NULL DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payments` (`id`, `payment_code`, `supplier_id`, `purchase_order_id`, `invoice_number`, `invoice_date`, `invoice_amount`, `paid_amount`, `pending_amount`, `payment_date`, `payment_method`, `payment_status`) VALUES
(1, 'PAY1001', 1, 1, 'INV-GB-2026-01', '2026-08-06', 225000.00, 225000.00, 0.00, '2026-08-20', 'NEFT/RTGS', 'Paid'),
(2, 'PAY1002', 2, 2, 'INV-LS-2026-08', '2026-08-08', 295000.00, 295000.00, 0.00, '2026-08-25', 'Bank Transfer', 'Paid'),
(3, 'PAY1003', 3, 3, 'INV-VL-2026-15', '2026-08-11', 175000.00, 175000.00, 0.00, '2026-08-26', 'NEFT/RTGS', 'Paid'),
(4, 'PAY1004', 4, 4, 'INV-AB-2026-44', '2026-08-17', 178000.00, 100000.00, 78000.00, '2026-08-30', 'NEFT/RTGS', 'Partially Paid'),
(5, 'PAY1005', 5, 5, 'INV-SS-2026-92', '2026-08-16', 210000.00, 210000.00, 0.00, '2026-08-30', 'Bank Transfer', 'Paid'),
(6, 'PAY1006', 6, 6, 'INV-GE-2026-31', '2026-08-20', 195000.00, 0.00, 195000.00, NULL, 'NEFT/RTGS', 'Pending'),
(7, 'PAY1007', 7, 7, 'INV-NP-2026-77', '2026-08-20', 120000.00, 120000.00, 0.00, '2026-09-01', 'NEFT/RTGS', 'Paid'),
(8, 'PAY1008', 8, 8, 'INV-BB-2026-08', '2026-08-24', 180000.00, 180000.00, 0.00, '2026-09-01', 'Bank Transfer', 'Paid'),
(9, 'PAY1009', 9, 9, 'INV-PD-2026-99', '2026-08-27', 204000.00, 0.00, 204000.00, NULL, 'NEFT/RTGS', 'Pending'),
(10, 'PAY1010', 10, 10, 'INV-RF-2026-55', '2026-08-30', 160000.00, 0.00, 160000.00, NULL, 'NEFT/RTGS', 'Overdue');

-- ------------------------------------------------------------------------------
-- 10. PERFORMANCE SCORES TABLE (5-Factor Multi-Criteria Matrix)
-- ------------------------------------------------------------------------------
CREATE TABLE `performance_scores` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `supplier_id` INT NOT NULL,
    `period` VARCHAR(20) NOT NULL DEFAULT '2026-Q1',
    `delivery_score` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    `quality_score` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    `fulfillment_score` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    `cost_score` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    `return_score` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    `overall_score` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    `grade` VARCHAR(5) NOT NULL DEFAULT 'A+',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `supplier_period` (`supplier_id`, `period`),
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `performance_scores` (`id`, `supplier_id`, `period`, `delivery_score`, `quality_score`, `fulfillment_score`, `cost_score`, `return_score`, `overall_score`, `grade`) VALUES
(1, 2, '2026-Q1', 100.00, 100.00, 100.00, 98.00, 100.00, 99.80, 'A+'),
(2, 5, '2026-Q1', 100.00, 99.60, 100.00, 96.50, 100.00, 99.53, 'A+'),
(3, 8, '2026-Q1', 100.00, 100.00, 100.00, 95.00, 100.00, 99.50, 'A+'),
(4, 9, '2026-Q1', 100.00, 99.50, 100.00, 97.00, 100.00, 99.55, 'A+'),
(5, 7, '2026-Q1', 100.00, 99.50, 100.00, 94.00, 100.00, 99.25, 'A+'),
(6, 1, '2026-Q1', 100.00, 98.48, 98.00, 96.00, 97.96, 98.55, 'A+'),
(7, 3, '2026-Q1', 100.00, 99.09, 99.00, 95.00, 98.99, 98.93, 'A+'),
(8, 12, '2026-Q1', 95.00, 98.00, 98.00, 92.00, 98.00, 96.50, 'A+'),
(9, 11, '2026-Q1', 92.00, 97.50, 97.00, 91.00, 96.00, 94.95, 'A+'),
(10, 10, '2026-Q1', 85.00, 98.00, 98.00, 90.00, 98.00, 93.30, 'A'),
(11, 4, '2026-Q1', 75.00, 98.00, 100.00, 92.00, 98.00, 90.90, 'A'),
(12, 6, '2026-Q1', 70.00, 97.33, 100.00, 89.00, 97.33, 88.83, 'A');

-- ------------------------------------------------------------------------------
-- 11. SYSTEM SETTINGS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `system_settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('company_name', 'GlowTech Beauty & Cosmetics Supply Chain'),
('currency', '₹'),
('current_period', '2026-Q1'),
('weight_delivery', '0.30'),
('weight_quality', '0.30'),
('weight_fulfillment', '0.20'),
('weight_cost', '0.10'),
('weight_returns', '0.10');

-- ------------------------------------------------------------------------------
-- 12. AUDIT LOGS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `module` VARCHAR(50) NOT NULL DEFAULT 'General',
    `record_id` INT DEFAULT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT '127.0.0.1',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `module`, `record_id`, `details`, `ip_address`) VALUES
(1, NULL, 'System Initialized', 'System', 1, 'Master Cosmetics Supply Chain Database Ready for Multi-Stakeholder Registration.', '127.0.0.1');

-- ------------------------------------------------------------------------------
-- 13. USER INVENTORY TABLE (Multi-Tier Ownership Tracking)
-- ------------------------------------------------------------------------------
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

-- ------------------------------------------------------------------------------
-- 14. PRODUCT TRANSFERS TABLE (Manufacturer -> Supplier -> Shopkeeper)
-- ------------------------------------------------------------------------------
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

