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
    
    <!-- Live Active Time Counter -->
    <script>
    (function() {
        // Get session start timestamp from the header element
        var activeTimeDisplay = document.getElementById('activeTimeDisplay');
        if (!activeTimeDisplay) return;
        
        var sessionStartEpoch = parseInt(activeTimeDisplay.getAttribute('data-session-start'), 10);
        
        // Validate session start timestamp - must be a reasonable value
        // (not in the future, and not more than 30 days in the past)
        var currentTime = Math.floor(Date.now() / 1000);
        var thirtyDaysAgo = currentTime - (30 * 24 * 60 * 60);
        if (isNaN(sessionStartEpoch) || sessionStartEpoch <= 0 || 
            sessionStartEpoch > currentTime || sessionStartEpoch < thirtyDaysAgo) {
            return;
        }
        
        // Format seconds into H:MM:SS format
        function formatActiveTime(totalSeconds) {
            var hours = Math.floor(totalSeconds / 3600);
            var minutes = Math.floor((totalSeconds % 3600) / 60);
            var seconds = totalSeconds % 60;
            
            // Pad minutes and seconds with leading zeros
            var minutesStr = minutes < 10 ? '0' + minutes : minutes;
            var secondsStr = seconds < 10 ? '0' + seconds : seconds;
            
            return hours + ':' + minutesStr + ':' + secondsStr;
        }
        
        // Update the active time display
        // Note: Uses client device time for real-time updates. May have minor drift from server time.
        function updateActiveTime() {
            // Calculate elapsed time: current device time - session start time
            var currentTimeEpoch = Math.floor(Date.now() / 1000);
            var elapsedSeconds = Math.max(0, currentTimeEpoch - sessionStartEpoch);
            var formattedTime = formatActiveTime(elapsedSeconds);
            
            // Update the main display
            var activeTimeValue = document.getElementById('activeTimeValue');
            if (activeTimeValue) {
                activeTimeValue.textContent = formattedTime;
            }
            
            // Update the mobile display (in dropdown)
            var activeTimeValueMobile = document.getElementById('activeTimeValueMobile');
            if (activeTimeValueMobile) {
                activeTimeValueMobile.textContent = formattedTime;
            }
        }
        
        // Initial update
        updateActiveTime();
        
        // Update every second - store interval ID for cleanup
        var activeTimeIntervalId = setInterval(updateActiveTime, 1000);
        
        // Cleanup interval when page is unloaded to prevent memory leaks
        window.addEventListener('beforeunload', function() {
            if (activeTimeIntervalId) {
                clearInterval(activeTimeIntervalId);
            }
        });
    })();
    </script>
</body>
</html>
