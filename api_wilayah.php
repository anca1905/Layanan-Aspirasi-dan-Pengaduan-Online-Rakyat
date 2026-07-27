<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (isset($_GET['type']) && isset($_GET['id'])) {
    $type = $_GET['type'];
    $id = $_GET['id'];
    
    // validasi type
    if ($type === 'districts' || $type === 'villages') {
        // filter id hanya angka
        $id = preg_replace('/[^0-9]/', '', $id);
        
        $url = "https://emsifa.github.io/api-wilayah-indonesia/api/{$type}/{$id}.json";
        
        // Menggunakan cURL karena allow_url_fopen mungkin di-disable di server
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        // Disable SSL verification in case the server has outdated root certificates
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($result && $httpcode >= 200 && $httpcode < 300) {
            echo $result;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch data from remote API', 'http_code' => $httpcode]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid type']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
}
