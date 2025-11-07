<?php
header('Content-Type: application/json');
$url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($_GET['q']) . '&addressdetails=1&limit=5';

$opts = [
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: YourApp/1.0 (your@email.com)'
        ]
    ]
];
$context = stream_context_create($opts);
$json = @file_get_contents($url, false, $context);

if ($json === FALSE) {
    echo json_encode([]);
} else {
    echo $json;
}
?>
