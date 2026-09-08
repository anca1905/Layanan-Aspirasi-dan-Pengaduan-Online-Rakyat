<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';
$valid_token = false;
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = "Token tidak valid atau tidak ditemukan.";
} else {
    // Validasi token
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    if ($stmt->rowCount() > 0) {
        $valid_token = true;
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if ($password !== $confirm_password) {
                $error = "Konfirmasi password tidak cocok!";
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
                $error = "Password harus minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka!";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
                $update->execute([$hashed, $user['id']]);
                
                $success = "Password berhasil diubah! Silakan login.";
                $valid_token = false; // Sembunyikan form
            }
        }
    } else {
        $error = "Token reset password tidak valid atau sudah kadaluarsa.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Pemkab Bombana</title>
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
            <div class="col-md-5 col-lg-4">
                <div class="card login-card">
                    <div class="login-header">
                        <img src="assets/img/logo_bombana.png" alt="Logo">
                        <h5 class="fw-bold mb-0" style="color: #8B0000;">RESET PASSWORD</h5>
                        <p class="text-muted small">Masukkan password baru Anda</p>
                    </div>
                    <div class="card-body p-4 bg-white">
                        <?php if ($error): ?> <div class="alert alert-danger py-2 small"><i class="fas fa-exclamation-circle me-1"></i> <?php echo $error; ?></div> <?php endif; ?>
                        <?php if ($success): ?> 
                            <div class="alert alert-success py-2 small"><i class="fas fa-check-circle me-1"></i> <?php echo $success; ?></div> 
                            <div class="d-grid mt-3"><a href="login.php" class="btn btn-primary fw-bold py-2">LOGIN SEKARANG</a></div>
                        <?php endif; ?>
                        
                        <?php if ($valid_token): ?>
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label text-muted fw-bold small">Password Baru <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control bg-light" required pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}" title="Minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka">
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-muted fw-bold small">Konfirmasi Password <span class="text-danger">*</span></label>
                                <input type="password" name="confirm_password" class="form-control bg-light" required>
                            </div>
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary fw-bold py-2"><i class="fas fa-save me-2"></i> SIMPAN PASSWORD</button>
                            </div>
                        </form>
                        <?php endif; ?>

                        <?php if (!$valid_token && !$success): ?>
                        <div class="text-center mt-3">
                            <a href="forgot_password.php" class="btn btn-outline-secondary btn-sm">Minta Link Baru</a>
                        </div>
                        <?php endif; ?>

                        <hr class="text-muted mt-4">
                        <div class="text-center mt-2">
                            <a href="login.php" class="text-decoration-none text-muted small"><i class="fas fa-arrow-left me-1"></i> Kembali ke Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
