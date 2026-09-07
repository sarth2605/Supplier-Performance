/**
 * Supplier Performance Analysis System
 * Reports Studio, Data Table Generator & PDF/CSV Exporter
 */

class ReportManager {
  constructor() {
    this.currentReportType = "master";
    this.currentFilterCategory = "All";
    this.currentFilterRating = "All";
  }

  getReportData(reportType, category = "All", rating = "All") {
    let suppliers = window.State.getSuppliersWithLatestMetrics();

    // Filter by category
    if (category && category !== "All") {
      suppliers = suppliers.filter(s => s.category.toLowerCase() === category.toLowerCase());
    }

    // Filter by rating
    if (rating && rating !== "All") {
      suppliers = suppliers.filter(s => s.rating.toLowerCase() === rating.toLowerCase());
    }

    switch (reportType) {
      case "top_performers":
        return suppliers
          .filter(s => s.overallScore >= 80)
          .sort((a, b) => b.overallScore - a.overallScore);

      case "critical_suppliers":
        return suppliers
          .filter(s => s.overallScore < 70 || (s.latestEvaluation && s.latestEvaluation.defectRate > 3.0))
          .sort((a, b) => a.overallScore - b.overallScore);

      case "monthly_trend":
        return window.State.getEvaluations()
          .map(ev => {
            const sup = window.State.getSupplierById(ev.supplierId);
            return {
              ...ev,
              supplierName: sup ? sup.name : "Unknown",
              supplierCode: sup ? sup.code : "N/A",
              category: sup ? sup.category : "N/A"
            };
          })
          .sort((a, b) => new Date(b.evaluationDate) - new Date(a.evaluationDate));

      case "comparison":
        const compIds = window.State.getComparisonSupplierIds();
        return suppliers.filter(s => compIds.includes(s.id));

      case "master":
      default:
        return [...suppliers].sort((a, b) => b.overallScore - a.overallScore);
    }
  }

  // --- CSV Export Generation ---
  exportToCSV(reportType = "master") {
    const data = this.getReportData(reportType, this.currentFilterCategory, this.currentFilterRating);
    if (!data || data.length === 0) {
      alert("No data available to export.");
      return;
    }

    let csvContent = "";
    const filename = `Supplier_Report_${reportType}_${new Date().toISOString().split("T")[0]}.csv`;

    if (reportType === "monthly_trend") {
      const headers = ["Evaluation ID", "Supplier Code", "Supplier Name", "Category", "Date", "Quality (30%)", "Delivery (25%)", "Cost (20%)", "Reliability (15%)", "Service (10%)", "Defect Rate %", "On-Time %", "Overall Score", "Rating Tier", "Evaluator"];
      csvContent += headers.map(h => `"${h}"`).join(",") + "\n";

      data.forEach(ev => {
        const row = [
          ev.id,
          ev.supplierCode,
          ev.supplierName,
          ev.category,
          ev.evaluationDate,
          ev.qualityScore,
          ev.deliveryScore,
          ev.costScore,
          ev.reliabilityScore,
          ev.serviceScore,
          ev.defectRate,
          ev.onTimeDeliveryRate,
          ev.overallScore,
          ev.rating,
          ev.evaluatorName
        ];
        csvContent += row.map(val => `"${val || ''}"`).join(",") + "\n";
      });
    } else {
      const headers = [
        "Supplier Code", "Supplier Name", "Company Name", "Category", "City", "State", 
        "Quality (30%)", "Delivery (25%)", "Cost (20%)", "Reliability (15%)", "Service (10%)", 
        "Defect Rate %", "On-Time %", "Overall Score %", "Rating Tier", "Status", "Contract End"
      ];
      csvContent += headers.map(h => `"${h}"`).join(",") + "\n";

      data.forEach(s => {
        const ev = s.latestEvaluation || {};
        const row = [
          s.code,
          s.name,
          s.companyName,
          s.category,
          s.city,
          s.state,
          ev.qualityScore || 0,
          ev.deliveryScore || 0,
          ev.costScore || 0,
          ev.reliabilityScore || 0,
          ev.serviceScore || 0,
          ev.defectRate || 0,
          ev.onTimeDeliveryRate || 0,
          s.overallScore,
          s.rating,
          s.status,
          s.contractEndDate
        ];
        csvContent += row.map(val => `"${val || ''}"`).join(",") + "\n";
      });
    }

    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", filename);
    link.style.visibility = "hidden";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  // --- Print / Styled PDF Preview ---
  printReport() {
    window.print();
  }

  // --- Backup JSON Data ---
  exportDatabaseJSON() {
    const state = window.State.loadState();
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(state, null, 2));
    const downloadAnchor = document.createElement("a");
    downloadAnchor.setAttribute("href", dataStr);
    downloadAnchor.setAttribute("download", `SPAS_Database_Backup_${new Date().toISOString().split("T")[0]}.json`);
    document.body.appendChild(downloadAnchor);
    downloadAnchor.click();
    downloadAnchor.remove();
  }
}

// Global Report manager singleton
window.Reports = new ReportManager();
