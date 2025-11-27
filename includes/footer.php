        </div> <!-- Close container-fluid -->
    </main> <!-- Close main content area -->
    
    <!-- Footer - HCI Principle: Consistency -->
    <footer class="app-footer" role="contentinfo">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                    <small>
                        <strong>PC Hardware Inventory System</strong> <span class="badge bg-secondary">v2.0</span>
                    </small>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <small>
                        <i class="bi bi-building me-1" aria-hidden="true"></i>ACLC College of Ormoc
                        &bull; &copy; <?php echo date('Y'); ?>
                    </small>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo BASE_PATH; ?>assets/js/main.js"></script>
    
    <!-- HCI UI Enhancements -->
    <script src="<?php echo BASE_PATH; ?>assets/js/ui-enhancements.js"></script>
    
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
