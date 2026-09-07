# Supplier Performance Analysis and Management System (SPAS)
**Domain Focus: Beauty, Cosmetics, Skincare & Personal Care Supply Chain**
*A Production-Grade Web Application for B.Sc. Computer Science Final-Year Capstone Project*

---

## 🌟 Project Overview
The **Supplier Performance Analysis and Management System (SPAS)** is an enterprise-grade corporate analytics and supply chain intelligence platform customized for the **Cosmetics, Beauty, Skincare & Personal Care** manufacturing and distribution industry.

The system manages suppliers across 8 specialized beauty product categories, purchase orders, deliveries, batch quality inspections, damaged goods returns, vendor payments, and evaluates supplier performance using a mathematically rigorous 5-factor weighted model.

---

## 💄 8 Product Categories & Taxonomy
1. **💄 Face Products**: Pore Minimizing Primer, Hydrating Foundation, Liquid Concealer, Loose Setting Powder, Setting Mist, Soft Velvet Blush, Shimmer Highlighter, Aloe Vera Moisture Gel.
2. **👁️ Eye Products**: 12-Shade Eyeshadow Palette, Waterproof Felt Tip Eyeliner, 24Hr Black Kajal Pencil, Volumizing & Curling Mascara, Eyebrow Definer, 3D Silk Magnetic Lashes.
3. **💋 Lip Products**: Transfer-Proof Matte Liquid Lipstick, Creamy Waterproof Lip Liner, High-Shine Plumping Gloss, Nourishing Berry Lip Balm, Overnight Lip Sleeping Mask.
4. **🧴 Skincare Products**: 10% Vitamin C Radiance Serum, 2% Hyaluronic Acid Serum, SPF 50+ PA++++ Ultra Light Sunscreen, Foaming Ceramide Cleanser, Oil-Free Gel Moisturizer, Charcoal Detox Clay Mask, Rosewater Mist Toner.
5. **💅 Nail Products**: Long-Wear Salon Nail Lacquer, UV LED Gel Top & Base Coat Set, Acetone-Free Vitamin E Remover.
6. **💇 Hair Beauty Products**: Keratin Repair Hair Serum, Sulfate-Free Biotin & Rice Water Shampoo, Moroccan Argan Hair Mask.
7. **🧼 Body Care Products**: Hydrating Shea Butter Body Lotion, Arabica Coffee Body Scrub, Citrus Foaming Body Wash.
8. **🧑‍🦱 Beauty Tools & Accessories**: 10-Piece Vegan Makeup Brush Set, Microfiber Beauty Blender, Rose Quartz Roller & Gua Sha Set, Ergonomic Precision Eyelash Curler.

---

## 🛠️ Technology Stack
- **Frontend**: HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.3, FontAwesome 6, Chart.js 4+
- **Design Style**: Modern SaaS corporate dashboard, responsive CSS grid, glassmorphic cards, vibrant status tokens
- **Backend**: PHP 8.0+ (Clean modular architecture with PDO prepared statements)
- **Database**: MySQL 5.7+ / MariaDB (Strict relational schema with 12 tables, foreign key constraints, indexes, and comprehensive cosmetics seed data)
- **Currency Standard**: Indian Rupees (₹ / INR) & USD configurable in `system_settings`
- **Server Environment**: Apache / XAMPP on Windows, macOS, or Linux

---

## 📐 Mathematical 5-Factor Scoring Model & Grading Scale

The system evaluates suppliers across **5 operational dimensions** with configurable weights (default: **30% - 30% - 20% - 10% - 10%**):

### 1. Delivery Performance ($30\%$ Weight)
$$\text{Delivery Score} = \left( \frac{\text{On-Time Deliveries}}{\text{Total Deliveries}} \right) \times 100$$

### 2. Product Quality ($30\%$ Weight)
$$\text{Defect Rate (\%)} = \left( \frac{\text{Defective Quantity}}{\text{Received Quantity}} \right) \times 100$$
$$\text{Quality Acceptance Rate (\%)} = \left( \frac{\text{Accepted Quantity}}{\text{Received Quantity}} \right) \times 100$$
$$\text{Quality Score (\%)} = 100 - \text{Defect Rate}$$

### 3. Order Fulfillment ($20\%$ Weight)
$$\text{Fulfillment Rate} = \left( \frac{\text{Delivered Quantity}}{\text{Ordered Quantity}} \right) \times 100$$

### 4. Cost Performance ($10\%$ Weight)
$$\text{Cost Score} = \min\left(100, \left( \frac{\text{Standard Price Benchmark}}{\text{Supplier Actual Price}} \right) \times 100\right)$$

### 5. Return Rate & Penalty ($10\%$ Weight)
$$\text{Return Rate} = \left( \frac{\text{Returned Quantity}}{\text{Delivered Quantity}} \right) \times 100$$
$$\text{Return Performance Score} = \max(0, 100 - (\text{Return Rate} \times 5))$$

### 6. Overall Weighted Score & Academic Grade Tier
$$\text{Overall Score} = (\text{Delivery} \times 0.30) + (\text{Quality} \times 0.30) + (\text{Fulfillment} \times 0.20) + (\text{Cost} \times 0.10) + (\text{Return Score} \times 0.10)$$

| Overall Score Range | Academic Grade | Corporate Tier Classification | Strategic Procurement Action |
| :--- | :---: | :--- | :--- |
| **90.0% – 100.0%** | **A+** | **Tier-1 Elite Partner** | Prime supplier for multi-year contract renewals & volume discounts |
| **80.0% – 89.9%** | **A** | **Qualified Preferred Partner** | Reliable standard operational vendor with strong consistency |
| **70.0% – 79.9%** | **B** | **Conditional Standard Partner** | Acceptable baseline; quarterly quality reviews recommended |
| **60.0% – 69.9%** | **C** | **Needs Improvement** | 30-Day Corrective Action Plan (CAP) required; packaging audit |
| **Below 60.0%** | **D** | **Poor / High Risk Vendor** | Immediate order suspension or replacement re-audit |

---

## 🚀 Step-by-Step Installation & Local Setup Guide (XAMPP)

### Step 1: Place Files into XAMPP
Copy the project files into your local web root:
```
C:\xampp\htdocs\Supplier Preferanec\
```

### Step 2: Start Apache and MySQL in XAMPP
Open the **XAMPP Control Panel** and start **Apache** and **MySQL**.

### Step 3: Import Database Schema & Seed Data
1. Open your browser and navigate to: `http://localhost/phpmyadmin`
2. Create a database named `supplier_performance_db`.
3. Click the **Import** tab.
4. Select `database/supplier_performance.sql` and click **Go**.

### Step 4: Access the Application
Open your web browser and visit:
```
http://localhost:8000/
```
*(Or `http://localhost/Supplier Preferanec/` if running on standard Apache port 80)*

---

## 🔑 Default User Accounts (1-Click Demo Login Supported)

| Role | Email Address | Password | Privileges |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@example.com` | `password` | Full system access, User Management, Weight Configurator, Audit Logs |
| **Manager** | `manager@example.com` | `password` | Supplier Management, Purchase Orders, Returns, Payments, Comparison Hub, Reports |
| **Operations Staff** | `staff@example.com` | `password` | Delivery Check-in, Quality Inspection Entry, Returns Entry, Catalog Browsing |

---

## 📂 Project Architecture & 12 Relational Tables
- `users`: User administration and RBAC credentials.
- `suppliers`: Registered cosmetics formulation labs, distributors, and contract manufacturers.
- `products`: Extensive cosmetics catalog with 8 categories, brands, SKUs, and stock levels.
- `purchase_orders` & `order_items`: Multi-item procurement orders and commitments.
- `deliveries`: Transit check-in and delay days calculation.
- `quality_inspections`: Defect rate, accepted/defective counts, and AQL compliance.
- `returns`: Returns and damaged goods tracking (Damaged, Wrong Product, Quality Issue) with credit notes.
- `payments`: Vendor invoices, paid amounts, pending balances, and payment methods (NEFT/RTGS, UPI).
- `performance_scores`: 5-factor multi-criteria scoring matrix and historical ratings.
- `system_settings`: Configurable factor weights and currency settings.
- `activity_logs`: Immutable audit trail tracking user transactions.

---

## 📄 License & Credits
Developed for **B.Sc. Computer Science Final-Year Capstone Project**.  
Built with PHP 8, MySQL, Bootstrap 5, Chart.js, and FontAwesome.
