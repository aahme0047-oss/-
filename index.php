<?php
session_start();
$USER = "NEROX";
$PASS = "NEROXVIP";

if (isset($_POST['login'])) {
    if ($_POST['username'] == $USER && $_POST['password'] == $PASS) {
        $_SESSION['loggedin'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "بيانات غير صحيحة";
    }
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Cairo", sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-box { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); width: 350px; }
        h2 { text-align: center; color: #333; margin-bottom: 30px; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px; }
        button { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; font-size: 18px; cursor: pointer; }
        .error { color: red; text-align: center; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>🔐 رفع الملفات</h2>';
        if (isset($error)) echo '<div class="error">'.$error.'</div>';
        echo '<form method="post">
            <input type="text" name="username" placeholder="اسم المستخدم" required>
            <input type="password" name="password" placeholder="كلمة المرور" required>
            <button type="submit" name="login">دخول</button>
        </form>
    </div>
</body>
</html>';
    exit;
}

$upload_dir = 'uploads';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$message = '';
if (isset($_FILES['files'])) {
    $total = count($_FILES['files']['name']);
    $success = 0;
    for ($i = 0; $i < $total; $i++) {
        $target = $upload_dir . '/' . basename($_FILES['files']['name'][$i]);
        if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $target)) {
            $success++;
        }
    }
    if ($success > 0) {
        $message = '<div style="background: #4CAF50; color: white; padding: 12px; border-radius: 8px; margin: 20px 0;">✅ تم رفع ' . $success . ' ملف بنجاح</div>';
    } else {
        $message = '<div style="background: #f44336; color: white; padding: 12px; border-radius: 8px; margin: 20px 0;">❌ فشل رفع الملفات</div>';
    }
}

$files = [];
if (is_dir($upload_dir)) {
    $files = array_diff(scandir($upload_dir), ['.', '..']);
}

function format_size($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
    if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
    return round($bytes / 1073741824, 2) . ' GB';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رفع الملفات</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Cairo", sans-serif; }
        body { background: #f5f5f5; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px 30px; color: white; display: flex; justify-content: space-between; align-items: center; }
        .navbar h1 { font-size: 24px; }
        .logout { background: rgba(255,255,255,0.2); color: white; text-decoration: none; padding: 8px 20px; border-radius: 20px; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .upload-box { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .upload-box h2 { color: #333; margin-bottom: 20px; }
        .file-input { border: 2px dashed #667eea; border-radius: 10px; padding: 30px; text-align: center; margin-bottom: 20px; }
        .file-input input[type="file"] { width: 100%; padding: 20px; }
        .btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-size: 16px; cursor: pointer; width: 100%; }
        .files-list { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .file-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #eee; }
        .file-item:last-child { border-bottom: none; }
        .file-info { display: flex; align-items: center; gap: 20px; }
        .file-name { font-weight: bold; color: #333; }
        .file-size { color: #666; font-size: 14px; }
        .file-link { background: #667eea; color: white; text-decoration: none; padding: 5px 15px; border-radius: 5px; font-size: 14px; }
        .file-link:hover { background: #764ba2; }
        .message { margin: 20px 0; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📁 رفع الملفات أون لاين</h1>
        <a href="?logout=1" class="logout">تسجيل خروج</a>
    </div>

    <div class="container">
        <?php echo $message; ?>

        <div class="upload-box">
            <h2>📤 رفع ملفات جديدة</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="file-input">
                    <input type="file" name="files[]" multiple required>
                    <p style="color: #666; margin-top: 10px;">يمكنك اختيار عدة ملفات دفعة واحدة</p>
                </div>
                <button type="submit" name="upload" class="btn">رفع الملفات</button>
            </form>
        </div>

        <div class="files-list">
            <h2 style="margin-bottom: 20px;">📋 الملفات المرفوعة</h2>
            <?php if (empty($files)): ?>
                <p style="text-align: center; color: #666; padding: 30px;">لا توجد ملفات مرفوعة بعد</p>
            <?php else: ?>
                <?php foreach ($files as $file): 
                    $file_path = $upload_dir . '/' . $file;
                    $file_size = filesize($file_path);
                    $file_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $upload_dir . '/' . $file;
                ?>
                <div class="file-item">
                    <div class="file-info">
                        <span class="file-name"><?php echo htmlspecialchars($file); ?></span>
                        <span class="file-size"><?php echo format_size($file_size); ?></span>
                    </div>
                    <a href="<?php echo $file_url; ?>" class="file-link" target="_blank">تحميل</a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_GET['logout'])): session_destroy(); header('Location: ' . $_SERVER['PHP_SELF']); exit; endif; ?>
</body>
</html>