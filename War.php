<?php
$BOT_TOKEN   = "8880729702:AAGvAiQOhvoti3FRf23oXNUpEhnOhV_8uFQ";
$CHAT_ID     = "-2502056375";
$SECRET_KEY  = "";
$SAVE_LOCAL  = true;
$UPLOAD_DIR  = __DIR__ . "/uploads/";
$LOG_FILE    = __DIR__ . "/wolf_log.txt";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method Not Allowed");
}

function wolf_log($msg) {
    global $LOG_FILE;
    @file_put_contents($LOG_FILE, "[" . date("Y-m-d H:i:s") . "] " . $msg . PHP_EOL, FILE_APPEND);
}

$base64_image = $_POST['base64_image'] ?? '';
$caption      = $_POST['caption'] ?? '';
$secret       = $_POST['secret'] ?? '';

if ($SECRET_KEY !== "" && $secret !== $SECRET_KEY) {
    wolf_log("ERROR: Invalid secret");
    http_response_code(403);
    exit("Forbidden");
}

if (empty($base64_image)) {
    wolf_log("ERROR: No image received");
    http_response_code(400);
    exit("No image");
}

$imageData = base64_decode($base64_image, true);
if ($imageData === false || strlen($imageData) < 100) {
    wolf_log("ERROR: Base64 decode failed");
    http_response_code(400);
    exit("Invalid image");
}

if (substr($imageData, 0, 3) !== "\xFF\xD8\xFF") {
    wolf_log("ERROR: Not a valid JPG");
    http_response_code(400);
    exit("Not JPG");
}

if ($SAVE_LOCAL && !is_dir($UPLOAD_DIR)) {
    @mkdir($UPLOAD_DIR, 0755, true);
}

$filename = "war_" . date("Ymd_His") . "_" . substr(md5(uniqid()), 0, 6) . ".jpg";
$filepath = $UPLOAD_DIR . $filename;

if ($SAVE_LOCAL) {
    file_put_contents($filepath, $imageData);
    wolf_log("Image saved: $filename (" . strlen($imageData) . " bytes)");
}

if (!empty($BOT_TOKEN) && !empty($CHAT_ID)) {
    $url = "https://api.telegram.org/bot{$BOT_TOKEN}/sendPhoto";

    $post_fields = [
        'chat_id'    => $CHAT_ID,
        'caption'    => $caption,
        'parse_mode' => 'HTML',
        'photo'      => new CURLFile($filepath, 'image/jpeg', $filename)
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $post_fields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($httpCode == 200) {
        wolf_log("Telegram sent OK: $filename");
    } else {
        wolf_log("Telegram FAILED ($httpCode): $curlErr | $response");
    }
} else {
    wolf_log("WARNING: Bot token or chat id missing");
}

http_response_code(200);
echo "OK|" . $filename;