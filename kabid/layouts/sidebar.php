        <div class="sidebar" style="width: 250px;">
            <div class="text-center py-4 border-bottom border-secondary">
                <img src="../assets/img/logo_bombana.png" alt="Logo" style="height: 60px;">
                <h6 class="mt-2 fw-bold text-white mb-0">KEPALA BIDANG</h6>
                <small style="color: var(--secondary-color);">Bina Marga Bombana</small>
            </div>
            <div class="nav-menu mt-3">
                <?php
                // Hitung laporan yang belum dibaca
                $stmt_unread = $pdo->query("SELECT COUNT(*) FROM reports WHERE is_read = 0");
                $unread_count = $stmt_unread->fetchColumn();
                $unread_badge = $unread_count > 0 ? " <span class='badge bg-danger ms-2'>$unread_count Baru</span>" : "";
                ?>
                <a href="index.php" class="<?php echo (isset($active_menu) && $active_menu == 'dashboard') ? 'active' : ''; ?>"><i class="fas fa-chart-line me-2"></i> Statistik Laporan</a>
                <a href="reports.php" class="<?php echo (isset($active_menu) && $active_menu == 'reports') ? 'active' : ''; ?>"><i class="fas fa-file-alt me-2"></i> Data Laporan <?php echo $unread_badge; ?></a>
                <a href="../logout.php" class="text-danger mt-5"><i class="fas fa-sign-out-alt me-2"></i> Keluar</a>
            </div>
        </div>
