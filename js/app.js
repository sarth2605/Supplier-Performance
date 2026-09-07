/**
 * Supplier Performance Analysis System
 * Main Application Controller, Routing, UI Renderer & Event Dispatcher
 */

class AppController {
  constructor() {
    this.currentView = "dashboard";
    this.activeSupplierId = 1;
    this.supplierViewMode = "table"; // 'table' or 'grid'
  }

  init() {
    this.applyTheme(window.State.getTheme());
    this.bindEvents();
    this.bindGlobalShortcuts();
    this.setupStateListeners();
    this.renderCurrentView();
    this.updateNotificationBadge();
    this.updateRoleIndicator();
  }

  // --- Theme Controller ---
  applyTheme(theme) {
    document.documentElement.setAttribute("data-theme", theme);
    const btn = document.getElementById("theme-toggle-btn");
    if (btn) {
      btn.innerHTML = theme === "dark" ? `<i class="fa-solid fa-sun"></i>` : `<i class="fa-solid fa-moon"></i>`;
    }
    if (window.Charts) {
      window.Charts.refreshAllCharts();
    }
  }

  toggleTheme() {
    const current = window.State.getTheme();
    const nextTheme = current === "dark" ? "light" : "dark";
    window.State.setTheme(nextTheme);
    this.applyTheme(nextTheme);
    this.showToast(`Switched to ${nextTheme} theme`, "info");
  }

  // --- State Subscription ---
  setupStateListeners() {
    window.State.subscribe((state, event) => {
      this.updateNotificationBadge();
      this.updateRoleIndicator();
      this.renderCurrentView();
    });
  }

  // --- Navigation & Routing ---
  navigate(viewId, param = null) {
    this.currentView = viewId;

    // Update sidebar nav state
    document.querySelectorAll(".nav-item").forEach(item => {
      if (item.getAttribute("data-target") === viewId) {
        item.classList.add("active");
      } else {
        item.classList.remove("active");
      }
    });

    // Hide all tab panes
    document.querySelectorAll(".tab-pane").forEach(pane => pane.classList.remove("active"));

    // Close mobile sidebar if open
    const sidebar = document.getElementById("sidebar");
    if (sidebar) sidebar.classList.remove("mobile-open");

    // Show target pane
    const targetPane = document.getElementById(`pane-${viewId}`);
    if (targetPane) {
      targetPane.classList.add("active");
    }

    if (param && viewId === "supplier-detail") {
      this.activeSupplierId = Number(param);
    }

    // Scroll to top
    window.scrollTo({ top: 0, behavior: "smooth" });

    // Render corresponding view
    this.renderCurrentView();
  }

  renderCurrentView() {
    switch (this.currentView) {
      case "dashboard":
        this.renderDashboard();
        break;
      case "suppliers":
        this.renderSuppliersList();
        break;
      case "supplier-detail":
        this.renderSupplierDetail(this.activeSupplierId);
        break;
      case "performance-eval":
        this.renderPerformanceEvaluationForm();
        break;
      case "comparison":
        this.renderComparisonMatrix();
        break;
      case "reports":
        this.renderReportsHub();
        break;
      case "notifications":
        this.renderNotificationsCenter();
        break;
      case "academic-showcase":
        this.renderAcademicShowcase();
        break;
      case "settings":
        this.renderSettings();
        break;
    }
  }

  // =======================================================
  // 1. DASHBOARD VIEW
  // =======================================================
  renderDashboard() {
    const kpis = window.State.getExecutiveDashboardMetrics();

    // Update KPI Cards
    document.getElementById("kpi-total-suppliers").textContent = kpis.totalSuppliers;
    document.getElementById("kpi-active-suppliers").textContent = kpis.activeSuppliers;
    document.getElementById("kpi-avg-score").textContent = `${kpis.averageScore}%`;
    document.getElementById("kpi-critical-count").textContent = kpis.criticalCount;

    // Top supplier badge
    const topCardEl = document.getElementById("kpi-top-supplier");
    if (kpis.topSupplier) {
      topCardEl.innerHTML = `
        <div class="kpi-details">
          <span class="kpi-label">Top Performer</span>
          <h2 class="kpi-value" style="font-size: 1.25rem;">${kpis.topSupplier.name}</h2>
          <span class="kpi-subtext" style="color: var(--tier-excellent); font-weight: 700;">
            <i class="fa-solid fa-trophy"></i> Score: ${kpis.topSupplier.overallScore}% (${kpis.topSupplier.rating})
          </span>
        </div>
        <div class="kpi-icon-wrap" style="--kpi-accent: #10b981; --kpi-icon-bg: rgba(16, 185, 129, 0.15);">
          <i class="fa-solid fa-award"></i>
        </div>
      `;
    }

    // Render Dashboard Charts
    setTimeout(() => {
      window.Charts.renderDashboardScores("chart-dashboard-scores");
      window.Charts.renderDashboardMonthlyTrend("chart-dashboard-trend");
      window.Charts.renderDashboardRadar("chart-dashboard-radar");
      window.Charts.renderDashboardScatter("chart-dashboard-scatter");
    }, 50);

    // Render Dashboard Ranked Suppliers Table
    const tbody = document.getElementById("dashboard-ranking-tbody");
    if (tbody) {
      tbody.innerHTML = kpis.rankedSuppliers.map((s, idx) => {
        const ev = s.latestEvaluation || {};
        return `
          <tr>
            <td style="font-weight: 700; color: var(--text-muted);">#${idx + 1}</td>
            <td>
              <div style="font-weight: 700; color: var(--text-primary); cursor: pointer;" onclick="app.navigate('supplier-detail', ${s.id})">
                ${s.name}
              </div>
              <div style="font-size: 0.75rem; color: var(--text-muted);">${s.code} • ${s.category}</div>
            </td>
            <td>
              <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div class="score-progress-bar" style="width: 100px;">
                  <div class="score-progress-fill" style="width: ${s.overallScore}%; background-color: ${s.ratingTier.color};"></div>
                </div>
                <span class="score-cell" style="color: ${s.ratingTier.color}; font-size: 0.95rem;">${s.overallScore}%</span>
              </div>
            </td>
            <td><span class="badge ${s.ratingTier.badgeClass}">${s.rating}</span></td>
            <td>${ev.defectRate !== undefined ? `${ev.defectRate}%` : 'N/A'}</td>
            <td>${ev.onTimeDeliveryRate !== undefined ? `${ev.onTimeDeliveryRate}%` : 'N/A'}</td>
            <td>
              <button class="btn btn-secondary btn-sm" onclick="app.navigate('supplier-detail', ${s.id})" title="View 360 Profile">
                <i class="fa-solid fa-chart-pie"></i> Details
              </button>
            </td>
          </tr>
        `;
      }).join("");
    }
  }

  // =======================================================
  // 2. SUPPLIER DIRECTORY VIEW
  // =======================================================
  renderSuppliersList() {
    const suppliers = window.State.getSuppliersWithLatestMetrics();
    const searchVal = (document.getElementById("supplier-search-input")?.value || "").toLowerCase();
    const categoryFilter = document.getElementById("supplier-category-filter")?.value || "All";
    const ratingFilter = document.getElementById("supplier-rating-filter")?.value || "All";
    const statusFilter = document.getElementById("supplier-status-filter")?.value || "All";

    const filtered = suppliers.filter(s => {
      const matchSearch = s.name.toLowerCase().includes(searchVal) ||
        s.code.toLowerCase().includes(searchVal) ||
        s.category.toLowerCase().includes(searchVal) ||
        (s.productsSupplied && s.productsSupplied.toLowerCase().includes(searchVal));
      const matchCat = categoryFilter === "All" || s.category.toLowerCase() === categoryFilter.toLowerCase();
      const matchRating = ratingFilter === "All" || s.rating.toLowerCase() === ratingFilter.toLowerCase();
      const matchStatus = statusFilter === "All" || s.status.toLowerCase() === statusFilter.toLowerCase();
      return matchSearch && matchCat && matchRating && matchStatus;
    });

    const countEl = document.getElementById("suppliers-count-badge");
    if (countEl) countEl.textContent = `${filtered.length} Suppliers`;

    const tableContainer = document.getElementById("suppliers-table-view");
    const gridContainer = document.getElementById("suppliers-grid-view");

    if (this.supplierViewMode === "table") {
      if (tableContainer) tableContainer.style.display = "block";
      if (gridContainer) gridContainer.style.display = "none";

      const tbody = document.getElementById("suppliers-table-tbody");
      if (tbody) {
        if (filtered.length === 0) {
          tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">No suppliers match your search filters.</td></tr>`;
        } else {
          tbody.innerHTML = filtered.map(s => {
            const ev = s.latestEvaluation || {};
            return `
              <tr>
                <td><code style="font-weight: 700; color: var(--primary);">${s.code}</code></td>
                <td>
                  <div style="font-weight: 700; color: var(--text-primary); cursor: pointer;" onclick="app.navigate('supplier-detail', ${s.id})">
                    ${s.name}
                  </div>
                  <div style="font-size: 0.75rem; color: var(--text-muted);">${s.companyName}</div>
                </td>
                <td><span class="badge" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color);">${s.category}</span></td>
                <td>${s.city}, ${s.state}</td>
                <td>
                  <span class="score-cell" style="color: ${s.ratingTier.color}; font-size: 1rem;">${s.overallScore}%</span>
                </td>
                <td><span class="badge ${s.ratingTier.badgeClass}">${s.rating}</span></td>
                <td>
                  <span class="badge ${s.status === 'Active' ? 'badge-active' : (s.status === 'Under Review' ? 'badge-review' : 'badge-inactive')}">
                    ${s.status}
                  </span>
                </td>
                <td>
                  <div style="display: flex; gap: 0.35rem;">
                    <button class="btn btn-secondary btn-sm" onclick="app.navigate('supplier-detail', ${s.id})" title="View Supplier Profile">
                      <i class="fa-solid fa-eye"></i>
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="app.openEvaluationModal(${s.id})" title="Add Performance Evaluation">
                      <i class="fa-solid fa-star"></i>
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="app.openEditSupplierModal(${s.id})" title="Edit Supplier Details">
                      <i class="fa-solid fa-pen"></i>
                    </button>
                    ${window.State.isAdmin() ? `
                      <button class="btn btn-danger btn-sm" onclick="app.deleteSupplier(${s.id})" title="Delete Supplier">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    ` : ''}
                  </div>
                </td>
              </tr>
            `;
          }).join("");
        }
      }
    } else {
      // Grid view
      if (tableContainer) tableContainer.style.display = "none";
      if (gridContainer) gridContainer.style.display = "grid";

      if (gridContainer) {
        if (filtered.length === 0) {
          gridContainer.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text-muted);">No suppliers match your search filters.</div>`;
        } else {
          gridContainer.innerHTML = filtered.map(s => `
            <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
              <div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                  <span class="badge" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color);">${s.category}</span>
                  <span class="badge ${s.ratingTier.badgeClass}">${s.rating}</span>
                </div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.25rem; color: var(--text-primary); cursor: pointer;" onclick="app.navigate('supplier-detail', ${s.id})">
                  ${s.name}
                </h3>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem;">${s.code} • ${s.city}, ${s.state}</p>

                <div style="background: var(--bg-surface-elevated); border-radius: var(--radius-md); padding: 0.85rem; margin-bottom: 1rem;">
                  <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                    <span style="font-size: 0.8rem; color: var(--text-secondary);">Overall Score</span>
                    <span class="score-cell" style="color: ${s.ratingTier.color}; font-size: 1.1rem;">${s.overallScore}%</span>
                  </div>
                  <div class="score-progress-bar">
                    <div class="score-progress-fill" style="width: ${s.overallScore}%; background-color: ${s.ratingTier.color};"></div>
                  </div>
                </div>
              </div>

              <div style="display: flex; gap: 0.5rem; border-top: 1px solid var(--border-subtle); padding-top: 0.85rem; margin-top: 0.5rem;">
                <button class="btn btn-primary btn-sm" style="flex: 1;" onclick="app.navigate('supplier-detail', ${s.id})">
                  <i class="fa-solid fa-chart-pie"></i> View Profile
                </button>
                <button class="btn btn-secondary btn-sm" onclick="app.openEvaluationModal(${s.id})" title="Evaluate">
                  <i class="fa-solid fa-star"></i>
                </button>
              </div>
            </div>
          `).join("");
        }
      }
    }
  }

  // =======================================================
  // 3. PERFORMANCE EVALUATION FORM & LIVE CALCULATOR
  // =======================================================
  renderPerformanceEvaluationForm() {
    const suppliers = window.State.getSuppliers();
    const selectEl = document.getElementById("eval-form-supplier-select");
    if (selectEl) {
      selectEl.innerHTML = suppliers.map(s => `<option value="${s.id}">${s.code} - ${s.name} (${s.category})</option>`).join("");
    }
    this.updateLiveEvaluationPreview();
  }

  updateLiveEvaluationPreview() {
    const q = parseFloat(document.getElementById("eval-input-quality")?.value || 85);
    const d = parseFloat(document.getElementById("eval-input-delivery")?.value || 85);
    const c = parseFloat(document.getElementById("eval-input-cost")?.value || 85);
    const r = parseFloat(document.getElementById("eval-input-reliability")?.value || 85);
    const s = parseFloat(document.getElementById("eval-input-service")?.value || 85);
    const defect = parseFloat(document.getElementById("eval-input-defect")?.value || 1.0);
    const onTime = parseFloat(document.getElementById("eval-input-ontime")?.value || 95.0);

    // Update value badge labels
    document.getElementById("val-quality").textContent = `${q}%`;
    document.getElementById("val-delivery").textContent = `${d}%`;
    document.getElementById("val-cost").textContent = `${c}%`;
    document.getElementById("val-reliability").textContent = `${r}%`;
    document.getElementById("val-service").textContent = `${s}%`;

    const weights = window.State.getWeights();
    const overallScore = Calculations.calculateOverallScore(q, d, c, r, s, weights);
    const ratingTier = Calculations.getRatingTier(overallScore);

    const scoreEl = document.getElementById("eval-live-score-value");
    if (scoreEl) {
      scoreEl.textContent = `${overallScore.toFixed(2)}%`;
      scoreEl.style.color = ratingTier.color;
    }

    const badgeEl = document.getElementById("eval-live-rating-badge");
    if (badgeEl) {
      badgeEl.className = `badge ${ratingTier.badgeClass}`;
      badgeEl.textContent = ratingTier.tier;
    }

    const formulaExplEl = document.getElementById("eval-formula-breakdown");
    if (formulaExplEl) {
      formulaExplEl.innerHTML = `
        (${q} × ${(weights.quality * 100)}%) + (${d} × ${(weights.delivery * 100)}%) + (${c} × ${(weights.cost * 100)}%) + (${r} × ${(weights.reliability * 100)}%) + (${s} × ${(weights.service * 100)}%) = <strong>${overallScore.toFixed(2)}%</strong>
      `;
    }

    const insights = Calculations.generateAnalyticalInsights({
      qualityScore: q,
      deliveryScore: d,
      costScore: c,
      reliabilityScore: r,
      serviceScore: s,
      defectRate: defect,
      onTimeDeliveryRate: onTime,
      overallScore: overallScore
    });

    const recEl = document.getElementById("eval-live-recommendation");
    if (recEl) {
      recEl.textContent = insights.recommendation;
    }
  }

  submitEvaluationForm(e) {
    e.preventDefault();
    const supplierId = Number(document.getElementById("eval-form-supplier-select").value);
    const evalDate = document.getElementById("eval-input-date").value || new Date().toISOString().split("T")[0];
    const q = parseFloat(document.getElementById("eval-input-quality").value);
    const d = parseFloat(document.getElementById("eval-input-delivery").value);
    const c = parseFloat(document.getElementById("eval-input-cost").value);
    const r = parseFloat(document.getElementById("eval-input-reliability").value);
    const s = parseFloat(document.getElementById("eval-input-service").value);
    const defect = parseFloat(document.getElementById("eval-input-defect").value);
    const onTime = parseFloat(document.getElementById("eval-input-ontime").value);
    const remarks = document.getElementById("eval-input-remarks").value;

    const newEval = window.State.addEvaluation({
      supplierId,
      evaluationDate: evalDate,
      qualityScore: q,
      deliveryScore: d,
      costScore: c,
      reliabilityScore: r,
      serviceScore: s,
      defectRate: defect,
      onTimeDeliveryRate: onTime,
      remarks
    });

    this.showToast(`Evaluation recorded! Overall Score: ${newEval.overallScore}% (${newEval.rating})`, "success");
    this.closeModal("modal-evaluation");
    this.navigate("supplier-detail", supplierId);
  }

  // =======================================================
  // 4. SUPPLIER 360 PROFILE & DEEP PERFORMANCE ANALYSIS
  // =======================================================
  renderSupplierDetail(supplierId) {
    const supplier = window.State.getSupplierById(supplierId);
    if (!supplier) {
      this.navigate("suppliers");
      return;
    }

    const latestEval = window.State.getLatestEvaluationForSupplier(supplierId);
    const evals = window.State.getEvaluationsForSupplier(supplierId);
    const products = window.State.getProductsForSupplier(supplierId);
    const orders = window.State.getOrdersForSupplier(supplierId);
    const complaints = window.State.getComplaintsForSupplier(supplierId);
    const ratingTier = Calculations.getRatingTier(latestEval ? latestEval.overallScore : 0);

    // Header Info
    document.getElementById("detail-supplier-name").textContent = supplier.name;
    document.getElementById("detail-supplier-code").textContent = `${supplier.code} • ${supplier.companyName}`;
    document.getElementById("detail-category-badge").textContent = supplier.category;
    document.getElementById("detail-rating-badge").className = `badge ${ratingTier.badgeClass}`;
    document.getElementById("detail-rating-badge").textContent = ratingTier.tier;

    // Contact & Registration details
    document.getElementById("detail-email").textContent = supplier.email;
    document.getElementById("detail-phone").textContent = supplier.phone;
    document.getElementById("detail-address").textContent = `${supplier.address}, ${supplier.city}, ${supplier.state}, ${supplier.country}`;
    document.getElementById("detail-contract").textContent = `${Calculations.formatDate(supplier.contractStartDate)} to ${Calculations.formatDate(supplier.contractEndDate)}`;
    document.getElementById("detail-registration").textContent = supplier.registrationNumber;

    // Overall Score KPI
    document.getElementById("detail-overall-score").textContent = latestEval ? `${latestEval.overallScore}%` : "Unrated";
    document.getElementById("detail-overall-score").style.color = ratingTier.color;
    document.getElementById("detail-defect-rate").textContent = latestEval ? `${latestEval.defectRate}%` : "N/A";
    document.getElementById("detail-ontime-rate").textContent = latestEval ? `${latestEval.onTimeDeliveryRate}%` : "N/A";
    document.getElementById("detail-orders-count").textContent = orders.length;

    // Strengths & Weaknesses
    if (latestEval) {
      document.getElementById("detail-strengths-text").textContent = latestEval.strengths || "Standard baseline performance.";
      document.getElementById("detail-weaknesses-text").textContent = latestEval.weaknesses || "No critical weaknesses flagged.";
      document.getElementById("detail-recommendation-text").textContent = latestEval.recommendation || "Maintain standard vendor engagement.";
    }

    // Render Charts
    setTimeout(() => {
      if (latestEval) {
        window.Charts.renderSupplierRadar("chart-detail-radar", latestEval);
      }
      window.Charts.renderSupplierHistoryTrend("chart-detail-trend", evals);
    }, 50);

    // Products table
    const productsTbody = document.getElementById("detail-products-tbody");
    if (productsTbody) {
      if (products.length === 0) {
        productsTbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No products registered for this supplier.</td></tr>`;
      } else {
        productsTbody.innerHTML = products.map(p => `
          <tr>
            <td><code>${p.code}</code></td>
            <td style="font-weight: 600;">${p.name}</td>
            <td>${p.category}</td>
            <td style="font-weight: 700;">${Calculations.formatCurrency(p.price)}</td>
            <td>${p.inStock.toLocaleString()} units</td>
          </tr>
        `).join("");
      }
    }

    // Orders table
    const ordersTbody = document.getElementById("detail-orders-tbody");
    if (ordersTbody) {
      if (orders.length === 0) {
        ordersTbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No orders found.</td></tr>`;
      } else {
        ordersTbody.innerHTML = orders.map(o => `
          <tr>
            <td><code>${o.orderNumber}</code></td>
            <td>${Calculations.formatDate(o.orderDate)}</td>
            <td>${Calculations.formatDate(o.expectedDate)}</td>
            <td>${o.actualDate ? Calculations.formatDate(o.actualDate) : '<span style="color: var(--text-muted);">Pending</span>'}</td>
            <td style="font-weight: 700;">${Calculations.formatCurrency(o.amount)}</td>
            <td>
              <span class="badge ${o.status.includes('On-Time') ? 'badge-excellent' : (o.status.includes('Late') ? 'badge-poor' : 'badge-review')}">
                ${o.status}
              </span>
            </td>
          </tr>
        `).join("");
      }
    }

    // Complaints table
    const complaintsTbody = document.getElementById("detail-complaints-tbody");
    if (complaintsTbody) {
      if (complaints.length === 0) {
        complaintsTbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No complaints logged. Excellent reliability!</td></tr>`;
      } else {
        complaintsTbody.innerHTML = complaints.map(c => `
          <tr>
            <td>${Calculations.formatDate(c.date)}</td>
            <td style="font-weight: 600;">${c.type}</td>
            <td><span class="badge ${c.severity === 'Critical' || c.severity === 'High' ? 'badge-critical' : 'badge-average'}">${c.severity}</span></td>
            <td>${c.description}</td>
            <td><span class="badge ${c.status === 'Resolved' || c.status === 'Closed' ? 'badge-active' : 'badge-review'}">${c.status}</span></td>
          </tr>
        `).join("");
      }
    }

    // Historical Evaluations Log table
    const evalsTbody = document.getElementById("detail-evals-tbody");
    if (evalsTbody) {
      evalsTbody.innerHTML = evals.map(e => `
        <tr>
          <td>${Calculations.formatDate(e.evaluationDate)}</td>
          <td><strong>${e.overallScore}%</strong></td>
          <td><span class="badge ${Calculations.getRatingTier(e.overallScore).badgeClass}">${e.rating}</span></td>
          <td>Q: ${e.qualityScore}% | D: ${e.deliveryScore}% | C: ${e.costScore}%</td>
          <td>${e.evaluatorName}</td>
          <td style="font-size: 0.8rem; color: var(--text-secondary);">${e.remarks}</td>
        </tr>
      `).join("");
    }
  }

  // =======================================================
  // 5. MULTI-SUPPLIER COMPARISON MATRIX
  // =======================================================
  renderComparisonMatrix() {
    const allSuppliers = window.State.getSuppliersWithLatestMetrics();
    const compIds = window.State.getComparisonSupplierIds();
    const selectedSuppliers = allSuppliers.filter(s => compIds.includes(s.id));

    // Render multi-select pills
    const selectorContainer = document.getElementById("comparison-selector-pills");
    if (selectorContainer) {
      selectorContainer.innerHTML = allSuppliers.map(s => {
        const isSelected = compIds.includes(s.id);
        return `
          <button class="btn ${isSelected ? 'btn-primary' : 'btn-secondary'} btn-sm" 
                  onclick="app.toggleComparisonSupplier(${s.id})"
                  style="border-radius: var(--radius-full);">
            <i class="fa-solid ${isSelected ? 'fa-check' : 'fa-plus'}"></i> ${s.name}
          </button>
        `;
      }).join("");
    }

    if (selectedSuppliers.length === 0) {
      document.getElementById("comparison-content-area").innerHTML = `
        <div class="card" style="text-align: center; padding: 3rem;">
          <i class="fa-solid fa-scale-balanced" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
          <h3>Select suppliers above to start comparison</h3>
          <p style="color: var(--text-muted); margin-top: 0.5rem;">Pick 2 to 4 suppliers to view side-by-side performance matrix and radar charts.</p>
        </div>
      `;
      return;
    }

    // Render Side-by-side Comparative Charts
    setTimeout(() => {
      window.Charts.renderComparisonRadar("chart-comparison-radar", selectedSuppliers);
      window.Charts.renderComparisonBar("chart-comparison-bar", selectedSuppliers);
    }, 50);

    // Calculate Highest/Lowest for highlighting
    const getBest = (getter) => Math.max(...selectedSuppliers.map(s => getter(s) || 0));
    const getWorst = (getter) => Math.min(...selectedSuppliers.map(s => getter(s) || 0));

    const bestOverall = getBest(s => s.overallScore);
    const bestQuality = getBest(s => s.latestEvaluation ? s.latestEvaluation.qualityScore : 0);
    const bestDelivery = getBest(s => s.latestEvaluation ? s.latestEvaluation.deliveryScore : 0);
    const bestCost = getBest(s => s.latestEvaluation ? s.latestEvaluation.costScore : 0);
    const bestReliability = getBest(s => s.latestEvaluation ? s.latestEvaluation.reliabilityScore : 0);
    const bestService = getBest(s => s.latestEvaluation ? s.latestEvaluation.serviceScore : 0);
    const lowestDefect = Math.min(...selectedSuppliers.map(s => s.latestEvaluation ? s.latestEvaluation.defectRate : 99));

    // Comparative Table Matrix
    const tableEl = document.getElementById("comparison-matrix-table");
    if (tableEl) {
      tableEl.innerHTML = `
        <thead>
          <tr>
            <th style="width: 220px;">Evaluation Parameter</th>
            ${selectedSuppliers.map(s => `
              <th style="text-align: center;">
                <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-primary);">${s.name}</div>
                <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">${s.code} • ${s.category}</div>
              </th>
            `).join("")}
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Overall Performance Score</strong></td>
            ${selectedSuppliers.map(s => `
              <td style="text-align: center;" class="${s.overallScore === bestOverall ? 'highlight-best' : ''}">
                <span style="font-size: 1.15rem; font-weight: 800;">${s.overallScore}%</span>
                <div><span class="badge ${s.ratingTier.badgeClass}">${s.rating}</span></div>
              </td>
            `).join("")}
          </tr>
          <tr>
            <td>Quality Score (Weight 30%)</td>
            ${selectedSuppliers.map(s => {
              const val = s.latestEvaluation ? s.latestEvaluation.qualityScore : 0;
              return `<td style="text-align: center;" class="${val === bestQuality ? 'highlight-best' : ''}">${val}%</td>`;
            }).join("")}
          </tr>
          <tr>
            <td>Delivery Score (Weight 25%)</td>
            ${selectedSuppliers.map(s => {
              const val = s.latestEvaluation ? s.latestEvaluation.deliveryScore : 0;
              return `<td style="text-align: center;" class="${val === bestDelivery ? 'highlight-best' : ''}">${val}%</td>`;
            }).join("")}
          </tr>
          <tr>
            <td>Cost Score (Weight 20%)</td>
            ${selectedSuppliers.map(s => {
              const val = s.latestEvaluation ? s.latestEvaluation.costScore : 0;
              return `<td style="text-align: center;" class="${val === bestCost ? 'highlight-best' : ''}">${val}%</td>`;
            }).join("")}
          </tr>
          <tr>
            <td>Reliability Score (Weight 15%)</td>
            ${selectedSuppliers.map(s => {
              const val = s.latestEvaluation ? s.latestEvaluation.reliabilityScore : 0;
              return `<td style="text-align: center;" class="${val === bestReliability ? 'highlight-best' : ''}">${val}%</td>`;
            }).join("")}
          </tr>
          <tr>
            <td>Service Score (Weight 10%)</td>
            ${selectedSuppliers.map(s => {
              const val = s.latestEvaluation ? s.latestEvaluation.serviceScore : 0;
              return `<td style="text-align: center;" class="${val === bestService ? 'highlight-best' : ''}">${val}%</td>`;
            }).join("")}
          </tr>
          <tr>
            <td>Defect Rate % (Lower is Better)</td>
            ${selectedSuppliers.map(s => {
              const val = s.latestEvaluation ? s.latestEvaluation.defectRate : 0;
              return `<td style="text-align: center;" class="${val === lowestDefect ? 'highlight-best' : (val > 3.0 ? 'highlight-worst' : '')}">${val}%</td>`;
            }).join("")}
          </tr>
          <tr>
            <td>On-Time Delivery Rate %</td>
            ${selectedSuppliers.map(s => {
              const val = s.latestEvaluation ? s.latestEvaluation.onTimeDeliveryRate : 0;
              return `<td style="text-align: center;">${val}%</td>`;
            }).join("")}
          </tr>
          <tr>
            <td>Strategic Recommendation</td>
            ${selectedSuppliers.map(s => `
              <td style="font-size: 0.8rem; color: var(--text-secondary); padding: 1rem; line-height: 1.4;">
                ${s.latestEvaluation ? s.latestEvaluation.recommendation : 'No evaluation record.'}
              </td>
            `).join("")}
          </tr>
        </tbody>
      `;
    }
  }

  toggleComparisonSupplier(id) {
    window.State.toggleComparisonSupplier(id);
    this.renderComparisonMatrix();
  }

  // =======================================================
  // 6. REPORTS & ANALYTICS HUB
  // =======================================================
  renderReportsHub() {
    const type = document.getElementById("report-type-select")?.value || "master";
    const category = document.getElementById("report-category-filter")?.value || "All";
    const rating = document.getElementById("report-rating-filter")?.value || "All";

    window.Reports.currentReportType = type;
    window.Reports.currentFilterCategory = category;
    window.Reports.currentFilterRating = rating;

    const data = window.Reports.getReportData(type, category, rating);
    const thead = document.getElementById("reports-table-thead");
    const tbody = document.getElementById("reports-table-tbody");

    if (!thead || !tbody) return;

    if (type === "monthly_trend") {
      thead.innerHTML = `
        <tr>
          <th>Evaluation Date</th>
          <th>Supplier</th>
          <th>Category</th>
          <th>Quality (30%)</th>
          <th>Delivery (25%)</th>
          <th>Cost (20%)</th>
          <th>Reliability (15%)</th>
          <th>Service (10%)</th>
          <th>Overall Score</th>
          <th>Rating</th>
          <th>Evaluator</th>
        </tr>
      `;

      tbody.innerHTML = data.map(ev => `
        <tr>
          <td>${Calculations.formatDate(ev.evaluationDate)}</td>
          <td><strong>${ev.supplierName}</strong> <span style="font-size: 0.75rem; color: var(--text-muted);">(${ev.supplierCode})</span></td>
          <td>${ev.category}</td>
          <td>${ev.qualityScore}%</td>
          <td>${ev.deliveryScore}%</td>
          <td>${ev.costScore}%</td>
          <td>${ev.reliabilityScore}%</td>
          <td>${ev.serviceScore}%</td>
          <td><strong class="score-cell">${ev.overallScore}%</strong></td>
          <td><span class="badge ${Calculations.getRatingTier(ev.overallScore).badgeClass}">${ev.rating}</span></td>
          <td>${ev.evaluatorName}</td>
        </tr>
      `).join("");
    } else {
      thead.innerHTML = `
        <tr>
          <th>Supplier Code</th>
          <th>Supplier Name</th>
          <th>Category</th>
          <th>Location</th>
          <th>Quality (30%)</th>
          <th>Delivery (25%)</th>
          <th>Cost (20%)</th>
          <th>Defect %</th>
          <th>Overall Score</th>
          <th>Rating</th>
          <th>Status</th>
        </tr>
      `;

      tbody.innerHTML = data.map(s => {
        const ev = s.latestEvaluation || {};
        return `
          <tr>
            <td><code>${s.code}</code></td>
            <td><strong>${s.name}</strong></td>
            <td>${s.category}</td>
            <td>${s.city}, ${s.state}</td>
            <td>${ev.qualityScore || 0}%</td>
            <td>${ev.deliveryScore || 0}%</td>
            <td>${ev.costScore || 0}%</td>
            <td>${ev.defectRate || 0}%</td>
            <td><strong class="score-cell" style="color: ${s.ratingTier.color};">${s.overallScore}%</strong></td>
            <td><span class="badge ${s.ratingTier.badgeClass}">${s.rating}</span></td>
            <td><span class="badge ${s.status === 'Active' ? 'badge-active' : 'badge-review'}">${s.status}</span></td>
          </tr>
        `;
      }).join("");
    }
  }

  // =======================================================
  // 7. NOTIFICATIONS CENTER
  // =======================================================
  renderNotificationsCenter() {
    const notifs = window.State.getNotifications();
    const container = document.getElementById("notifications-list-container");
    if (!container) return;

    if (notifs.length === 0) {
      container.innerHTML = `
        <div class="card" style="text-align: center; padding: 3rem;">
          <i class="fa-solid fa-bell-slash" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
          <h3>No notifications at this time</h3>
          <p style="color: var(--text-muted); margin-top: 0.5rem;">All supplier risk triggers and alerts are currently clear.</p>
        </div>
      `;
      return;
    }

    container.innerHTML = notifs.map(n => `
      <div class="card" style="margin-bottom: 0.85rem; border-left: 4px solid ${n.type === 'Critical' ? '#ef4444' : (n.type === 'Warning' ? '#f59e0b' : '#3b82f6')}; opacity: ${n.status === 'Read' ? 0.75 : 1};">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
          <div style="display: flex; gap: 0.85rem;">
            <div style="font-size: 1.25rem; color: ${n.type === 'Critical' ? '#ef4444' : (n.type === 'Warning' ? '#f59e0b' : '#3b82f6')};">
              <i class="fa-solid ${n.type === 'Critical' ? 'fa-triangle-exclamation' : (n.type === 'Warning' ? 'fa-circle-exclamation' : 'fa-circle-info')}"></i>
            </div>
            <div>
              <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.25rem;">
                ${n.title}
                ${n.status === 'Unread' ? '<span class="badge badge-poor" style="font-size: 0.65rem; margin-left: 0.5rem;">NEW</span>' : ''}
              </h4>
              <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.4;">${n.message}</p>
              <span style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem; display: inline-block;">${Calculations.formatDate(n.date)}</span>
            </div>
          </div>
          <div>
            ${n.status === 'Unread' ? `
              <button class="btn btn-secondary btn-sm" onclick="app.markNotificationAsRead(${n.id})">Mark as read</button>
            ` : ''}
            ${n.supplierId ? `
              <button class="btn btn-primary btn-sm" onclick="app.navigate('supplier-detail', ${n.supplierId})">View Supplier</button>
            ` : ''}
          </div>
        </div>
      </div>
    `).join("");
  }

  markNotificationAsRead(id) {
    window.State.markNotificationAsRead(id);
    this.renderNotificationsCenter();
    this.updateNotificationBadge();
  }

  markAllNotificationsRead() {
    window.State.markAllNotificationsAsRead();
    this.renderNotificationsCenter();
    this.updateNotificationBadge();
    this.showToast("All notifications marked as read.", "info");
  }

  updateNotificationBadge() {
    const count = window.State.getUnreadNotificationsCount();
    const badgeEl = document.getElementById("sidebar-notif-badge");
    const headerDot = document.getElementById("header-notif-dot");
    if (badgeEl) {
      badgeEl.textContent = count;
      badgeEl.style.display = count > 0 ? "inline-block" : "none";
    }
    if (headerDot) {
      headerDot.style.display = count > 0 ? "block" : "none";
    }
  }

  // =======================================================
  // 8. ACADEMIC & FINAL YEAR SHOWCASE DELIVERABLES
  // =======================================================
  renderAcademicShowcase() {
    // Academic tab controller initialized
  }

  switchAcademicTab(subTabId) {
    document.querySelectorAll(".academic-subtab-btn").forEach(btn => {
      btn.classList.toggle("active", btn.getAttribute("data-academic-tab") === subTabId);
    });
    document.querySelectorAll(".academic-content-pane").forEach(pane => {
      pane.style.display = pane.id === `academic-pane-${subTabId}` ? "block" : "none";
    });
  }

  copySQLSchema() {
    const codeEl = document.getElementById("sql-schema-code");
    if (codeEl) {
      navigator.clipboard.writeText(codeEl.textContent);
      this.showToast("MySQL Schema copied to clipboard!", "success");
    }
  }

  // =======================================================
  // 9. SYSTEM SETTINGS & WEIGHT CONFIGURATION
  // =======================================================
  renderSettings() {
    const weights = window.State.getWeights();
    const thresholds = window.State.getThresholds();

    document.getElementById("setting-weight-quality").value = Math.round(weights.quality * 100);
    document.getElementById("setting-weight-delivery").value = Math.round(weights.delivery * 100);
    document.getElementById("setting-weight-cost").value = Math.round(weights.cost * 100);
    document.getElementById("setting-weight-reliability").value = Math.round(weights.reliability * 100);
    document.getElementById("setting-weight-service").value = Math.round(weights.service * 100);

    document.getElementById("setting-val-quality").textContent = `${Math.round(weights.quality * 100)}%`;
    document.getElementById("setting-val-delivery").textContent = `${Math.round(weights.delivery * 100)}%`;
    document.getElementById("setting-val-cost").textContent = `${Math.round(weights.cost * 100)}%`;
    document.getElementById("setting-val-reliability").textContent = `${Math.round(weights.reliability * 100)}%`;
    document.getElementById("setting-val-service").textContent = `${Math.round(weights.service * 100)}%`;

    this.checkSettingsWeightsSum();
  }

  checkSettingsWeightsSum() {
    const q = parseInt(document.getElementById("setting-weight-quality")?.value || 30);
    const d = parseInt(document.getElementById("setting-weight-delivery")?.value || 25);
    const c = parseInt(document.getElementById("setting-weight-cost")?.value || 20);
    const r = parseInt(document.getElementById("setting-weight-reliability")?.value || 15);
    const s = parseInt(document.getElementById("setting-weight-service")?.value || 10);

    document.getElementById("setting-val-quality").textContent = `${q}%`;
    document.getElementById("setting-val-delivery").textContent = `${d}%`;
    document.getElementById("setting-val-cost").textContent = `${c}%`;
    document.getElementById("setting-val-reliability").textContent = `${r}%`;
    document.getElementById("setting-val-service").textContent = `${s}%`;

    const sum = q + d + c + r + s;
    const sumEl = document.getElementById("setting-total-weight-indicator");
    if (sumEl) {
      sumEl.textContent = `Total Weight: ${sum}%`;
      sumEl.style.color = sum === 100 ? "#10b981" : "#ef4444";
    }
  }

  saveSettingsForm(e) {
    e.preventDefault();
    const q = parseInt(document.getElementById("setting-weight-quality").value);
    const d = parseInt(document.getElementById("setting-weight-delivery").value);
    const c = parseInt(document.getElementById("setting-weight-cost").value);
    const r = parseInt(document.getElementById("setting-weight-reliability").value);
    const s = parseInt(document.getElementById("setting-weight-service").value);

    if (q + d + c + r + s !== 100) {
      alert(`The sum of weights must equal exactly 100%. Current sum is ${q + d + c + r + s}%.`);
      return;
    }

    window.State.updateSettings({
      weights: {
        quality: q / 100,
        delivery: d / 100,
        cost: c / 100,
        reliability: r / 100,
        service: s / 100
      }
    });

    this.showToast("Weight formula updated and all supplier scores re-calculated!", "success");
  }

  // --- Role Indicator & Switcher ---
  updateRoleIndicator() {
    const user = window.State.getCurrentUser();
    const select = document.getElementById("header-role-select");
    if (select) select.value = user.role;

    const sidebarName = document.getElementById("sidebar-user-name");
    const sidebarRole = document.getElementById("sidebar-user-role");
    if (sidebarName) sidebarName.textContent = user.name;
    if (sidebarRole) sidebarRole.textContent = `${user.role} • ${user.department}`;
  }

  onRoleChange(role) {
    window.State.switchRole(role);
    this.showToast(`Switched active profile to ${role} role`, "info");
    this.renderCurrentView();
  }

  // --- Modals Management ---
  openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add("active");
  }

  closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove("active");
  }

  openAddSupplierModal() {
    document.getElementById("supplier-modal-title").textContent = "Add New Supplier";
    document.getElementById("supplier-form").reset();
    document.getElementById("supplier-form-id").value = "";
    document.getElementById("supplier-form-code").value = `SUP-${Math.floor(100 + Math.random() * 900)}`;
    this.openModal("modal-supplier");
  }

  openEditSupplierModal(supplierId) {
    const supplier = window.State.getSupplierById(supplierId);
    if (!supplier) return;

    document.getElementById("supplier-modal-title").textContent = "Edit Supplier Details";
    document.getElementById("supplier-form-id").value = supplier.id;
    document.getElementById("supplier-form-code").value = supplier.code;
    document.getElementById("supplier-form-name").value = supplier.name;
    document.getElementById("supplier-form-company").value = supplier.companyName;
    document.getElementById("supplier-form-email").value = supplier.email;
    document.getElementById("supplier-form-phone").value = supplier.phone;
    document.getElementById("supplier-form-category").value = supplier.category;
    document.getElementById("supplier-form-city").value = supplier.city;
    document.getElementById("supplier-form-state").value = supplier.state;
    document.getElementById("supplier-form-address").value = supplier.address;
    document.getElementById("supplier-form-products").value = supplier.productsSupplied || "";
    document.getElementById("supplier-form-reg").value = supplier.registrationNumber;
    document.getElementById("supplier-form-start").value = supplier.contractStartDate;
    document.getElementById("supplier-form-end").value = supplier.contractEndDate;
    document.getElementById("supplier-form-status").value = supplier.status;

    this.openModal("modal-supplier");
  }

  saveSupplierForm(e) {
    e.preventDefault();
    const id = document.getElementById("supplier-form-id").value;
    const data = {
      code: document.getElementById("supplier-form-code").value,
      name: document.getElementById("supplier-form-name").value,
      companyName: document.getElementById("supplier-form-company").value,
      email: document.getElementById("supplier-form-email").value,
      phone: document.getElementById("supplier-form-phone").value,
      category: document.getElementById("supplier-form-category").value,
      city: document.getElementById("supplier-form-city").value,
      state: document.getElementById("supplier-form-state").value,
      address: document.getElementById("supplier-form-address").value,
      productsSupplied: document.getElementById("supplier-form-products").value,
      registrationNumber: document.getElementById("supplier-form-reg").value,
      contractStartDate: document.getElementById("supplier-form-start").value,
      contractEndDate: document.getElementById("supplier-form-end").value,
      status: document.getElementById("supplier-form-status").value
    };

    if (id) {
      window.State.updateSupplier(id, data);
      this.showToast("Supplier details updated successfully.", "success");
    } else {
      window.State.addSupplier(data);
      this.showToast("New supplier registered successfully.", "success");
    }

    this.closeModal("modal-supplier");
    this.renderSuppliersList();
  }

  deleteSupplier(id) {
    const s = window.State.getSupplierById(id);
    if (!s) return;
    if (confirm(`Are you sure you want to delete supplier "${s.name}" (${s.code}) and all associated records?`)) {
      window.State.deleteSupplier(id);
      this.showToast("Supplier removed from database.", "info");
      this.renderSuppliersList();
    }
  }

  openEvaluationModal(supplierId = null) {
    const selectEl = document.getElementById("eval-form-supplier-select");
    const suppliers = window.State.getSuppliers();
    if (selectEl) {
      selectEl.innerHTML = suppliers.map(s => `<option value="${s.id}" ${s.id === Number(supplierId) ? 'selected' : ''}>${s.code} - ${s.name} (${s.category})</option>`).join("");
    }
    this.updateLiveEvaluationPreview();
    this.openModal("modal-evaluation");
  }

  // --- Toast Notifications ---
  showToast(message, type = "info") {
    const container = document.getElementById("toast-container");
    if (!container) return;

    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;

    let icon = "fa-circle-info";
    let color = "#3b82f6";
    if (type === "success") { icon = "fa-circle-check"; color = "#10b981"; }
    else if (type === "error") { icon = "fa-circle-xmark"; color = "#ef4444"; }
    else if (type === "warning") { icon = "fa-triangle-exclamation"; color = "#f59e0b"; }

    toast.innerHTML = `
      <i class="fa-solid ${icon}" style="color: ${color}; font-size: 1.15rem;"></i>
      <span style="font-weight: 600; font-size: 0.85rem;">${message}</span>
    `;

    container.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = "0";
      toast.style.transform = "translateX(100%)";
      toast.style.transition = "all 0.3s ease";
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  }

  // --- Global Event Binding ---
  bindEvents() {
    // Navigation items
    document.querySelectorAll(".nav-item").forEach(item => {
      item.addEventListener("click", (e) => {
        e.preventDefault();
        const target = item.getAttribute("data-target");
        if (target) this.navigate(target);
      });
    });

    // Mobile menu toggle
    document.getElementById("mobile-menu-btn")?.addEventListener("click", () => {
      document.getElementById("sidebar")?.classList.toggle("mobile-open");
    });

    // Theme toggle
    document.getElementById("theme-toggle-btn")?.addEventListener("click", () => {
      this.toggleTheme();
    });

    // Role switcher
    document.getElementById("header-role-select")?.addEventListener("change", (e) => {
      this.onRoleChange(e.target.value);
    });

    // Supplier search & filters
    document.getElementById("supplier-search-input")?.addEventListener("input", () => this.renderSuppliersList());
    document.getElementById("supplier-category-filter")?.addEventListener("change", () => this.renderSuppliersList());
    document.getElementById("supplier-rating-filter")?.addEventListener("change", () => this.renderSuppliersList());
    document.getElementById("supplier-status-filter")?.addEventListener("change", () => this.renderSuppliersList());

    // Supplier view mode toggle
    document.getElementById("btn-view-table")?.addEventListener("click", () => {
      this.supplierViewMode = "table";
      document.getElementById("btn-view-table")?.classList.add("btn-primary");
      document.getElementById("btn-view-table")?.classList.remove("btn-secondary");
      document.getElementById("btn-view-grid")?.classList.remove("btn-primary");
      document.getElementById("btn-view-grid")?.classList.add("btn-secondary");
      this.renderSuppliersList();
    });

    document.getElementById("btn-view-grid")?.addEventListener("click", () => {
      this.supplierViewMode = "grid";
      document.getElementById("btn-view-grid")?.classList.add("btn-primary");
      document.getElementById("btn-view-grid")?.classList.remove("btn-secondary");
      document.getElementById("btn-view-table")?.classList.remove("btn-primary");
      document.getElementById("btn-view-table")?.classList.add("btn-secondary");
      this.renderSuppliersList();
    });

    // Supplier form
    document.getElementById("supplier-form")?.addEventListener("submit", (e) => this.saveSupplierForm(e));

    // Evaluation live sliders
    const evalSliders = ["eval-input-quality", "eval-input-delivery", "eval-input-cost", "eval-input-reliability", "eval-input-service", "eval-input-defect", "eval-input-ontime"];
    evalSliders.forEach(id => {
      document.getElementById(id)?.addEventListener("input", () => this.updateLiveEvaluationPreview());
    });

    // Evaluation form submission
    document.getElementById("evaluation-form")?.addEventListener("submit", (e) => this.submitEvaluationForm(e));

    // Settings weight sliders
    const weightSliders = ["setting-weight-quality", "setting-weight-delivery", "setting-weight-cost", "setting-weight-reliability", "setting-weight-service"];
    weightSliders.forEach(id => {
      document.getElementById(id)?.addEventListener("input", () => this.checkSettingsWeightsSum());
    });

    document.getElementById("settings-form")?.addEventListener("submit", (e) => this.saveSettingsForm(e));

    // Report filters
    document.getElementById("report-type-select")?.addEventListener("change", () => this.renderReportsHub());
    document.getElementById("report-category-filter")?.addEventListener("change", () => this.renderReportsHub());
    document.getElementById("report-rating-filter")?.addEventListener("change", () => this.renderReportsHub());

    // Export buttons
    document.getElementById("btn-export-csv")?.addEventListener("click", () => {
      window.Reports.exportToCSV(window.Reports.currentReportType);
    });

    document.getElementById("btn-export-print")?.addEventListener("click", () => {
      window.Reports.printReport();
    });

    document.getElementById("btn-backup-json")?.addEventListener("click", () => {
      window.Reports.exportDatabaseJSON();
      this.showToast("Database JSON backup generated.", "success");
    });

    document.getElementById("btn-reset-data")?.addEventListener("click", () => {
      if (confirm("Reset application data back to original factory demo state? All custom entries will be restored to defaults.")) {
        window.State.resetToDefault();
        this.showToast("Database reset to demo state.", "info");
        location.reload();
      }
    });

    // Global quick search
    document.getElementById("global-search-input")?.addEventListener("keyup", (e) => {
      if (e.key === "Enter") {
        const val = e.target.value;
        this.navigate("suppliers");
        const supInput = document.getElementById("supplier-search-input");
        if (supInput) {
          supInput.value = val;
          this.renderSuppliersList();
        }
      }
    });
  }

  bindGlobalShortcuts() {
    window.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        document.querySelectorAll(".modal-overlay.active").forEach(m => m.classList.remove("active"));
      }
    });
  }
}

// Global Application Instance
window.app = new AppController();
document.addEventListener("DOMContentLoaded", () => {
  window.app.init();
});
