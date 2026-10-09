<?php
require_once 'config/database.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pelapor') {
    header("Location: login.php");
    exit;
}

$message = '';
$tracking_code = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sesuaikan dengan nama kolom di database ana.sql
    $user_id = $_SESSION['user_id'];
    $nama = $_SESSION['name'];
    $lokasi = $_POST['location'] ?? '';
    $kecamatan = $_POST['kecamatan'] ?? '';
    $desa = $_POST['desa'] ?? '';
    $severity = $_POST['severity'] ?? 'Sedang';
    $deskripsi = $_POST['description'] ?? '';
    $lat = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
    $lng = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;

    // ===== VALIDASI KOORDINAT WILAYAH BOMBANA (Server-Side) =====
    if ($lat === null || $lng === null) {
        $message = "<div class='alert alert-danger border-start border-5 border-danger'>
                        <h5 class='alert-heading fw-bold'><i class='fas fa-map-marker-alt me-2'></i>Koordinat Lokasi Wajib Diisi!</h5>
                        <p class='mb-0'>Anda harus mendeteksi lokasi menggunakan GPS atau memilih titik dari peta sebelum mengirim laporan.</p>
                    </div>";
        goto end_post;
    }

    // Validasi Wilayah Resmi Kabupaten Bombana (Point-In-Polygon)
    if (!function_exists('isPointInBombanaPHP')) {
        function pointInPolygonPHP($x, $y, $vs) {
            $inside = false;
            $count = count($vs);
            for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
                $xi = $vs[$i][0]; $yi = $vs[$i][1];
                $xj = $vs[$j][0]; $yj = $vs[$j][1];
                $intersect = (($yi > $y) != ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);
                if ($intersect) $inside = !$inside;
            }
            return $inside;
        }

        function isPointInBombanaPHP($lat, $lng) {
            static $boundary = null;
            if ($boundary === null) {
                $json = @file_get_contents(__DIR__ . '/assets/bombana_boundary_simple.json');
                $boundary = json_decode($json, true);
            }
            if (!$boundary || empty($boundary['coordinates'])) {
                return $lat >= -5.4 && $lat <= -4.2 && $lng >= 121.2 && $lng <= 122.4;
            }
            $pt = [$lng, $lat];
            foreach ($boundary['coordinates'] as $poly) {
                if (pointInPolygonPHP($pt[0], $pt[1], $poly[0])) {
                    $inHole = false;
                    for ($h = 1; $h < count($poly); $h++) {
                        if (pointInPolygonPHP($pt[0], $pt[1], $poly[$h])) {
                            $inHole = true;
                            break;
                        }
                    }
                    if (!$inHole) return true;
                }
            }
            return false;
        }
    }

    if (!isPointInBombanaPHP($lat, $lng)) {
        $message = "<div class='alert alert-danger border-start border-5 border-danger'>
                        <h5 class='alert-heading fw-bold'><i class='fas fa-ban me-2'></i>Lokasi Ditolak!</h5>
                        <p class='mb-0'>Koordinat yang Anda masukkan berada di luar batas resmi wilayah Kabupaten Bombana (terdeteksi Kolaka/Kabupaten tetangga). Sistem hanya menerima pengaduan untuk wilayah Kabupaten Bombana.</p>
                    </div>";
        goto end_post;
    }


    // Handle File Upload (Foto Bukti)
    $foto = '';
    $uploaded_photos = [];
    if (isset($_FILES['photo']) && is_array($_FILES['photo']['name'])) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $allowed_types = ['jpg', 'jpeg', 'png'];
        $total_files = count($_FILES['photo']['name']);

        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['photo']['error'][$i] == 0) {
                $file_extension = pathinfo($_FILES["photo"]["name"][$i], PATHINFO_EXTENSION);
                if (in_array(strtolower($file_extension), $allowed_types)) {
                    // Penamaan: TrackingCode_urutan.ext
                    $foto_name = $tracking_code . '_' . ($i + 1) . '.' . $file_extension;
                    $target_file = $target_dir . $foto_name;
                    if (move_uploaded_file($_FILES["photo"]["tmp_name"][$i], $target_file)) {
                        $uploaded_photos[] = $foto_name;
                    }
                }
            }
        }
        $foto = implode(',', $uploaded_photos); // Gabungkan dengan koma
    }

    try {
        // Query disesuaikan persis dengan struktur ana.sql
        $stmt = $pdo->prepare("INSERT INTO reports (tracking_code, user_id, reporter_name, location, kecamatan, desa, severity, latitude, longitude, photo, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Diproses')");
        $stmt->execute([$tracking_code, $user_id, $nama, $lokasi, $kecamatan, $desa, $severity, $lat, $lng, $foto, $deskripsi]);

        // Notifikasi email ke admin PUPR
        try {
            $stmtAdmin = $pdo->prepare("SELECT email FROM users WHERE role = 'admin' AND email IS NOT NULL LIMIT 1");
            $stmtAdmin->execute();
            if ($admin = $stmtAdmin->fetch()) {
                $admin_email = $admin['email'];
                require_once 'vendor/autoload.php';
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; 
                $mail->SMTPAuth   = true;
                $mail->Username   = 'arsyadhijrah49720@gmail.com'; 
                $mail->Password   = 'kxzq hgzn fewa nruj'; 
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->setFrom('noreply@bombanakab.go.id', 'Sistem Pengaduan Bombana');
                $mail->addAddress($admin_email);
                $mail->isHTML(true);
                $mail->Subject = 'Laporan Jalan Rusak Baru - ' . $tracking_code;
                $mail->Body    = "
                    <h3>Halo Admin PUPR,</h3>
                    <p>Ada laporan jalan rusak baru dari pelapor <strong>{$nama}</strong>.</p>
                    <ul>
                        <li><strong>Kode Laporan:</strong> {$tracking_code}</li>
                        <li><strong>Kecamatan:</strong> {$kecamatan}</li>
                        <li><strong>Desa:</strong> {$desa}</li>
                        <li><strong>Tingkat Kerusakan:</strong> {$severity}</li>
                        <li><strong>Lokasi:</strong> {$lokasi}</li>
                    </ul>
                    <p>Silakan login ke sistem untuk menindaklanjuti laporan ini.</p>
                ";
                $mail->send();
            }
        } catch (Exception $e) {
            // Abaikan error email agar laporan tetap tersimpan
        }

        $message = "<div class='alert alert-success shadow-sm border-0 border-start border-5 border-success rounded-end'>
                        <h5 class='alert-heading fw-bold'><i class='fas fa-check-circle me-2'></i>Laporan Berhasil Terkirim!</h5>
                        <p>Terima kasih. Laporan Anda beserta titik koordinat lokasi telah masuk ke sistem kami.</p>
                        <hr>
                        <p class='mb-0'>Kode Pelacakan Anda: <strong class='fs-4 text-primary'>$tracking_code</strong></p>
                        <p class='small text-muted mt-2'>*Harap simpan kode pelacakan ini untuk mengecek status laporan Anda.</p>
                    </div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i> Terjadi kesalahan: " . $e->getMessage() . "</div>";
    }
    end_post:
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Laporan - Pemkab Bombana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/img/logo_bombana.png" alt="Logo Bombana">
                <div>
                    <div>PEMERINTAH KABUPATEN BOMBANA</div>
                    
                </div>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars text-white" style="font-size: 1.5rem;"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link" href="index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link active" href="submit_report.php">Buat Laporan</a></li>
                    <li class="nav-item"><a class="nav-link" href="track.php">Riwayat Laporan</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i> Profil</a></li>
                    <li class="nav-item ms-lg-3"><a class="btn btn-outline-danger btn-sm px-3 rounded-pill" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Keluar</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color: var(--primary-color);">Beranda</a></li>
                        <li class="breadcrumb-item active">Buat Laporan</li>
                    </ol>
                </nav>

                <?php echo $message; ?>

                <?php if (empty($message) || strpos($message, 'alert-danger') !== false): ?>
                    <div class="card shadow-lg border-0" style="border-top: 5px solid var(--primary-color) !important;">
                        <div class="card-header bg-white pt-4 pb-0 border-0 text-center">
                            <h3 class="fw-bold" style="color: var(--primary-color);">Formulir Pelaporan Infrastruktur</h3>
                            <p class="text-muted">Lengkapi data di bawah ini. Titik lokasi Anda akan dideteksi secara otomatis.</p>
                        </div>
                        <div class="card-body p-4 p-md-5">
                            <!-- Info pelapor: Nama diambil dari profil -->
                            <div class="alert alert-info py-2 small mb-3 d-flex align-items-center gap-2">
                                <i class="fas fa-user-circle fa-lg"></i>
                                <div>Laporan ini akan dikirim atas nama <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>. <a href="profile.php" class="fw-bold">Edit Profil</a></div>
                            </div>

                            <form action="" method="POST" enctype="multipart/form-data">

                                <div class="mb-4 p-3 bg-light rounded border border-warning">
                                    <label class="form-label fw-bold text-dark"><i class="fas fa-map-marker-alt text-danger me-2"></i>Titik Koordinat Lokasi Laporan (Wajib)</label>
                                    <p class="small text-muted mb-2">Pilih lokasi melalui peta atau deteksi otomatis saat Anda berada di lokasi jalan yang rusak agar sistem dapat mencatat titik GPS secara akurat.</p>

                                    <div class="d-flex flex-column flex-md-row gap-2 mb-3">
                                        <button type="button" id="btnLokasi" class="btn btn-warning fw-bold text-dark flex-grow-1">
                                            <i class="fas fa-location-arrow me-2"></i> Deteksi Lokasi Saat Ini
                                        </button>
                                        <button type="button" id="btnPeta" class="btn btn-info fw-bold text-white flex-grow-1" data-bs-toggle="modal" data-bs-target="#mapModal">
                                            <i class="fas fa-map me-2"></i> Pilih dari Peta
                                        </button>
                                    </div>

                                    <div id="lokasiStatus" class="small fw-bold mt-2 mb-2"></div>
                                    <div id="miniMapContainer" style="height: 150px; display: none; border-radius: 8px; border: 1px solid #ccc; z-index: 1;"></div>
                                    <div id="radiusWarning" class="mt-2"></div>

                                    <input type="hidden" name="latitude" id="lat">
                                    <input type="hidden" name="longitude" id="lng">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nama Kecamatan <span class="text-danger">*</span></label>
                                    <select name="kecamatan" id="kecamatan" class="form-select" required>
                                        <option value="">-- Pilih Kecamatan --</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nama Desa / Kelurahan <span class="text-danger">*</span></label>
                                    <select name="desa" id="desa" class="form-select" required>
                                        <option value="">-- Pilih Desa / Kelurahan --</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Detail Alamat / Patokan Lokasi <span class="text-danger">*</span></label>
                                    <textarea name="location" class="form-control" rows="4" placeholder="Contoh: Jl. Poros Kasipute, depan SMA 1 Bombana" required></textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold"><i class="fas fa-camera me-1 text-danger"></i> Foto / Dokumentasi Kerusakan <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-2 mb-2">
                                        <button type="button" class="btn btn-outline-danger btn-sm fw-bold" id="btnKamera" onclick="document.getElementById('photoInput').setAttribute('capture','environment'); document.getElementById('photoInput').removeAttribute('multiple'); document.getElementById('photoInput').click();">
                                            <i class="fas fa-camera me-1"></i> Ambil Foto (Kamera)
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnGaleri" onclick="document.getElementById('photoInput').removeAttribute('capture'); document.getElementById('photoInput').setAttribute('multiple','multiple'); document.getElementById('photoInput').click();">
                                            <i class="fas fa-image me-1"></i> Pilih dari Galeri (Bisa > 1)
                                        </button>
                                    </div>
                                    <input type="file" id="photoInput" name="photo[]" class="form-control d-none" accept="image/*" multiple required>
                                    <div id="previewFoto" class="mt-2 d-flex flex-wrap gap-2"></div>
                                    <div class="form-text text-muted">Format: JPG/PNG. Anda dapat memilih lebih dari satu foto dari galeri.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tingkat Kerusakan <span class="text-danger">*</span></label>
                                    <select name="severity" class="form-select" required>
                                        <option value="Ringan">Ringan (Lubang kecil, retak)</option>
                                        <option value="Sedang" selected>Sedang (Jalan bergelombang, aspal terkelupas)</option>
                                        <option value="Berat">Berat (Jalan putus, jembatan amblas, berlumpur parah)</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Deskripsi Laporan <span class="text-danger">*</span></label>
                                    <textarea name="description" class="form-control" rows="4" placeholder="Jelaskan kondisi secara detail..." required></textarea>
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" id="btnSubmit" class="btn btn-primary btn-lg fw-bold"><i class="fas fa-paper-plane me-2"></i> Kirim Laporan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Modal Peta -->
    <div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="mapModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="mapModalLabel"><i class="fas fa-map-marked-alt text-danger me-2"></i>Pilih Titik Lokasi - Kabupaten Bombana</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0">
            <div id="map" style="height: 420px; width: 100%; z-index: 10;"></div>
            <div class="p-2 bg-warning-subtle border-top text-center small">
              <i class="fas fa-info-circle text-warning me-1"></i>
              Klik atau geser pin <strong>hanya di dalam wilayah Kabupaten Bombana</strong>. Kecamatan & Desa akan terisi otomatis.
            </div>
            <div id="mapStatusInfo" class="px-3 py-2 text-center small fw-bold" style="min-height:32px;"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            <button type="button" class="btn btn-primary" id="btnSaveMap">
              <i class="fas fa-check-circle me-1"></i> Simpan Lokasi
            </button>
          </div>
        </div>
      </div>
    </div>


    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Preview foto
        document.getElementById('photoInput').addEventListener('change', async function() {
            let file = this.files[0];
            let previewContainer = document.getElementById('previewContainer');
            if (file) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    previewContainer.innerHTML = `<img src="${e.target.result}" alt="Preview" class="img-fluid rounded border" style="max-height: 250px;">`;
                }
                reader.readAsDataURL(file);
            } else {
                previewContainer.innerHTML = '';
            }
        });

        // ======================================
        // DATA WILAYAH & BATAS RESMI BOMBANA
        // ======================================
        let bombanaBoundaryData = null;
        let allBombanaData = {};
        const selectKecamatan = document.getElementById('kecamatan');
        const selectDesa = document.getElementById('desa');

        const defaultLat = -4.7667;
        const defaultLng = 121.9667;

        let map = null;
        let marker = null;
        let miniMap = null;
        let miniMarker = null;
        let boundaryGeoLayer = null;

        let pendingMapLat = null;
        let pendingMapLng = null;
        let isCurrentLocationValid = false;

        // 1. Muat batas resmi MultiPolygon Kabupaten Bombana (94 KB)
        fetch('assets/bombana_boundary_simple.json')
            .then(r => r.json())
            .then(data => {
                bombanaBoundaryData = data;
                if (map && !boundaryGeoLayer) {
                    renderBoundaryOnMap();
                }
            })
            .catch(e => console.error('Error load bombana boundary:', e));

        // 2. Muat data nama Desa & Kecamatan lengkap
        fetch('assets/bombana_data.json')
            .then(r => r.json())
            .then(data => { allBombanaData = data; })
            .catch(e => console.error('Error load bombana data:', e));

        // 3. Muat dropdown Kecamatan dari API
        fetch('api_wilayah.php?type=districts&id=7406')
            .then(r => r.json())
            .then(districts => {
                districts.forEach(d => {
                    let opt = document.createElement('option');
                    opt.setAttribute('data-id', d.id);
                    opt.value = d.name;
                    opt.textContent = d.name;
                    selectKecamatan.appendChild(opt);
                });
            })
            .catch(e => console.error('Error load kecamatan:', e));

        // Pilih kecamatan manual
        selectKecamatan.addEventListener('change', function() {
            let districtId = this.options[this.selectedIndex].getAttribute('data-id');
            selectDesa.innerHTML = '<option value="">-- Sedang Memuat... --</option>';
            if (districtId) {
                loadVillages(districtId, null);
            } else {
                selectDesa.innerHTML = '<option value="">-- Pilih Desa / Kelurahan --</option>';
            }
        });

        function loadVillages(districtId, autoSelectName) {
            return fetch(`api_wilayah.php?type=villages&id=${districtId}`)
                .then(r => r.json())
                .then(villages => {
                    selectDesa.innerHTML = '<option value="">-- Pilih Desa / Kelurahan --</option>';
                    villages.forEach(v => {
                        let opt = document.createElement('option');
                        opt.value = v.name;
                        opt.textContent = v.name;
                        selectDesa.appendChild(opt);
                    });
                    if (autoSelectName) {
                        pilihOptionTerdekat(selectDesa, autoSelectName);
                    }
                });
        }

        // ======================================
        // ALGORITMA POINT-IN-POLYGON (RESMI)
        // ======================================
        function pointInPolygonJS(x, y, vs) {
            let inside = false;
            for (let i = 0, j = vs.length - 1; i < vs.length; j = i++) {
                const xi = vs[i][0], yi = vs[i][1];
                const xj = vs[j][0], yj = vs[j][1];
                const intersect = ((yi > y) !== (yj > y)) && (x < (xj - xi) * (y - yi) / (yj - yi) + xi);
                if (intersect) inside = !inside;
            }
            return inside;
        }

        function isPointInBombana(lat, lng) {
            if (!bombanaBoundaryData || !bombanaBoundaryData.coordinates) {
                // Fallback bounding box jika json belum selesai termuat
                return lat >= -5.4 && lat <= -4.2 && lng >= 121.2 && lng <= 122.4;
            }
            const pt = [lng, lat]; // GeoJSON format: [longitude, latitude]
            for (const poly of bombanaBoundaryData.coordinates) {
                if (pointInPolygonJS(pt[0], pt[1], poly[0])) {
                    let inHole = false;
                    for (let h = 1; h < poly.length; h++) {
                        if (pointInPolygonJS(pt[0], pt[1], poly[h])) {
                            inHole = true;
                            break;
                        }
                    }
                    if (!inHole) return true;
                }
            }
            return false;
        }

        // ======================================
        // PENCARIAN KECAMATAN & DESA AKURAT
        // ======================================
        function cariKecamatan(rawName) {
            if (!rawName) return null;
            let target = rawName.toLowerCase().replace(/^(kecamatan|kec\.)\s+/i, '').trim();
            if (target.length < 3) return null;

            // 1. Exact match
            for (let opt of selectKecamatan.options) {
                if (!opt.value) continue;
                let val = opt.value.toLowerCase().trim();
                if (val === target) return opt;
            }

            // 2. Strict start/end match (hindari salah mencocokkan Poleang dengan Poleang Timur)
            for (let opt of selectKecamatan.options) {
                if (!opt.value) continue;
                let val = opt.value.toLowerCase().trim();
                if (val === target || target.startsWith(val) || val.startsWith(target)) {
                    if (Math.abs(val.length - target.length) <= 3) {
                        return opt;
                    }
                }
            }
            return null;
        }

        function findDistrictByVillage(villageName) {
            if (!villageName || !allBombanaData) return null;
            let target = villageName.toLowerCase().replace(/^(desa|kelurahan|kel\.|ds\.)\s+/i, '').trim();
            if (target.length < 3) return null;

            // 1. Exact match
            for (const dist in allBombanaData) {
                for (const v of allBombanaData[dist]) {
                    let vName = v.name.toLowerCase().trim();
                    if (vName === target) {
                        return { district: dist, village: v.name };
                    }
                }
            }

            // 2. Clean word match (misal "Kasipute 1" -> "Kasipute")
            for (const dist in allBombanaData) {
                for (const v of allBombanaData[dist]) {
                    let vName = v.name.toLowerCase().trim();
                    let vWords = vName.split(/\s+/);
                    let tWords = target.split(/\s+/);
                    if (vWords.length === tWords.length && vWords[0] === tWords[0]) {
                        return { district: dist, village: v.name };
                    }
                }
            }
            return null;
        }

        function pilihOptionTerdekat(selectEl, targetName) {
            if (!targetName || !selectEl.options.length) return;
            let target = targetName.toLowerCase().replace(/^(desa|kelurahan|kel\.|ds\.)\s+/i, '').trim();
            if (target.length < 3) return;

            for (let opt of selectEl.options) {
                if (!opt.value) continue;
                let val = opt.value.toLowerCase().trim();
                if (val === target) {
                    opt.selected = true;
                    return;
                }
            }

            for (let opt of selectEl.options) {
                if (!opt.value) continue;
                let val = opt.value.toLowerCase().trim();
                if (val.includes(target) || target.includes(val)) {
                    if (Math.abs(val.length - target.length) <= 4) {
                        opt.selected = true;
                        return;
                    }
                }
            }
        }

        // ======================================
        // REVERSE GEOCODING
        // ======================================
        function reverseGeocodeAndFill(lat, lng, onDone) {
            let url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1&accept-language=id&zoom=15`;

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    let addr = (data && data.address) ? data.address : {};

                    // Hanya ambil kandidat kecamatan yang VALID (JANGAN gunakan county / state_district)
                    let rawKec = addr.city_district || addr.district || addr.suburb || '';
                    let rawDesa = addr.village || addr.hamlet || addr.neighbourhood || addr.town || '';

                    let matchedKec = cariKecamatan(rawKec);
                    let matchedDesaName = null;

                    // Jika kecamatan belum terdeteksi, cari dari basis data nama desa Bombana
                    if (!matchedKec && rawDesa && allBombanaData) {
                        let res = findDistrictByVillage(rawDesa);
                        if (res) {
                            matchedKec = cariKecamatan(res.district);
                            matchedDesaName = res.village;
                        }
                    }

                    if (matchedKec) {
                        matchedKec.selected = true;
                        let districtId = matchedKec.getAttribute('data-id');
                        let targetDesa = matchedDesaName || rawDesa;

                        loadVillages(districtId, targetDesa).then(() => {
                            setMapStatus(
                                `<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> ` +
                                `Lokasi Bombana Valid &bull; Kec: <strong>${matchedKec.value}</strong>` +
                                (selectDesa.value ? ` &bull; Desa: <strong>${selectDesa.value}</strong>` : '') +
                                `</span>`
                            );
                            if (onDone) onDone(matchedKec.value, selectDesa.value, true);
                        });
                    } else {
                        // Titik sah di Bombana, tapi belum ada detail nama jalan di satelit
                        setMapStatus(
                            `<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> Titik Sah di Kabupaten Bombana (${lat.toFixed(5)}, ${lng.toFixed(5)})</span><br>` +
                            `<span class="small text-muted">Nama jalan/desa belum terdata di satelit untuk titik ini. Silakan pilih Kecamatan manual di form.</span>`
                        );
                        if (onDone) onDone(null, null, true);
                    }
                })
                .catch(err => {
                    console.error('Reverse geocode error:', err);
                    setMapStatus(`<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> Titik Sah di Kabupaten Bombana. Silakan pilih Kecamatan manual.</span>`);
                    if (onDone) onDone(null, null, true);
                });
        }

        // ======================================
        // PETA MODAL & VISUALISASI BATAS RESMI
        // ======================================
        function setMapStatus(html) {
            let el = document.getElementById('mapStatusInfo');
            if (el) el.innerHTML = html;
        }

        function renderBoundaryOnMap() {
            if (!map || !bombanaBoundaryData || boundaryGeoLayer) return;

            // Gambar garis batas resmi Kabupaten Bombana (Merah Dotted)
            boundaryGeoLayer = L.geoJSON(bombanaBoundaryData, {
                style: {
                    color: '#c0392b',
                    weight: 2.5,
                    dashArray: '6, 6',
                    fillColor: '#27ae60',
                    fillOpacity: 0.08,
                    interactive: false
                }
            }).addTo(map);

            // Shading area luar Bombana agar kontras
            L.polygon([
                [[-90, -180], [-90, 180], [90, 180], [90, -180]],
                [[-5.5, 121.1], [-5.5, 122.5], [-4.1, 122.5], [-4.1, 121.1]]
            ], {
                color: 'none',
                fillColor: '#2c3e50',
                fillOpacity: 0.12,
                interactive: false
            }).addTo(map);
        }

        document.getElementById('mapModal').addEventListener('shown.bs.modal', function () {
            let savedLat = parseFloat(document.getElementById('lat').value);
            let savedLng = parseFloat(document.getElementById('lng').value);

            let initialLat = (savedLat && isPointInBombana(savedLat, savedLng)) ? savedLat : defaultLat;
            let initialLng = (savedLng && isPointInBombana(savedLat, savedLng)) ? savedLng : defaultLng;

            isCurrentLocationValid = isPointInBombana(initialLat, initialLng);
            pendingMapLat = isCurrentLocationValid ? initialLat : null;
            pendingMapLng = isCurrentLocationValid ? initialLng : null;

            setMapStatus('<span class="text-muted"><i class="fas fa-info-circle me-1"></i> Klik titik di dalam garis merah Kabupaten Bombana atau geser pin merah.</span>');

            if (!map) {
                // Batasi kamera hanya di sekitar Bombana
                let bombanaMaxBounds = L.latLngBounds([-5.6, 121.0], [-4.0, 122.6]);

                map = L.map('map', {
                    maxBounds: bombanaMaxBounds,
                    maxBoundsViscosity: 0.8,
                    minZoom: 9
                }).setView([initialLat, initialLng], 11);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                renderBoundaryOnMap();

                marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

                // Handler klik peta
                map.on('click', function(e) {
                    let lat = e.latlng.lat;
                    let lng = e.latlng.lng;

                    if (!isPointInBombana(lat, lng)) {
                        // DI LUAR BOMBANA (KOLAKA DLL)
                        isCurrentLocationValid = false;
                        pendingMapLat = null;
                        pendingMapLng = null;

                        setMapStatus('<span class="text-danger fw-bold"><i class="fas fa-ban me-1"></i> LOKASI DITOLAK! Titik yang Anda klik berada di luar Kabupaten Bombana (Kolaka/Kabupaten Lain). Laporan HANYA KHUSUS untuk Kabupaten Bombana.</span>');
                        return;
                    }

                    // DI DALAM BOMBANA
                    isCurrentLocationValid = true;
                    pendingMapLat = lat;
                    pendingMapLng = lng;
                    marker.setLatLng([lat, lng]);

                    setMapStatus('<span class="text-info"><i class="fas fa-spinner fa-spin me-1"></i> Titik Bombana terdeteksi. Mengidentifikasi wilayah...</span>');
                    reverseGeocodeAndFill(lat, lng);
                });

                // Handler drag marker
                marker.on('dragend', function() {
                    let p = marker.getLatLng();
                    if (!isPointInBombana(p.lat, p.lng)) {
                        isCurrentLocationValid = false;
                        pendingMapLat = null;
                        pendingMapLng = null;
                        setMapStatus('<span class="text-danger fw-bold"><i class="fas fa-ban me-1"></i> Pin ditarik ke luar Kabupaten Bombana! Silakan geser pin kembali ke dalam area Bombana.</span>');
                        return;
                    }

                    isCurrentLocationValid = true;
                    pendingMapLat = p.lat;
                    pendingMapLng = p.lng;
                    setMapStatus('<span class="text-info"><i class="fas fa-spinner fa-spin me-1"></i> Titik Bombana terdeteksi. Mengidentifikasi wilayah...</span>');
                    reverseGeocodeAndFill(p.lat, p.lng);
                });

            } else {
                map.setView([initialLat, initialLng], 11);
                marker.setLatLng([initialLat, initialLng]);
                map.invalidateSize();
            }
        });

        // Tombol Simpan Lokasi di Modal
        document.getElementById('btnSaveMap').addEventListener('click', function() {
            if (!isCurrentLocationValid || pendingMapLat === null || pendingMapLng === null) {
                alert('LOKASI TIDAK VALID!\n\nTitik yang Anda pilih berada di luar wilayah Kabupaten Bombana (Kolaka/Daerah Tetangga).\nSilakan klik titik di dalam area Kabupaten Bombana terlebih dahulu.');
                setMapStatus('<span class="text-danger fw-bold"><i class="fas fa-ban me-1"></i> Lokasi tidak valid! Harus di dalam wilayah Kabupaten Bombana.</span>');
                return;
            }

            document.getElementById('lat').value = pendingMapLat;
            document.getElementById('lng').value = pendingMapLng;

            document.getElementById('lokasiStatus').innerHTML =
                `<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> Titik Peta Tersimpan: ` +
                `${parseFloat(pendingMapLat).toFixed(6)}, ${parseFloat(pendingMapLng).toFixed(6)}` +
                (selectKecamatan.value ? ` &bull; Kec: <strong>${selectKecamatan.value}</strong>` : '') +
                (selectDesa.value ? ` &bull; Desa: <strong>${selectDesa.value}</strong>` : '') +
                `</span>`;

            updateMiniMap(pendingMapLat, pendingMapLng);
            bootstrap.Modal.getInstance(document.getElementById('mapModal')).hide();
        });

        // ======================================
        // MINI MAP & RADIUS CHECK
        // ======================================
        function checkRadius(lat, lng) {
            let warnEl = document.getElementById('radiusWarning');
            if (!warnEl) return;
            warnEl.innerHTML = '<span class="text-info small"><i class="fas fa-spinner fa-spin me-1"></i>Mengecek laporan di sekitarnya...</span>';
            fetch(`api_check_radius.php?lat=${lat}&lng=${lng}`)
                .then(r => r.json())
                .then(data => {
                    if (data.found) {
                        let html = `<div class="alert alert-warning py-2 mb-0 mt-2 small border-start border-4 border-warning">
                            <strong><i class="fas fa-exclamation-triangle me-1"></i> Informasi:</strong>
                            Ada ${data.reports.length} laporan jalan rusak dalam radius 20 meter dari titik ini. 
                            (Kode: ${data.reports.map(r => r.tracking_code).join(', ')}). 
                            Anda tetap dapat mengirimkan laporan ini jika kondisinya berbeda.
                        </div>`;
                        warnEl.innerHTML = html;
                    } else {
                        warnEl.innerHTML = '';
                    }
                })
                .catch(e => {
                    console.error('Radius check error:', e);
                    warnEl.innerHTML = '';
                });
        }

        function updateMiniMap(lat, lng) {
            let container = document.getElementById('miniMapContainer');
            container.style.display = 'block';
            if (!miniMap) {
                miniMap = L.map('miniMapContainer', {
                    zoomControl: false, dragging: false, scrollWheelZoom: false, doubleClickZoom: false
                }).setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(miniMap);
                miniMarker = L.marker([lat, lng]).addTo(miniMap);
            } else {
                miniMap.setView([lat, lng], 15);
                miniMarker.setLatLng([lat, lng]);
                miniMap.invalidateSize();
            }
            checkRadius(lat, lng);
        }

        // ======================================
        // DETEKSI LOKASI GPS
        // ======================================
        document.getElementById('btnLokasi').addEventListener('click', function() {
            let btn = this;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sedang mencari sinyal GPS...';
            btn.disabled = true;

            if (!navigator.geolocation) {
                alert('Browser Anda tidak mendukung fitur GPS.');
                btn.innerHTML = '<i class="fas fa-location-arrow me-2"></i> Deteksi Lokasi Saat Ini';
                btn.disabled = false;
                return;
            }

            navigator.geolocation.getCurrentPosition(function(pos) {
                let lat = pos.coords.latitude;
                let lng = pos.coords.longitude;

                if (!isPointInBombana(lat, lng)) {
                    btn.disabled = false;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-warning');
                    btn.innerHTML = '<i class="fas fa-ban me-2"></i> Di Luar Bombana';

                    // Kosongkan koordinat
                    document.getElementById('lat').value = '';
                    document.getElementById('lng').value = '';
                    document.getElementById('miniMapContainer').style.display = 'none';

                    document.getElementById('lokasiStatus').innerHTML =
                        '<span class="text-danger fw-bold"><i class="fas fa-ban me-1"></i> Lokasi GPS Ditolak! Titik Anda berada di luar wilayah Kabupaten Bombana (Kolaka/Daerah Tetangga). Form pelaporan ini HANYA KHUSUS Kabupaten Bombana.</span>';

                    alert('LOKASI GPS DITOLAK!\n\nPosisi GPS Anda saat ini berada di luar wilayah Kabupaten Bombana (Kolaka/Daerah Tetangga).\n\nSistem hanya menerima laporan jalan rusak di dalam wilayah Kabupaten Bombana. Jika Anda ingin melaporkan jalan di Bombana, silakan gunakan tombol "Pilih dari Peta".');
                    return;
                }

                // Titik GPS sah di Bombana
                btn.disabled = false;
                btn.classList.remove('btn-warning');
                btn.classList.add('btn-success');
                btn.innerHTML = '<i class="fas fa-check-circle me-2"></i> Lokasi GPS Sah!';

                document.getElementById('lat').value = lat;
                document.getElementById('lng').value = lng;
                pendingMapLat = lat;
                pendingMapLng = lng;
                isCurrentLocationValid = true;

                updateMiniMap(lat, lng);

                document.getElementById('lokasiStatus').innerHTML =
                    `<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> Koordinat GPS Sah: ${lat.toFixed(6)}, ${lng.toFixed(6)}</span> ` +
                    `<span class="text-muted small"><i class="fas fa-spinner fa-spin ms-1"></i> Mendeteksi wilayah...</span>`;

                reverseGeocodeAndFill(lat, lng, function(kec, desa) {
                    document.getElementById('lokasiStatus').innerHTML =
                        `<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> Koordinat GPS: ${lat.toFixed(6)}, ${lng.toFixed(6)}` +
                        (kec ? ` &bull; Kec: <strong>${kec}</strong>` : '') +
                        (desa ? ` &bull; Desa: <strong>${desa}</strong>` : '') +
                        `</span>`;
                });

            }, function(err) {
                btn.innerHTML = '<i class="fas fa-location-arrow me-2"></i> Deteksi Lokasi Saat Ini';
                btn.disabled = false;
                alert('Gagal mendeteksi lokasi GPS. Pastikan izin GPS telah diberikan di browser/ponsel Anda.');
            }, { enableHighAccuracy: true, timeout: 15000 });
        });

        // ======================================
        // VALIDASI SUBMIT FORM
        // ======================================
        document.querySelector('form').addEventListener('submit', function(e) {
            let lat = document.getElementById('lat').value;
            let lng = document.getElementById('lng').value;
            if (!lat || !lng) {
                e.preventDefault();
                let statusEl = document.getElementById('lokasiStatus');
                statusEl.innerHTML = '<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> KOORDINAT LOKASI WAJIB DIISI! Silakan gunakan tombol "Deteksi Lokasi Saat Ini" atau "Pilih dari Peta" terlebih dahulu.</span>';
                statusEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                alert('PENGIRIMAN DITOLAK!\n\nAnda belum menentukan titik lokasi jalan di dalam wilayah Kabupaten Bombana.\nSilakan gunakan Deteksi Lokasi atau Pilih dari Peta terlebih dahulu.');
                return false;
            }
        });
    </script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>




