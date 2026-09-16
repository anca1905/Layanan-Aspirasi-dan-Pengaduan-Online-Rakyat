<?php
session_start();
// if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../login.php"); exit; }

require_once '../config/database.php';

// Fitur Filter Status & Pencarian
$where = "1=1"; // Gunakan 1=1 agar penambahan kondisi (AND) lebih dinamis
$params = [];

if (isset($_GET['status']) && $_GET['status'] != '') {
    $where .= " AND status = ?";
    $params[] = $_GET['status'];
} else {
    // Default saat halaman pertama kali dibuka: tampilkan laporan baru dan disetujui
    $where .= " AND status IN ('Sedang Diproses', 'Disetujui')";
}

if (isset($_GET['cari']) && $_GET['cari'] != '') {
    $where .= " AND (tracking_code LIKE ? OR reporter_name LIKE ?)";
    $params[] = "%" . $_GET['cari'] . "%";
    $params[] = "%" . $_GET['cari'] . "%";
}
if (isset($_GET['kecamatan']) && $_GET['kecamatan'] != '') {
    $where .= " AND kecamatan = ?";
    $params[] = $_GET['kecamatan'];
}
if (isset($_GET['desa']) && $_GET['desa'] != '') {
    $where .= " AND desa = ?";
    $params[] = $_GET['desa'];
}

try {
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE $where ORDER BY created_at DESC");
    $stmt->execute($params);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$page_title = 'Data Laporan - Kabid Pemkab Bombana';
$active_menu = 'reports';

require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<div class="flex-grow-1">
    <div class="topbar d-flex justify-content-between align-items-center">
        <h4 class="mb-0 fw-bold" style="color: var(--primary-color);">Manajemen Data Laporan</h4>
    </div>

    <div class="container-fluid p-4">

        <div class="card border-0 shadow-sm border-top border-primary border-4">
            <div class="card-body">

                <form method="GET" class="row g-3 mb-4">
                    <?php if(isset($_GET['kecamatan'])): ?>
                        <input type="hidden" name="kecamatan" value="<?php echo htmlspecialchars($_GET['kecamatan']); ?>">
                    <?php endif; ?>
                    <?php if(isset($_GET['desa'])): ?>
                        <input type="hidden" name="desa" value="<?php echo htmlspecialchars($_GET['desa']); ?>">
                    <?php endif; ?>
                    <div class="col-md-4">
                        <input type="text" name="cari" class="form-control" placeholder="Cari Tracking ID atau Nama..." value="<?php echo isset($_GET['cari']) ? $_GET['cari'] : ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">-- Laporan Masuk & Disetujui --</option>
                            <option value="Sedang Diproses" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Sedang Diproses') ? 'selected' : ''; ?>>Sedang Diproses</option>
                            <option value="Disetujui" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Disetujui') ? 'selected' : ''; ?>>Disetujui</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-2"></i> Tampilkan</button>
                    </div>
                    <div class="col-md-3 text-end">
                        <!-- Tombol cetak dipindahkan ke menu Pelaporan -->
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Tracking ID</th>
                                <th>Nama Pelapor</th>
                                <th>Kategori</th>
                                <th>Desa</th>
                                <th>Tanggal Masuk</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            if (empty($reports)): ?>
                                <tr>
                                    <td colspan="8" class="py-4 text-muted">Data laporan tidak ditemukan.</td>
                                </tr>
                            <?php else: foreach ($reports as $row): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td class="fw-bold text-primary"><?php echo htmlspecialchars($row['tracking_code'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['reporter_name'] ?? ''); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['severity'] ?? ''); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['desa'] ?? '-'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <?php
                                        $s = strtolower($row['status'] ?? '');
                                        if ($s == 'sedang diproses' || $s == 'diproses') echo '<span class="badge bg-warning text-dark border"><i class="fas fa-clock"></i> Diproses</span>';
                                        elseif ($s == 'disetujui') echo '<span class="badge bg-success"><i class="fas fa-check"></i> Disetujui</span>';
                                        else echo '<span class="badge bg-danger"><i class="fas fa-times"></i> Ditolak</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <a href="detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-search"></i> Detail Laporan
                                        </a>
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
</div>

<?php require_once 'layouts/footer.php'; ?>