<?php
session_start();
require_once '../config/database.php';

// if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../login.php"); exit; }
$admin_name = $_SESSION['name'] ?? 'Administrator Sistem';

$msg = "";

// Ambil data pengaturan
try {
    $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
    $setting = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error Database: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $site_name = $_POST['site_name'];
    $instansi_name = $_POST['instansi_name'];
    $instansi_address = $_POST['instansi_address'];
    $instansi_contact = $_POST['instansi_contact'];

    $logo_sql = "";
    $guide_sql = "";
    $params = [$site_name, $instansi_name, $instansi_address, $instansi_contact];
    
    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $fileType = $_FILES['logo']['type'];
        $allowed = ['image/png', 'image/jpeg', 'image/jpg'];
        if (in_array($fileType, $allowed)) {
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $newFileName = 'logo_' . time() . '.' . $ext;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], '../assets/img/' . $newFileName)) {
                $logo_sql = ", logo_path = ?";
                $params[] = $newFileName;
            }
        } else {
            $msg = "<div class='alert alert-danger'>Format logo hanya boleh PNG / JPG.</div>";
        }
    }

    // Handle guide pdf upload
    if (empty($msg) && isset($_FILES['guide_pdf']) && $_FILES['guide_pdf']['error'] == 0) {
        $fileType = $_FILES['guide_pdf']['type'];
        $allowedPdf = ['application/pdf'];
        if (in_array($fileType, $allowedPdf) || pathinfo($_FILES['guide_pdf']['name'], PATHINFO_EXTENSION) == 'pdf') {
            $ext = pathinfo($_FILES['guide_pdf']['name'], PATHINFO_EXTENSION);
            $newPdfName = 'panduan_' . time() . '.' . $ext;

            if (move_uploaded_file($_FILES['guide_pdf']['tmp_name'], '../uploads/' . $newPdfName)) {
                $guide_sql = ", guide_path = ?";
                $params[] = $newPdfName;
            }
        } else {
            $msg = "<div class='alert alert-danger'>Format panduan hanya boleh PDF.</div>";
        }
    }

    if (empty($msg)) {
        try {
            $updateQuery = "UPDATE settings SET site_name=?, instansi_name=?, instansi_address=?, instansi_contact=? $logo_sql $guide_sql WHERE id=1";
            $stmt = $pdo->prepare($updateQuery);
            $stmt->execute($params);

            $_SESSION['success_msg'] = "Pengaturan berhasil diperbarui.";
            header("Location: settings.php");
            exit();
        } catch (PDOException $e) {
            $msg = "<div class='alert alert-danger'>Gagal memperbarui pengaturan: " . $e->getMessage() . "</div>";
        }
    }
}

$page_title = 'Pengaturan Website - Admin Pemkab Bombana';
$active_menu = 'settings';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

<div class="flex-grow-1">
    <div class="topbar d-flex justify-content-between align-items-center">
        <h4 class="mb-0 fw-bold" style="color: var(--primary-color);">Pengaturan Aplikasi</h4>
        <div class="d-flex align-items-center">
            <span class="me-3 fw-bold">Halo, <?php echo $admin_name; ?>!</span>
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin_name); ?>&background=DAA520&color=fff" class="rounded-circle" width="40" alt="Admin">
        </div>
    </div>

    <div class="container-fluid p-4">
        <?php echo $msg; ?>
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 border-start border-5 border-success">
                <i class='fas fa-check-circle me-2'></i> <?php echo $_SESSION['success_msg'];
                                                            unset($_SESSION['success_msg']); ?>
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm border-top border-primary border-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-sliders-h me-2 text-primary"></i> Konfigurasi Sistem</h5>
            </div>
            <div class="card-body p-4">
                <form action="settings.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nama Situs Tampilan (Header & Footer)</label>
                            <input type="text" name="site_name" class="form-control shadow-sm" value="<?php echo htmlspecialchars($setting['site_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nama Instansi Resmi (Untuk KOP Surat)</label>
                            <input type="text" name="instansi_name" class="form-control shadow-sm" value="<?php echo htmlspecialchars($setting['instansi_name']); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Alamat Lengkap Instansi</label>
                        <textarea name="instansi_address" class="form-control shadow-sm" rows="2" required><?php echo htmlspecialchars($setting['instansi_address']); ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Informasi Kontak (Web & Email untuk KOP)</label>
                        <input type="text" name="instansi_contact" class="form-control shadow-sm" value="<?php echo htmlspecialchars($setting['instansi_contact']); ?>" required>
                    </div>

                    <hr class="my-4">

                    <div class="mb-4 bg-light p-3 rounded border">
                        <label class="form-label fw-bold text-primary"><i class="fas fa-image me-2"></i> Logo Aplikasi</label>
                        <div class="d-flex align-items-center mt-2">
                            <img src="../assets/img/<?php echo $setting['logo_path']; ?>" class="rounded shadow-sm border p-2 bg-white me-3" style="max-height: 80px;">
                            <div class="w-100">
                                <input type="file" name="logo" class="form-control shadow-sm" accept="image/png, image/jpeg">
                                <small class="text-muted mt-1 d-block"><i class="fas fa-info-circle me-1"></i> Biarkan kosong jika tidak ingin mengubah logo. Disarankan gambar PNG transparan.</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 bg-light p-3 rounded border">
                        <label class="form-label fw-bold text-success"><i class="fas fa-file-pdf me-2"></i> Panduan Penggunaan Sistem (PDF)</label>
                        <div class="mt-2">
                            <?php if(!empty($setting['guide_path'])): ?>
                                <div class="mb-2">
                                    <a href="../uploads/<?php echo $setting['guide_path']; ?>" target="_blank" class="btn btn-sm btn-outline-success"><i class="fas fa-eye me-1"></i> Lihat Panduan Saat Ini</a>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="guide_pdf" class="form-control shadow-sm" accept="application/pdf">
                            <small class="text-muted mt-1 d-block"><i class="fas fa-info-circle me-1"></i> Unggah file PDF panduan penggunaan. Biarkan kosong jika tidak ingin mengubah panduan saat ini.</small>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" name="update_settings" class="btn btn-primary btn-lg fw-bold px-5"><i class="fas fa-save me-2"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>