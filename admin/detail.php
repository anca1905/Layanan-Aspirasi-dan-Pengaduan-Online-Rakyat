<?php
session_start();
// if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../login.php"); exit; }

require_once '../config/database.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<script>alert('ID Laporan tidak valid!'); window.location='reports.php';</script>";
    exit;
}

$message = '';

// Proses Update Status & Tanggapan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = $_POST['status'];
    $tanggapan = $_POST['tanggapan'];

    try {
        $stmt = $pdo->prepare("UPDATE reports SET status = ?, tanggapan = ? WHERE id = ?");
        $stmt->execute([$status, $tanggapan, $id]);
        $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm border-0 border-start border-5 border-success'>
                        <i class='fas fa-check-circle me-2'></i> Status dan tanggapan berhasil diperbarui!
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i> Gagal memperbarui: " . $e->getMessage() . "</div>";
    }
}

// Ambil Data Laporan
try {
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
    $stmt->execute([$id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        die("Data laporan tidak ditemukan.");
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
$page_title = 'Detail Laporan - Admin Pemkab Bombana';
$active_menu = 'reports';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<div class="flex-grow-1">
    <div class="topbar d-flex justify-content-between align-items-center">
        <h4 class="mb-0 fw-bold" style="color: var(--primary-color);">
            <a href="reports.php" class="text-decoration-none text-muted me-2"><i class="fas fa-arrow-left"></i></a>
            Proses Laporan
        </h4>
    </div>

    <div class="container-fluid p-4">
        <?php echo $message; ?>

        <div class="row">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm mb-4 border-top border-primary border-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2 text-primary"></i> Detail Informasi</h5>
                        <span class="badge bg-light text-dark border p-2 fs-6">Tracking ID: <strong><?php echo htmlspecialchars($report['tracking_code'] ?? ''); ?></strong></span>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-label">Tanggal Masuk</div>
                                <div class="detail-value"><i class="fas fa-calendar-alt text-muted me-2"></i><?php echo date('d F Y, H:i', strtotime($report['created_at'])); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-label">Status Kategori (Tingkat Kerusakan)</div>
                                <div class="detail-value"><span class="badge bg-secondary"><?php echo htmlspecialchars($report['severity'] ?? ''); ?></span></div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-label">Nama Pelapor</div>
                                <div class="detail-value"><i class="fas fa-user text-muted me-2"></i><?php echo htmlspecialchars($report['reporter_name'] ?? ''); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-label">Kecamatan / Desa</div>
                                <div class="detail-value"><i class="fas fa-map text-muted me-2"></i><?php echo htmlspecialchars($report['kecamatan'] ?? '') . ' / ' . htmlspecialchars($report['desa'] ?? ''); ?></div>
                            </div>
                            <div class="col-md-12 mt-3">
                                <div class="detail-label">Detail Lokasi / Patokan</div>
                                <div class="detail-value"><i class="fas fa-map-marker-alt text-muted me-2"></i><?php echo htmlspecialchars($report['location'] ?? ''); ?></div>
                                <?php if (!empty($report['latitude']) && !empty($report['longitude'])): ?>
                                    <div class="mt-2">
                                        <a href="https://www.google.com/maps?q=<?php echo $report['latitude']; ?>,<?php echo $report['longitude']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-map-marked-alt me-1"></i> Lihat di Google Maps
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="detail-label text-primary">Isi Laporan (Kronologi)</div>
                        <div class="p-3 bg-light rounded border mb-4">
                            <p class="mb-0" style="white-space: pre-line; line-height: 1.6;"><?php echo htmlspecialchars($report['description'] ?? ''); ?></p>
                        </div>

                        <div class="detail-label text-primary"><i class="fas fa-paperclip me-1"></i> Lampiran Foto Bukti</div>
                        <?php if (!empty($report['photo'])): ?>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <?php 
                                $photos = explode(',', $report['photo']);
                                foreach($photos as $foto):
                                    $foto = trim($foto);
                                    if (!empty($foto) && file_exists('../uploads/' . $foto)):
                                ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($foto); ?>" alt="Bukti" class="img-fluid rounded shadow-sm border" style="max-height: 400px; object-fit: contain; background-color: #f8f9fa; flex: 1 1 auto; max-width: 100%;">
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-secondary mt-2"><i class="fas fa-image me-2"></i> Pelapor tidak menyertakan lampiran foto.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm border-top border-warning border-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-gavel me-2 text-warning"></i> Tindak Lanjut & Tanggapan</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="" method="POST">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Ubah Status Laporan</label>
                                <select name="status" class="form-select form-select-lg shadow-sm" required>
                                    <option value="Menunggu" <?php echo ($report['status'] == 'Menunggu') ? 'selected' : ''; ?>>🟡 Pending (Menunggu dikonfirmasi)</option>
                                    <option value="Disetujui" <?php echo ($report['status'] == 'Disetujui') ? 'selected' : ''; ?>>🟢 Disetujui</option>
                                    <option value="Ditolak" <?php echo ($report['status'] == 'Ditolak') ? 'selected' : ''; ?>>🔴 Ditolak (Laporan Tidak Valid)</option>
                                </select>
                            </div>



                            <div class="mb-4">
                                <label class="form-label fw-bold">Tanggapan Instansi (Akan dibaca pelapor)</label>
                                <textarea name="tanggapan" class="form-control shadow-sm" rows="6" placeholder="Tuliskan respon resmi dari instansi terkait di sini..."><?php echo htmlspecialchars($report['tanggapan'] ?? ''); ?></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold"><i class="fas fa-save me-2"></i> Simpan Pembaruan</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="alert alert-info mt-3 border-0 shadow-sm">
                    <h6 class="fw-bold"><i class="fas fa-lightbulb me-2"></i> Petunjuk:</h6>
                    <ul class="mb-0 small ps-3">
                        <li>Ubah status ke <strong>Disetujui</strong> jika laporan diterima dan diteruskan ke Kabid/Instansi Teknis.</li>
                        <li>Tuliskan tanggapan dengan bahasa yang formal dan solutif.</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>