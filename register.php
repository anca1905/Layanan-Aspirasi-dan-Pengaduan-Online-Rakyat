<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $name = $_POST['name'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        $error = "Username hanya boleh berisi huruf dan angka, tanpa spasi atau karakter khusus!";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
        $error = "Password harus minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka!";
    } else {
        // Cek username atau email sudah ada atau belum
        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt_check->execute([$username, $email]);
        if ($stmt_check->rowCount() > 0) {
            $error = "Username atau Email tersebut sudah terdaftar! Silakan login.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password, role) VALUES (?, ?, ?, ?, 'pelapor')");
                $stmt->execute([$name, $username, $email, $hashed]);
                // Setelah registrasi berhasil, langsung arahkan ke halaman login
                header("Location: login.php?registered=1");
                exit;
            } catch (PDOException $e) {
                $error = "Terjadi kesalahan sistem: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Pelapor - Pemkab Bombana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(rgba(139, 0, 0, 0.8), rgba(139, 0, 0, 0.9)), url('assets/img/bg_hero.png') center/cover; height: 100vh; display: flex; align-items: center; }
        .login-card { border: none; border-radius: 15px; box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3); overflow: hidden; }
        .login-header { background-color: #fff; padding: 30px 20px 10px; text-align: center; }
        .login-header img { height: 80px; margin-bottom: 15px; }
        .form-control:focus { box-shadow: none; border-color: #8B0000; }
        .btn-primary { background-color: #8B0000; border-color: #8B0000; }
        .btn-primary:hover { background-color: #660000; border-color: #660000; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card login-card">
                    <div class="login-header">
                        <img src="assets/img/logo_bombana.png" alt="Logo">
                        <h5 class="fw-bold mb-0" style="color: #8B0000;">REGISTRASI AKUN</h5>
                        <p class="text-muted small">Sistem Pengaduan Jalan Kabupaten Bombana</p>
                    </div>
                    <div class="card-body p-4 bg-white">
                        <?php if ($error): ?> <div class="alert alert-danger py-2 small"><i class="fas fa-exclamation-circle me-1"></i> <?php echo $error; ?></div> <?php endif; ?>
                        <?php if ($success): ?> <div class="alert alert-success py-2 small"><i class="fas fa-check-circle me-1"></i> <?php echo $success; ?></div> <?php endif; ?>
                        
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label text-muted fw-bold small">Alamat Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control bg-light" required placeholder="Masukkan Email Anda">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted fw-bold small">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control bg-light" required placeholder="Buat Username Anda">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted fw-bold small">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control bg-light" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted fw-bold small">Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control bg-light" required pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}" title="Minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka">
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label text-muted fw-bold small">Konfirmasi <span class="text-danger">*</span></label>
                                    <input type="password" name="confirm_password" class="form-control bg-light" required>
                                </div>
                            </div>
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary fw-bold py-2"><i class="fas fa-user-plus me-2"></i> DAFTAR SEKARANG</button>
                            </div>
                        </form>
                        <hr class="text-muted">
                        <div class="text-center mt-3">
                            <span class="small text-muted">Sudah punya akun?</span> <a href="login.php" class="small fw-bold text-decoration-none" style="color: #DAA520;">Masuk di sini</a>
                        </div>
                        <div class="text-center mt-3">
                            <a href="index.php" class="text-decoration-none text-muted small"><i class="fas fa-arrow-left me-1"></i> Kembali ke Beranda</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
