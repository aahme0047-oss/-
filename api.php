<?php
header('Content-Type: application/json; charset=utf-8');

// 1. بيانات البوت والشات
$botToken = "8986995026:AAFB5vTBUmCr5rdPHsXH-46fc4qxiuTKQZA"; // مثال: 123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ
$chatId   = "-1002967826564"; // مثال: -100123456789 أو 987654321

// التأكد من أن الطلب POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// استلام البيانات
$base64Image = $_POST['base64_image'] ?? '';
$caption     = $_POST['caption'] ?? '';

if (empty($base64Image)) {
    http_response_code(400);
    echo json_encode(['status' => false, 'error' => 'No image data provided']);
    exit;
}

// فك تشفير الـ Base64
$imageData = base64_decode($base64Image);
if (!$imageData) {
    http_response_code(400);
    echo json_encode(['status' => false, 'error' => 'Invalid Base64 payload']);
    exit;
}

// حفظ الصورة في ملف مؤقت لرفعها لتليجرام
$tmpFile = tempnam(sys_get_temp_dir(), 'nth_');
file_put_contents($tmpFile, $imageData);

// تجهيز الطلب لـ Telegram API
$telegramUrl = "https://api.telegram.org/bot{$botToken}/sendPhoto";

$postData = [
    'chat_id'    => $chatId,
    'caption'    => $caption,
    'parse_mode' => 'HTML',
    'photo'      => new CURLFile($tmpFile, 'image/jpeg', 'screenshot.jpg')
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $telegramUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response  = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// مسح الملف المؤقت فوراً
if (file_exists($tmpFile)) {
    unlink($tmpFile);
}

// التحقق من النتيجة والرد على كود الـ Lua
if ($curlError) {
    http_response_code(500);
    echo json_encode(['status' => false, 'error' => $curlError]);
    exit;
}

$resData = json_decode($response, true);
if ($httpCode === 200 && !empty($resData['ok'])) {
    echo json_encode(['status' => true, 'message' => 'Feedback sent successfully']);
} else {
    http_response_code(500);
    $errorDescription = $resData['description'] ?? 'Telegram API rejection';
    echo json_encode(['status' => false, 'error' => $errorDescription]);
}
