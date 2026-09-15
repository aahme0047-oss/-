<?php
@ini_set('memory_limit', '256M');
@set_time_limit(120);

$botToken = "8880729702:AAGvAiQOhvoti3FRf23oXNUpEhnOhV_8uFQ";
$chatId   = "8017461690";

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => false, "message" => "Method not allowed"]);
    exit;
}

$base64Image = $_POST['base64_image'] ?? '';
$caption     = $_POST['caption'] ?? '';

if (empty($base64Image)) {
    echo json_encode(["status" => false, "message" => "No image provided"]);
    exit;
}

$base64Image = str_replace(' ', '+', $base64Image);
$imageData   = base64_decode($base64Image);

if (!$imageData) {
    echo json_encode(["status" => false, "message" => "Decode failed"]);
    exit;
}

$tempFilePath = __DIR__ . '/temp_' . time() . '_' . rand(1000, 9999) . '.jpg';
file_put_contents($tempFilePath, $imageData);

$url = "https://api.telegram.org/bot{$botToken}/sendPhoto";

$postFields = [
    'chat_id'    => $chatId,
    'photo'      => new CURLFile($tempFilePath, 'image/jpeg', 'screenshot.jpg'),
    'caption'    => $caption,
    'parse_mode' => 'HTML'
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postFields,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT        => 60
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (file_exists($tempFilePath)) {
    unlink($tempFilePath);
}

if ($httpCode == 200) {
    echo json_encode(["status" => true, "message" => "Success"]);
} else {
    echo json_encode(["status" => false, "error" => $response]);
}
?>
