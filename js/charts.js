/**
 * Supplier Performance Analysis System
 * Chart.js Visualizations & Dashboard Analytics Controller
 */

class ChartManager {
  constructor() {
    this.instances = {};
  }

  // Safely destroy existing chart instance on a canvas
  destroyChart(canvasId) {
    if (this.instances[canvasId]) {
      try {
        this.instances[canvasId].destroy();
      } catch (e) {
        console.warn(`Error destroying chart on canvas ${canvasId}:`, e);
      }
      delete this.instances[canvasId];
    }
  }

  getThemeColors() {
    const isDark = document.documentElement.getAttribute("data-theme") === "dark";
    return {
      textColor: isDark ? "#94a3b8" : "#64748b",
      gridColor: isDark ? "rgba(255, 255, 255, 0.08)" : "rgba(0, 0, 0, 0.06)",
      tooltipBg: isDark ? "#1e293b" : "#0f172a",
      tooltipText: "#ffffff",
      palette: [
        "#3b82f6", // Blue
        "#10b981", // Emerald
        "#8b5cf6", // Purple
        "#f59e0b", // Amber
        "#ec4899", // Pink
        "#06b6d4"  // Cyan
      ]
    };
  }

  // --- 1. Dashboard: Overall Score Bar Chart ---
  renderDashboardScores(canvasId = "chart-dashboard-scores") {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    this.destroyChart(canvasId);

    const suppliers = window.State.getSuppliersWithLatestMetrics();
    const rated = suppliers.filter(s => s.overallScore > 0).sort((a, b) => b.overallScore - a.overallScore);
    const theme = this.getThemeColors();

    const labels = rated.map(s => s.name.length > 16 ? s.name.substring(0, 15) + "…" : s.name);
    const data = rated.map(s => s.overallScore);
    const bgColors = rated.map(s => s.ratingTier.color);

    const ctx = canvas.getContext("2d");
    this.instances[canvasId] = new Chart(ctx, {
      type: "bar",
      data: {
        labels: labels,
        datasets: [{
          label: "Overall Score (%)",
          data: data,
          backgroundColor: bgColors,
          borderRadius: 6,
          borderSkipped: false,
          maxBarThickness: 36
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: theme.tooltipBg,
            titleColor: theme.tooltipText,
            bodyColor: theme.tooltipText,
            padding: 12,
            cornerRadius: 8,
            callbacks: {
              label: (ctx) => `Score: ${ctx.parsed.y}% (${rated[ctx.dataIndex].rating})`
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: theme.textColor, font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" } }
          },
          y: {
            min: 0,
            max: 100,
            grid: { color: theme.gridColor },
            ticks: {
              color: theme.textColor,
              stepSize: 20,
              callback: (val) => `${val}%`
            }
          }
        }
      }
    });
  }

  // --- 2. Dashboard: Monthly Performance Trend Line Chart ---
  renderDashboardMonthlyTrend(canvasId = "chart-dashboard-trend") {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    this.destroyChart(canvasId);

    const theme = this.getThemeColors();
    const months = ["Dec", "Jan", "Feb", "Mar", "Apr", "May"];

    // Aggregated historical averages
    const qualityTrend = [84.2, 85.0, 86.8, 87.5, 88.0, 89.2];
    const deliveryTrend = [82.5, 83.1, 85.4, 86.0, 87.2, 88.5];
    const overallTrend = [81.8, 82.7, 84.6, 85.3, 86.1, 87.8];

    const ctx = canvas.getContext("2d");
    this.instances[canvasId] = new Chart(ctx, {
      type: "line",
      data: {
        labels: months,
        datasets: [
          {
            label: "Overall Avg",
            data: overallTrend,
            borderColor: "#3b82f6",
            backgroundColor: "rgba(59, 130, 246, 0.12)",
            fill: true,
            tension: 0.35,
            borderWidth: 3,
            pointBackgroundColor: "#3b82f6",
            pointRadius: 4,
            pointHoverRadius: 6
          },
          {
            label: "Quality",
            data: qualityTrend,
            borderColor: "#10b981",
            borderDash: [4, 4],
            tension: 0.35,
            borderWidth: 2,
            pointBackgroundColor: "#10b981",
            pointRadius: 3
          },
          {
            label: "Delivery",
            data: deliveryTrend,
            borderColor: "#8b5cf6",
            borderDash: [4, 4],
            tension: 0.35,
            borderWidth: 2,
            pointBackgroundColor: "#8b5cf6",
            pointRadius: 3
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: "index", intersect: false },
        plugins: {
          legend: {
            position: "top",
            labels: { color: theme.textColor, boxWidth: 12, usePointStyle: true, font: { size: 12 } }
          },
          tooltip: {
            backgroundColor: theme.tooltipBg,
            titleColor: theme.tooltipText,
            bodyColor: theme.tooltipText,
            padding: 12,
            cornerRadius: 8
          }
        },
        scales: {
          x: {
            grid: { color: theme.gridColor },
            ticks: { color: theme.textColor }
          },
          y: {
            min: 70,
            max: 100,
            grid: { color: theme.gridColor },
            ticks: {
              color: theme.textColor,
              callback: (v) => `${v}%`
            }
          }
        }
      }
    });
  }

  // --- 3. Dashboard: Average System Parameter Radar ---
  renderDashboardRadar(canvasId = "chart-dashboard-radar") {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    this.destroyChart(canvasId);

    const theme = this.getThemeColors();
    const evals = window.State.getEvaluations();

    let avgQ = 0, avgD = 0, avgC = 0, avgR = 0, avgS = 0;
    if (evals.length > 0) {
      avgQ = (evals.reduce((a, e) => a + e.qualityScore, 0) / evals.length).toFixed(1);
      avgD = (evals.reduce((a, e) => a + e.deliveryScore, 0) / evals.length).toFixed(1);
      avgC = (evals.reduce((a, e) => a + e.costScore, 0) / evals.length).toFixed(1);
      avgR = (evals.reduce((a, e) => a + e.reliabilityScore, 0) / evals.length).toFixed(1);
      avgS = (evals.reduce((a, e) => a + e.serviceScore, 0) / evals.length).toFixed(1);
    }

    const ctx = canvas.getContext("2d");
    this.instances[canvasId] = new Chart(ctx, {
      type: "radar",
      data: {
        labels: ["Quality (30%)", "Delivery (25%)", "Cost (20%)", "Reliability (15%)", "Service (10%)"],
        datasets: [{
          label: "Network Average",
          data: [avgQ, avgD, avgC, avgR, avgS],
          backgroundColor: "rgba(99, 102, 241, 0.22)",
          borderColor: "#6366f1",
          borderWidth: 2,
          pointBackgroundColor: "#6366f1",
          pointRadius: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: theme.tooltipBg,
            padding: 10,
            cornerRadius: 8
          }
        },
        scales: {
          r: {
            min: 50,
            max: 100,
            ticks: { display: false, stepSize: 10 },
            grid: { color: theme.gridColor },
            angleLines: { color: theme.gridColor },
            pointLabels: {
              color: theme.textColor,
              font: { size: 11, weight: "600", family: "'Plus Jakarta Sans', sans-serif" }
            }
          }
        }
      }
    });
  }

  // --- 4. Dashboard: Defect vs Delivery Scatter Chart ---
  renderDashboardScatter(canvasId = "chart-dashboard-scatter") {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    this.destroyChart(canvasId);

    const theme = this.getThemeColors();
    const suppliers = window.State.getSuppliersWithLatestMetrics();
    const rated = suppliers.filter(s => s.latestEvaluation);

    const scatterData = rated.map(s => ({
      x: s.latestEvaluation.defectRate,
      y: s.latestEvaluation.onTimeDeliveryRate,
      supplierName: s.name,
      ratingTier: s.ratingTier
    }));

    const ctx = canvas.getContext("2d");
    this.instances[canvasId] = new Chart(ctx, {
      type: "scatter",
      data: {
        datasets: [{
          label: "Suppliers",
          data: scatterData,
          backgroundColor: scatterData.map(d => d.ratingTier.color),
          pointRadius: 7,
          pointHoverRadius: 9
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: theme.tooltipBg,
            titleColor: theme.tooltipText,
            bodyColor: theme.tooltipText,
            padding: 12,
            cornerRadius: 8,
            callbacks: {
              title: (ctx) => ctx[0].raw.supplierName,
              label: (ctx) => [
                `Defect Rate: ${ctx.raw.x}%`,
                `On-Time Delivery: ${ctx.raw.y}%`
              ]
            }
          }
        },
        scales: {
          x: {
            title: { display: true, text: "Defect Rate (%) [Lower is Better]", color: theme.textColor, font: { size: 11 } },
            grid: { color: theme.gridColor },
            ticks: { color: theme.textColor, callback: (v) => `${v}%` }
          },
          y: {
            title: { display: true, text: "On-Time Delivery (%) [Higher is Better]", color: theme.textColor, font: { size: 11 } },
            min: 50,
            max: 100,
            grid: { color: theme.gridColor },
            ticks: { color: theme.textColor, callback: (v) => `${v}%` }
          }
        }
      }
    });
  }

  // --- 5. Supplier Detail: Parameter Radar Chart ---
  renderSupplierRadar(canvasId, evaluation) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !evaluation) return;
    this.destroyChart(canvasId);

    const theme = this.getThemeColors();
    const ctx = canvas.getContext("2d");

    this.instances[canvasId] = new Chart(ctx, {
      type: "radar",
      data: {
        labels: ["Quality", "Delivery", "Cost", "Reliability", "Service"],
        datasets: [{
          label: "Score",
          data: [
            evaluation.qualityScore,
            evaluation.deliveryScore,
            evaluation.costScore,
            evaluation.reliabilityScore,
            evaluation.serviceScore
          ],
          backgroundColor: "rgba(59, 130, 246, 0.25)",
          borderColor: "#3b82f6",
          borderWidth: 2.5,
          pointBackgroundColor: "#3b82f6",
          pointRadius: 5
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: theme.tooltipBg,
            padding: 10,
            cornerRadius: 8
          }
        },
        scales: {
          r: {
            min: 0,
            max: 100,
            ticks: { display: true, stepSize: 20, color: theme.textColor, backdropColor: "transparent" },
            grid: { color: theme.gridColor },
            angleLines: { color: theme.gridColor },
            pointLabels: {
              color: theme.textColor,
              font: { size: 12, weight: "bold", family: "'Plus Jakarta Sans', sans-serif" }
            }
          }
        }
      }
    });
  }

  // --- 6. Supplier Detail: Historical Score Trajectory ---
  renderSupplierHistoryTrend(canvasId, evaluations) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !evaluations || evaluations.length === 0) return;
    this.destroyChart(canvasId);

    const theme = this.getThemeColors();
    const sorted = [...evaluations].sort((a, b) => new Date(a.evaluationDate) - new Date(b.evaluationDate));
    const labels = sorted.map(e => Calculations.formatDate(e.evaluationDate));
    const data = sorted.map(e => e.overallScore);

    const ctx = canvas.getContext("2d");
    this.instances[canvasId] = new Chart(ctx, {
      type: "line",
      data: {
        labels: labels,
        datasets: [{
          label: "Overall Score (%)",
          data: data,
          borderColor: "#10b981",
          backgroundColor: "rgba(16, 185, 129, 0.12)",
          fill: true,
          tension: 0.3,
          borderWidth: 3,
          pointBackgroundColor: "#10b981",
          pointRadius: 5,
          pointHoverRadius: 7
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: theme.tooltipBg,
            padding: 12,
            cornerRadius: 8
          }
        },
        scales: {
          x: { grid: { color: theme.gridColor }, ticks: { color: theme.textColor } },
          y: {
            min: Math.max(0, Math.min(...data) - 10),
            max: 100,
            grid: { color: theme.gridColor },
            ticks: { color: theme.textColor, callback: (v) => `${v}%` }
          }
        }
      }
    });
  }

  // --- 7. Comparison: Overlaid Radar & Grouped Bar Charts ---
  renderComparisonRadar(canvasId, suppliersList) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !suppliersList || suppliersList.length === 0) return;
    this.destroyChart(canvasId);

    const theme = this.getThemeColors();
    const colors = [
      { border: "#3b82f6", bg: "rgba(59, 130, 246, 0.2)" },
      { border: "#10b981", bg: "rgba(16, 185, 129, 0.2)" },
      { border: "#f59e0b", bg: "rgba(245, 158, 11, 0.2)" },
      { border: "#ec4899", bg: "rgba(236, 72, 153, 0.2)" }
    ];

    const datasets = suppliersList.map((sup, idx) => {
      const ev = sup.latestEvaluation || { qualityScore: 0, deliveryScore: 0, costScore: 0, reliabilityScore: 0, serviceScore: 0 };
      const c = colors[idx % colors.length];
      return {
        label: sup.name,
        data: [ev.qualityScore, ev.deliveryScore, ev.costScore, ev.reliabilityScore, ev.serviceScore],
        borderColor: c.border,
        backgroundColor: c.bg,
        borderWidth: 2.5,
        pointBackgroundColor: c.border,
        pointRadius: 4
      };
    });

    const ctx = canvas.getContext("2d");
    this.instances[canvasId] = new Chart(ctx, {
      type: "radar",
      data: {
        labels: ["Quality", "Delivery", "Cost", "Reliability", "Service"],
        datasets: datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "top",
            labels: { color: theme.textColor, font: { size: 12 } }
          },
          tooltip: {
            backgroundColor: theme.tooltipBg,
            padding: 12,
            cornerRadius: 8
          }
        },
        scales: {
          r: {
            min: 0,
            max: 100,
            grid: { color: theme.gridColor },
            angleLines: { color: theme.gridColor },
            pointLabels: { color: theme.textColor, font: { size: 12, weight: "600" } }
          }
        }
      }
    });
  }

  renderComparisonBar(canvasId, suppliersList) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !suppliersList || suppliersList.length === 0) return;
    this.destroyChart(canvasId);

    const theme = this.getThemeColors();
    const colors = ["#3b82f6", "#10b981", "#f59e0b", "#ec4899"];

    const datasets = suppliersList.map((sup, idx) => {
      const ev = sup.latestEvaluation || { qualityScore: 0, deliveryScore: 0, costScore: 0, reliabilityScore: 0, serviceScore: 0, overallScore: 0 };
      return {
        label: sup.name,
        data: [ev.qualityScore, ev.deliveryScore, ev.costScore, ev.reliabilityScore, ev.serviceScore, ev.overallScore],
        backgroundColor: colors[idx % colors.length],
        borderRadius: 4
      };
    });

    const ctx = canvas.getContext("2d");
    this.instances[canvasId] = new Chart(ctx, {
      type: "bar",
      data: {
        labels: ["Quality", "Delivery", "Cost", "Reliability", "Service", "Overall Score"],
        datasets: datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "top",
            labels: { color: theme.textColor, font: { size: 12 } }
          },
          tooltip: {
            backgroundColor: theme.tooltipBg,
            padding: 12,
            cornerRadius: 8
          }
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: theme.textColor } },
          y: {
            min: 0,
            max: 100,
            grid: { color: theme.gridColor },
            ticks: { color: theme.textColor, callback: (v) => `${v}%` }
          }
        }
      }
    });
  }

  // Refresh all active charts when theme changes
  refreshAllCharts() {
    this.renderDashboardScores();
    this.renderDashboardMonthlyTrend();
    this.renderDashboardRadar();
    this.renderDashboardScatter();
  }
}

// Global chart manager singleton
window.Charts = new ChartManager();
