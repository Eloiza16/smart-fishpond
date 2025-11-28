<?php // update_settings.php
// Accepts POST { feeding_time, feed1_hour, feed1_minute, feed2_hour, ... }
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'msg'=>'POST only']);
    exit;
}

// read POST values safely
$feeding_time = isset($_POST['feeding_time']) ? (int)$_POST['feeding_time'] : null;
$feeds = [];
for ($i=1;$i<=3;$i++) {
    $h = isset($_POST["feed{$i}_hour"]) ? (int)$_POST["feed{$i}_hour"] : null;
    $m = isset($_POST["feed{$i}_minute"]) ? (int)$_POST["feed{$i}_minute"] : null;
    $feeds[] = ['hour'=>$h,'minute'=>$m];
}

// validation
$errors = [];
if ($feeding_time === null) $errors[] = 'feeding time missing';
if ($feeding_time < 0 || $feeding_time > 20) $errors[] = 'feeding time must be between 1 and 20';
foreach ($feeds as $idx => $f) {
    if (!is_int($f['hour']) || $f['hour'] < 0 || $f['hour'] > 23) $errors[] = "feed".($idx+1)." hour must be 0-23";
    if (!is_int($f['minute']) || $f['minute'] < 0 || $f['minute'] > 59) $errors[] = "feed".($idx+1)." minute must be 0-59";
}
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'errors'=>$errors]);
    exit;
}

$fn = __DIR__ . '/settings.json';
$data = ['feeding_time'=>$feeding_time,'feeds'=>$feeds];
if (file_put_contents($fn, json_encode($data, JSON_PRETTY_PRINT)) === false) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'msg'=>'Failed to write settings file']);
    exit;
}

echo json_encode(['ok'=>true,'msg'=>'Settings saved']);
exit;
?>