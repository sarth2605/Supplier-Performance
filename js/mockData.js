/**
 * Supplier Performance Analysis System
 * Initial Mock Database & Seed Dataset
 */

const DEFAULT_SETTINGS = {
  weights: {
    quality: 0.30,
    delivery: 0.25,
    cost: 0.20,
    reliability: 0.15,
    service: 0.10
  },
  thresholds: {
    excellent: 90,
    good: 80,
    average: 70,
    poor: 60
  },
  defectRateAlertThreshold: 3.0, // Alert if > 3%
  onTimeDeliveryAlertThreshold: 85.0, // Alert if < 85%
  contractExpiryWarningDays: 45
};

const INITIAL_USERS = [
  {
    id: 1,
    name: "Admin User",
    email: "admin@supplierflow.com",
    role: "Admin",
    department: "Executive Operations",
    status: "Active",
    avatar: "https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80"
  },
  {
    id: 2,
    name: "Rajesh Kumar",
    email: "rajesh.manager@supplierflow.com",
    role: "Manager",
    department: "Procurement & Sourcing",
    status: "Active",
    avatar: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&auto=format&fit=crop&q=80"
  },
  {
    id: 3,
    name: "Priya Sharma",
    email: "priya.analyst@supplierflow.com",
    role: "Analyst",
    department: "Quality Assurance & Audits",
    status: "Active",
    avatar: "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=120&auto=format&fit=crop&q=80"
  }
];

const INITIAL_SUPPLIERS = [
  {
    id: 1,
    code: "SUP-101",
    name: "Apex Microtech Ltd",
    companyName: "Apex Microtech Solutions Pvt Ltd",
    email: "contact@apexmicro.com",
    phone: "+91 98234 56789",
    address: "Plot 42, Electronic City Phase 1",
    city: "Bengaluru",
    state: "Karnataka",
    country: "India",
    category: "Electronics",
    productsSupplied: "Semiconductors, Microcontrollers, PCB Assemblies, Power Regulators",
    registrationNumber: "REG-KA-2021-9982",
    taxId: "GSTIN29AAACA1234F1Z5",
    contractStartDate: "2023-01-15",
    contractEndDate: "2025-06-30",
    status: "Active",
    website: "https://apexmicro.example.com",
    establishedYear: 2012
  },
  {
    id: 2,
    code: "SUP-102",
    name: "Tata Steel & Alloy Works",
    companyName: "Tata Steel Industrial Division",
    email: "sales@tatasteelalloy.com",
    phone: "+91 94123 45678",
    address: "Industrial Growth Center, Gate 3",
    city: "Jamshedpur",
    state: "Jharkhand",
    country: "India",
    category: "Raw Materials",
    productsSupplied: "Hot Rolled Coils, Stainless Steel Tubes, Forged Billets, Sheet Metal",
    registrationNumber: "REG-JH-2019-4501",
    taxId: "GSTIN20AAACT5678E1Z8",
    contractStartDate: "2022-06-01",
    contractEndDate: "2024-12-31",
    status: "Active",
    website: "https://tatasteelalloy.example.com",
    establishedYear: 2004
  },
  {
    id: 3,
    code: "SUP-103",
    name: "Speedline Logistics Hub",
    companyName: "Speedline Freight & Cargo Express",
    email: "support@speedlinelogistics.com",
    phone: "+91 98987 65432",
    address: "Warehouse Complex B4, Nhava Sheva Port Road",
    city: "Navi Mumbai",
    state: "Maharashtra",
    country: "India",
    category: "Logistics",
    productsSupplied: "Cold Chain Transport, Heavy Container Freight, Express Air Freight, Warehousing",
    registrationNumber: "REG-MH-2020-7712",
    taxId: "GSTIN27AAACS9981D1ZX",
    contractStartDate: "2023-04-01",
    contractEndDate: "2025-03-31",
    status: "Active",
    website: "https://speedlinelogistics.example.com",
    establishedYear: 2015
  },
  {
    id: 4,
    code: "SUP-104",
    name: "EcoPack Solutions",
    companyName: "EcoPack Biodegradable Containers LLP",
    email: "info@ecopack.co.in",
    phone: "+91 97654 32109",
    address: "Sector 18, Udyog Vihar Phase 4",
    city: "Gurugram",
    state: "Haryana",
    country: "India",
    category: "Packaging",
    productsSupplied: "Corrugated Shipping Cartons, Biodegradable Tape, Air Pillows, Custom Inserts",
    registrationNumber: "REG-HR-2022-3341",
    taxId: "GSTIN06AAACE4321A1Z9",
    contractStartDate: "2023-08-10",
    contractEndDate: "2024-09-30",
    status: "Active",
    website: "https://ecopack.example.com",
    establishedYear: 2019
  },
  {
    id: 5,
    code: "SUP-105",
    name: "Nova Precision Tools",
    companyName: "Nova Heavy Engineering & Tools Ltd",
    email: "orders@novatools.com",
    phone: "+91 93210 98765",
    address: "GIDC Industrial Estate, Makarpura",
    city: "Vadodara",
    state: "Gujarat",
    country: "India",
    category: "Machinery",
    productsSupplied: "CNC Cutters, High-Speed Carbide Toolbits, Hydraulic Press Valves, Milling Heads",
    registrationNumber: "REG-GJ-2020-1190",
    taxId: "GSTIN24AAACN6543K1ZM",
    contractStartDate: "2022-11-01",
    contractEndDate: "2025-10-31",
    status: "Active",
    website: "https://novatools.example.com",
    establishedYear: 2008
  },
  {
    id: 6,
    code: "SUP-106",
    name: "Zenith Cloud & IT Systems",
    companyName: "Zenith Information Technologies Ltd",
    email: "enterprise@zenithit.com",
    phone: "+91 91234 56780",
    address: "Infopark Campus, Phase 2, Kakkanad",
    city: "Kochi",
    state: "Kerala",
    country: "India",
    category: "IT Services",
    productsSupplied: "ERP Custom Integrations, Cloud Infrastructure Hosting, EDI Data Gateways",
    registrationNumber: "REG-KL-2021-8823",
    taxId: "GSTIN32AAACZ1122J1Z2",
    contractStartDate: "2023-02-01",
    contractEndDate: "2026-01-31",
    status: "Active",
    website: "https://zenithit.example.com",
    establishedYear: 2017
  },
  {
    id: 7,
    code: "SUP-107",
    name: "Vortex Chemical Refineries",
    companyName: "Vortex Specialty Polymers Pvt Ltd",
    email: "sales@vortexchem.com",
    phone: "+91 98450 12345",
    address: "Plot 89, SIPCOT Industrial Park",
    city: "Ranipet",
    state: "Tamil Nadu",
    country: "India",
    category: "Chemicals",
    productsSupplied: "Industrial Epoxy Adhesives, Resin Pellets, Degreasing Solvents, Coating Polymers",
    registrationNumber: "REG-TN-2018-0912",
    taxId: "GSTIN33AAACV7766M1ZQ",
    contractStartDate: "2021-05-15",
    contractEndDate: "2024-05-14",
    status: "Under Review",
    website: "https://vortexchem.example.com",
    establishedYear: 2011
  },
  {
    id: 8,
    code: "SUP-108",
    name: "RapidFast Courier Co",
    companyName: "RapidFast Express Courier Services",
    email: "dispatch@rapidfast.in",
    phone: "+91 99001 22334",
    address: "Transport Nagar, Ring Road 2",
    city: "Indore",
    state: "Madhya Pradesh",
    country: "India",
    category: "Logistics",
    productsSupplied: "Last-mile Parcel Delivery, Urgent Document Courier, Regional Dispatch",
    registrationNumber: "REG-MP-2022-6789",
    taxId: "GSTIN23AAACR8833B1ZR",
    contractStartDate: "2023-09-01",
    contractEndDate: "2024-08-31",
    status: "Inactive",
    website: "https://rapidfast.example.com",
    establishedYear: 2021
  }
];

const INITIAL_EVALUATIONS = [
  // Supplier 1: Apex Microtech (Excellent)
  {
    id: 101,
    supplierId: 1,
    evaluationDate: "2024-05-15",
    evaluatorId: 2,
    evaluatorName: "Rajesh Kumar",
    qualityScore: 94,
    deliveryScore: 92,
    costScore: 88,
    reliabilityScore: 95,
    serviceScore: 90,
    defectRate: 0.8,
    onTimeDeliveryRate: 98.5,
    overallScore: 92.05,
    rating: "Excellent",
    strengths: "Zero critical defects in microcontrollers; advanced automated testing labs; rapid turnaround on technical escalations.",
    weaknesses: "Custom batch lead times are slightly rigid during high seasonal demand.",
    recommendation: "Top tier strategic partner. Strongly recommend extending the contract for 3 years and increasing order allocation.",
    remarks: "Quarterly audit passed with 99.2% compliance."
  },
  {
    id: 102,
    supplierId: 1,
    evaluationDate: "2024-04-12",
    evaluatorId: 3,
    evaluatorName: "Priya Sharma",
    qualityScore: 93,
    deliveryScore: 90,
    costScore: 87,
    reliabilityScore: 94,
    serviceScore: 88,
    defectRate: 0.9,
    onTimeDeliveryRate: 97.0,
    overallScore: 91.00,
    rating: "Excellent",
    strengths: "High solderability index; consistent packaging protections.",
    weaknesses: "Slight delay in shipping documentation for April batch.",
    recommendation: "Maintain preferred vendor status.",
    remarks: "Consistent excellence across parameters."
  },
  {
    id: 103,
    supplierId: 1,
    evaluationDate: "2024-03-10",
    evaluatorId: 2,
    evaluatorName: "Rajesh Kumar",
    qualityScore: 91,
    deliveryScore: 89,
    costScore: 86,
    reliabilityScore: 92,
    serviceScore: 88,
    defectRate: 1.1,
    onTimeDeliveryRate: 96.2,
    overallScore: 89.45,
    rating: "Good",
    strengths: "Excellent component longevity and test documentation.",
    weaknesses: "Pricing marginally higher than import alternatives.",
    recommendation: "High reliability justifies the moderate price premium.",
    remarks: "Stable baseline performance."
  },

  // Supplier 2: Tata Steel & Alloy Works (Good)
  {
    id: 201,
    supplierId: 2,
    evaluationDate: "2024-05-18",
    evaluatorId: 2,
    evaluatorName: "Rajesh Kumar",
    qualityScore: 88,
    deliveryScore: 85,
    costScore: 92,
    reliabilityScore: 90,
    serviceScore: 84,
    defectRate: 1.4,
    onTimeDeliveryRate: 93.0,
    overallScore: 87.95,
    rating: "Good",
    strengths: "Very competitive bulk metal pricing; high production capacity; certified ISO metallurgical compliance.",
    weaknesses: "Minor surface oxidation noted on one batch of stainless steel tubes; dispatch notifications delayed.",
    recommendation: "Solid reliable Tier-1 raw materials supplier. Implement silica packaging for monsoon transport.",
    remarks: "Maintains high compliance standards across standard billets."
  },
  {
    id: 202,
    supplierId: 2,
    evaluationDate: "2024-04-15",
    evaluatorId: 3,
    evaluatorName: "Priya Sharma",
    qualityScore: 86,
    deliveryScore: 84,
    costScore: 91,
    reliabilityScore: 88,
    serviceScore: 82,
    defectRate: 1.6,
    onTimeDeliveryRate: 92.0,
    overallScore: 86.20,
    rating: "Good",
    strengths: "Volume discounts and flexible payment terms.",
    weaknesses: "Railway siding logistics caused 2-day transit delay.",
    recommendation: "Coordinate dispatch schedules with local freight hubs.",
    remarks: "Satisfactory delivery."
  },

  // Supplier 3: Speedline Logistics Hub (Good)
  {
    id: 301,
    supplierId: 3,
    evaluationDate: "2024-05-20",
    evaluatorId: 3,
    evaluatorName: "Priya Sharma",
    qualityScore: 82,
    deliveryScore: 96,
    costScore: 78,
    reliabilityScore: 88,
    serviceScore: 86,
    defectRate: 0.5,
    onTimeDeliveryRate: 99.0,
    overallScore: 86.00,
    rating: "Good",
    strengths: "Exceptional dispatch speed, 24/7 real-time GPS tracking dashboard, zero lost consignments.",
    weaknesses: "Fuel surcharge policy is rigid; invoice dispute resolution takes over 10 days.",
    recommendation: "Primary logistics choice for urgent, high-value, and temperature-sensitive freight.",
    remarks: "Delivery KPIs exceeded company service targets."
  },

  // Supplier 4: EcoPack Solutions (Average)
  {
    id: 401,
    supplierId: 4,
    evaluationDate: "2024-05-22",
    evaluatorId: 3,
    evaluatorName: "Priya Sharma",
    qualityScore: 76,
    deliveryScore: 80,
    costScore: 85,
    reliabilityScore: 74,
    serviceScore: 82,
    defectRate: 3.2,
    onTimeDeliveryRate: 88.0,
    overallScore: 79.10,
    rating: "Average",
    strengths: "100% biodegradable and recyclable certified packaging; attractive sustainable branding.",
    weaknesses: "Inconsistent carton wall thickness during high-humidity season; defect rate at 3.2%.",
    recommendation: "Mandate edge crush test (ECT) verification before batch dispatch. Schedule supplier quality audit.",
    remarks: "Requires quality improvement plan within 30 days."
  },

  // Supplier 5: Nova Precision Tools (Good)
  {
    id: 501,
    supplierId: 5,
    evaluationDate: "2024-05-25",
    evaluatorId: 2,
    evaluatorName: "Rajesh Kumar",
    qualityScore: 91,
    deliveryScore: 86,
    costScore: 82,
    reliabilityScore: 89,
    serviceScore: 85,
    defectRate: 1.1,
    onTimeDeliveryRate: 94.0,
    overallScore: 87.05,
    rating: "Good",
    strengths: "Extremely durable carbide toolings; customized CNC profiling support.",
    weaknesses: "Extended lead times during peak industrial tooling cycles.",
    recommendation: "Maintain good standing. Establish minimum buffer inventory on standard CNC cutters.",
    remarks: "High mechanical precision verified by QA lab."
  },

  // Supplier 6: Zenith Cloud & IT Systems (Excellent)
  {
    id: 601,
    supplierId: 6,
    evaluationDate: "2024-05-28",
    evaluatorId: 1,
    evaluatorName: "Admin User",
    qualityScore: 95,
    deliveryScore: 94,
    costScore: 84,
    reliabilityScore: 92,
    serviceScore: 96,
    defectRate: 0.2,
    onTimeDeliveryRate: 99.5,
    overallScore: 92.20,
    rating: "Excellent",
    strengths: "99.99% EDI and API system uptime; average technical support response time under 12 minutes.",
    weaknesses: "Premium hourly billing rates for customized microservice integrations.",
    recommendation: "Benchmark provider for enterprise cloud and data integrations.",
    remarks: "Zero security incidents logged this fiscal year."
  },

  // Supplier 7: Vortex Chemical Refineries (Poor)
  {
    id: 701,
    supplierId: 7,
    evaluationDate: "2024-05-30",
    evaluatorId: 3,
    evaluatorName: "Priya Sharma",
    qualityScore: 62,
    deliveryScore: 68,
    costScore: 72,
    reliabilityScore: 58,
    serviceScore: 60,
    defectRate: 6.8,
    onTimeDeliveryRate: 74.0,
    overallScore: 64.60,
    rating: "Poor",
    strengths: "Low initial quotation for bulk resin supplies.",
    weaknesses: "High impurity rate (6.8% defect count); two consecutive delayed shipments; unresponsive account manager.",
    recommendation: "Issue formal Corrective Action Request (CAR). Freeze POs until factory audit passes.",
    remarks: "Elevated risk of production stoppage if resin defects recur."
  },

  // Supplier 8: RapidFast Courier Co (Critical)
  {
    id: 801,
    supplierId: 8,
    evaluationDate: "2024-04-10",
    evaluatorId: 2,
    evaluatorName: "Rajesh Kumar",
    qualityScore: 54,
    deliveryScore: 50,
    costScore: 65,
    reliabilityScore: 48,
    serviceScore: 52,
    defectRate: 8.5,
    onTimeDeliveryRate: 62.0,
    overallScore: 53.60,
    rating: "Critical",
    strengths: "Economical unit price per consignment.",
    weaknesses: "Unacceptable loss and parcel damage rate (8.5%); frequent untracked delays; failed SLAs.",
    recommendation: "Terminate vendor contract immediately. Reroute logistics volume to Speedline Logistics.",
    remarks: "Vendor placed on blacklisted de-activation status."
  }
];

const INITIAL_PRODUCTS = [
  { id: 1, supplierId: 1, name: "ARM Cortex-M4 Microcontroller 64-Pin", code: "PRD-M4-01", category: "Electronics", price: 420.00, inStock: 4500, leadTimeDays: 5, status: "Available" },
  { id: 2, supplierId: 1, name: "Multi-Layer PCB Board (FR4 High-Temp)", code: "PRD-PCB-08", category: "Electronics", price: 180.00, inStock: 8200, leadTimeDays: 7, status: "Available" },
  { id: 3, supplierId: 1, name: "Low-Dropout Voltage Regulator 3.3V", code: "PRD-LDO-33", category: "Electronics", price: 28.50, inStock: 15000, leadTimeDays: 3, status: "Available" },
  { id: 4, supplierId: 2, name: "Hot Rolled Steel Coils (Grade IS 2062)", code: "PRD-HRC-20", category: "Raw Materials", price: 54000.00, inStock: 120, leadTimeDays: 14, status: "Available" },
  { id: 5, supplierId: 2, name: "Seamless Stainless Steel Tubing 316L", code: "PRD-SST-316", category: "Raw Materials", price: 1450.00, inStock: 850, leadTimeDays: 10, status: "Available" },
  { id: 6, supplierId: 3, name: "Dedicated 32-Foot Container Freight", code: "SRV-LOG-32F", category: "Logistics", price: 28500.00, inStock: 50, leadTimeDays: 1, status: "Available" },
  { id: 7, supplierId: 4, name: "Heavy Duty 5-Ply Corrugated Box (40x30x30 cm)", code: "PRD-BOX-5P", category: "Packaging", price: 45.00, inStock: 12000, leadTimeDays: 4, status: "Available" },
  { id: 8, supplierId: 5, name: "Carbide End Mill 4-Flute 10mm", code: "PRD-TOOL-10M", category: "Machinery", price: 1250.00, inStock: 340, leadTimeDays: 8, status: "Available" },
  { id: 9, supplierId: 7, name: "Industrial Grade Epoxy Adhesive (Part A+B)", code: "PRD-CHM-EPX", category: "Chemicals", price: 890.00, inStock: 180, leadTimeDays: 12, status: "Available" }
];

const INITIAL_ORDERS = [
  { id: 1, supplierId: 1, orderNumber: "PO-2024-0581", orderDate: "2024-05-01", expectedDate: "2024-05-08", actualDate: "2024-05-07", quantity: 2000, amount: 840000.00, status: "Delivered On-Time", defectCount: 2 },
  { id: 2, supplierId: 1, orderNumber: "PO-2024-0599", orderDate: "2024-05-18", expectedDate: "2024-05-25", actualDate: "2024-05-24", quantity: 5000, amount: 900000.00, status: "Delivered On-Time", defectCount: 0 },
  { id: 3, supplierId: 2, orderNumber: "PO-2024-0540", orderDate: "2024-04-20", expectedDate: "2024-05-06", actualDate: "2024-05-05", quantity: 25, amount: 1350000.00, status: "Delivered On-Time", defectCount: 0 },
  { id: 4, supplierId: 2, orderNumber: "PO-2024-0612", orderDate: "2024-05-22", expectedDate: "2024-06-05", actualDate: null, quantity: 15, amount: 810000.00, status: "In Transit", defectCount: 0 },
  { id: 5, supplierId: 3, orderNumber: "PO-2024-0601", orderDate: "2024-05-10", expectedDate: "2024-05-12", actualDate: "2024-05-11", quantity: 4, amount: 114000.00, status: "Delivered On-Time", defectCount: 0 },
  { id: 6, supplierId: 4, orderNumber: "PO-2024-0570", orderDate: "2024-04-28", expectedDate: "2024-05-04", actualDate: "2024-05-07", quantity: 3000, amount: 135000.00, status: "Delivered Late", defectCount: 96 },
  { id: 7, supplierId: 5, orderNumber: "PO-2024-0555", orderDate: "2024-05-02", expectedDate: "2024-05-12", actualDate: "2024-05-11", quantity: 100, amount: 125000.00, status: "Delivered On-Time", defectCount: 1 },
  { id: 8, supplierId: 7, orderNumber: "PO-2024-0518", orderDate: "2024-04-14", expectedDate: "2024-04-26", actualDate: "2024-05-03", quantity: 200, amount: 178000.00, status: "Delivered Late", defectCount: 22 },
  { id: 9, supplierId: 8, orderNumber: "PO-2024-0490", orderDate: "2024-03-25", expectedDate: "2024-03-28", actualDate: "2024-04-05", quantity: 80, amount: 32000.00, status: "Delivered Late", defectCount: 14 }
];

const INITIAL_COMPLAINTS = [
  {
    id: 1,
    supplierId: 7,
    type: "Quality Defect",
    severity: "High",
    description: "Impurity flakes detected in resin shipment batch #204 resulting in coating finish blemishes.",
    date: "2024-05-12",
    status: "In Investigation",
    resolution: "Samples dispatched to third-party testing facility for chemical chromatography."
  },
  {
    id: 2,
    supplierId: 4,
    type: "Quality Defect",
    severity: "Medium",
    description: "Burst strength of corrugated boxes fell below 14 kg/cm² during humidity testing.",
    date: "2024-05-08",
    status: "Resolved",
    resolution: "Supplier replaced batch with double-wall flute construction at no additional cost."
  },
  {
    id: 3,
    supplierId: 8,
    type: "Delayed Shipment",
    severity: "Critical",
    description: "Express consignments delayed by 8 days without GPS tracking updates.",
    date: "2024-04-02",
    status: "Closed",
    resolution: "Freight charges refunded; vendor contract flagged for termination."
  },
  {
    id: 4,
    supplierId: 2,
    type: "Packaging Damage",
    severity: "Low",
    description: "Protective film on stainless tubes torn during flatbed loading.",
    date: "2024-03-18",
    status: "Resolved",
    resolution: "Supplier updated strapping protocol with rubber corner guards."
  }
];

const INITIAL_NOTIFICATIONS = [
  {
    id: 1,
    userId: 1,
    supplierId: 7,
    supplierName: "Vortex Chemical Refineries",
    title: "High Defect Rate Warning",
    message: "Defect rate of 6.8% detected in May evaluation for Vortex Chemical Refineries (Threshold: 3.0%).",
    type: "Critical",
    status: "Unread",
    date: "2024-05-30"
  },
  {
    id: 2,
    userId: 1,
    supplierId: 8,
    supplierName: "RapidFast Courier Co",
    title: "Critical Supplier Performance Alert",
    message: "RapidFast Courier Co overall score dropped to 53.60% (Below 60% Critical threshold). Action recommended: Decommission.",
    type: "Critical",
    status: "Unread",
    date: "2024-05-29"
  },
  {
    id: 3,
    userId: 2,
    supplierId: 4,
    supplierName: "EcoPack Solutions",
    title: "Contract Expiry Countdown (4 Months)",
    message: "Contract with EcoPack Solutions expires on 2024-09-30. Review renewal terms and audit score (79.10%).",
    type: "Warning",
    status: "Unread",
    date: "2024-05-25"
  },
  {
    id: 4,
    userId: 3,
    supplierId: 4,
    supplierName: "EcoPack Solutions",
    title: "Performance Score Dropped Below 80%",
    message: "EcoPack Solutions current score is 79.10% (Average tier). Delivery on-time is 88.0%.",
    type: "Warning",
    status: "Read",
    date: "2024-05-22"
  },
  {
    id: 5,
    userId: 1,
    supplierId: 1,
    supplierName: "Apex Microtech Ltd",
    title: "Top Performer Milestone Reached",
    message: "Apex Microtech Ltd achieved 92.05% Excellent score. Recognized as Category Leader in Electronics.",
    type: "Success",
    status: "Read",
    date: "2024-05-15"
  },
  {
    id: 6,
    userId: 2,
    supplierId: 2,
    supplierName: "Tata Steel & Alloy Works",
    title: "Upcoming Contract Expiry (Dec 2024)",
    message: "Contract for Tata Steel & Alloy Works ends in 2024-12-31. Initiate procurement RFP or contract renewal.",
    type: "Info",
    status: "Read",
    date: "2024-05-10"
  }
];
