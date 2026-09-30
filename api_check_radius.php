<?php
// api_check_radius.php
require 'config/database.php';

header('Content-Type: application/json');

$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$lng = isset($_GET['lng']) ? (float)$_GET['lng'] : null;

if ($lat === null || $lng === null) {
    echo json_encode(['error' => 'Missing lat or lng']);
    exit;
}

// 20 meters = 0.02 km
$radius = 20; // meters

// Haversine formula in MySQL
// 6371000 is Earth radius in meters
$sql = "
SELECT id, tracking_code, kecamatan, desa, status,
    (6371000 * acos(
        cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) +
        sin(radians(?)) * sin(radians(latitude))
    )) AS distance
FROM reports
WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND latitude != '' AND longitude != ''
HAVING distance <= ?
ORDER BY distance ASC
LIMIT 5
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("dddi", $lat, $lng, $lat, $radius);
$stmt->execute();
$result = $stmt->get_result();

$reports = [];
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}

echo json_encode(['found' => count($reports) > 0, 'reports' => $reports]);
?>

