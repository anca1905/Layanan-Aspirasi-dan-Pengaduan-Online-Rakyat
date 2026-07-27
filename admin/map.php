<?php
session_start();
require_once '../config/database.php';

try {
    $stmt = $pdo->query("SELECT tracking_code, location, severity, latitude, longitude, status FROM reports WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error Database: " . $e->getMessage());
}

$page_title = 'Peta Sebaran Jalan Rusak - Admin Pemkab Bombana';
$active_menu = 'map';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
?>

        <!-- Leaflet CSS -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
        <style>
            #map { height: 600px; width: 100%; border-radius: 10px; z-index: 1; }
        </style>

        <div class="flex-grow-1">
            <div class="topbar d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold" style="color: var(--primary-color);">Peta Sebaran Jalan Rusak</h4>
            </div>

            <div class="container-fluid p-4">
                <div class="card border-0 shadow-sm border-top border-primary border-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-map-marked-alt me-2 text-primary"></i> Peta Interaktif Pelaporan</h5>
                    </div>
                    <div class="card-body p-2">
                        <div id="map"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leaflet JS -->
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        
        <script>
            // Set default view to Kabupaten Bombana (approx coordinates)
            var map = L.map('map').setView([-4.7667, 121.9667], 10);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            var reportsData = <?php echo json_encode($reports); ?>;

            reportsData.forEach(function(report) {
                if (report.latitude && report.longitude) {
                    var markerColor = 'blue';
                    if (report.severity == 'Berat') markerColor = 'red';
                    else if (report.severity == 'Sedang') markerColor = 'orange';
                    else if (report.severity == 'Ringan') markerColor = 'green';

                    // Using a simple custom colored icon approach via CSS class if needed, or standard Leaflet icon.
                    // For simplicity, standard marker.
                    var marker = L.marker([parseFloat(report.latitude), parseFloat(report.longitude)]).addTo(map);
                    
                    var popupHtml = "<b>" + report.tracking_code + "</b><br>" +
                                    "Kategori: " + report.severity + "<br>" +
                                    "Status: " + report.status + "<br>" +
                                    "Lokasi: " + report.location;
                    
                    marker.bindPopup(popupHtml);
                }
            });
        </script>

<?php require_once 'layouts/footer.php'; ?>
