<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'kabid') {
    header("Location: ../login.php");
    exit();
}

// Ambil setting instansi dari database
$app_settings = [];
try {
    $stmtS = $pdo->query("SELECT * FROM settings WHERE id = 1");
    $app_settings = $stmtS->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Gunakan nilai default jika tabel tidak tersedia
    $app_settings = [
        'site_name'        => 'SIPKJ',
        'instansi_name'    => 'Pemerintah Kabupaten',
        'instansi_address' => '-',
        'instansi_contact' => '-',
        'logo_path'        => '',
    ];
}

// Ambil nama Kepala Bidang yang sedang login
$kabid_name = $_SESSION['name'] ?? 'Kepala Bidang Bina Marga';

// --- Bangun query dengan filter ---
$where  = "status = 'Disetujui'";
$params = [];

if (!empty($_GET['status'])) {
    $where   .= " AND status = ?";
    $params[] = $_GET['status'];
}
if (!empty($_GET['cari'])) {
    $where   .= " AND (tracking_code LIKE ? OR reporter_name LIKE ?)";
    $params[] = "%" . $_GET['cari'] . "%";
    $params[] = "%" . $_GET['cari'] . "%";
}

try {
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE $where ORDER BY created_at DESC");
    $stmt->execute($params);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Label filter untuk judul cetak
$filter_label = '';
if (!empty($_GET['status'])) $filter_label .= ' | Status: ' . htmlspecialchars($_GET['status']);
if (!empty($_GET['cari']))   $filter_label .= ' | Pencarian: ' . htmlspecialchars($_GET['cari']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan - <?php echo htmlspecialchars($app_settings['site_name']); ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Times, serif;
            background: #fff;
            margin: 0;
            padding: 0;
            font-size: 13px;
            color: #000;
        }
        .print-container {
            width: 100%;
            max-width: 850px;
            margin: 20px auto;
            padding: 30px;
        }

        /* ===== KOP SURAT ===== */
        .kop-surat {
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 3px double #000;
            padding-bottom: 12px;
            margin-bottom: 20px;
            gap: 20px;
        }
        .kop-surat img {
            width: 75px;
            height: auto;
        }
        .kop-text { text-align: center; }
        .kop-text h2 { margin: 0; font-size: 1rem; font-weight: normal; }
        .kop-text h1 { margin: 4px 0; font-size: 1.4rem; font-weight: bold; text-transform: uppercase; }
        .kop-text p  { margin: 2px 0; font-size: 0.82rem; }

        /* ===== JUDUL DOKUMEN ===== */
        .doc-title {
            text-align: center;
            margin: 25px 0 5px;
            font-weight: bold;
            font-size: 1rem;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .doc-subtitle {
            text-align: center;
            font-size: 0.82rem;
            color: #333;
            margin-bottom: 20px;
        }

        /* ===== TABEL ===== */
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { border: 1px solid #000; padding: 7px 9px; vertical-align: middle; }
        thead th {
            background-color: #ddd;
            text-align: center;
            font-weight: bold;
            font-size: 0.85rem;
        }
        tbody td { font-size: 0.85rem; }
        .center { text-align: center; }

        /* ===== STATUS ===== */
        .status-menunggu { color: #856404; font-weight: bold; }
        .status-disetujui  { color: #155724; font-weight: bold; }
        .status-ditolak   { color: #721c24; font-weight: bold; }

        /* ===== RINGKASAN ===== */
        .summary-box {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            font-size: 0.85rem;
        }
        .summary-item {
            border: 1px solid #000;
            padding: 6px 14px;
            text-align: center;
        }
        .summary-item strong { display: block; font-size: 1.1rem; }

        /* ===== TTD ===== */
        .ttd-wrap { display: flex; justify-content: flex-end; margin-top: 10px; }
        .ttd-box  { width: 280px; text-align: center; font-size: 0.9rem; }
        .ttd-box .ttd-space { height: 75px; }
        .ttd-box .ttd-name  { font-weight: bold; text-decoration: underline; margin: 0; }
        .ttd-box .ttd-nip   { margin: 4px 0 0; }

        /* ===== TOMBOL AKSI (tidak ikut tercetak) ===== */
        .no-print {
            margin-bottom: 20px;
            text-align: right;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .no-print button, .no-print a {
            padding: 9px 22px;
            font-weight: bold;
            font-size: 0.9rem;
            cursor: pointer;
            border: 2px solid #000;
            border-radius: 4px;
            text-decoration: none;
            color: #000;
            background: #f0f0f0;
        }
        .no-print .btn-print { background: #ffc107; border-color: #d39e00; }

        /* ===== PRINT MEDIA ===== */
        @media print {
            body { margin: 0; }
            .print-container { margin: 0; padding: 15px; border: none; width: 100%; max-width: 100%; }
            .no-print { display: none !important; }
            table { page-break-inside: auto; }
            tr    { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
        }
    </style>
</head>
<body>

<div class="print-container">

    <!-- Tombol aksi (tidak ikut tercetak) -->
    <div class="no-print">
        <a href="reports.php">&#8592; Kembali</a>
        <button class="btn-print" onclick="window.print()">&#128438; Print Dokumen</button>
    </div>

    <!-- KOP SURAT -->
    <div class="kop-surat">
        <?php if (!empty($app_settings['logo_path'])): ?>
            <img src="../assets/img/<?php echo htmlspecialchars($app_settings['logo_path']); ?>" alt="Logo Instansi">
        <?php endif; ?>
        <div class="kop-text">
            <h2>PEMERINTAH KABUPATEN</h2>
            <h1><?php echo htmlspecialchars($app_settings['instansi_name']); ?></h1>
            <p><?php echo htmlspecialchars($app_settings['instansi_address']); ?></p>
            <p><?php echo htmlspecialchars($app_settings['instansi_contact']); ?></p>
        </div>
    </div>

    <!-- JUDUL -->
    <div class="doc-title">Rekapitulasi Laporan Kerusakan Jalan</div>
    <div class="doc-subtitle">
        <?php echo htmlspecialchars($app_settings['site_name']); ?>
        <?php if ($filter_label): ?>
            &mdash; <em>Filter<?php echo $filter_label; ?></em>
        <?php endif; ?>
        <br>Dicetak pada: <?php echo date('d F Y, H:i:s'); ?>
        &nbsp;&bull;&nbsp;Dicetak oleh: <?php echo htmlspecialchars($kabid_name); ?>
    </div>

    <!-- RINGKASAN -->
    <?php
    $summary_total = count($reports);
    $summary_disetujui  = count(array_filter($reports, fn($r) => strtolower($r['status']) === 'disetujui'));
    ?>
    <div class="summary-box">
        <div class="summary-item"><strong><?php echo $summary_total; ?></strong>Total Disetujui</div>
        <div class="summary-item"><strong><?php echo $summary_disetujui; ?></strong>Disetujui</div>
    </div>

    <!-- TABEL DATA -->
    <table>
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="14%">Tracking ID</th>
                <th width="17%">Nama Pelapor</th>
                <th width="25%">Lokasi Kerusakan</th>
                <th width="10%">Tingkat</th>
                <th width="13%">Tanggal Lapor</th>
                <th width="17%">Status Akhir</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reports)): ?>
                <tr>
                    <td colspan="7" class="center">Tidak ada data pengaduan yang sesuai filter.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($reports as $row): ?>
                    <?php
                    $s_lower = strtolower($row['status'] ?? '');
                    $s_class = match($s_lower) {
                        'menunggu'  => 'status-menunggu',
                        'disetujui' => 'status-disetujui',
                        'ditolak'   => 'status-ditolak',
                        default     => '',
                    };
                    ?>
                    <tr>
                        <td class="center"><?php echo $no++; ?></td>
                        <td class="center"><?php echo htmlspecialchars($row['tracking_code'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['reporter_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars(($row['kecamatan'] ?? '') . ' / ' . ($row['desa'] ?? '') . ' - ' . ($row['location'] ?? '-')); ?></td>
                        <td class="center"><?php echo htmlspecialchars($row['severity'] ?? '-'); ?></td>
                        <td class="center"><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                        <td class="center <?php echo $s_class; ?>"><?php echo htmlspecialchars(ucfirst($row['status'] ?? '-')); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- TANDA TANGAN -->
    <div class="ttd-wrap">
        <div class="ttd-box">
            <p><?php echo htmlspecialchars($app_settings['instansi_name']); ?>, <?php echo date('d F Y'); ?></p>
            <p>Kepala Bidang Bina Marga</p>
            <div class="ttd-space"></div>
            <p class="ttd-name"><?php echo htmlspecialchars($kabid_name); ?></p>
            <p class="ttd-nip">NIP. ___________________________</p>
        </div>
    </div>

    <div style="clear: both;"></div>
</div>

</body>
</html>
