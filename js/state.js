/**
 * Supplier Performance Analysis System
 * Central Reactive State Manager with LocalStorage Persistence
 */

class StateManager {
  constructor() {
    this.STORAGE_KEY = "SPAS_APP_STATE_V1";
    this.listeners = new Set();
    this.state = this.loadState();
  }

  loadState() {
    try {
      const stored = localStorage.getItem(this.STORAGE_KEY);
      if (stored) {
        return JSON.parse(stored);
      }
    } catch (e) {
      console.warn("Could not load from localStorage, initializing default state.", e);
    }
    return this.getDefaultState();
  }

  getDefaultState() {
    return {
      settings: JSON.parse(JSON.stringify(DEFAULT_SETTINGS)),
      users: JSON.parse(JSON.stringify(INITIAL_USERS)),
      currentUser: INITIAL_USERS[0], // Admin by default
      suppliers: JSON.parse(JSON.stringify(INITIAL_SUPPLIERS)),
      evaluations: JSON.parse(JSON.stringify(INITIAL_EVALUATIONS)),
      products: JSON.parse(JSON.stringify(INITIAL_PRODUCTS)),
      orders: JSON.parse(JSON.stringify(INITIAL_ORDERS)),
      complaints: JSON.parse(JSON.stringify(INITIAL_COMPLAINTS)),
      notifications: JSON.parse(JSON.stringify(INITIAL_NOTIFICATIONS)),
      theme: "light",
      activeTab: "dashboard",
      selectedSupplierId: 1,
      comparisonSupplierIds: [1, 2, 3]
    };
  }

  saveState() {
    try {
      localStorage.setItem(this.STORAGE_KEY, JSON.stringify(this.state));
    } catch (e) {
      console.error("Failed to save state to localStorage", e);
    }
    this.notify();
  }

  subscribe(callback) {
    this.listeners.add(callback);
    return () => this.listeners.delete(callback);
  }

  notify(eventData = {}) {
    this.listeners.forEach(fn => {
      try {
        fn(this.state, eventData);
      } catch (err) {
        console.error("Error in state subscriber:", err);
      }
    });
  }

  resetToDefault() {
    this.state = this.getDefaultState();
    this.saveState();
  }

  // --- Users & Roles ---
  getUsers() {
    return this.state.users || [];
  }

  getCurrentUser() {
    return this.state.currentUser || INITIAL_USERS[0];
  }

  setCurrentUser(userId) {
    const user = this.state.users.find(u => u.id === Number(userId));
    if (user) {
      this.state.currentUser = user;
      this.saveState();
    }
  }

  switchRole(roleName) {
    const user = this.state.users.find(u => u.role.toLowerCase() === roleName.toLowerCase()) || {
      id: 99,
      name: `${roleName} User`,
      email: `${roleName.toLowerCase()}@supplierflow.com`,
      role: roleName,
      department: "Procurement",
      status: "Active"
    };
    this.state.currentUser = user;
    this.saveState();
  }

  isAdmin() {
    return this.getCurrentUser().role === "Admin";
  }

  // --- Settings & Weights ---
  getSettings() {
    return this.state.settings || DEFAULT_SETTINGS;
  }

  getWeights() {
    return (this.state.settings && this.state.settings.weights) ? this.state.settings.weights : DEFAULT_SETTINGS.weights;
  }

  getThresholds() {
    return (this.state.settings && this.state.settings.thresholds) ? this.state.settings.thresholds : DEFAULT_SETTINGS.thresholds;
  }

  updateSettings(newSettings) {
    this.state.settings = { ...this.state.settings, ...newSettings };
    
    // Re-calculate all evaluations overall scores and ratings based on new weights & thresholds
    const weights = this.getWeights();
    const thresholds = this.getThresholds();
    
    this.state.evaluations = this.state.evaluations.map(ev => {
      const newScore = Calculations.calculateOverallScore(
        ev.qualityScore,
        ev.deliveryScore,
        ev.costScore,
        ev.reliabilityScore,
        ev.serviceScore,
        weights
      );
      const ratingTier = Calculations.getRatingTier(newScore, thresholds);
      const insights = Calculations.generateAnalyticalInsights({
        qualityScore: ev.qualityScore,
        deliveryScore: ev.deliveryScore,
        costScore: ev.costScore,
        reliabilityScore: ev.reliabilityScore,
        serviceScore: ev.serviceScore,
        defectRate: ev.defectRate,
        onTimeDeliveryRate: ev.onTimeDeliveryRate,
        overallScore: newScore
      });

      return {
        ...ev,
        overallScore: newScore,
        rating: ratingTier.tier,
        recommendation: insights.recommendation,
        strengths: insights.strengths,
        weaknesses: insights.weaknesses
      };
    });

    this.saveState();
  }

  // --- Theme ---
  getTheme() {
    return this.state.theme || "light";
  }

  setTheme(theme) {
    this.state.theme = theme;
    this.saveState();
  }

  // --- Suppliers CRUD ---
  getSuppliers() {
    return this.state.suppliers || [];
  }

  getSupplierById(id) {
    return this.getSuppliers().find(s => s.id === Number(id));
  }

  addSupplier(supplierData) {
    const id = Date.now();
    const newCode = supplierData.code || `SUP-${Math.floor(100 + Math.random() * 900)}`;
    const newSupplier = {
      id,
      code: newCode,
      name: supplierData.name,
      companyName: supplierData.companyName || supplierData.name,
      email: supplierData.email,
      phone: supplierData.phone,
      address: supplierData.address,
      city: supplierData.city,
      state: supplierData.state,
      country: supplierData.country || "India",
      category: supplierData.category || "Raw Materials",
      productsSupplied: supplierData.productsSupplied || "",
      registrationNumber: supplierData.registrationNumber || `REG-${Date.now().toString().slice(-4)}`,
      taxId: supplierData.taxId || "",
      contractStartDate: supplierData.contractStartDate || new Date().toISOString().split("T")[0],
      contractEndDate: supplierData.contractEndDate || new Date(Date.now() + 365*24*3600*1000).toISOString().split("T")[0],
      status: supplierData.status || "Active",
      website: supplierData.website || "",
      establishedYear: supplierData.establishedYear || new Date().getFullYear()
    };

    this.state.suppliers.unshift(newSupplier);

    // If initial evaluation scores are provided in the add modal, automatically record first evaluation
    if (supplierData.initialQuality !== undefined && supplierData.initialQuality !== "") {
      this.addEvaluation({
        supplierId: id,
        evaluationDate: new Date().toISOString().split("T")[0],
        qualityScore: parseFloat(supplierData.initialQuality) || 85,
        deliveryScore: parseFloat(supplierData.initialDelivery) || 85,
        costScore: parseFloat(supplierData.initialCost) || 85,
        reliabilityScore: parseFloat(supplierData.initialReliability) || 85,
        serviceScore: parseFloat(supplierData.initialService) || 85,
        defectRate: parseFloat(supplierData.initialDefectRate) || 1.0,
        onTimeDeliveryRate: parseFloat(supplierData.initialOnTime) || 95.0,
        remarks: "Initial baseline supplier onboarding assessment."
      });
    }

    // Add activity notification
    this.addNotification({
      title: "New Supplier Onboarded",
      message: `${newSupplier.name} (${newSupplier.code}) was successfully registered.`,
      type: "Success",
      supplierId: id
    });

    this.saveState();
    return newSupplier;
  }

  updateSupplier(id, updatedFields) {
    const idx = this.state.suppliers.findIndex(s => s.id === Number(id));
    if (idx !== -1) {
      this.state.suppliers[idx] = { ...this.state.suppliers[idx], ...updatedFields };
      this.saveState();
      return this.state.suppliers[idx];
    }
    return null;
  }

  deleteSupplier(id) {
    const numId = Number(id);
    const supplier = this.getSupplierById(numId);
    this.state.suppliers = this.state.suppliers.filter(s => s.id !== numId);
    this.state.evaluations = this.state.evaluations.filter(e => e.supplierId !== numId);
    this.state.products = this.state.products.filter(p => p.supplierId !== numId);
    this.state.orders = this.state.orders.filter(o => o.supplierId !== numId);
    this.state.complaints = this.state.complaints.filter(c => c.supplierId !== numId);
    this.state.comparisonSupplierIds = this.state.comparisonSupplierIds.filter(sId => sId !== numId);

    if (supplier) {
      this.addNotification({
        title: "Supplier Removed",
        message: `${supplier.name} (${supplier.code}) and related records were deleted.`,
        type: "Info"
      });
    }

    this.saveState();
  }

  // --- Evaluations CRUD ---
  getEvaluations() {
    return this.state.evaluations || [];
  }

  getEvaluationsForSupplier(supplierId) {
    return this.getEvaluations()
      .filter(e => e.supplierId === Number(supplierId))
      .sort((a, b) => new Date(b.evaluationDate) - new Date(a.evaluationDate));
  }

  getLatestEvaluationForSupplier(supplierId) {
    const evals = this.getEvaluationsForSupplier(supplierId);
    return evals.length > 0 ? evals[0] : null;
  }

  addEvaluation(evalData) {
    const id = Date.now();
    const weights = this.getWeights();
    const thresholds = this.getThresholds();
    
    const overallScore = Calculations.calculateOverallScore(
      evalData.qualityScore,
      evalData.deliveryScore,
      evalData.costScore,
      evalData.reliabilityScore,
      evalData.serviceScore,
      weights
    );

    const ratingInfo = Calculations.getRatingTier(overallScore, thresholds);
    const insights = Calculations.generateAnalyticalInsights({
      qualityScore: evalData.qualityScore,
      deliveryScore: evalData.deliveryScore,
      costScore: evalData.costScore,
      reliabilityScore: evalData.reliabilityScore,
      serviceScore: evalData.serviceScore,
      defectRate: evalData.defectRate,
      onTimeDeliveryRate: evalData.onTimeDeliveryRate,
      overallScore: overallScore
    });

    const user = this.getCurrentUser();
    const supplier = this.getSupplierById(evalData.supplierId);

    const newEval = {
      id,
      supplierId: Number(evalData.supplierId),
      evaluationDate: evalData.evaluationDate || new Date().toISOString().split("T")[0],
      evaluatorId: user.id,
      evaluatorName: user.name,
      qualityScore: parseFloat(evalData.qualityScore) || 0,
      deliveryScore: parseFloat(evalData.deliveryScore) || 0,
      costScore: parseFloat(evalData.costScore) || 0,
      reliabilityScore: parseFloat(evalData.reliabilityScore) || 0,
      serviceScore: parseFloat(evalData.serviceScore) || 0,
      defectRate: parseFloat(evalData.defectRate) || 0,
      onTimeDeliveryRate: parseFloat(evalData.onTimeDeliveryRate) || 100,
      overallScore: overallScore,
      rating: ratingInfo.tier,
      strengths: evalData.strengths || insights.strengths,
      weaknesses: evalData.weaknesses || insights.weaknesses,
      recommendation: evalData.recommendation || insights.recommendation,
      remarks: evalData.remarks || "Periodic performance appraisal submitted."
    };

    this.state.evaluations.unshift(newEval);

    // Trigger smart notification if critical or warning
    if (ratingInfo.tier === "Critical" || ratingInfo.tier === "Poor") {
      this.addNotification({
        title: `Low Score Alert: ${supplier ? supplier.name : 'Supplier'}`,
        message: `Evaluation scored ${overallScore}% (${ratingInfo.tier}). Action: Review Corrective Action Plan.`,
        type: "Critical",
        supplierId: newEval.supplierId
      });
    }

    if (newEval.defectRate > (this.state.settings.defectRateAlertThreshold || 3.0)) {
      this.addNotification({
        title: `High Defect Rate Flagged: ${supplier ? supplier.name : 'Supplier'}`,
        message: `Defect rate ${newEval.defectRate}% exceeds standard tolerance threshold (3.0%).`,
        type: "Warning",
        supplierId: newEval.supplierId
      });
    }

    this.saveState();
    return newEval;
  }

  // --- Products, Orders, Complaints ---
  getProducts() {
    return this.state.products || [];
  }

  getProductsForSupplier(supplierId) {
    return this.getProducts().filter(p => p.supplierId === Number(supplierId));
  }

  addProduct(productData) {
    const newProduct = {
      id: Date.now(),
      supplierId: Number(productData.supplierId),
      name: productData.name,
      code: productData.code || `PRD-${Math.floor(100 + Math.random() * 900)}`,
      category: productData.category || "General",
      price: parseFloat(productData.price) || 0,
      inStock: parseInt(productData.inStock) || 0,
      leadTimeDays: parseInt(productData.leadTimeDays) || 7,
      status: productData.status || "Available"
    };
    this.state.products.push(newProduct);
    this.saveState();
    return newProduct;
  }

  getOrders() {
    return this.state.orders || [];
  }

  getOrdersForSupplier(supplierId) {
    return this.getOrders().filter(o => o.supplierId === Number(supplierId));
  }

  addOrder(orderData) {
    const newOrder = {
      id: Date.now(),
      supplierId: Number(orderData.supplierId),
      orderNumber: orderData.orderNumber || `PO-${new Date().getFullYear()}-${Math.floor(1000 + Math.random() * 9000)}`,
      orderDate: orderData.orderDate || new Date().toISOString().split("T")[0],
      expectedDate: orderData.expectedDate,
      actualDate: orderData.actualDate || null,
      quantity: parseInt(orderData.quantity) || 1,
      amount: parseFloat(orderData.amount) || 0,
      status: orderData.status || "Pending",
      defectCount: parseInt(orderData.defectCount) || 0
    };
    this.state.orders.unshift(newOrder);
    this.saveState();
    return newOrder;
  }

  getComplaints() {
    return this.state.complaints || [];
  }

  getComplaintsForSupplier(supplierId) {
    return this.getComplaints().filter(c => c.supplierId === Number(supplierId));
  }

  addComplaint(complaintData) {
    const newComplaint = {
      id: Date.now(),
      supplierId: Number(complaintData.supplierId),
      type: complaintData.type || "Quality Defect",
      severity: complaintData.severity || "Medium",
      description: complaintData.description,
      date: complaintData.date || new Date().toISOString().split("T")[0],
      status: complaintData.status || "Open",
      resolution: complaintData.resolution || ""
    };
    this.state.complaints.unshift(newComplaint);
    this.saveState();
    return newComplaint;
  }

  // --- Notifications ---
  getNotifications() {
    return this.state.notifications || [];
  }

  getUnreadNotificationsCount() {
    return this.getNotifications().filter(n => n.status === "Unread").length;
  }

  addNotification(notifData) {
    const user = this.getCurrentUser();
    const newNotif = {
      id: Date.now(),
      userId: user.id,
      supplierId: notifData.supplierId || null,
      title: notifData.title,
      message: notifData.message,
      type: notifData.type || "Info", // Critical, Warning, Info, Success
      status: "Unread",
      date: new Date().toISOString().split("T")[0]
    };
    this.state.notifications.unshift(newNotif);
    this.saveState();
    return newNotif;
  }

  markNotificationAsRead(id) {
    const notif = this.state.notifications.find(n => n.id === Number(id));
    if (notif) {
      notif.status = "Read";
      this.saveState();
    }
  }

  markAllNotificationsAsRead() {
    this.state.notifications.forEach(n => n.status = "Read");
    this.saveState();
  }

  clearAllNotifications() {
    this.state.notifications = [];
    this.saveState();
  }

  // --- Multi-Supplier Comparison Selection ---
  getComparisonSupplierIds() {
    return this.state.comparisonSupplierIds || [];
  }

  setComparisonSupplierIds(ids) {
    this.state.comparisonSupplierIds = ids.map(id => Number(id));
    this.saveState();
  }

  toggleComparisonSupplier(supplierId) {
    const id = Number(supplierId);
    let list = [...this.getComparisonSupplierIds()];
    if (list.includes(id)) {
      list = list.filter(item => item !== id);
    } else {
      if (list.length >= 4) {
        list.shift(); // keep max 4
      }
      list.push(id);
    }
    this.setComparisonSupplierIds(list);
  }

  // --- Aggregated Supplier Directory with Metrics ---
  getSuppliersWithLatestMetrics() {
    const suppliers = this.getSuppliers();
    return suppliers.map(s => {
      const latestEval = this.getLatestEvaluationForSupplier(s.id);
      const evals = this.getEvaluationsForSupplier(s.id);
      const orders = this.getOrdersForSupplier(s.id);
      const complaints = this.getComplaintsForSupplier(s.id);
      
      const overallScore = latestEval ? latestEval.overallScore : 0;
      const rating = latestEval ? latestEval.rating : "Unrated";
      const ratingTier = Calculations.getRatingTier(overallScore);

      // Contract Expiry calculation
      let daysUntilContractExpiry = 999;
      if (s.contractEndDate) {
        const diffMs = new Date(s.contractEndDate).getTime() - new Date().getTime();
        daysUntilContractExpiry = Math.ceil(diffMs / (1000 * 3600 * 24));
      }

      return {
        ...s,
        latestEvaluation: latestEval,
        evaluationCount: evals.length,
        overallScore: overallScore,
        rating: rating,
        ratingTier: ratingTier,
        orderCount: orders.length,
        openComplaintsCount: complaints.filter(c => c.status !== 'Resolved' && c.status !== 'Closed').length,
        daysUntilContractExpiry: daysUntilContractExpiry,
        isExpiringSoon: daysUntilContractExpiry <= (this.state.settings.contractExpiryWarningDays || 45) && daysUntilContractExpiry >= 0,
        isExpired: daysUntilContractExpiry < 0
      };
    });
  }

  // --- Executive Dashboard Aggregate Metrics ---
  getExecutiveDashboardMetrics() {
    const suppliers = this.getSuppliersWithLatestMetrics();
    const totalSuppliers = suppliers.length;
    const activeSuppliers = suppliers.filter(s => s.status === "Active").length;
    
    const ratedSuppliers = suppliers.filter(s => s.overallScore > 0);
    const avgScore = ratedSuppliers.length > 0
      ? (ratedSuppliers.reduce((acc, s) => acc + s.overallScore, 0) / ratedSuppliers.length).toFixed(2)
      : "0.00";

    // Sorted top to bottom
    const sorted = [...ratedSuppliers].sort((a, b) => b.overallScore - a.overallScore);
    const topSupplier = sorted.length > 0 ? sorted[0] : null;
    const criticalSuppliers = suppliers.filter(s => s.overallScore > 0 && s.overallScore < 70);

    // Distribution by rating tier
    const distribution = {
      excellent: suppliers.filter(s => s.rating === "Excellent").length,
      good: suppliers.filter(s => s.rating === "Good").length,
      average: suppliers.filter(s => s.rating === "Average").length,
      poor: suppliers.filter(s => s.rating === "Poor").length,
      critical: suppliers.filter(s => s.rating === "Critical").length,
      unrated: suppliers.filter(s => s.overallScore === 0).length
    };

    return {
      totalSuppliers,
      activeSuppliers,
      inactiveSuppliers: totalSuppliers - activeSuppliers,
      averageScore: avgScore,
      topSupplier,
      criticalCount: criticalSuppliers.length,
      criticalSuppliers,
      distribution,
      rankedSuppliers: sorted
    };
  }
}

// Instantiate global State singleton
window.State = new StateManager();
