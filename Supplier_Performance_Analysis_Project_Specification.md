# Supplier Performance Analysis System
## B.Sc. Computer Science Final Year Project — Master Project Specification

### 1. Project Title
**Supplier Performance Analysis System**

### 2. Project Overview
A web-based system for evaluating, monitoring, comparing, ranking, and reporting supplier performance using quality, delivery, cost, reliability, service, and defect-related metrics.

### 3. Project Objectives
- Maintain supplier information digitally.
- Record supplier performance evaluations.
- Automatically calculate overall supplier scores.
- Rank and compare suppliers.
- Identify poor-performing suppliers.
- Track monthly and historical performance.
- Generate management reports.
- Provide a professional dashboard with charts and KPIs.

### 4. User Roles
**Admin:** Full access to suppliers, performance data, reports, users, settings, and system management.

**Manager/Analyst:** Dashboard, supplier details, performance analysis, comparison, and reports.

### 5. Website Sections
1. Login
2. Dashboard
3. Supplier Management
4. Add Supplier
5. Supplier Details
6. Performance Management
7. Performance History
8. Performance Analysis
9. Supplier Comparison
10. Reports
11. Notifications
12. User Management
13. Settings
14. Logout

### 6. Dashboard
Display:
- Total Suppliers
- Active Suppliers
- Average Performance Score
- Top Supplier
- Poor/Critical Suppliers
- Supplier Performance Bar Chart
- Monthly Performance Line Chart
- Quality/Delivery/Cost charts
- Supplier ranking table
- Recent activities

### 7. Supplier Management
Supplier fields:
- Supplier ID/Code
- Supplier Name
- Company Name
- Email
- Phone
- Address
- City
- State
- Country
- Category
- Products Supplied
- Registration Number
- Contract Start Date
- Contract End Date
- Status

Actions:
- Add
- View
- Edit
- Delete
- Search
- Filter
- Sort

### 8. Performance Parameters
Recommended parameters:
- Quality Score
- Delivery Score
- Cost Score
- Reliability Score
- Service Score
- Defect Rate
- On-Time Delivery
- Remarks

### 9. Weighted Performance Calculation
Suggested weights:
- Quality = 30%
- Delivery = 25%
- Cost = 20%
- Reliability = 15%
- Service = 10%

Formula:

**Overall Score = (Quality × 0.30) + (Delivery × 0.25) + (Cost × 0.20) + (Reliability × 0.15) + (Service × 0.10)**

Example:
Quality 90, Delivery 85, Cost 88, Reliability 92, Service 90 gives an overall score of **88.65%**.

### 10. Rating System
- 90–100: Excellent
- 80–89: Good
- 70–79: Average
- 60–69: Poor
- Below 60: Critical

### 11. Performance Analysis
For each supplier show:
- Overall score
- Parameter-wise scores
- Historical performance
- Strengths
- Weak areas
- Defect and delivery trends
- Automatic recommendation text

Example recommendation:
“Supplier has excellent quality performance but delivery performance needs improvement.”

### 12. Supplier Comparison
Allow users to select multiple suppliers and compare:
- Quality
- Delivery
- Cost
- Reliability
- Service
- Overall score
- Rating

Display the result in a table and charts and identify the highest-performing supplier.

### 13. Reports
Reports should include:
- Supplier Performance Report
- Supplier Comparison Report
- Monthly Performance Report
- Quarterly Report
- Annual Report
- Top Supplier Report
- Poor Supplier Report

Export options:
- PDF
- Excel
- CSV
- Print

### 14. Notifications
Generate alerts for:
- Low performance score
- High defect rate
- Late delivery performance
- Contract expiry
- Pending performance evaluation

### 15. Database Design
**users**
- id
- name
- email
- password
- role
- status
- created_at

**suppliers**
- id
- supplier_code
- supplier_name
- company_name
- email
- phone
- address
- category
- status
- created_at

**performance**
- id
- supplier_id
- evaluation_date
- quality_score
- delivery_score
- cost_score
- reliability_score
- service_score
- defect_rate
- overall_score
- rating
- remarks

**products**
- id
- supplier_id
- product_name
- category
- price
- quantity

**orders**
- id
- supplier_id
- order_number
- order_date
- expected_date
- actual_date
- quantity
- status

**complaints**
- id
- supplier_id
- complaint_type
- description
- date
- status
- resolution

**notifications**
- id
- user_id
- title
- message
- type
- status
- created_at

### 16. Recommended Technology Stack
**Frontend:** HTML5, CSS3, Bootstrap, JavaScript

**Backend:** PHP

**Database:** MySQL

**Server/Development:** Apache + XAMPP

**Charts:** Chart.js

### 17. System Architecture
User → Frontend → PHP Backend → Business Logic → MySQL → Analysis/Calculations → Dashboard/Reports

### 18. UI Structure
Professional layout:
- Left sidebar navigation
- Top header
- Search
- Notification icon
- User profile
- Responsive content area
- KPI cards
- Tables
- Charts
- Modal forms where useful

### 19. Validation
- Required field validation
- Email validation
- Phone validation
- Score range validation (0–100)
- Duplicate supplier code prevention
- Date validation
- Server-side validation

### 20. Security
- Password hashing
- Session authentication
- Role-based authorization
- Prepared SQL statements
- Input validation/sanitization
- Secure logout
- Database backup

### 21. Major Project Modules
1. Authentication Module
2. Supplier Management Module
3. Performance Management Module
4. Performance Calculation Module
5. Supplier Comparison Module
6. Reporting Module
7. Notification Module
8. User Management Module
9. Database Management Module

### 22. Main Project Flow
Login → Dashboard → Supplier Management → Add Supplier → Add Performance Data → Automatic Score Calculation → Supplier Rating → Performance Analysis → Comparison → Reports → Decision Making

### 23. Recommended Folder Structure
```text
supplier-performance-analysis/
├── config/
├── database/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── auth/
├── dashboard/
├── suppliers/
├── performance/
├── comparison/
├── reports/
├── notifications/
├── users/
├── settings/
├── includes/
└── index.php
```

### 24. Final Year Project Deliverables
- Working web application
- MySQL database
- ER diagram
- Data Flow Diagram
- Use Case Diagram
- System architecture diagram
- Screenshots
- Test cases
- Project report
- Presentation
- Viva preparation

### 25. Suggested Future Enhancements
- Email notifications
- Supplier self-service portal
- AI-based performance prediction
- Advanced trend forecasting
- Automated purchase recommendations
- Cloud deployment
- Mobile-friendly/PWA version

### 26. Project Objective Statement for Viva
“The main objective of the Supplier Performance Analysis System is to evaluate, monitor, compare and rank suppliers based on quality, delivery, cost, reliability and service performance. The system automatically calculates an overall performance score and provides dashboards, charts and reports to support better supplier selection and management decisions.”
