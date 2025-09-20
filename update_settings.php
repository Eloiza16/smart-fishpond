<?php // update_settings.php
// Accepts POST { feedKg, feed1_hour, feed1_minute, feed2_hour, ... }
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'msg'=>'POST only']);
    exit;
}

// read POST values safely
$feedKg = isset($_POST['feedKg']) ? (int)$_POST['feedKg'] : null;
$feeds = [];
for ($i=1;$i<=3;$i++) {
    $h = isset($_POST["feed{$i}_hour"]) ? (int)$_POST["feed{$i}_hour"] : null;
    $m = isset($_POST["feed{$i}_minute"]) ? (int)$_POST["feed{$i}_minute"] : null;
    $feeds[] = ['hour'=>$h,'minute'=>$m];
}

// validation
$errors = [];
if ($feedKg === null) $errors[] = 'feedKg missing';
if ($feedKg < 0 || $feedKg > 20) $errors[] = 'feedKg must be between 0 and 20';
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
$data = ['feedKg'=>$feedKg,'feeds'=>$feeds];
if (file_put_contents($fn, json_encode($data, JSON_PRETTY_PRINT)) === false) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'msg'=>'Failed to write settings file']);
    exit;
}

echo json_encode(['ok'=>true,'msg'=>'Settings saved']);
exit;
?>