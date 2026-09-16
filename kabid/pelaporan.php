<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'kabid') { header("Location: ../login.php"); exit; }

require_once '../config/database.php';

// Ambil tahun-tahun yang tersedia dari database
$stmt_years = $pdo->query("SELECT DISTINCT YEAR(created_at) as tahun FROM reports ORDER BY tahun DESC");
$years = $stmt_years->fetchAll(PDO::FETCH_COLUMN);
if (empty($years)) {
    $years = [date('Y')];
}

// Ambil kecamatan yang tersedia
$stmt_kecamatan = $pdo->query("SELECT DISTINCT kecamatan FROM reports WHERE kecamatan IS NOT NULL AND kecamatan != '' ORDER BY kecamatan ASC");
$kecamatans = $stmt_kecamatan->fetchAll(PDO::FETCH_COLUMN);

// Fitur Filter Status & Pencarian (Kabid biasanya fokus pada Disetujui)
$where = "status = 'Disetujui'";
$params = [];

if (!empty($_GET['status'])) {
    $where .= " AND status = ?";
    $params[] = $_GET['status'];
}
if (!empty($_GET['cari'])) {
    $where .= " AND (tracking_code LIKE ? OR reporter_name LIKE ?)";
    $params[] = "%" . $_GET['cari'] . "%";
    $params[] = "%" . $_GET['cari'] . "%";
}
if (!empty($_GET['kecamatan'])) {
    $where .= " AND kecamatan = ?";
    $params[] = $_GET['kecamatan'];
}
if (!empty($_GET['kategori'])) {
    $where .= " AND severity = ?";
    $params[] = $_GET['kategori'];
}
if (!empty($_GET['tahun'])) {
    $where .= " AND YEAR(created_at) = ?";
    $params[] = $_GET['tahun'];
}
if (!empty($_GET['bulan'])) {
    $where .= " AND MONTH(created_at) = ?";
    $params[] = $_GET['bulan'];
}

try {
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE $where ORDER BY created_at DESC");
    $stmt->execute($params);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$page_title = 'Pelaporan (Cetak) - Kabid Pemkab Bombana';
$active_menu = 'pelaporan';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

        <div class="flex-grow-1">
            <div class="topbar d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold" style="color: var(--primary-color);">Menu Pelaporan (Filter & Cetak)</h4>
            </div>

            <div class="container-fluid p-4">

                <div class="card border-0 shadow-sm border-top border-primary border-4 mb-4">
                    <div class="card-header bg-white pt-3 pb-2 border-0">
                        <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-filter me-1"></i> Filter Laporan (Data Disetujui)</h6>
                    </div>
                    <div class="card-body pb-2">

                        <form method="GET" class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label small text-muted fw-bold">Tahun</label>
                                <select name="tahun" class="form-select">
                                    <option value="">-- Semua Tahun --</option>
                                    <?php foreach ($years as $y): ?>
                                        <option value="<?php echo htmlspecialchars($y); ?>" <?php echo (isset($_GET['tahun']) && $_GET['tahun'] == $y) ? 'selected' : ''; ?>><?php echo htmlspecialchars($y); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted fw-bold">Bulan</label>
                                <select name="bulan" class="form-select">
                                    <option value="">-- Semua Bulan --</option>
                                    <?php
                                    $months = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                                    foreach ($months as $num => $name): ?>
                                        <option value="<?php echo $num; ?>" <?php echo (isset($_GET['bulan']) && $_GET['bulan'] == $num) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted fw-bold">Kecamatan</label>
                                <select name="kecamatan" class="form-select">
                                    <option value="">-- Semua Kecamatan --</option>
                                    <?php foreach ($kecamatans as $kec): ?>
                                        <option value="<?php echo htmlspecialchars($kec); ?>" <?php echo (isset($_GET['kecamatan']) && $_GET['kecamatan'] == $kec) ? 'selected' : ''; ?>><?php echo htmlspecialchars($kec); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted fw-bold">Kategori</label>
                                <select name="kategori" class="form-select">
                                    <option value="">-- Semua Kategori --</option>
                                    <option value="Ringan" <?php echo (isset($_GET['kategori']) && $_GET['kategori'] == 'Ringan') ? 'selected' : ''; ?>>Ringan</option>
                                    <option value="Sedang" <?php echo (isset($_GET['kategori']) && $_GET['kategori'] == 'Sedang') ? 'selected' : ''; ?>>Sedang</option>
                                    <option value="Berat" <?php echo (isset($_GET['kategori']) && $_GET['kategori'] == 'Berat') ? 'selected' : ''; ?>>Berat</option>
                                </select>
                            </div>
                            
                            <!-- Baris tombol -->
                            <div class="col-md-12 d-flex justify-content-between align-items-center mt-3 border-top pt-3">
                                <div>
                                    <input type="text" name="cari" class="form-control" placeholder="Pencarian spesifik..." value="<?php echo isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : ''; ?>" style="width: 250px;">
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary fw-bold px-4"><i class="fas fa-filter me-2"></i> Terapkan Filter</button>
                                    <?php
                                    $print_query = http_build_query([
                                        'status'    => $_GET['status'] ?? '',
                                        'cari'      => $_GET['cari'] ?? '',
                                        'kecamatan' => $_GET['kecamatan'] ?? '',
                                        'kategori'  => $_GET['kategori'] ?? '',
                                        'tahun'     => $_GET['tahun'] ?? '',
                                        'bulan'     => $_GET['bulan'] ?? ''
                                    ]);
                                    ?>
                                    <a href="print.php?<?php echo $print_query; ?>" target="_blank" class="btn btn-warning fw-bold text-dark px-4"><i class="fas fa-print me-2"></i> Cetak Dokumen</a>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted mb-3">Ditemukan <strong><?php echo count($reports); ?></strong> laporan disetujui sesuai filter.</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle text-center datatable" style="font-size: 0.9rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Tracking ID</th>
                                        <th>Nama Pelapor</th>
                                        <th>Kecamatan</th>
                                        <th>Kategori</th>
                                        <th>Tanggal Lapor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1;
                                    if (empty($reports)): ?>
                                        <tr>
                                            <td colspan="6" class="py-4 text-muted">Data laporan tidak ditemukan untuk filter ini.</td>
                                        </tr>
                                        <?php else: foreach ($reports as $row): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td class="fw-bold text-primary"><?php echo htmlspecialchars($row['tracking_code'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($row['reporter_name'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($row['kecamatan'] ?? '-'); ?></td>
                                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['severity'] ?? ''); ?></span></td>
                                                <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
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
