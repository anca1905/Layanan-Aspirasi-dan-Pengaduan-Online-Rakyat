<?php
function get_curl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

$districts_json = get_curl("https://emsifa.github.io/api-wilayah-indonesia/api/districts/7406.json");
$districts = json_decode($districts_json, true);
$all = [];
if ($districts) {
    foreach ($districts as $d) {
        $villages_json = get_curl("https://emsifa.github.io/api-wilayah-indonesia/api/villages/{$d['id']}.json");
        $villages = json_decode($villages_json, true);
        $all[$d['name']] = $villages;
    }
    file_put_contents("assets/bombana_data.json", json_encode($all));
    echo "Success: saved " . count($all) . " districts";
} else {
    echo "Failed to load districts.";
}
?>
