/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Dashboard Visualizations Controller (Chart.js 4+)
 * Enterprise Chart Animations & Tooltips
 */

function initDashboardCharts(data) {
    if (!data) return;

    // Common Chart.js Default Font & Styles
    Chart.defaults.font.family = "'Plus Jakarta Sans', -apple-system, sans-serif";
    Chart.defaults.color = "#64748b";

    // 1. Supplier Performance Bar Chart
    const barCanvas = document.getElementById("chart-supplier-scores");
    if (barCanvas && data.barLabels && data.barData) {
        new Chart(barCanvas.getContext("2d"), {
            type: "bar",
            data: {
                labels: data.barLabels,
                datasets: [{
                    label: "Overall Score (%)",
                    data: data.barData,
                    backgroundColor: data.barColors || "#2563eb",
                    borderRadius: 6,
                    maxBarThickness: 36
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: (ctx) => ` Overall Score: ${ctx.parsed.y}%`
                        }
                    }
                },
                scales: {
                    x: { 
                        grid: { display: false }, 
                        ticks: { font: { size: 11, weight: '600' } } 
                    },
                    y: {
                        min: 0,
                        max: 100,
                        grid: { color: "#f1f5f9" },
                        ticks: { 
                            callback: (v) => `${v}%`,
                            stepSize: 20
                        }
                    }
                }
            }
        });
    }

    // 2. Product Transfer Chart (Stage Volume or Status Breakdown)
    const transferCanvas = document.getElementById("chart-product-transfers");
    if (transferCanvas && data.transferLabels && data.transferData) {
        new Chart(transferCanvas.getContext("2d"), {
            type: "doughnut",
            data: {
                labels: data.transferLabels,
                datasets: [{
                    data: data.transferData,
                    backgroundColor: data.transferColors || [
                        "#10b981", // Received / Stage 2
                        "#f59e0b", // In Transit / Stage 1
                        "#8b5cf6", // Retail Stocked
                        "#ef4444"  // Cancelled
                    ],
                    borderWidth: 2,
                    borderColor: "#ffffff"
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1200,
                    animateRotate: true,
                    animateScale: true
                },
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: { boxWidth: 12, font: { size: 11, weight: '500' }, padding: 14 }
                    },
                    tooltip: {
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: (ctx) => ` ${ctx.label}: ${ctx.raw} units`
                        }
                    }
                },
                cutout: "66%"
            }
        });
    }

    // 3. Monthly Supplier Performance Trend Line Chart
    const trendCanvas = document.getElementById("chart-monthly-trend");
    if (trendCanvas && data.trendMonths && data.trendScores) {
        new Chart(trendCanvas.getContext("2d"), {
            type: "line",
            data: {
                labels: data.trendMonths,
                datasets: [{
                    label: "Average Supplier Score",
                    data: data.trendScores,
                    borderColor: "#2563eb",
                    backgroundColor: "rgba(37, 99, 235, 0.1)",
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2.5,
                    pointBackgroundColor: "#2563eb",
                    pointBorderColor: "#ffffff",
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 1000, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: { 
                        padding: 10, 
                        cornerRadius: 8,
                        callbacks: { label: (ctx) => ` Avg Score: ${ctx.parsed.y}%` }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: {
                        min: 50,
                        max: 100,
                        grid: { color: "#f8fafc" },
                        ticks: { callback: (v) => `${v}%`, stepSize: 15 }
                    }
                }
            }
        });
    }

    // 4. Monthly Delivery Performance Trend Chart
    const deliveryCanvas = document.getElementById("chart-delivery-trend");
    if (deliveryCanvas && data.deliveryMonths && data.deliveryScores) {
        new Chart(deliveryCanvas.getContext("2d"), {
            type: "line",
            data: {
                labels: data.deliveryMonths,
                datasets: [{
                    label: "On-Time Rate",
                    data: data.deliveryScores,
                    borderColor: "#10b981",
                    backgroundColor: "rgba(16, 185, 129, 0.1)",
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2.5,
                    pointBackgroundColor: "#10b981",
                    pointBorderColor: "#ffffff",
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 1000, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: { 
                        padding: 10, 
                        cornerRadius: 8,
                        callbacks: { label: (ctx) => ` On-Time Rate: ${ctx.parsed.y}%` }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: {
                        min: 60,
                        max: 100,
                        grid: { color: "#f8fafc" },
                        ticks: { callback: (v) => `${v}%`, stepSize: 10 }
                    }
                }
            }
        });
    }

    // 5. Monthly Quality Performance Trend Chart
    const qualityCanvas = document.getElementById("chart-quality-trend");
    if (qualityCanvas && data.qualityMonths && data.qualityScores) {
        new Chart(qualityCanvas.getContext("2d"), {
            type: "line",
            data: {
                labels: data.qualityMonths,
                datasets: [{
                    label: "Quality Compliance",
                    data: data.qualityScores,
                    borderColor: "#8b5cf6",
                    backgroundColor: "rgba(139, 92, 246, 0.1)",
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2.5,
                    pointBackgroundColor: "#8b5cf6",
                    pointBorderColor: "#ffffff",
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 1000, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: { 
                        padding: 10, 
                        cornerRadius: 8,
                        callbacks: { label: (ctx) => ` Quality: ${ctx.parsed.y}%` }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: {
                        min: 70,
                        max: 100,
                        grid: { color: "#f8fafc" },
                        ticks: { callback: (v) => `${v}%`, stepSize: 10 }
                    }
                }
            }
        });
    }

    // 6. Rating Distribution Doughnut Chart (if container present)
    const ratingCanvas = document.getElementById("chart-rating-distribution");
    if (ratingCanvas && data.distributionData) {
        new Chart(ratingCanvas.getContext("2d"), {
            type: "doughnut",
            data: {
                labels: ["A+ (90-100)", "A (80-89)", "B (70-79)", "C (60-69)", "D (<60)"],
                datasets: [{
                    data: data.distributionData,
                    backgroundColor: ["#10b981", "#3b82f6", "#f59e0b", "#f97316", "#ef4444"],
                    borderWidth: 2,
                    borderColor: "#ffffff"
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: "bottom", labels: { boxWidth: 10, font: { size: 10 } } }
                },
                cutout: "68%"
            }
        });
    }
}
