<?php
require_once 'config/database.php';

$token = $_GET['token'] ?? '';
$message = '';
$is_success = false;

if ($token) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->execute([$token]);
    if ($stmt->rowCount() > 0) {
        $update = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?");
        $update->execute([$token]);
        $message = "Email Anda berhasil diverifikasi! Akun Anda sekarang sudah aktif.";
        $is_success = true;
    } else {
        $message = "Token tidak valid atau akun Anda sudah diverifikasi sebelumnya.";
    }
} else {
    $message = "Token verifikasi tidak ditemukan.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Akun - Pemkab Bombana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(rgba(139, 0, 0, 0.8), rgba(139, 0, 0, 0.9)), url('assets/img/bg_hero.png') center/cover; height: 100vh; display: flex; align-items: center; }
        .card { border: none; border-radius: 15px; box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3); }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card p-4 bg-white text-center">
                    <img src="assets/img/logo_bombana.png" alt="Logo" style="height: 80px; margin: 0 auto 15px;">
                    <h4 class="fw-bold" style="color: #8B0000;">Verifikasi Akun</h4>
                    
                    <?php if ($is_success): ?>
                        <div class="alert alert-success mt-3 py-2"><i class="fas fa-check-circle me-1"></i> <?php echo $message; ?></div>
                    <?php else: ?>
                        <div class="alert alert-danger mt-3 py-2"><i class="fas fa-exclamation-circle me-1"></i> <?php echo $message; ?></div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="login.php" class="btn btn-primary fw-bold" style="background-color: #8B0000; border: none;">Ke Halaman Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
