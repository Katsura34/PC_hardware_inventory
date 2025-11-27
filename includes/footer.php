    </main> <!-- Close main content area -->
    
    <!-- Footer - HCI Principle: Consistency -->
    <footer class="bg-light text-center text-muted py-4 mt-auto" role="contentinfo">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-md-start mb-2 mb-md-0">
                    <small>
                        <strong>PC Hardware Inventory System</strong> <span class="badge bg-secondary">v2.0</span>
                    </small>
                </div>
                <div class="col-md-6 text-md-end">
                    <small>
                        <i class="bi bi-building me-1" aria-hidden="true"></i>ACLC College of Ormoc
                        &bull; &copy; <?php echo date('Y'); ?>
                    </small>
                </div>
            </div>
            <!-- Keyboard shortcuts hint -->
            <div class="mt-2">
                <small class="text-muted">
                    <i class="bi bi-keyboard me-1" aria-hidden="true"></i>
                    Press <kbd>Ctrl</kbd> + <kbd>/</kbd> for keyboard shortcuts
                </small>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo BASE_PATH; ?>assets/js/main.js"></script>
    
    <!-- HCI Enhancement: Prevent double-click submissions -->
    <script>
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                // Prevent double submission
                setTimeout(function() {
                    submitBtn.disabled = true;
                }, 0);
            }
        });
    });
    </script>
</body>
</html>
