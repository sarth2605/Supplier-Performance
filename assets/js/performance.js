/**
 * Supplier Performance Analysis System
 * Live Performance Calculation & Recommendation Preview Engine
 */

function setupPerformanceCalculator(weights) {
    const w = weights || { quality: 0.30, delivery: 0.25, cost: 0.20, reliability: 0.15, service: 0.10 };

    const inputs = {
        quality: document.getElementById("input-quality"),
        delivery: document.getElementById("input-delivery"),
        cost: document.getElementById("input-cost"),
        reliability: document.getElementById("input-reliability"),
        service: document.getElementById("input-service"),
        defect: document.getElementById("input-defect"),
        ontime: document.getElementById("input-ontime")
    };

    const valDisplays = {
        quality: document.getElementById("val-quality"),
        delivery: document.getElementById("val-delivery"),
        cost: document.getElementById("val-cost"),
        reliability: document.getElementById("val-reliability"),
        service: document.getElementById("val-service")
    };

    const scoreDisplay = document.getElementById("preview-overall-score");
    const badgeDisplay = document.getElementById("preview-rating-badge");
    const formulaDisplay = document.getElementById("preview-formula-text");
    const recommendationDisplay = document.getElementById("preview-recommendation");

    function calculate() {
        const q = parseFloat(inputs.quality?.value || 0);
        const d = parseFloat(inputs.delivery?.value || 0);
        const c = parseFloat(inputs.cost?.value || 0);
        const r = parseFloat(inputs.reliability?.value || 0);
        const s = parseFloat(inputs.service?.value || 0);
        const defect = parseFloat(inputs.defect?.value || 0);
        const ontime = parseFloat(inputs.ontime?.value || 100);

        // Update display numbers
        if (valDisplays.quality) valDisplays.quality.textContent = q + "%";
        if (valDisplays.delivery) valDisplays.delivery.textContent = d + "%";
        if (valDisplays.cost) valDisplays.cost.textContent = c + "%";
        if (valDisplays.reliability) valDisplays.reliability.textContent = r + "%";
        if (valDisplays.service) valDisplays.service.textContent = s + "%";

        const overall = Math.round(((q * w.quality) + (d * w.delivery) + (c * w.cost) + (r * w.reliability) + (s * w.service)) * 100) / 100;

        if (scoreDisplay) {
            scoreDisplay.textContent = overall.toFixed(2) + "%";
        }

        // Rating Tier Classification
        let tier = "Critical";
        let badgeClass = "badge-tier-critical";
        let tierColor = "#ef4444";

        if (overall >= 90) {
            tier = "Excellent";
            badgeClass = "badge-tier-excellent";
            tierColor = "#10b981";
        } else if (overall >= 80) {
            tier = "Good";
            badgeClass = "badge-tier-good";
            tierColor = "#3b82f6";
        } else if (overall >= 70) {
            tier = "Average";
            badgeClass = "badge-tier-average";
            tierColor = "#f59e0b";
        } else if (overall >= 60) {
            tier = "Poor";
            badgeClass = "badge-tier-poor";
            tierColor = "#f97316";
        }

        if (badgeDisplay) {
            badgeDisplay.className = "badge " + badgeClass + " fs-6 px-3 py-2";
            badgeDisplay.textContent = tier;
        }

        if (scoreDisplay) {
            scoreDisplay.style.color = tierColor;
        }

        if (formulaDisplay) {
            formulaDisplay.innerHTML = `(${q} × ${Math.round(w.quality*100)}%) + (${d} × ${Math.round(w.delivery*100)}%) + (${c} × ${Math.round(w.cost*100)}%) + (${r} × ${Math.round(w.reliability*100)}%) + (${s} × ${Math.round(w.service*100)}%) = <strong>${overall.toFixed(2)}%</strong>`;
        }

        // Generate Recommendation text
        if (recommendationDisplay) {
            let rec = "";
            if (overall >= 90) {
                rec = `Supplier performance is excellent (${overall.toFixed(2)}%). Priority candidate for long-term contract renewal and volume expansion.`;
            } else if (overall >= 80) {
                if (q > d) {
                    rec = `Supplier demonstrates high quality (${q}%), but delivery timeliness (${d}%) needs closer operational monitoring.`;
                } else if (d > q) {
                    rec = `Delivery punctuality is strong (${d}%), but component quality (${q}%) requires routine QA verification.`;
                } else {
                    rec = `Solid, reliable standard performance. Maintain existing purchase orders with quarterly reviews.`;
                }
            } else if (overall >= 70) {
                rec = `Average performance (${overall.toFixed(2)}%). Issue formal improvement targets before committing to long-term orders.`;
            } else if (overall >= 60) {
                rec = `Poor performance (${overall.toFixed(2)}%). Mandate a Corrective Action Plan (CAP) within 30 days and restrict order volume.`;
            } else {
                rec = `CRITICAL RISK (${overall.toFixed(2)}%). Procurement freeze recommended. Decommission and transition volume.`;
            }
            recommendationDisplay.textContent = rec;
        }
    }

    // Bind event listeners to all inputs
    Object.values(inputs).forEach(input => {
        if (input) {
            input.addEventListener("input", calculate);
        }
    });

    // Run initial calculation
    calculate();
}
