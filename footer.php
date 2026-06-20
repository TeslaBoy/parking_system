<?php if (isset($_SESSION['user_id'])): ?>
    </main>

    <div id="sidebarOverlay" class="sidebar-overlay"></div>

    <footer class="app-footer">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                <span class="text-muted small">&copy; <?php echo date('Y'); ?> Smart Parking System. Всі права захищені.</span>
                <span class="text-muted small mt-1 mt-md-0">Система управління парковками</span>
            </div>
        </div>
    </footer>
<?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script src="js/script.js"></script>
</body>
</html>