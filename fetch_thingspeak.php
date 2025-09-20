<?php
header('Content-Type: application/json');

$channel_id = "2917018";
$read_api_key = "QD0G8MB5D8P4NBTP";

$results = isset($_GET['results']) ? intval($_GET['results']) : 100;

$url = "https://api.thingspeak.com/channels/$channel_id/feeds.json?results=$results&api_key=$read_api_key";

$resp = @file_get_contents($url);

if ($resp === FALSE) {
    echo json_encode(["ok" => false, "msg" => "Failed to fetch ThingSpeak"]);
    exit;
}

echo $resp;
