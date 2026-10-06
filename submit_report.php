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

    // Batas wilayah Kabupaten Bombana: lat -5.4 s/d -4.2, lng 121.2 s/d 122.4
    $BOMBANA_LAT_MIN = -5.4;
    $BOMBANA_LAT_MAX = -4.2;
    $BOMBANA_LNG_MIN = 121.2;
    $BOMBANA_LNG_MAX = 122.4;

    if ($lat < $BOMBANA_LAT_MIN || $lat > $BOMBANA_LAT_MAX ||
        $lng < $BOMBANA_LNG_MIN || $lng > $BOMBANA_LNG_MAX) {
            $message = "<div class='alert alert-danger border-start border-5 border-danger'>
                            <h5 class='alert-heading fw-bold'><i class='fas fa-exclamation-triangle me-2'></i>Lokasi Tidak Valid!</h5>
                            <p class='mb-0'>Koordinat yang Anda masukkan berada di luar wilayah Kabupaten Bombana. Laporan ini hanya dapat diajukan untuk lokasi di dalam Kabupaten Bombana.</p>
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

        let map;
        let marker;
        let miniMap;
        let miniMarker;
        const defaultLat = -4.7667;
        const defaultLng = 121.9667;
        let lastValidLat = defaultLat;
        let lastValidLng = defaultLng;
        let pendingMapLat = defaultLat;
        let pendingMapLng = defaultLng;

        const BATAS_LAT_MIN = -5.4;
        const BATAS_LAT_MAX = -4.2;
        const BATAS_LNG_MIN = 121.2;
        const BATAS_LNG_MAX = 122.4;

        let bombanaBounds = L.latLngBounds(
            [BATAS_LAT_MIN, BATAS_LNG_MIN],
            [BATAS_LAT_MAX, BATAS_LNG_MAX]
        );

        function diDalamBombana(lat, lng) {
            return lat >= BATAS_LAT_MIN && lat <= BATAS_LAT_MAX &&
                   lng >= BATAS_LNG_MIN && lng <= BATAS_LNG_MAX;
        }

        // ======================================
        // KECAMATAN & DESA DATA
        // ======================================
        const selectKecamatan = document.getElementById('kecamatan');
        const selectDesa = document.getElementById('desa');
        let allBombanaData = {};

        // Load data mapping Desa -> Kecamatan
        fetch('assets/bombana_data.json')
            .then(r => r.json())
            .then(data => {
                allBombanaData = data;
            })
            .catch(e => console.error('Error load bombana data:', e));

        // Muat daftar kecamatan dari API
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

        // Saat kecamatan dipilih manual, muat desa
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

        function pilihOptionTerdekat(selectEl, targetName) {
            if (!targetName || !selectEl.options.length) return;
            let target = targetName.toLowerCase().trim();
            let bestOpt = null;
            let bestScore = -1;

            for (let opt of selectEl.options) {
                if (!opt.value) continue;
                let val = opt.value.toLowerCase().trim();
                let score = 0;
                if (val === target) score = 100;
                else if (val.includes(target) || target.includes(val)) {
                    score = Math.floor(70 * Math.min(val.length, target.length) / Math.max(val.length, target.length));
                }
                if (score > bestScore) {
                    bestScore = score;
                    bestOpt = opt;
                }
            }
            if (bestOpt && bestScore >= 20) {
                bestOpt.selected = true;
            }
        }

        function cariKecamatanTerdekat(targetName) {
            if (!targetName) return null;
            let target = targetName.toLowerCase()
                .replace(/^kecamatan\s+/i, '')
                .replace(/^kec\.?\s+/i, '')
                .trim();
            let bestOpt = null;
            let bestScore = -1;

            for (let opt of selectKecamatan.options) {
                if (!opt.value) continue;
                let val = opt.value.toLowerCase().trim();
                let score = 0;
                if (val === target) score = 100;
                else if (val.includes(target) || target.includes(val)) {
                    score = Math.floor(70 * Math.min(val.length, target.length) / Math.max(val.length, target.length));
                }
                if (score > bestScore) {
                    bestScore = score;
                    bestOpt = opt;
                }
            }
            return (bestOpt && bestScore >= 20) ? bestOpt : null;
        }

        // ======================================
        // REVERSE GEOCODING & WILAYAH CHECK
        // ======================================
        function reverseGeocodeAndFill(lat, lng, onDone) {
            setMapStatus('<span class="text-info"><i class="fas fa-spinner fa-spin me-1"></i> Mendeteksi wilayah...</span>');
            let url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1&accept-language=id&zoom=14`;

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (!data || !data.address) {
                        setMapStatus('<span class="text-warning"><i class="fas fa-exclamation-circle me-1"></i> Lokasi terdeteksi, tapi tidak ada detail alamat.</span>');
                        if (onDone) onDone(null, null, true);
                        return;
                    }

                    let addr = data.address;
                    let addrString = Object.values(addr).join(' ').toLowerCase();
                    
                    // CEK KETAT BOMBANA (MENOLAK KOLAKA/KONAWE DSB)
                    if (!addrString.includes('bombana') && !addrString.includes('kabaena')) {
                        let detected = addr.county || addr.state_district || addr.city || "Luar Wilayah";
                        setMapStatus(`<span class="text-danger"><i class="fas fa-ban me-1"></i> Lokasi Ditolak! Titik berada di wilayah <strong>${detected}</strong>. Laporan ini HANYA KHUSUS Kabupaten Bombana!</span>`);
                        
                        // Reset map selection
                        if (marker) {
                            marker.setLatLng([defaultLat, defaultLng]);
                        }
                        pendingMapLat = defaultLat;
                        pendingMapLng = defaultLng;
                        
                        if (onDone) onDone(null, null, false); // false = ditolak
                        return;
                    }

                    let kecKandidats = [
                        addr.county, addr.city_district, addr.state_district, addr.suburb, addr.city, addr.town
                    ].filter(Boolean).map(k => k.replace(/^Kecamatan\s+/i, '').replace(/^Kec\.?\s+/i, '').trim());

                    let desaKandidats = [
                        addr.village, addr.hamlet, addr.neighbourhood, addr.suburb, addr.town
                    ].filter(Boolean).map(d => d.replace(/^Desa\s+/i, '').replace(/^Kelurahan\s+/i, '').trim());

                    let matchedKec = null;
                    for (let kk of kecKandidats) {
                        matchedKec = cariKecamatanTerdekat(kk);
                        if (matchedKec) break;
                    }

                    let desaRaw = desaKandidats[0] || '';

                    // Jika nominatim tidak menemukan kecamatan, cari kecamatan berdasarkan nama Desa
                    if (!matchedKec && desaRaw && Object.keys(allBombanaData).length > 0) {
                        let desaTarget = desaRaw.toLowerCase();
                        let foundDist = null;
                        for (let distName in allBombanaData) {
                            let villages = allBombanaData[distName];
                            if (!villages) continue;
                            for (let v of villages) {
                                let vName = v.name.toLowerCase();
                                if (vName === desaTarget || vName.includes(desaTarget) || desaTarget.includes(vName)) {
                                    foundDist = distName;
                                    break;
                                }
                            }
                            if (foundDist) break;
                        }
                        if (foundDist) {
                            matchedKec = cariKecamatanTerdekat(foundDist);
                        }
                    }

                    if (matchedKec) {
                        matchedKec.selected = true;
                        let districtId = matchedKec.getAttribute('data-id');
                        if (districtId) {
                            loadVillages(districtId, desaRaw).then(() => {
                                setMapStatus(
                                    `<span class="text-success"><i class="fas fa-check-circle me-1"></i> ` +
                                    `Berhasil Deteksi! Kec: <strong>${matchedKec.value}</strong>` +
                                    (selectDesa.value ? ` &bull; Desa: <strong>${selectDesa.value}</strong>` : '') +
                                    `</span>`
                                );
                                if (onDone) onDone(matchedKec.value, selectDesa.value, true);
                            });
                        }
                    } else {
                        setMapStatus('<span class="text-warning"><i class="fas fa-check me-1"></i> Titik terdeteksi di Bombana. Pilih kecamatan secara manual.</span>');
                        if (onDone) onDone(null, desaRaw, true);
                    }
                })
                .catch(err => {
                    console.error('Reverse geocode error:', err);
                    setMapStatus('<span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i> Gagal mendeteksi wilayah. Pastikan internet stabil dan pilih manual.</span>');
                    if (onDone) onDone(null, null, true);
                });
        }

        // ======================================
        // PETA MODAL
        // ======================================
        function setMapStatus(html) {
            let el = document.getElementById('mapStatusInfo');
            if (el) el.innerHTML = html;
        }

        document.getElementById('mapModal').addEventListener('shown.bs.modal', function () {
            let curLat = parseFloat(document.getElementById('lat').value) || defaultLat;
            let curLng = parseFloat(document.getElementById('lng').value) || defaultLng;

            if (!diDalamBombana(curLat, curLng)) { curLat = defaultLat; curLng = defaultLng; }
            pendingMapLat = curLat; pendingMapLng = curLng;
            lastValidLat = curLat; lastValidLng = curLng;

            setMapStatus('<span class="text-muted">Klik titik di peta atau geser pin merah untuk memilih lokasi.</span>');

            if (!map) {
                map = L.map('map', { maxBounds: bombanaBounds, maxBoundsViscosity: 1.0, minZoom: 9 }).setView([curLat, curLng], 11);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);

                L.polygon([
                    [[-90, -180], [-90, 180], [90, 180], [90, -180]],
                    [[BATAS_LAT_MIN, BATAS_LNG_MIN], [BATAS_LAT_MIN, BATAS_LNG_MAX], [BATAS_LAT_MAX, BATAS_LNG_MAX], [BATAS_LAT_MAX, BATAS_LNG_MIN]]
                ], { color: 'none', fillColor: '#c0392b', fillOpacity: 0.2, interactive: false }).addTo(map);

                L.rectangle(bombanaBounds, { color: '#c0392b', weight: 2, fill: false, dashArray: '8 5', interactive: false }).addTo(map);

                marker = L.marker([curLat, curLng], { draggable: true }).addTo(map);

                marker.on('drag', function() {
                    let p = marker.getLatLng();
                    let clampedLat = Math.max(BATAS_LAT_MIN, Math.min(BATAS_LAT_MAX, p.lat));
                    let clampedLng = Math.max(BATAS_LNG_MIN, Math.min(BATAS_LNG_MAX, p.lng));
                    if (p.lat !== clampedLat || p.lng !== clampedLng) {
                        marker.setLatLng([clampedLat, clampedLng]);
                    }
                });

                marker.on('dragend', function() {
                    let p = marker.getLatLng();
                    let lat = Math.max(BATAS_LAT_MIN, Math.min(BATAS_LAT_MAX, p.lat));
                    let lng = Math.max(BATAS_LNG_MIN, Math.min(BATAS_LNG_MAX, p.lng));
                    marker.setLatLng([lat, lng]);
                    pendingMapLat = lat; pendingMapLng = lng;
                    reverseGeocodeAndFill(lat, lng);
                });

                map.on('click', function(e) {
                    let lat = e.latlng.lat; let lng = e.latlng.lng;
                    if (!diDalamBombana(lat, lng)) {
                        setMapStatus('<span class="text-danger"><i class="fas fa-ban me-1"></i> Di luar kotak batas Bombana!</span>');
                        return;
                    }
                    marker.setLatLng([lat, lng]);
                    pendingMapLat = lat; pendingMapLng = lng;
                    reverseGeocodeAndFill(lat, lng);
                });

            } else {
                map.setView([curLat, curLng], 11);
                marker.setLatLng([curLat, curLng]);
                map.invalidateSize();
            }
        });

        // Tombol Simpan Lokasi di modal
        document.getElementById('btnSaveMap').addEventListener('click', function() {
            if (!diDalamBombana(pendingMapLat, pendingMapLng)) {
                setMapStatus('<span class="text-danger"><i class="fas fa-ban me-1"></i> Lokasi tidak valid! Harus di dalam wilayah Kabupaten Bombana.</span>');
                return;
            }

            document.getElementById('lat').value = pendingMapLat;
            document.getElementById('lng').value = pendingMapLng;

            document.getElementById('lokasiStatus').innerHTML =
                `<span class="text-success"><i class="fas fa-check-circle me-1"></i> Koordinat tersimpan: ` +
                `${parseFloat(pendingMapLat).toFixed(6)}, ${parseFloat(pendingMapLng).toFixed(6)}</span>`;

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
        // GPS DETECT
        // ======================================
        document.getElementById('btnLokasi').addEventListener('click', function() {
            let btn = this;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sedang mencari lokasi...';
            btn.disabled = true;

            if (!navigator.geolocation) {
                alert('Browser Anda tidak mendukung fitur GPS.');
                btn.innerHTML = '<i class="fas fa-location-arrow me-2"></i> Deteksi Lokasi Saya';
                btn.disabled = false;
                return;
            }

            navigator.geolocation.getCurrentPosition(function(pos) {
                let lat = pos.coords.latitude;
                let lng = pos.coords.longitude;

                if (!diDalamBombana(lat, lng)) {
                    btn.innerHTML = '<i class="fas fa-location-arrow me-2"></i> Coba Deteksi Lagi';
                    btn.disabled = false;
                    alert('Lokasi GPS Anda saat ini berada di luar kotak batas Kabupaten Bombana (atau terlalu jauh). Laporan harus dari Bombana!');
                    return;
                }

                document.getElementById('lokasiStatus').innerHTML =
                    `<span class="text-info"><i class="fas fa-spinner fa-spin me-1"></i> Memvalidasi wilayah Bombana...</span>`;

                reverseGeocodeAndFill(lat, lng, function(kec, desa, isValidBombana) {
                    btn.disabled = false;
                    
                    if (isValidBombana === false) {
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-warning');
                        btn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i> Di Luar Bombana';
                        document.getElementById('lokasiStatus').innerHTML = '<span class="text-danger"><i class="fas fa-ban me-1"></i> Lokasi GPS Anda masuk wilayah tetangga (Bukan Bombana). Ditolak.</span>';
                        return;
                    }

                    btn.classList.remove('btn-warning');
                    btn.classList.add('btn-success');
                    btn.innerHTML = '<i class="fas fa-check-circle me-2"></i> Lokasi Terdeteksi!';

                    document.getElementById('lat').value = lat;
                    document.getElementById('lng').value = lng;
                    pendingMapLat = lat; pendingMapLng = lng;
                    lastValidLat = lat; lastValidLng = lng;

                    updateMiniMap(lat, lng);

                    document.getElementById('lokasiStatus').innerHTML =
                        `<span class="text-success"><i class="fas fa-check-circle me-1"></i> Koordinat GPS: ${lat.toFixed(6)}, ${lng.toFixed(6)}` +
                        (kec ? ` &bull; Kec: <strong>${kec}</strong>` : '') +
                        (desa ? ` &bull; Desa: <strong>${desa}</strong>` : '') +
                        `</span>`;
                });

            }, function(err) {
                btn.innerHTML = '<i class="fas fa-location-arrow me-2"></i> Coba Deteksi Lagi';
                btn.disabled = false;
                alert('Gagal mendeteksi lokasi GPS. Pastikan GPS aktif dan izin diberikan.');
            }, { enableHighAccuracy: true, timeout: 15000 });
        });
        // ======================================
        // FORM SUBMIT VALIDATION
        // ======================================
        document.querySelector('form').addEventListener('submit', function(e) {
            let lat = document.getElementById('lat').value;
            let lng = document.getElementById('lng').value;
            if (!lat || !lng) {
                e.preventDefault();
                let statusEl = document.getElementById('lokasiStatus');
                statusEl.innerHTML = '<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> KOORDINAT LOKASI WAJIB DIISI! Silakan gunakan Deteksi GPS atau Pilih dari Peta terlebih dahulu.</span>';
                statusEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    </script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>




