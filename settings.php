<?php // settings.php
header('Content-Type: application/json');
$fn = __DIR__ . '/settings.json';
if (!file_exists($fn)) {
    // create default
    $default = [
        'feeding_time' => 1,
        'feeds' => [
            ['hour'=>8,'minute'=>0],
            ['hour'=>12,'minute'=>0],
            ['hour'=>17,'minute'=>0]
        ]
    ];
    file_put_contents($fn, json_encode($default, JSON_PRETTY_PRINT));
}
echo file_get_contents($fn);
exit;
?>