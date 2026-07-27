<?php
session_start();
// if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../login.php"); exit; }

require_once '../config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $name = $_POST['name'];
        $username = $_POST['username'];
        $role = $_POST['role'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $username, $password, $role]);
            $message = "<div class='alert alert-success'>Pengguna berhasil ditambahkan.</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Gagal menambahkan. Mungkin username sudah dipakai.</div>";
        }
    } elseif ($_POST['action'] == 'delete') {
        $id = $_POST['id'];
        try {
            if ($id != $_SESSION['user_id']) { // Jangan hapus diri sendiri
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $message = "<div class='alert alert-success'>Pengguna berhasil dihapus.</div>";
            } else {
                $message = "<div class='alert alert-warning'>Anda tidak dapat menghapus akun Anda sendiri.</div>";
            }
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Gagal menghapus pengguna.</div>";
        }
    }
}

try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error Database: " . $e->getMessage());
}

$page_title = 'Kelola Pengguna - Admin Pemkab Bombana';
$active_menu = 'users';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

        <div class="flex-grow-1">
            <div class="topbar d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold" style="color: var(--primary-color);">Kelola Pengguna/User</h4>
            </div>

            <div class="container-fluid p-4">
                <?php echo $message; ?>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm border-top border-primary border-4 mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-user-plus me-2 text-primary"></i> Tambah Pengguna</h5>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST">
                                    <input type="hidden" name="action" value="add">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Nama Lengkap</label>
                                        <input type="text" name="name" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Username / NIK</label>
                                        <input type="text" name="username" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Hak Akses (Role)</label>
                                        <select name="role" class="form-select" required>
                                            <option value="pelapor">Pelapor</option>
                                            <option value="kabid">K.Bid Bina Marga</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Password</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 fw-bold">Simpan Pengguna</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm border-top border-secondary border-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-users me-2 text-secondary"></i> Daftar Pengguna</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">No</th>
                                                <th>Nama Lengkap</th>
                                                <th>Username / NIK</th>
                                                <th>Role</th>
                                                <th>Terdaftar</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; foreach ($users as $u): ?>
                                                <tr>
                                                    <td class="ps-4"><?php echo $no++; ?></td>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($u['name']); ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($u['username']); ?>
                                                        <?php if(!empty($u['nik'])) echo '<br><small class="text-muted">NIK: ' . htmlspecialchars($u['nik']) . '</small>'; ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            if ($u['role'] == 'admin') echo '<span class="badge bg-danger">Admin</span>';
                                                            elseif ($u['role'] == 'kabid') echo '<span class="badge bg-warning text-dark">K.Bid</span>';
                                                            else echo '<span class="badge bg-success">Pelapor</span>';
                                                        ?>
                                                    </td>
                                                    <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                                                    <td class="text-center">
                                                        <form action="" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun ini?');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i></button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php require_once 'layouts/footer.php'; ?>
