/**
 * Supplier Performance Analysis System
 * Calculations, Weight Formulations, Rating Tiers & Recommendation Engine
 */

const Calculations = {
  /**
   * Calculates overall weighted score using configured weights.
   * Formula: (Quality * W_q) + (Delivery * W_d) + (Cost * W_c) + (Reliability * W_r) + (Service * W_s)
   */
  calculateOverallScore(quality, delivery, cost, reliability, service, weights = null) {
    const w = weights || (window.State ? window.State.getWeights() : DEFAULT_SETTINGS.weights);
    const q = parseFloat(quality) || 0;
    const d = parseFloat(delivery) || 0;
    const c = parseFloat(cost) || 0;
    const r = parseFloat(reliability) || 0;
    const s = parseFloat(service) || 0;

    const totalScore = (q * w.quality) + (d * w.delivery) + (c * w.cost) + (r * w.reliability) + (s * w.service);
    return Math.min(100, Math.max(0, Math.round(totalScore * 100) / 100));
  },

  /**
   * Determines the rating tier based on overall score.
   */
  getRatingTier(score, thresholds = null) {
    const t = thresholds || (window.State ? window.State.getThresholds() : DEFAULT_SETTINGS.thresholds);
    const numScore = parseFloat(score) || 0;

    if (numScore >= t.excellent) {
      return {
        tier: "Excellent",
        badgeClass: "badge-excellent",
        color: "#10b981", // Emerald green
        bgColor: "rgba(16, 185, 129, 0.15)",
        description: "Optimal performance exceeding all benchmarks"
      };
    } else if (numScore >= t.good) {
      return {
        tier: "Good",
        badgeClass: "badge-good",
        color: "#3b82f6", // Blue
        bgColor: "rgba(59, 130, 246, 0.15)",
        description: "Reliable performance meeting core standards"
      };
    } else if (numScore >= t.average) {
      return {
        tier: "Average",
        badgeClass: "badge-average",
        color: "#f59e0b", // Amber
        bgColor: "rgba(245, 158, 11, 0.15)",
        description: "Acceptable performance with notable room for improvement"
      };
    } else if (numScore >= t.poor) {
      return {
        tier: "Poor",
        badgeClass: "badge-poor",
        color: "#f97316", // Orange
        bgColor: "rgba(249, 115, 22, 0.15)",
        description: "Sub-par performance requiring formal corrective action"
      };
    } else {
      return {
        tier: "Critical",
        badgeClass: "badge-critical",
        color: "#ef4444", // Red
        bgColor: "rgba(239, 68, 68, 0.15)",
        description: "Unacceptable risk level; recommended for decommission"
      };
    }
  },

  /**
   * Generates automated recommendations, strengths, and weaknesses from evaluation metrics.
   */
  generateAnalyticalInsights(params) {
    const q = parseFloat(params.qualityScore) || 0;
    const d = parseFloat(params.deliveryScore) || 0;
    const c = parseFloat(params.costScore) || 0;
    const r = parseFloat(params.reliabilityScore) || 0;
    const s = parseFloat(params.serviceScore) || 0;
    const defect = parseFloat(params.defectRate) || 0;
    const onTime = parseFloat(params.onTimeDeliveryRate) || 0;
    const overall = parseFloat(params.overallScore) || this.calculateOverallScore(q, d, c, r, s);

    const strengths = [];
    const weaknesses = [];
    const risks = [];

    // Quality check
    if (q >= 90) strengths.push("Superior quality assurance standards with minimal batch deviations.");
    else if (q < 75) weaknesses.push(`Quality score (${q}%) is subpar; inspection failure rate elevated.`);

    // Delivery check
    if (d >= 90) strengths.push("Exceptional dispatch adherence and fulfillment punctuality.");
    else if (d < 75) weaknesses.push(`Delivery adherence (${d}%) experiences recurrent scheduling friction.`);

    // Cost check
    if (c >= 90) strengths.push("Highly competitive unit pricing and favorable procurement margins.");
    else if (c < 75) weaknesses.push("Higher cost structure compared to category industry benchmarks.");

    // Reliability check
    if (r >= 90) strengths.push("Consistent process repeatability and resilient supply continuity.");
    else if (r < 70) weaknesses.push("Fluctuating lead-times and intermittent supply availability.");

    // Service check
    if (s >= 90) strengths.push("Highly proactive client support and rapid escalation resolution.");
    else if (s < 70) weaknesses.push("Customer service responsiveness requires improvement.");

    // Defect & On-time
    if (defect > 3.0) risks.push(`Defect rate of ${defect}% exceeds the 3.0% maximum allowable threshold.`);
    if (onTime < 85.0) risks.push(`On-time delivery rate (${onTime}%) falls below target 85% SLA.`);

    // Fallbacks if empty
    if (strengths.length === 0) strengths.push("Stable general operations maintaining standard baseline requirements.");
    if (weaknesses.length === 0) weaknesses.push("No acute operational weaknesses detected in current evaluation cycle.");

    // Strategic Action Recommendation Synthesis
    let strategicRecommendation = "";
    if (overall >= 90) {
      strategicRecommendation = `Strategic Tier-1 Partner. Priority candidate for volume expansion and long-term contract renewal. Overall rating is ${overall}%.`;
    } else if (overall >= 80) {
      if (q > d) {
        strategicRecommendation = `Supplier demonstrates excellent quality (${q}%), but delivery timelines (${d}%) require tighter monitoring. Suitable for core operations.`;
      } else if (d > q) {
        strategicRecommendation = `Strong delivery performance (${d}%), however quality consistency (${q}%) should be closely audited in upcoming quarters.`;
      } else {
        strategicRecommendation = `Dependable performance across key parameters. Maintain existing order volumes with routine bi-quarterly reviews.`;
      }
    } else if (overall >= 70) {
      strategicRecommendation = `Average performance. Issue actionable improvement targets for ${weaknesses[0] || 'sub-optimal parameters'} before committing to long-term orders.`;
    } else if (overall >= 60) {
      strategicRecommendation = `Poor performance (${overall}%). Mandate a formal Corrective Action Plan (CAP) within 30 days and restrict order volumes.`;
    } else {
      strategicRecommendation = `CRITICAL RISK (${overall}%). Immediate procurement freeze recommended. Transition volume to alternative vetted suppliers.`;
    }

    return {
      strengths: strengths.join(" "),
      weaknesses: weaknesses.join(" "),
      risks: risks,
      recommendation: strategicRecommendation
    };
  },

  /**
   * Formats numbers to 2 decimal places or currency
   */
  formatScore(score) {
    return Number(score || 0).toFixed(2);
  },

  formatCurrency(amount, currency = "INR") {
    if (currency === "INR") {
      return "₹" + Number(amount || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
    }
    return "$" + Number(amount || 0).toLocaleString('en-US', { maximumFractionDigits: 2 });
  },

  formatDate(dateString) {
    if (!dateString) return "N/A";
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return dateString;
    return d.toLocaleDateString("en-US", { year: "numeric", month: "short", day: "numeric" });
  }
};

window.Calculations = Calculations;
