/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Global Application Interactions, Animations, Counters & Dialogs
 */

document.addEventListener("DOMContentLoaded", () => {
    // 1. Mobile Sidebar Drawer Toggle
    const mobileBtn = document.getElementById("mobile-sidebar-toggle");
    const mobileCloseBtn = document.getElementById("mobile-sidebar-close");
    const backdrop = document.getElementById("sidebar-backdrop");

    if (mobileBtn) {
        mobileBtn.addEventListener("click", () => {
            document.body.classList.toggle("mobile-sidebar-open");
        });
    }

    if (mobileCloseBtn) {
        mobileCloseBtn.addEventListener("click", () => {
            document.body.classList.remove("mobile-sidebar-open");
        });
    }

    if (backdrop) {
        backdrop.addEventListener("click", () => {
            document.body.classList.remove("mobile-sidebar-open");
        });
    }

    // Auto-close mobile drawer when tapping a navigation link on small screens
    const sidebarNavLinks = document.querySelectorAll(".app-sidebar .nav-link");
    sidebarNavLinks.forEach(link => {
        link.addEventListener("click", () => {
            if (window.innerWidth < 992) {
                document.body.classList.remove("mobile-sidebar-open");
            }
        });
    });

    // Close on Escape key press
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && document.body.classList.contains("mobile-sidebar-open")) {
            document.body.classList.remove("mobile-sidebar-open");
        }
    });

    // 2. Desktop Sidebar Collapse Toggle
    const desktopToggle = document.getElementById("desktop-sidebar-toggle");
    if (desktopToggle) {
        desktopToggle.addEventListener("click", () => {
            document.body.classList.toggle("sidebar-collapsed");
            localStorage.setItem("spas_sidebar_collapsed", document.body.classList.contains("sidebar-collapsed") ? "1" : "0");
        });

        // Restore saved preference
        if (localStorage.getItem("spas_sidebar_collapsed") === "1") {
            document.body.classList.add("sidebar-collapsed");
        }
    }

    // 3. Smooth Animated Number Counters (Triggers on Viewport Visibility)
    const animateCounter = (counter) => {
        if (counter.dataset.animated === "true") return;
        counter.dataset.animated = "true";

        const target = parseFloat(counter.getAttribute("data-target")) || 0;
        const isPercent = counter.getAttribute("data-percent") === "true";
        const decimals = parseInt(counter.getAttribute("data-decimal") || (isPercent ? "1" : "0"), 10);
        const prefix = counter.getAttribute("data-prefix") || "";
        const suffix = counter.getAttribute("data-suffix") || (isPercent ? "%" : "");

        const duration = 1200;
        const frameRate = 1000 / 60;
        const totalFrames = Math.round(duration / frameRate);
        let frame = 0;

        const counterTimer = setInterval(() => {
            frame++;
            // Ease-out cubic animation formula
            const progress = frame / totalFrames;
            const easeOutProgress = 1 - Math.pow(1 - progress, 3);
            const currentVal = target * easeOutProgress;

            const formattedVal = decimals > 0 
                ? currentVal.toFixed(decimals) 
                : Math.floor(currentVal).toLocaleString();

            counter.textContent = prefix + formattedVal + suffix;

            if (frame >= totalFrames) {
                const finalFormatted = decimals > 0 
                    ? target.toFixed(decimals) 
                    : Math.floor(target).toLocaleString();
                counter.textContent = prefix + finalFormatted + suffix;
                clearInterval(counterTimer);
            }
        }, frameRate);
    };

    if ("IntersectionObserver" in window) {
        const counterObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.2 });

        document.querySelectorAll(".counter-value").forEach(c => counterObserver.observe(c));
    } else {
        document.querySelectorAll(".counter-value").forEach(c => animateCounter(c));
    }

    // 4. Password Visibility Toggle
    const togglePassBtn = document.getElementById("toggle-password-btn");
    const passInput = document.getElementById("password-input");
    if (togglePassBtn && passInput) {
        togglePassBtn.addEventListener("click", () => {
            const isPassword = passInput.type === "password";
            passInput.type = isPassword ? "text" : "password";
            togglePassBtn.innerHTML = isPassword ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>';
        });
    }

    // 5. Initialize Bootstrap Tooltips & Popovers
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    // 6. Auto-dismiss Toast Notifications & Flash Messages
    const flashAlerts = document.querySelectorAll(".alert.alert-dismissible:not(.stay-open)");
    flashAlerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add("fade-out-toast");
            setTimeout(() => {
                if (alert.parentNode) {
                    const bsAlert = bootstrap.Alert.getInstance(alert) || new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 400);
        }, 4500);
    });

    // 7. Form Submission Loading States
    document.querySelectorAll("form:not(.no-spin)").forEach(form => {
        form.addEventListener("submit", (e) => {
            if (form.checkValidity && !form.checkValidity()) return;
            const submitBtn = form.querySelector('button[type="submit"]:not(.no-spin)');
            if (submitBtn && !submitBtn.disabled) {
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1.5" role="status" aria-hidden="true"></span> Processing...`;
                
                // Fallback reset in case submission is blocked or takes too long
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 8000);
            }
        });
    });

    // 8. Staggered Card Animation on Page Load
    const animatedCards = document.querySelectorAll(".animate-slide-up");
    animatedCards.forEach((card, index) => {
        card.style.animationDelay = `${Math.min(index * 60, 400)}ms`;
    });
});

/**
 * Global Enterprise Confirmation Dialog Modal
 * Replaces native window.confirm with a modern Bootstrap modal
 */
function showConfirmDialog(title, message, onConfirm, confirmBtnText = "Yes, Proceed", btnClass = "btn-danger") {
    let modalEl = document.getElementById("spasConfirmModal");
    if (!modalEl) {
        modalEl = document.createElement("div");
        modalEl.id = "spasConfirmModal";
        modalEl.className = "modal fade";
        modalEl.tabIndex = -1;
        modalEl.innerHTML = `
            <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header border-0 pb-0 pt-4 px-4">
                        <div class="d-flex align-items-center gap-2.5">
                            <div class="p-2.5 rounded-circle bg-danger bg-opacity-10 text-danger" id="spasModalIcon">
                                <i class="fa-solid fa-triangle-exclamation fs-5"></i>
                            </div>
                            <h5 class="modal-title fw-bold text-dark mb-0" id="spasModalTitle">Confirmation</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4 py-3 text-secondary small" id="spasModalMessage">
                        Are you sure you want to proceed with this action?
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light px-3.5 py-2 rounded-3 fw-semibold small" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn px-4 py-2 rounded-3 fw-semibold small text-white shadow-sm" id="spasModalConfirmBtn">Confirm</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modalEl);
    }

    document.getElementById("spasModalTitle").textContent = title || "Confirm Action";
    document.getElementById("spasModalMessage").textContent = message || "Are you sure you want to proceed?";
    
    const confirmBtn = document.getElementById("spasModalConfirmBtn");
    confirmBtn.className = `btn px-4 py-2 rounded-3 fw-semibold small text-white shadow-sm ${btnClass}`;
    confirmBtn.textContent = confirmBtnText;

    const bsModal = new bootstrap.Modal(modalEl);

    // Remove prior handlers
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

    newConfirmBtn.addEventListener("click", () => {
        bsModal.hide();
        if (typeof onConfirm === "function") {
            onConfirm();
        }
    });

    bsModal.show();
}

/**
 * Legacy confirmAction fallback compatible with existing onclick handlers
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        if (typeof callback === "function") callback();
        return true;
    }
    return false;
}
