<?php
require_once 'config/database.php';
require_once 'vendor/autoload.php';

// Cek user yang belum verifikasi
$stmt = $pdo->query("SELECT * FROM users WHERE is_verified = 0");
$unverified = $stmt->fetchAll();
echo "Unverified users: " . count($unverified) . "\n";

// Cek kirim email test
$mail = new \PHPMailer\PHPMailer\PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; 
    $mail->SMTPAuth   = true;
    $mail->Username   = 'arsyadhijrah49720@gmail.com'; 
    $mail->Password   = 'kxzq hgzn fewa nruj'; 
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    
    $mail->setFrom('noreply@bombanakab.go.id', 'Sistem Pengaduan Bombana');
    $mail->addAddress('test@example.com');
    $mail->Subject = 'Test Email';
    $mail->Body    = 'Test email body';
    $mail->send();
    echo "Test email sent.\n";
} catch (Exception $e) {
    echo "Mail Error: " . $mail->ErrorInfo . "\n";
    echo "Exception: " . $e->getMessage() . "\n";
}
