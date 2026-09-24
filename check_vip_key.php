<?php
header('Content-Type: application/json; charset=utf-8');

$KEYS_FILE = __DIR__ . "/hero_keys.txt";

$key = trim($_POST['key'] ?? $_GET['key'] ?? '');
$hwid = trim($_POST['hwid'] ?? $_GET['hwid'] ?? '');
$game_id = trim($_POST['game_id'] ?? $_GET['game_id'] ?? '');

if ($key === '') {
    echo json_encode(["status" => false, "msg" => "Missing key"]);
    exit;
}

if (!file_exists($KEYS_FILE)) {
    echo json_encode(["status" => false, "msg" => "No keys file"]);
    exit;
}

$data = file_get_contents($KEYS_FILE);
$lines = explode("\n", trim($data));

$found = false;
$foundIdx = -1;
$row = null;
$allKeys = [];

foreach ($lines as $idx => $line) {
    if (trim($line) === "") continue;
    $parts = explode("|", $line);
    if (count($parts) < 7) continue;
    $entry = [
        "id" => intval($parts[0]),
        "key" => $parts[1],
        "days" => intval($parts[2]),
        "created" => $parts[3],
        "expires" => $parts[4],
        "hwid" => $parts[5],
        "active" => intval($parts[6]),
        "note" => $parts[7] ?? ""
    ];
    $allKeys[] = $entry;
    if ($entry["key"] === $key) {
        $found = true;
        $foundIdx = count($allKeys) - 1;
        $row = $entry;
    }
}

if (!$found || !$row) {
    echo json_encode(["status" => false, "msg" => "Invalid key"]);
    exit;
}

if (!$row['active']) {
    echo json_encode(["status" => false, "msg" => "Key disabled"]);
    exit;
}

if (strtotime($row['expires']) < time()) {
    echo json_encode(["status" => false, "msg" => "Key expired"]);
    exit;
}

if ($row['hwid'] !== "" && $row['hwid'] !== $hwid) {
    echo json_encode(["status" => false, "msg" => "Key used on another device"]);
    exit;
}

if ($row['hwid'] === "") {
    $allKeys[$foundIdx]["hwid"] = $hwid;
    $newLines = [];
    foreach ($allKeys as $k) {
        $newLines[] = $k["id"] . "|" . $k["key"] . "|" . $k["days"] . "|" . $k["created"] . "|" . $k["expires"] . "|" . $k["hwid"] . "|" . $k["active"] . "|" . $k["note"];
    }
    file_put_contents($KEYS_FILE, implode("\n", $newLines));
}

$daysLeft = ceil((strtotime($row['expires']) - time()) / 86400);

echo json_encode([
    "status" => true,
    "type" => "VIP",
    "msg" => "Login OK",
    "days_left" => $daysLeft,
    "expires_at" => $row['expires']
]);