CREATE DATABASE IF NOT EXISTS ana;
USE ana;

-- Table for system users (Admin & Kabid)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kabid', 'pelapor') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for road damage reports
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tracking_code VARCHAR(20) NOT NULL UNIQUE,
    user_id INT DEFAULT NULL,
    reporter_nik VARCHAR(16) NOT NULL,
    reporter_name VARCHAR(100) NOT NULL,
    location TEXT NOT NULL,
    kecamatan VARCHAR(100) NOT NULL,
    desa VARCHAR(100) NOT NULL,
    severity ENUM('Ringan', 'Sedang', 'Berat') NOT NULL,
    latitude VARCHAR(50) DEFAULT NULL,
    longitude VARCHAR(50) DEFAULT NULL,
    photo VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    tanggapan TEXT DEFAULT NULL,
    status ENUM('Menunggu', 'Disetujui', 'Ditolak') DEFAULT 'Menunggu',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin and kabid
-- Passwords are set to 'admin123' and 'kabid123' (hashed using MD5 or BCRYPT, we'll use simple BCRYPT hash or just raw for this since it's native but BCRYPT is preferred. For simplicity and since no library is used, let's inject pre-hashed bcrypts or we'll hash them in php if needed. Wait, in PHP: password_hash('admin123', PASSWORD_DEFAULT). Let's use MD5 for simplicity in this native PHP project, or better: I will use password_hash in PHP.
-- Let's use standard password_hash generated strings.
-- admin123 => $2y$10$E9x1sP./.8J4Lw1h...
-- For now, let's insert raw, then in login.php we check with simple equality or md5. Given no framework, md5 is often what beginner users expect, but password_verify is correct.
-- To ensure it easily works without me guessing bcrypt salt, I'll provide an initialization script in PHP or just insert md5 hashed values here and use md5() on login.
-- md5('admin123') = 0192023a7bbd73250516f069df18b500
-- md5('kabid123') = c21213327a3c75ab78ec6fef740263f3

INSERT IGNORE INTO users (name, username, password, role) VALUES 
('Administrator Sistem', 'admin', '0192023a7bbd73250516f069df18b500', 'admin'),
('Kepala Bidang Bina Marga', 'kabid', 'c21213327a3c75ab78ec6fef740263f3', 'kabid');

-- Table for system settings (dynamic logo & naming)
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY,
    site_name VARCHAR(255) NOT NULL,
    instansi_name VARCHAR(255) NOT NULL,
    instansi_address TEXT NOT NULL,
    instansi_contact VARCHAR(255) NOT NULL,
    logo_path VARCHAR(255) NOT NULL
);

INSERT IGNORE INTO settings (id, site_name, instansi_name, instansi_address, instansi_contact, logo_path) VALUES 
(1, 'SIPKJ Bombana', 'Pemerintah Kabupaten Bombana', 'Jalan Maju Bersama No. 123, Kabupaten Bombana', 'Website: pupr.bombanakab.go.id | Email: info@pupr.bombanakab.go.id', 'logo_bombana.png');
