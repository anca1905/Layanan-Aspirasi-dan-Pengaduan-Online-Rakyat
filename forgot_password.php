<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Silakan masukkan email Anda.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $token = bin2hex(random_bytes(32));
            $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = ?");
            $update->execute([$token, $email]);
            
            $success = "Instruksi reset password telah diproses.";
            
            // Pengiriman email dengan PHPMailer
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $path = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/');
            $reset_link = "$protocol://$host$path/reset_password.php?token=$token";
            
            require 'vendor/autoload.php';
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            try {
                // Konfigurasi SMTP
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; // Ganti dengan SMTP Anda
                $mail->SMTPAuth   = true;
                $mail->Username   = 'arsyadhijrah49720@gmail.com'; // Ganti dengan Email Anda
                $mail->Password   = 'kxzq hgzn fewa nruj'; // Ganti dengan App Password Email Anda
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Pengirim & Penerima
                $mail->setFrom('noreply@bombanakab.go.id', 'Sistem Pengaduan Bombana');
                $mail->addAddress($email);

                // Konten Email
                $mail->isHTML(true);
                $mail->Subject = 'Reset Password - Sistem Pengaduan Bombana';
                $mail->Body    = "
                    <h3>Halo,</h3>
                    <p>Kami menerima permintaan untuk mereset password akun Anda di Sistem Pengaduan Jalan Kabupaten Bombana.</p>
                    <p>Silakan klik link di bawah ini untuk mengatur ulang password Anda:</p>
                    <p><a href='{$reset_link}' style='padding: 10px 15px; background-color: #8B0000; color: white; text-decoration: none; border-radius: 5px;'>Reset Password Sekarang</a></p>
                    <p>Link ini akan kadaluarsa dalam 1 jam.</p>
                    <p>Jika Anda tidak pernah meminta reset password, abaikan email ini.</p>
                    <br>
                    <p>Terima kasih,<br>Tim IT Pemkab Bombana</p>
                ";
                $mail->AltBody = "Halo,\n\nKami menerima permintaan untuk mereset password akun Anda.\n\nSilakan klik link berikut untuk mereset password Anda:\n{$reset_link}\n\nJika Anda tidak meminta reset password, abaikan pesan ini.\n\nTerima kasih,\nTim IT Pemkab Bombana";

                $mail->send();
                $success = "Instruksi reset password telah dikirim ke email Anda. Silakan cek Inbox atau folder Spam.";
            } catch (Exception $e) {
                $error = "Pesan gagal dikirim. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            // Untuk keamanan, tetap tampilkan pesan sukses agar tidak bisa menebak email yang terdaftar
            $success = "Instruksi reset password telah dikirim ke email Anda. Silakan cek Inbox atau folder Spam.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Pemkab Bombana</title>
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
                        <h5 class="fw-bold mb-0" style="color: #8B0000;">LUPA PASSWORD</h5>
                        <p class="text-muted small">Sistem Pengaduan Jalan Kabupaten Bombana</p>
                    </div>
                    <div class="card-body p-4 bg-white">
                        <?php if ($error): ?> <div class="alert alert-danger py-2 small"><i class="fas fa-exclamation-circle me-1"></i> <?php echo $error; ?></div> <?php endif; ?>
                        <?php if ($success): ?> <div class="alert alert-success py-2 small"><i class="fas fa-check-circle me-1"></i> <?php echo $success; ?></div> <?php endif; ?>
                        
                        <form action="" method="POST">
                            <div class="mb-4">
                                <label class="form-label text-muted fw-bold small">Alamat Email Terdaftar</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                                    <input type="email" name="email" class="form-control bg-light" required placeholder="Masukkan email Anda...">
                                </div>
                            </div>
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary fw-bold py-2"><i class="fas fa-paper-plane me-2"></i> KIRIM LINK RESET</button>
                            </div>
                        </form>

                        <hr class="text-muted">
                        <div class="text-center mt-3">
                            <a href="login.php" class="text-decoration-none text-muted small"><i class="fas fa-arrow-left me-1"></i> Kembali ke Halaman Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
