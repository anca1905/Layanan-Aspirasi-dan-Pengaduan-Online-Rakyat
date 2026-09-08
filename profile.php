<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pelapor') {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';
$user_id = $_SESSION['user_id'];

// Ambil data user terkini dari DB
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_name = trim($_POST['name'] ?? '');
    $new_password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($new_name)) {
        $error = "Nama lengkap tidak boleh kosong.";
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } elseif (!empty($new_password) && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $new_password)) {
        $error = "Password harus minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka!";
    } else {
        try {
            if (!empty($new_password)) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_update = $pdo->prepare("UPDATE users SET name = ?, password = ? WHERE id = ?");
                $stmt_update->execute([$new_name, $hashed, $user_id]);
            } else {
                $stmt_update = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
                $stmt_update->execute([$new_name, $user_id]);
            }
            // Update session
            $_SESSION['name'] = $new_name;
            $user['name'] = $new_name;
            $message = "Profil berhasil diperbarui!";
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Pemkab Bombana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
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
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link" href="index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="submit_report.php">Buat Laporan</a></li>
                    <li class="nav-item"><a class="nav-link" href="track.php">Riwayat Laporan</a></li>
                    <li class="nav-item"><a class="nav-link active fw-bold" href="profile.php"><i class="fas fa-user me-1"></i> Profil</a></li>
                    <li class="nav-item ms-lg-3"><a class="btn btn-outline-danger btn-sm px-3 rounded-pill" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Keluar</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">

                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color: var(--primary-color);">Beranda</a></li>
                        <li class="breadcrumb-item active">Profil Saya</li>
                    </ol>
                </nav>

                <!-- Kartu Identitas -->
                <div class="card shadow-sm border-0 mb-4" style="border-top: 4px solid var(--secondary-color) !important;">
                    <div class="card-body p-4 d-flex align-items-center gap-4">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:80px;height:80px;background:var(--primary-color);">
                            <i class="fas fa-user fa-2x text-white"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user['name']); ?></h5>
                            <p class="text-muted mb-1 small"><i class="fas fa-id-card me-1"></i> NIK: <strong><?php echo htmlspecialchars($user['nik'] ?? '-'); ?></strong></p>
                            <p class="text-muted mb-1 small"><i class="fas fa-user-circle me-1"></i> Username: <strong><?php echo htmlspecialchars($user['username']); ?></strong></p>
                            <span class="badge" style="background-color: var(--primary-color);">Pelapor</span>
                        </div>
                    </div>
                </div>

                <!-- Form Edit Profil -->
                <div class="card shadow-lg border-0" style="border-top: 5px solid var(--primary-color) !important;">
                    <div class="card-header bg-white pt-4 pb-0 border-0 text-center">
                        <h4 class="fw-bold" style="color: var(--primary-color);"><i class="fas fa-user-edit me-2"></i>Edit Profil</h4>
                        <p class="text-muted small">NIK dan Username Anda tidak dapat diubah</p>
                    </div>
                    <div class="card-body p-4 p-md-5">

                        <?php if ($message): ?>
                            <div class="alert alert-success py-2 small"><i class="fas fa-check-circle me-1"></i> <?php echo $message; ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2 small"><i class="fas fa-exclamation-circle me-1"></i> <?php echo $error; ?></div>
                        <?php endif; ?>

                        <form action="" method="POST">
                            <!-- NIK (readonly) -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">NIK (No. KTP) <span class="text-muted small fw-normal">— tidak dapat diubah</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-id-card text-muted"></i></span>
                                    <input type="text" class="form-control bg-light text-muted" value="<?php echo htmlspecialchars($user['nik'] ?? ''); ?>" readonly>
                                </div>
                            </div>

                            <!-- Username (readonly) -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Username <span class="text-muted small fw-normal">— tidak dapat diubah</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                                    <input type="text" class="form-control bg-light text-muted" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                </div>
                            </div>

                            <!-- Nama Lengkap -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                            </div>

                            <hr class="my-4">
                            <p class="fw-bold text-muted small mb-3"><i class="fas fa-lock me-1"></i> Ganti Password (Kosongkan jika tidak ingin mengubah)</p>

                            <!-- Password Baru -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted fw-bold small">Password Baru <span class="text-secondary fw-normal">(Kosongkan jika tidak ingin mengubah)</span></label>
                                    <input type="password" name="password" class="form-control bg-light" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}" title="Minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka">
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label text-muted fw-bold small">Konfirmasi Password Baru</label>
                                    <input type="password" name="confirm_password" class="form-control bg-light">
                                </div>
                            </div>

                            <div class="d-grid mt-2">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold" style="background-color: var(--primary-color); border-color: var(--primary-color);">
                                    <i class="fas fa-save me-2"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>

                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="submit_report.php" class="btn btn-warning fw-bold px-4">
                        <i class="fas fa-pen me-2"></i> Buat Laporan Baru
                    </a>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
