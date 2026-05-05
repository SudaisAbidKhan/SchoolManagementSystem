<?php
// ============================================================
//  includes/footer.php
//  Closes all open wrappers + scripts
// ============================================================
?>

    </div><!-- /.content-inner -->
</div><!-- /#pageContent -->

</div><!-- /#mainWrapper -->

<!-- ── FOOTER BAR ────────────────────────────────────────── -->
<footer class="footer-bar text-center text-muted small">
    &copy; <?= date('Y') ?> School Management System. All rights reserved.
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= BASE_URL ?>assets/js/script.js"></script>

<script>
// ── Sidebar toggle ───────────────────────────────────────
const sidebar      = document.getElementById('sidebar');
const pageContent  = document.getElementById('pageContent');
const toggleBtn    = document.getElementById('sidebarToggle');
const COLLAPSED_KEY = 'sidebar_collapsed';

// Restore state from localStorage
if (localStorage.getItem(COLLAPSED_KEY) === 'true') {
    sidebar.classList.add('collapsed');
    pageContent.classList.add('expanded');
}

toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    pageContent.classList.toggle('expanded');
    localStorage.setItem(
        COLLAPSED_KEY,
        sidebar.classList.contains('collapsed')
    );
});

// ── Auto-dismiss alerts after 4s ────────────────────────
document.querySelectorAll('.alert-dismissible').forEach(alert => {
    setTimeout(() => {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
        if (bsAlert) bsAlert.close();
    }, 4000);
});
</script>
</body>
</html>
