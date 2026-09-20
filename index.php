<?php
session_start();
require_once 'config/database.php';

try {
    $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
    $setting = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $setting = [];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan Pengaduan Masyarakat - Pemerintah Kabupaten Bombana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/img/logo_bombana.png" alt="Logo Bombana">
                <div>
                    <div>PEMERINTAH KABUPATEN BOMBANA</div>
                    <div style="font-size: 0.75rem; color: #DAA520; font-weight: 500;">Layanan Pengaduan Terpadu</div>
                </div>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars text-white" style="font-size: 1.5rem;"></i>
            </button>
            <div class="collapse navbar-collapse mt-3 mt-lg-0" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="submit_report.php">Buat Laporan</a></li>
                    <li class="nav-item"><a class="nav-link" href="track.php">Riwayat Laporan</a></li>
                    <?php if (!empty($setting['guide_path'])): ?>
                    <li class="nav-item"><a class="nav-link" href="uploads/<?php echo $setting['guide_path']; ?>" target="_blank"><i class="fas fa-file-pdf me-1"></i> Panduan</a></li>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id']) || isset($_SESSION['role'])): ?>
                        <li class="nav-item ms-lg-3"><a class="btn btn-outline-danger btn-sm px-3 rounded-pill" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Keluar</a></li>
                    <?php else: ?>
                        <li class="nav-item ms-lg-3"><a class="btn btn-outline-primary btn-sm px-3 rounded-pill fw-bold" href="login.php" style="border-color: var(--secondary-color); color: var(--secondary-color);"><i class="fas fa-sign-in-alt me-1"></i> Masuk</a></li>
                        <li class="nav-item ms-lg-2"><a class="btn btn-primary btn-sm px-3 rounded-pill fw-bold text-white mt-2 mt-lg-0" href="register.php" style="background-color: var(--secondary-color); border-color: var(--secondary-color);"><i class="fas fa-user-plus me-1"></i> Daftar</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <img src="assets/img/logo_bombana.png" alt="Logo" style="height: 100px; margin-bottom: 20px; filter: drop-shadow(0 0 10px rgba(0,0,0,0.5));">
                    <h1>Layanan Aspirasi dan Pengaduan Online Rakyat</h1>
                    <p>Sampaikan laporan, aspirasi, dan keluhan Anda terkait pelayanan publik di lingkungan Pemerintah Kabupaten Bombana secara mudah, aman, dan transparan.</p>
                    <div class="d-flex flex-column flex-md-row gap-3 justify-content-center mt-4">
                        <a href="submit_report.php" class="btn btn-warning btn-lg shadow w-100 w-md-auto"><i class="fas fa-pen me-2"></i> Buat Laporan</a>
                        <a href="track.php" class="btn btn-outline-light btn-lg shadow w-100 w-md-auto" style="border-width: 2px;"><i class="fas fa-history me-2"></i> Riwayat Laporan</a>
                        <?php if (!empty($setting['guide_path'])): ?>
                        <a href="uploads/<?php echo $setting['guide_path']; ?>" target="_blank" class="btn btn-info btn-lg shadow w-100 w-md-auto text-white" style="border-width: 2px;"><i class="fas fa-book me-2"></i> Panduan Penggunaan</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container card-container-overlap">
        <div class="row justify-content-center">
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center py-4">
                    <div class="card-body">
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 80px; height: 80px; color: var(--primary-color);">
                            <i class="fas fa-edit fa-2x"></i>
                        </div>
                        <h5 class="fw-bold">1. Tulis Laporan</h5>
                        <p class="text-muted small">Tuliskan kronologi laporan secara detail dan lampirkan bukti pendukung yang valid.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center py-4">
                    <div class="card-body">
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 80px; height: 80px; color: var(--secondary-color);">
                            <i class="fas fa-share fa-2x"></i>
                        </div>
                        <h5 class="fw-bold">2. Proses Verifikasi</h5>
                        <p class="text-muted small">Laporan Anda akan diverifikasi dan diteruskan kepada instansi/dinas berwenang.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center py-4">
                    <div class="card-body">
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 80px; height: 80px; color: #28a745;">
                            <i class="fas fa-check-double fa-2x"></i>
                        </div>
                        <h5 class="fw-bold">3. Tindak Lanjut</h5>
                        <p class="text-muted small">Instansi terkait akan menindaklanjuti dan menyelesaikan pengaduan Anda.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <img src="assets/img/logo_bombana.png" alt="Logo" style="height: 60px; margin-bottom: 15px; opacity: 0.8;">
                    <h5>Pemerintah Kabupaten Bombana</h5>
                    <p>Pusat Layanan Aspirasi dan Pengaduan Terpadu</p>
                    <hr>
                    <p>&copy; <?php echo date("Y"); ?> <strong class="text-gold">Pemkab Bombana</strong>. Semua Hak Dilindungi Undang-Undang.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>