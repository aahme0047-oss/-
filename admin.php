<?php
session_start();
$ADMIN_USER = "07782436777";
$ADMIN_PASS = "assembleRelease";
$KEYS_FILE = __DIR__ . "/hero_keys.txt";

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['login'])) {
    if ($_POST['username'] === $ADMIN_USER && $_POST['password'] === $ADMIN_PASS) {
        $_SESSION['hero_admin'] = true;
    } else {
        $login_error = "بيانات الدخول غير صحيحة";
    }
}

function loadKeys() {
    global $KEYS_FILE;
    if (!file_exists($KEYS_FILE)) return [];
    $data = file_get_contents($KEYS_FILE);
    if (!$data) return [];
    $lines = explode("\n", trim($data));
    $keys = [];
    foreach ($lines as $line) {
        if (trim($line) === "") continue;
        $parts = explode("|", $line);
        if (count($parts) < 7) continue;
        $keys[] = [
            "id" => intval($parts[0]),
            "key" => $parts[1],
            "days" => intval($parts[2]),
            "created" => $parts[3],
            "expires" => $parts[4],
            "hwid" => $parts[5],
            "active" => intval($parts[6]),
            "note" => $parts[7] ?? ""
        ];
    }
    return $keys;
}

function saveKeys($keys) {
    global $KEYS_FILE;
    $lines = [];
    foreach ($keys as $k) {
        $lines[] = $k["id"] . "|" . $k["key"] . "|" . $k["days"] . "|" . $k["created"] . "|" . $k["expires"] . "|" . $k["hwid"] . "|" . $k["active"] . "|" . $k["note"];
    }
    file_put_contents($KEYS_FILE, implode("\n", $lines));
}

function generateKey($prefix = "HERO") {
    $chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    $seg = function() use ($chars) {
        $s = "";
        for ($i = 0; $i < 4; $i++) $s .= $chars[random_int(0, strlen($chars)-1)];
        return $s;
    };
    return $prefix . "-" . $seg() . "-" . $seg() . "-" . $seg();
}

function nextId($keys) {
    $max = 0;
    foreach ($keys as $k) if ($k["id"] > $max) $max = $k["id"];
    return $max + 1;
}

if (!isset($_SESSION['hero_admin'])) {
    ?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>HERO MOD - Login</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
            body { min-height: 100vh; background: linear-gradient(135deg, #0a0e1a 0%, #1a1f35 100%); display: flex; align-items: center; justify-content: center; padding: 20px; }
            .login-box { background: #131829; padding: 40px 30px; border-radius: 16px; width: 100%; max-width: 400px; box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 40px rgba(255,183,41,0.15); border: 1px solid rgba(255,183,41,0.2); text-align: center; }
            .login-box h1 { color: #ffb729; font-size: 28px; margin-bottom: 8px; letter-spacing: 2px; }
            .login-box p { color: #6a7a94; margin-bottom: 30px; font-size: 13px; }
            .login-box input { width: 100%; padding: 14px 16px; margin-bottom: 15px; background: #0a0e1a; border: 1px solid #2a3448; border-radius: 10px; color: #fff; font-size: 15px; outline: none; transition: 0.2s; }
            .login-box input:focus { border-color: #ffb729; box-shadow: 0 0 0 3px rgba(255,183,41,0.1); }
            .login-box button { width: 100%; padding: 14px; background: linear-gradient(135deg, #ffb729, #ff8c00); border: none; border-radius: 10px; color: #0a0e1a; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.2s; }
            .login-box button:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255,183,41,0.3); }
            .error { background: rgba(255,70,70,0.1); border: 1px solid rgba(255,70,70,0.3); color: #ff6b6b; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 13px; }
        </style>
    </head>
    <body>
        <form class="login-box" method="POST">
            <h1>HERO MOD</h1>
            <p>لوحة التحكم الإدارية</p>
            <?php if (isset($login_error)): ?><div class="error"><?= $login_error ?></div><?php endif; ?>
            <input type="text" name="username" placeholder="اسم المستخدم" required autocomplete="off">
            <input type="password" name="password" placeholder="كلمة المرور" required autocomplete="off">
            <button type="submit" name="login">تسجيل الدخول</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

$keys = loadKeys();
$message = "";

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === "create") {
        $days = intval($_POST['days'] ?? 30);
        $qty = intval($_POST['qty'] ?? 1);
        $custom = trim($_POST['custom_key'] ?? "");
        $note = trim($_POST['note'] ?? "");
        if ($days < 1) $days = 1;
        if ($days > 3650) $days = 3650;
        if ($qty < 1) $qty = 1;
        if ($qty > 500) $qty = 500;

        $created = 0;
        for ($i = 0; $i < $qty; $i++) {
            $key = $custom !== "" && $qty === 1 ? $custom : generateKey();
            $exists = false;
            foreach ($keys as $k) if ($k["key"] === $key) { $exists = true; break; }
            if ($exists) continue;

            $keys[] = [
                "id" => nextId($keys),
                "key" => $key,
                "days" => $days,
                "created" => date("Y-m-d H:i:s"),
                "expires" => date("Y-m-d H:i:s", strtotime("+$days days")),
                "hwid" => "",
                "active" => 1,
                "note" => $note
            ];
            $created++;
        }
        saveKeys($keys);
        $message = "تم إنشاء $created مفتاح";
    }

    if ($action === "delete") {
        $id = intval($_POST['id'] ?? 0);
        $new = [];
        foreach ($keys as $k) if ($k["id"] !== $id) $new[] = $k;
        saveKeys($new);
        $keys = $new;
        $message = "تم الحذف";
    }

    if ($action === "toggle") {
        foreach ($keys as &$k) if ($k["id"] === intval($_POST['id'])) $k["active"] = 1 - $k["active"];
        unset($k);
        saveKeys($keys);
        $message = "تم التحديث";
    }

    if ($action === "reset_hwid") {
        foreach ($keys as &$k) if ($k["id"] === intval($_POST['id'])) { $k["hwid"] = ""; }
        unset($k);
        saveKeys($keys);
        $message = "تم تصفير الجهاز";
    }

    if ($action === "delete_expired") {
        $now = time();
        $new = [];
        foreach ($keys as $k) if (strtotime($k["expires"]) >= $now) $new[] = $k;
        saveKeys($new);
        $keys = $new;
        $message = "تم حذف المنتهية";
    }
}

$filter = $_GET['filter'] ?? 'all';
$filtered = [];
$now = time();
foreach ($keys as $k) {
    $expired = strtotime($k["expires"]) < $now;
    $unused = $k["hwid"] === "";
    if ($filter === "active" && !(!$expired && $k["active"])) continue;
    if ($filter === "expired" && !$expired) continue;
    if ($filter === "unused" && !($unused && $k["active"])) continue;
    $filtered[] = $k;
}
$filtered = array_reverse($filtered);
$filtered = array_slice($filtered, 0, 500);

$total = count($keys);
$activeCount = 0;
$unusedCount = 0;
$expiredCount = 0;
foreach ($keys as $k) {
    $expired = strtotime($k["expires"]) < $now;
    if (!$expired && $k["active"]) $activeCount++;
    if ($k["hwid"] === "") $unusedCount++;
    if ($expired) $expiredCount++;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HERO MOD - Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background: #0a0e1a; color: #e1e8f0; min-height: 100vh; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #131829; padding: 20px 25px; border-radius: 14px; margin-bottom: 20px; border: 1px solid rgba(255,183,41,0.15); }
        .header h1 { color: #ffb729; font-size: 22px; letter-spacing: 1px; }
        .header a { background: #ff4757; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.2s; }
        .header a:hover { background: #ff6b7a; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: #131829; padding: 20px; border-radius: 12px; border: 1px solid #1e2638; border-right: 3px solid #ffb729; }
        .stat-card h3 { color: #6a7a94; font-size: 12px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
        .stat-card p { color: #fff; font-size: 26px; font-weight: bold; }
        .panel { background: #131829; padding: 25px; border-radius: 14px; margin-bottom: 20px; border: 1px solid #1e2638; }
        .panel h2 { color: #ffb729; font-size: 16px; margin-bottom: 20px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .form-grid input, .form-grid select { background: #0a0e1a; border: 1px solid #2a3448; color: #fff; padding: 12px 14px; border-radius: 8px; font-size: 14px; outline: none; width: 100%; }
        .form-grid input:focus { border-color: #ffb729; }
        .btn { background: linear-gradient(135deg, #ffb729, #ff8c00); color: #0a0e1a; padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(255,183,41,0.3); }
        .btn-danger { background: #ff4757; color: #fff; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .filters { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 15px; }
        .filters a { padding: 8px 16px; background: #1a2332; color: #8a9bb5; text-decoration: none; border-radius: 8px; font-size: 13px; transition: 0.2s; border: 1px solid #2a3448; }
        .filters a.active { background: #ffb729; color: #0a0e1a; border-color: #ffb729; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #0a0e1a; color: #6a7a94; padding: 12px 10px; text-align: right; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #1e2638; }
        table td { padding: 12px 10px; border-bottom: 1px solid #1a2233; font-size: 13px; }
        table tr:hover { background: rgba(255,183,41,0.03); }
        .key-cell { font-family: 'Courier New', monospace; background: #0a0e1a; padding: 5px 10px; border-radius: 6px; color: #ffb729; font-weight: bold; font-size: 13px; cursor: pointer; user-select: all; border: 1px solid #1e2638; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-active { background: rgba(46,213,115,0.15); color: #2ed573; }
        .badge-expired { background: rgba(255,71,87,0.15); color: #ff4757; }
        .badge-disabled { background: rgba(108,117,125,0.15); color: #8a94a6; }
        .badge-unused { background: rgba(255,183,41,0.15); color: #ffb729; }
        .msg { background: rgba(46,213,115,0.1); border: 1px solid rgba(46,213,115,0.3); color: #2ed573; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .actions { display: flex; gap: 5px; flex-wrap: wrap; }
        .actions button { padding: 5px 10px; font-size: 11px; border: none; border-radius: 6px; cursor: pointer; color: #fff; }
        .act-del { background: #ff4757; }
        .act-tog { background: #ffa502; }
        .act-rst { background: #1e90ff; }
        .empty { text-align: center; padding: 40px; color: #6a7a94; }
        @media (max-width: 768px) { table { font-size: 11px; } table th, table td { padding: 8px 5px; } .key-cell { font-size: 11px; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🎮 HERO MOD - لوحة التحكم</h1>
        <a href="?logout=1">خروج</a>
    </div>

    <?php if ($message): ?><div class="msg">✓ <?= htmlspecialchars($message) ?></div><?php endif; ?>

    <div class="stats">
        <div class="stat-card"><h3>إجمالي المفاتيح</h3><p><?= $total ?></p></div>
        <div class="stat-card"><h3>نشطة</h3><p style="color:#2ed573"><?= $activeCount ?></p></div>
        <div class="stat-card"><h3>غير مستخدمة</h3><p style="color:#ffb729"><?= $unusedCount ?></p></div>
        <div class="stat-card"><h3>منتهية</h3><p style="color:#ff4757"><?= $expiredCount ?></p></div>
    </div>

    <div class="panel">
        <h2>➕ إنشاء مفاتيح جديدة</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-grid">
                <select name="days">
                    <option value="1">يوم واحد</option>
                    <option value="3">3 أيام</option>
                    <option value="7">أسبوع</option>
                    <option value="15">15 يوم</option>
                    <option value="30" selected>شهر</option>
                    <option value="60">شهرين</option>
                    <option value="90">3 أشهر</option>
                    <option value="180">6 أشهر</option>
                    <option value="365">سنة</option>
                    <option value="3650">10 سنوات</option>
                </select>
                <input type="number" name="qty" value="1" min="1" max="500" placeholder="الكمية">
                <input type="text" name="custom_key" placeholder="مفتاح مخصص (اختياري)" autocomplete="off">
                <input type="text" name="note" placeholder="ملاحظة (اختياري)" autocomplete="off">
            </div>
            <button type="submit" class="btn">إنشاء</button>
        </form>
    </div>

    <div class="panel">
        <h2>📋 إدارة المفاتيح</h2>
        <div class="filters">
            <a href="?filter=all" class="<?= $filter==='all'?'active':'' ?>">الكل</a>
            <a href="?filter=active" class="<?= $filter==='active'?'active':'' ?>">نشطة</a>
            <a href="?filter=unused" class="<?= $filter==='unused'?'active':'' ?>">غير مستخدمة</a>
            <a href="?filter=expired" class="<?= $filter==='expired'?'active':'' ?>">منتهية</a>
        </div>

        <form method="POST" style="margin-bottom:15px" onsubmit="return confirm('حذف جميع المفاتيح المنتهية؟')">
            <input type="hidden" name="action" value="delete_expired">
            <button type="submit" class="btn btn-danger btn-sm">🗑 حذف المنتهية</button>
        </form>

        <?php if (empty($filtered)): ?>
            <div class="empty">لا توجد مفاتيح</div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>المفتاح</th>
                            <th>المدة</th>
                            <th>ينتهي</th>
                            <th>الحالة</th>
                            <th>الجهاز</th>
                            <th>ملاحظة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filtered as $r): 
                            $expired = strtotime($r['expires']) < time();
                            $unused = $r['hwid'] === "";
                            if (!$r['active']) $badge = '<span class="badge badge-disabled">معطّل</span>';
                            elseif ($expired) $badge = '<span class="badge badge-expired">منتهي</span>';
                            elseif ($unused) $badge = '<span class="badge badge-unused">جديد</span>';
                            else $badge = '<span class="badge badge-active">نشط</span>';
                        ?>
                        <tr>
                            <td><?= $r['id'] ?></td>
                            <td><span class="key-cell" onclick="copyKey('<?= htmlspecialchars($r['key']) ?>')"><?= htmlspecialchars($r['key']) ?></span></td>
                            <td><?= $r['days'] ?> يوم</td>
                            <td><?= date('Y/m/d', strtotime($r['expires'])) ?></td>
                            <td><?= $badge ?></td>
                            <td style="font-size:11px;color:#8a94a6"><?= $r['hwid'] ? htmlspecialchars(substr($r['hwid'], 0, 12)) . '...' : '-' ?></td>
                            <td style="font-size:11px;color:#8a94a6"><?= htmlspecialchars($r['note'] ?? '-') ?></td>
                            <td>
                                <div class="actions">
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="act-tog"><?= $r['active'] ? 'تعطيل' : 'تفعيل' ?></button>
                                    </form>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action" value="reset_hwid">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="act-rst">تصفير</button>
                                    </form>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('حذف المفتاح؟')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="act-del">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
function copyKey(key) {
    const el = document.createElement('textarea');
    el.value = key;
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
    alert('تم نسخ المفتاح:\n' + key);
}
</script>
</body>
</html>