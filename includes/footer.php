<?php
/**
 * Supplier Performance Analysis System
 * Common Footer Template
 */
?>
        </main> <!-- /main.page-body -->

        <!-- Bottom Page Footer -->
        <footer class="app-footer bg-surface border-top py-3 px-3 px-md-4 mt-auto">
            <div class="container-fluid d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2 text-muted small">
                <div>
                    &copy; <?= date('Y') ?> <strong><?= APP_FULL_NAME ?></strong>. All rights reserved.
                </div>
                <div>
                    <?= APP_AUTHOR ?> &bull; <span class="badge bg-light text-dark border">PHP 8+ &bull; MySQL &bull; Bootstrap 5</span>
                </div>
            </div>
        </footer>

    </div> <!-- /main-wrapper -->
</div> <!-- /app-layout -->

<!-- Bootstrap 5.3 Bundle JS (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Global Application Script -->
<script src="<?= BASE_URL ?>assets/js/app.js"></script>

<!-- Custom Injected Scripts (if set by child views) -->
<?php if (isset($extra_js) && is_array($extra_js)): ?>
    <?php foreach ($extra_js as $js_file): ?>
        <script src="<?= BASE_URL . $js_file ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
