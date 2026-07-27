        <div class="sidebar" style="width: 250px;">
            <div class="text-center py-4 border-bottom border-secondary">
                <img src="../assets/img/logo_bombana.png" alt="Logo" style="height: 60px;">
                <h6 class="mt-2 fw-bold text-white mb-0">ADMINISTRATOR</h6>
                <small style="color: var(--secondary-color);">Pemkab Bombana</small>
            </div>
            <div class="nav-menu mt-3">
                <a href="index.php" class="<?php echo (isset($active_menu) && $active_menu == 'dashboard') ? 'active' : ''; ?>"><i class="fas fa-home me-2"></i> Dashboard</a>
                <a href="reports.php" class="<?php echo (isset($active_menu) && $active_menu == 'reports') ? 'active' : ''; ?>"><i class="fas fa-file-alt me-2"></i> Data Laporan</a>
                <a href="map.php" class="<?php echo (isset($active_menu) && $active_menu == 'map') ? 'active' : ''; ?>"><i class="fas fa-map-marked-alt me-2"></i> Peta Sebaran</a>
                <a href="users.php" class="<?php echo (isset($active_menu) && $active_menu == 'users') ? 'active' : ''; ?>"><i class="fas fa-users me-2"></i> Kelola Pengguna</a>
                <a href="settings.php" class="<?php echo (isset($active_menu) && $active_menu == 'settings') ? 'active' : ''; ?>"><i class="fas fa-cog me-2"></i> Pengaturan</a>
                <a href="../logout.php" class="text-danger mt-5"><i class="fas fa-sign-out-alt me-2"></i> Keluar</a>
            </div>
        </div>
