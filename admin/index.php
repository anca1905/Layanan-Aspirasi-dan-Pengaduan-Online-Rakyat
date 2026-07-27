<?php
session_start();
// Dummy session check (Pastikan file login.php nanti meng-set session ini)
// if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../login.php"); exit; }

require_once '../config/database.php';

// Ambil Statistik Laporan
try {
    $stmt_total = $pdo->query("SELECT COUNT(*) FROM reports");
    $total_laporan = $stmt_total->fetchColumn();

    $stmt_ringan = $pdo->query("SELECT COUNT(*) FROM reports WHERE severity = 'Ringan'");
    $total_ringan = $stmt_ringan->fetchColumn();

    $stmt_sedang = $pdo->query("SELECT COUNT(*) FROM reports WHERE severity = 'Sedang'");
    $total_sedang = $stmt_sedang->fetchColumn();

    $stmt_berat = $pdo->query("SELECT COUNT(*) FROM reports WHERE severity = 'Berat'");
    $total_berat = $stmt_berat->fetchColumn();

    // Ambil 5 Laporan Terbaru
    $stmt_recent = $pdo->query("SELECT * FROM reports ORDER BY created_at DESC LIMIT 5");
    $recent_reports = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

    // Ambil Laporan berdasarkan Kecamatan (dikelompokkan dengan desa)
    $stmt_kecamatan = $pdo->query("SELECT kecamatan, desa, COUNT(*) as total FROM reports GROUP BY kecamatan, desa ORDER BY kecamatan ASC, total DESC");
    $laporan_kecamatan_raw = $stmt_kecamatan->fetchAll(PDO::FETCH_ASSOC);
    
    $laporan_kecamatan = [];
    foreach ($laporan_kecamatan_raw as $row) {
        $kec = $row['kecamatan'] ?: 'Tidak Diketahui';
        if (!isset($laporan_kecamatan[$kec])) {
            $laporan_kecamatan[$kec] = ['total' => 0, 'desa' => []];
        }
        $laporan_kecamatan[$kec]['total'] += $row['total'];
        $laporan_kecamatan[$kec]['desa'][] = ['nama' => $row['desa'], 'jumlah' => $row['total']];
    }
    
    uasort($laporan_kecamatan, function($a, $b) {
        return $b['total'] <=> $a['total'];
    });
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
$page_title = 'Dashboard Admin - Pemkab Bombana';
$active_menu = 'dashboard';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

        <div class="flex-grow-1">
            <div class="topbar">
                <h4 class="mb-0 fw-bold" style="color: var(--primary-color);">Dashboard Overview</h4>
                <div class="d-flex align-items-center">
                    <span class="me-3 fw-bold">Halo, Admin!</span>
                    <img src="https://ui-avatars.com/api/?name=Admin+Bombana&background=DAA520&color=fff" class="rounded-circle" width="40" alt="Admin">
                </div>
            </div>

            <div class="container-fluid p-4">

                <div class="row mb-4">
                    <!-- Total Laporan -->
                    <div class="col-md-3">
                        <div class="card stat-card border-bottom border-primary border-5">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1 text-uppercase fw-bold small">Total Laporan</p>
                                    <h2 class="mb-0 fw-bold"><?php echo $total_laporan; ?></h2>
                                </div>
                                <div class="icon-box bg-primary"><i class="fas fa-folder-open"></i></div>
                            </div>
                        </div>
                    </div>
                    <!-- Kerusakan Ringan -->
                    <div class="col-md-3">
                        <div class="card stat-card border-bottom border-success border-5">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1 text-uppercase fw-bold small">Kerusakan Ringan</p>
                                    <h2 class="mb-0 fw-bold"><?php echo $total_ringan; ?></h2>
                                </div>
                                <div class="icon-box bg-success"><i class="fas fa-road"></i></div>
                            </div>
                        </div>
                    </div>
                    <!-- Kerusakan Sedang -->
                    <div class="col-md-3">
                        <div class="card stat-card border-bottom border-warning border-5">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1 text-uppercase fw-bold small">Kerusakan Sedang</p>
                                    <h2 class="mb-0 fw-bold"><?php echo $total_sedang; ?></h2>
                                </div>
                                <div class="icon-box bg-warning text-dark"><i class="fas fa-exclamation-triangle"></i></div>
                            </div>
                        </div>
                    </div>
                    <!-- Kerusakan Berat -->
                    <div class="col-md-3">
                        <div class="card stat-card border-bottom border-danger border-5">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1 text-uppercase fw-bold small">Kerusakan Berat</p>
                                    <h2 class="mb-0 fw-bold"><?php echo $total_berat; ?></h2>
                                </div>
                                <div class="icon-box bg-danger"><i class="fas fa-times-circle"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold" style="color: var(--primary-color);"><i class="fas fa-list me-2"></i>5 Laporan Masuk Terbaru</h5>
                                <a href="reports.php" class="btn btn-sm btn-outline-primary">Lihat Semua Laporan</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">Tracking ID</th>
                                                <th>Kategori</th>
                                                <th>Pelapor</th>
                                                <th>Tanggal</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recent_reports)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-4">Belum ada laporan masuk.</td>
                                                </tr>
                                                <?php else: foreach ($recent_reports as $row): ?>
                                                    <tr>
                                                        <td class="ps-4 fw-bold"><?php echo htmlspecialchars($row['tracking_code'] ?? ''); ?></td>
                                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['severity'] ?? ''); ?></span></td>
                                                        <td><?php echo htmlspecialchars($row['reporter_name'] ?? ''); ?></td>
                                                        <td><?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?></td>
                                                        <td>
                                                            <?php
                                                            $s = strtolower($row['status'] ?? '');
                                                            if ($s == 'menunggu') echo '<span class="badge bg-warning text-dark">Menunggu</span>';
                                                            elseif ($s == 'disetujui') echo '<span class="badge bg-success">Disetujui</span>';
                                                            else echo '<span class="badge bg-danger">Ditolak</span>';
                                                            ?>
                                                        </td>
                                                    </tr>
                                            <?php endforeach;
                                            endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 fw-bold" style="color: var(--primary-color);"><i class="fas fa-map-marked-alt me-2"></i>Laporan per Kecamatan</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="accordion accordion-flush" id="accordionKecamatan">
                                    <?php if (empty($laporan_kecamatan)): ?>
                                        <div class="text-center py-4 text-muted">Belum ada data.</div>
                                    <?php else: 
                                        $i = 0;
                                        foreach ($laporan_kecamatan as $kec_name => $kec_data): 
                                        $i++;
                                        $collapseId = 'collapseKec' . $i;
                                    ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading<?php echo $i; ?>">
                                                <button class="accordion-button collapsed px-3 py-3" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="false" aria-controls="<?php echo $collapseId; ?>">
                                                    <span class="fw-bold text-secondary me-auto"><?php echo htmlspecialchars($kec_name); ?></span>
                                                    <span class="badge bg-primary rounded-pill px-3 ms-2"><?php echo $kec_data['total']; ?></span>
                                                </button>
                                            </h2>
                                            <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $i; ?>" data-bs-parent="#accordionKecamatan">
                                                <div class="accordion-body p-0">
                                                    <ul class="list-group list-group-flush bg-light">
                                                        <?php foreach ($kec_data['desa'] as $desa): ?>
                                                            <a href="reports.php?kecamatan=<?php echo urlencode($kec_name); ?>&desa=<?php echo urlencode($desa['nama']); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center bg-transparent ps-4 py-2 small border-0 border-bottom text-decoration-none">
                                                                <span class="text-muted"><i class="fas fa-map-marker-alt text-danger me-2"></i> <?php echo htmlspecialchars($desa['nama'] ?: 'Tidak Diketahui'); ?></span>
                                                                <span class="badge bg-secondary rounded-pill"><?php echo $desa['jumlah']; ?></span>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

<?php require_once 'layouts/footer.php'; ?>