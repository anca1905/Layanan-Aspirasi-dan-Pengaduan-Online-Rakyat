<?php
require_once 'config/database.php';
session_start();

$report_detail = null;
$error = '';
$reports = [];

// Jika sedang melihat detail laporan
if (isset($_GET['tracking_id']) && !empty(trim($_GET['tracking_id']))) {
    $tracking_id = trim($_GET['tracking_id']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM reports WHERE tracking_code = ?");
        $stmt->execute([$tracking_id]);
        $report_detail = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$report_detail) {
            $error = "Data laporan tidak ditemukan.";
        }
    } catch (PDOException $e) {
        $error = "Terjadi kesalahan sistem: " . $e->getMessage();
    }
} else {
    // Ambil riwayat laporan (Publik) berdasarkan pencarian jika ada
    try {
        if (isset($_GET['search_code']) && !empty(trim($_GET['search_code']))) {
            $search_code = trim($_GET['search_code']);
            $stmt = $pdo->prepare("SELECT * FROM reports WHERE tracking_code LIKE ? ORDER BY created_at DESC");
            $stmt->execute(['%' . $search_code . '%']);
        } else {
            $stmt = $pdo->query("SELECT * FROM reports ORDER BY created_at DESC");
        }
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Terjadi kesalahan sistem: " . $e->getMessage();
    }
}

function getStatusBadge($status) {
    $status = strtolower($status);
    if ($status == 'menunggu') {
        return '<span class="status-badge status-pending"><i class="fas fa-clock me-1"></i> Menunggu</span>';
    } elseif ($status == 'disetujui') {
        return '<span class="status-badge status-selesai"><i class="fas fa-check-circle me-1"></i> Disetujui</span>';
    } elseif ($status == 'ditolak') {
        return '<span class="status-badge status-ditolak"><i class="fas fa-times-circle me-1"></i> Ditolak</span>';
    }
    return '<span class="badge bg-secondary p-2">' . htmlspecialchars($status) . '</span>';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Laporan - Pemerintah Kabupaten Bombana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .status-badge {
            font-size: 0.85rem;
            padding: 6px 12px;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }
        .status-pending { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .status-selesai { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-ditolak { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .table-custom th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border: none;
        }
        .table-custom td {
            vertical-align: middle;
        }
        .table-custom tbody tr {
            transition: all 0.2s ease-in-out;
        }
        .table-custom tbody tr:hover {
            background-color: rgba(0,0,0,0.02);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body class="bg-light">

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
                    <li class="nav-item"><a class="nav-link" href="index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="submit_report.php">Buat Laporan</a></li>
                    <li class="nav-item"><a class="nav-link active fw-bold" href="track.php">Riwayat Laporan</a></li>
                    
                    <?php if (isset($_SESSION['user_id']) || isset($_SESSION['role'])): ?>
                        <li class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i> Profil</a></li>
                        <li class="nav-item ms-lg-3"><a class="btn btn-outline-danger btn-sm px-3 rounded-pill" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Keluar</a></li>
                    <?php else: ?>
                        <li class="nav-item ms-lg-3"><a class="btn btn-outline-primary btn-sm px-3 rounded-pill fw-bold" href="login.php" style="border-color: var(--secondary-color); color: var(--secondary-color);"><i class="fas fa-sign-in-alt me-1"></i> Masuk</a></li>
                        <li class="nav-item ms-lg-2"><a class="btn btn-primary btn-sm px-3 rounded-pill fw-bold text-white mt-2 mt-lg-0" href="register.php" style="background-color: var(--secondary-color); border-color: var(--secondary-color);"><i class="fas fa-user-plus me-1"></i> Daftar</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color: var(--primary-color);">Beranda</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Riwayat Laporan</li>
                    </ol>
                </nav>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger shadow-sm border-0 border-start border-5 border-danger">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                        <div class="mt-2"><a href="track.php" class="btn btn-sm btn-outline-danger">Kembali ke Riwayat</a></div>
                    </div>
                <?php endif; ?>

                <?php if ($report_detail): ?>
                    <!-- Tampilan Detail Laporan -->
                    <div class="mb-3">
                        <a href="track.php" class="btn btn-secondary btn-sm shadow-sm"><i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Laporan</a>
                    </div>
                    <div class="card shadow-lg border-0 mb-5" style="border-top: 5px solid var(--primary-color) !important; border-radius: 12px; overflow: hidden;">
                        <div class="card-header bg-white pt-4 pb-3 border-bottom text-center">
                            <h4 class="fw-bold text-dark mb-1">Detail Laporan Anda</h4>
                            <span class="text-muted small">Tracking ID: <strong class="text-primary"><?php echo htmlspecialchars($report_detail['tracking_code']); ?></strong></span>
                        </div>
                        <div class="card-body p-4 p-md-5">

                            <div class="text-center mb-4 pb-4 border-bottom">
                                <h6 class="text-muted text-uppercase fw-bold mb-3">Status Saat Ini:</h6>
                                <?php echo getStatusBadge($report_detail['status']); ?>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <p class="text-muted small mb-1">Tanggal Dilaporkan</p>
                                    <h6 class="fw-bold"><i class="fas fa-calendar-alt me-2 text-primary"></i><?php echo date('d F Y, H:i', strtotime($report_detail['created_at'])); ?></h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <p class="text-muted small mb-1">Kategori Pengaduan</p>
                                    <h6 class="fw-bold"><i class="fas fa-tag me-2 text-warning"></i><?php echo htmlspecialchars($report_detail['severity']); ?></h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <p class="text-muted small mb-1">Nama Pelapor</p>
                                    <h6 class="fw-bold"><i class="fas fa-user me-2 text-secondary"></i><?php echo htmlspecialchars($report_detail['reporter_name']); ?></h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <p class="text-muted small mb-1">Kecamatan / Desa</p>
                                    <h6 class="fw-bold"><i class="fas fa-map me-2 text-info"></i><?php echo htmlspecialchars($report_detail['kecamatan'] ?? '') . ' / ' . htmlspecialchars($report_detail['desa'] ?? ''); ?></h6>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <p class="text-muted small mb-1">Detail Lokasi Kejadian</p>
                                    <h6 class="fw-bold"><i class="fas fa-map-marker-alt me-2 text-danger"></i><?php echo htmlspecialchars($report_detail['location']); ?></h6>
                                    <?php if (!empty($report_detail['latitude']) && !empty($report_detail['longitude'])): ?>
                                        <div class="mt-2">
                                            <a href="https://www.google.com/maps?q=<?php echo $report_detail['latitude']; ?>,<?php echo $report_detail['longitude']; ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-map-marked-alt me-1"></i> Lihat di Google Maps
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mt-3 p-4 bg-light rounded border-0 shadow-sm">
                                <p class="text-muted small mb-2 fw-bold"><i class="fas fa-align-left me-1"></i> Isi Laporan:</p>
                                <p class="mb-0" style="white-space: pre-line;"><?php echo htmlspecialchars($report_detail['description']); ?></p>
                            </div>

                            <?php if (!empty($report_detail['photo'])): ?>
                                <div class="mt-4">
                                    <p class="text-muted small mb-2 fw-bold"><i class="fas fa-camera retro me-1"></i> Lampiran Bukti:</p>
                                    <div class="d-flex flex-wrap gap-3 justify-content-center">
                                        <?php 
                                        $photos = explode(',', $report_detail['photo']);
                                        foreach($photos as $foto):
                                            $foto = trim($foto);
                                            if (!empty($foto) && file_exists('uploads/' . $foto)):
                                        ?>
                                            <img src="uploads/<?php echo htmlspecialchars($foto); ?>" alt="Bukti Laporan" class="img-fluid rounded shadow" style="max-height: 400px; object-fit: cover; flex: 1 1 auto; max-width: 100%;">
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($report_detail['tanggapan'])): ?>
                                <div class="mt-5 p-4 rounded shadow-sm" style="background-color: #f8f9fa; border-left: 5px solid var(--secondary-color);">
                                    <h6 class="fw-bold" style="color: var(--primary-color);"><i class="fas fa-reply me-2"></i>Tanggapan Instansi Terkait:</h6>
                                    <p class="mb-0 mt-3" style="white-space: pre-line;"><?php echo htmlspecialchars($report_detail['tanggapan']); ?></p>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                <?php elseif (!isset($_GET['tracking_id'])): ?>
                    <!-- Tampilan Tabel Riwayat Laporan -->
                    <div class="card shadow-lg border-0 mb-5" style="border-top: 5px solid var(--primary-color) !important; border-radius: 12px; overflow: hidden;">
                        <div class="card-header bg-white pt-4 pb-3 border-0 d-flex justify-content-between align-items-center flex-wrap">
                            <div class="mb-3 mb-md-0">
                                <h3 class="fw-bold mb-0" style="color: var(--primary-color);"><i class="fas fa-history me-2"></i>Riwayat Laporan</h3>
                                <p class="text-muted small mb-0 mt-1">Daftar seluruh laporan pengaduan masyarakat yang telah masuk.</p>
                            </div>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <form action="track.php" method="GET" class="d-flex shadow-sm rounded">
                                    <input type="text" name="search_code" class="form-control border-end-0 shadow-none" placeholder="Cari Kode Laporan..." value="<?php echo isset($_GET['search_code']) ? htmlspecialchars($_GET['search_code']) : ''; ?>" style="border-top-right-radius: 0; border-bottom-right-radius: 0; min-width: 200px;">
                                    <button class="btn btn-primary border-start-0" type="submit" style="border-top-left-radius: 0; border-bottom-left-radius: 0;"><i class="fas fa-search"></i></button>
                                </form>
                                <a href="submit_report.php" class="btn btn-warning fw-bold shadow-sm"><i class="fas fa-plus me-1"></i> Laporan Baru</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-custom mb-0 text-center">
                                    <thead>
                                        <tr>
                                            <th class="py-3" style="width: 5%;">No</th>
                                            <th class="py-3" style="width: 20%;">Tanggal</th>
                                            <th class="py-3" style="width: 25%;">Kode Laporan</th>
                                            <th class="py-3" style="width: 15%;">Kategori</th>
                                            <th class="py-3" style="width: 20%;">Status</th>
                                            <th class="py-3" style="width: 15%;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white border-top-0">
                                        <?php if (count($reports) > 0): ?>
                                            <?php $no = 1; foreach ($reports as $r): ?>
                                                <tr>
                                                    <td class="text-muted fw-bold"><?php echo $no++; ?></td>
                                                    <td>
                                                        <div class="fw-bold text-dark"><?php echo date('d M Y', strtotime($r['created_at'])); ?></div>
                                                        <small class="text-muted"><i class="fas fa-clock me-1"></i><?php echo date('H:i', strtotime($r['created_at'])); ?></small>
                                                    </td>
                                                    <td class="fw-bold font-monospace text-primary align-middle"><?php echo htmlspecialchars($r['tracking_code']); ?></td>
                                                    <td class="align-middle">
                                                        <span class="badge bg-light text-dark border"><i class="fas fa-tag me-1 text-warning"></i><?php echo htmlspecialchars($r['severity']); ?></span>
                                                    </td>
                                                    <td class="align-middle"><?php echo getStatusBadge($r['status']); ?></td>
                                                    <td class="align-middle">
                                                        <a href="track.php?tracking_id=<?php echo urlencode($r['tracking_code']); ?>" class="btn btn-sm btn-info text-white shadow-sm" title="Lihat Detail">
                                                            <i class="fas fa-eye me-1"></i> Detail
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="py-5 text-center text-muted">
                                                    <i class="fas fa-folder-open fa-4x mb-3 opacity-25"></i>
                                                    <h5 class="fw-bold text-secondary">Belum Ada Riwayat</h5>
                                                    <p class="mb-0">Belum ada laporan pengaduan yang masuk ke sistem.</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <footer class="mt-auto">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
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