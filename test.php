<?php
$BOMBANA_LAT_MIN = -5.4;
$BOMBANA_LAT_MAX = -4.2;
$BOMBANA_LNG_MIN = 121.2;
$BOMBANA_LNG_MAX = 122.4;

function test($lat, $lng) {
    global $BOMBANA_LAT_MIN, $BOMBANA_LAT_MAX, $BOMBANA_LNG_MIN, $BOMBANA_LNG_MAX;
    if ($lat < $BOMBANA_LAT_MIN || $lat > $BOMBANA_LAT_MAX ||
        $lng < $BOMBANA_LNG_MIN || $lng > $BOMBANA_LNG_MAX) {
        return "BLOCKED";
    }
    return "ALLOWED";
}

echo "Jakarta (-6.2, 106.8): " . test(-6.2, 106.8) . "\n";
echo "Kendari (-3.9, 122.5): " . test(-3.9, 122.5) . "\n";
echo "Bombana (-4.7, 121.9): " . test(-4.7, 121.9) . "\n";
?>
