<?php
session_start();

require_once 'config/database.php';

$error = '';
$success_msg = '';
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success_msg = "Registrasi berhasil! Silakan masuk dengan Username atau Email dan password Anda.";
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password']) || md5($password) === $user['password']) {
                if ($user['role'] === 'pelapor' && isset($user['is_verified']) && $user['is_verified'] == 0) {
                    $error = "Akun Anda belum diverifikasi. Silakan cek email Anda untuk memverifikasi akun.";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
                    if ($user['role'] === 'admin') {
                        header("Location: admin/index.php");
                        exit;
                    } elseif ($user['role'] === 'kabid') {
                        header("Location: kabid/index.php");
                        exit;
                    } elseif ($user['role'] === 'pelapor') {
                        header("Location: submit_report.php");
                        exit;
                    }
                }
            } else {
                $error = "Password yang Anda masukkan salah!";
            }
        } else {
            $error = "Username atau Email tidak terdaftar!";
        }
    } catch (PDOException $e) {
        $error = "System Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pemkab Bombana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(rgba(139, 0, 0, 0.8), rgba(139, 0, 0, 0.9)), url('assets/img/bg_hero.png') center/cover;
            height: 100vh;
            display: flex;
            align-items: center;
        }

        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .login-header {
            background-color: #fff;
            padding: 30px 20px 10px;
            text-align: center;
        }

        .login-header img {
            height: 80px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">

                <div class="card login-card">
                    <div class="login-header">
                        <img src="assets/img/logo_bombana.png" alt="Logo Bombana">
                        <h5 class="fw-bold mb-0" style="color: var(--primary-color);">SISTEM PENGADUAN</h5>
                        <p class="text-muted small">Pemerintah Kabupaten Bombana</p>
                    </div>

                    <div class="card-body p-4 bg-white">
                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2 small"><i class="fas fa-exclamation-circle me-1"></i> <?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success_msg): ?>
                            <div class="alert alert-success py-2 small"><i class="fas fa-check-circle me-1"></i> <?php echo $success_msg; ?></div>
                        <?php endif; ?>

                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label text-muted fw-bold small">Username atau Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                                    <input type="text" name="username" class="form-control bg-light" placeholder="Masukkan Username atau Email Anda..." required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-muted fw-bold small">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                    <input type="password" name="password" class="form-control bg-light" placeholder="Masukkan password..." required>
                                </div>
                                <div class="text-end mt-2">
                                    <a href="forgot_password.php" class="small text-decoration-none" style="color: var(--primary-color);">Lupa Password?</a>
                                </div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary fw-bold py-2"><i class="fas fa-sign-in-alt me-2"></i> MASUK</button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <span class="small text-muted">Belum punya akun Pelapor?</span> <a href="register.php" class="small fw-bold text-decoration-none" style="color: var(--primary-color);">Daftar di sini</a>
                        </div>
                        <div class="text-center mt-4">
                            <a href="index.php" class="text-decoration-none text-muted small"><i class="fas fa-arrow-left me-1"></i> Kembali ke Beranda Publik</a>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3 text-white-50 small">
                    &copy; <?php echo date('Y'); ?> IT Support Pemkab Bombana
                </div>

            </div>
        </div>
    </div>

</body>

</html>