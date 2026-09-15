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
    if (isset($_POST['submit_comment'])) {
        $comment_text = trim($_POST['comment']);
        if (!empty($comment_text)) {
            $stmt_ins = $pdo->prepare("INSERT INTO comments (report_id, user_id, comment) VALUES (?, ?, ?)");
            $stmt_ins->execute([$id, $_SESSION['user_id'], $comment_text]);
            $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm border-0 border-start border-5 border-success'>
                            <i class='fas fa-check-circle me-2'></i> Komentar berhasil ditambahkan!
                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                        </div>";
        }
    } else {
        $status = $_POST['status'];
        $tanggapan = $_POST['tanggapan'];

        try {
            $stmt = $pdo->prepare("UPDATE reports SET status = ?, tanggapan = ? WHERE id = ?");
            $stmt->execute([$status, $tanggapan, $id]);
            
            // Notifikasi email ke pelapor
            try {
                $stmtReporter = $pdo->prepare("SELECT u.email, u.name, r.tracking_code FROM reports r JOIN users u ON r.user_id = u.id WHERE r.id = ? AND u.email IS NOT NULL");
                $stmtReporter->execute([$id]);
                if ($reporter = $stmtReporter->fetch()) {
                    require_once '../vendor/autoload.php';
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; 
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'arsyadhijrah49720@gmail.com'; 
                    $mail->Password   = 'kxzq hgzn fewa nruj'; 
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    
                    $mail->setFrom('noreply@bombanakab.go.id', 'Sistem Pengaduan Bombana');
                    $mail->addAddress($reporter['email']);
                    
                    $mail->isHTML(true);
                    $mail->Subject = 'Pembaruan Status Laporan Anda - ' . $reporter['tracking_code'];
                    $mail->Body    = "
                        <h3>Halo {$reporter['name']},</h3>
                        <p>Status laporan jalan rusak Anda dengan kode <strong>{$reporter['tracking_code']}</strong> telah diperbarui.</p>
                        <p><strong>Status Baru:</strong> {$status}</p>
                        <p><strong>Tanggapan Instansi:</strong></p>
                        <blockquote style='border-left: 4px solid #8B0000; padding-left: 10px; margin-left: 0;'>
                            " . nl2br(htmlspecialchars($tanggapan)) . "
                        </blockquote>
                        <p>Silakan login ke sistem untuk mengecek riwayat secara lengkap.</p>
                        <br>
                        <p>Terima kasih,<br>Tim IT Pemkab Bombana</p>
                    ";
                    $mail->send();
                }
            } catch (Exception $e) {
                // Abaikan error email
            }

            $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm border-0 border-start border-5 border-success'>
                            <i class='fas fa-check-circle me-2'></i> Status dan tanggapan berhasil diperbarui!
                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";

        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Ambil Data Laporan
try {
    // Mark as read when admin opens detail
    $pdo->prepare("UPDATE reports SET is_read = 1 WHERE id = ?")->execute([$id]);

    $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
    $stmt->execute([$id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        echo "<script>alert('Laporan tidak ditemukan!'); window.location='reports.php';</script>";
        exit;
    }

    // Ambil komentar
    $stmt_comments = $pdo->prepare("
        SELECT c.*, u.name, u.role 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.report_id = ? 
        ORDER BY c.created_at ASC
    ");
    $stmt_comments->execute([$id]);
    $comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);

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
                
                <!-- KOMENTAR SECTION -->
                <div class="card border-0 shadow-sm mb-4 border-top border-info border-4 mt-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-comments me-2 text-info"></i> Kolom Diskusi / Komentar</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <?php if (!empty($comments)): ?>
                                <?php foreach ($comments as $c): ?>
                                    <div class="d-flex mb-3">
                                        <div class="flex-shrink-0">
                                            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold;">
                                                <?php echo strtoupper(substr($c['name'], 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <div class="bg-light p-3 rounded shadow-sm">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <h6 class="fw-bold mb-0">
                                                        <?php echo htmlspecialchars($c['name']); ?>
                                                        <?php if ($c['role'] == 'admin' || $c['role'] == 'kabid'): ?>
                                                            <span class="badge bg-danger ms-1" style="font-size: 0.65em;">Admin</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <small class="text-muted" style="font-size: 0.75rem;"><?php echo date('d M Y, H:i', strtotime($c['created_at'])); ?></small>
                                                </div>
                                                <p class="mb-0 small" style="white-space: pre-line;"><?php echo htmlspecialchars($c['comment']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted small italic">Belum ada diskusi untuk laporan ini.</p>
                            <?php endif; ?>
                        </div>

                        <form action="" method="POST">
                            <div class="mb-3">
                                <textarea name="comment" class="form-control bg-light" rows="3" placeholder="Tulis komentar/balasan Anda di sini..." required></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="submit_comment" class="btn btn-info text-white fw-bold"><i class="fas fa-paper-plane me-1"></i> Kirim Komentar</button>
                            </div>
                        </form>
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
                                    <!-- <option value="Diproses" <?php echo ($report['status'] == 'Diproses') ? 'selected' : ''; ?>>🟡 Sedang Diproses</option> -->
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