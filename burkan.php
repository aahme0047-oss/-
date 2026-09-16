<?php
session_start();
define('ADMIN_USER', 'BURKANMOD');
define('ADMIN_PASS', '07782436777');
define('DATA_DIR', __DIR__ . '/nthmod_data');
define('KEYS_FILE', DATA_DIR . '/keys.json');
define('LOGS_FILE', DATA_DIR . '/logs.json');
if (!is_dir(DATA_DIR)) { mkdir(DATA_DIR, 0755, true); }
if (!file_exists(KEYS_FILE)) { file_put_contents(KEYS_FILE, json_encode([])); }
if (!file_exists(LOGS_FILE)) { file_put_contents(LOGS_FILE, json_encode([])); }
function load_keys() { $d = file_get_contents(KEYS_FILE); $a = json_decode($d, true); return is_array($a) ? $a : []; }
function save_keys($k) { file_put_contents(KEYS_FILE, json_encode($k, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); }
function load_logs() { $d = file_get_contents(LOGS_FILE); $a = json_decode($d, true); return is_array($a) ? $a : []; }
function save_logs($l) { if (count($l) > 1000) { $l = array_slice($l, -1000); } file_put_contents(LOGS_FILE, json_encode($l, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); }
function generate_key($p = 'NTH') { $x = []; for ($i = 0; $i < 4; $i++) { $x[] = strtoupper(bin2hex(random_bytes(2))); } return $p . '-' . implode('-', $x); }
function is_logged_in() { return isset($_SESSION['admin']) && $_SESSION['admin'] === true; }
function require_login() { if (!is_logged_in()) { header('Location: ?action=login'); exit; } }
$action = $_GET['action'] ?? 'dashboard';
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $u = trim($_POST['username'] ?? '');
        $p = $_POST['password'] ?? '';
        if ($u === ADMIN_USER && $p === ADMIN_PASS) {
            $_SESSION['admin'] = true;
            $_SESSION['admin_name'] = $u;
            header('Location: ?action=dashboard');
            exit;
        } else { $login_error = 'اسم المستخدم أو كلمة المرور غير صحيحة'; }
    }
    ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تسجيل الدخول - NTHMOD</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Tahoma,sans-serif}
body{background:linear-gradient(135deg,#0f0c29,#302b63,#24243e);min-height:100vh;display:flex;align-items:center;justify-content:center;color:#fff}
.box{background:rgba(255,255,255,0.05);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.1);padding:45px;border-radius:20px;width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.5)}
h1{text-align:center;margin-bottom:30px;font-size:28px;background:linear-gradient(90deg,#00d4ff,#7b2ff7);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.field{margin-bottom:20px}
label{display:block;margin-bottom:8px;font-size:14px;color:#b8b8d0}
input{width:100%;padding:14px 16px;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:#fff;font-size:15px;outline:none;transition:0.3s}
input:focus{border-color:#7b2ff7;box-shadow:0 0 20px rgba(123,47,247,0.4)}
button{width:100%;padding:14px;background:linear-gradient(90deg,#7b2ff7,#00d4ff);border:none;border-radius:10px;color:#fff;font-size:16px;font-weight:bold;cursor:pointer;transition:0.3s;margin-top:10px}
button:hover{transform:translateY(-2px);box-shadow:0 10px 30px rgba(123,47,247,0.5)}
.error{background:rgba(255,50,50,0.2);border:1px solid rgba(255,50,50,0.5);padding:12px;border-radius:10px;margin-bottom:20px;text-align:center;font-size:14px}
.hint{text-align:center;margin-top:20px;font-size:12px;color:#888}
</style>
</head>
<body>
<div class="box">
<h1>🔐 NTHMOD ADMIN</h1>
<?php if (!empty($login_error)): ?><div class="error"><?= htmlspecialchars($login_error) ?></div><?php endif; ?>
<form method="POST">
<div class="field"><label>اسم المستخدم</label><input type="text" name="username" required autofocus></div>
<div class="field"><label>كلمة المرور</label><input type="password" name="password" required></div>
<button type="submit">دخول</button>
</form>
<div class="hint">NTHMOD VIP Panel © 2025</div>
</div>
</body>
</html>
<?php
    exit;
}
if ($action === 'logout') { session_destroy(); header('Location: ?action=login'); exit; }
require_login();
if ($action === 'api_generate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $count = max(1, min(500, (int)($_POST['count'] ?? 1)));
    $days = max(1, min(9999, (int)($_POST['days'] ?? 30)));
    $type = in_array($_POST['type'] ?? 'VIP', ['VIP','FREE']) ? $_POST['type'] : 'VIP';
    $prefix = preg_replace('/[^A-Z0-9]/', '', strtoupper($_POST['prefix'] ?? 'NTH'));
    if ($prefix === '') { $prefix = 'NTH'; }
    $custom_name = trim($_POST['custom_name'] ?? '');
    $features = $_POST['features'] ?? [];
    if (!is_array($features)) { $features = []; }
    $keys = load_keys();
    $created = [];
    for ($i = 0; $i < $count; $i++) {
        do {
            $key = $custom_name !== '' ? strtoupper($custom_name) . '-' . strtoupper(bin2hex(random_bytes(3))) : generate_key($prefix);
        } while (isset($keys[$key]));
        $keys[$key] = [
            'key' => $key,
            'type' => $type,
            'features' => $features,
            'days' => $days,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$days} days")),
            'hwid' => null,
            'activated_at' => null,
            'banned' => false,
            'notes' => '',
            'created_by' => $_SESSION['admin_name'] ?? 'BURKANMOD'
        ];
        $created[] = $key;
    }
    save_keys($keys);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'keys' => $created]);
    exit;
}
if ($action === 'api_keys') {
    header('Content-Type: application/json');
    echo json_encode(array_values(load_keys()));
    exit;
}
if ($action === 'api_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = $_POST['key'] ?? '';
    $keys = load_keys();
    if (isset($keys[$key])) { unset($keys[$key]); save_keys($keys); header('Content-Type: application/json'); echo json_encode(['success' => true]); }
    else { header('Content-Type: application/json'); echo json_encode(['success' => false]); }
    exit;
}
if ($action === 'api_ban' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = $_POST['key'] ?? '';
    $keys = load_keys();
    if (isset($keys[$key])) {
        $keys[$key]['banned'] = !($keys[$key]['banned'] ?? false);
        save_keys($keys);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'banned' => $keys[$key]['banned']]);
    } else { header('Content-Type: application/json'); echo json_encode(['success' => false]); }
    exit;
}
if ($action === 'api_reset_hwid' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = $_POST['key'] ?? '';
    $keys = load_keys();
    if (isset($keys[$key])) {
        $keys[$key]['hwid'] = null;
        $keys[$key]['activated_at'] = null;
        save_keys($keys);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else { header('Content-Type: application/json'); echo json_encode(['success' => false]); }
    exit;
}
if ($action === 'api_extend' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = $_POST['key'] ?? '';
    $days = max(1, min(9999, (int)($_POST['days'] ?? 30)));
    $keys = load_keys();
    if (isset($keys[$key])) {
        $base = strtotime($keys[$key]['expires_at'] ?? 'now');
        if ($base < time()) { $base = time(); }
        $keys[$key]['expires_at'] = date('Y-m-d H:i:s', strtotime("+{$days} days", $base));
        save_keys($keys);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else { header('Content-Type: application/json'); echo json_encode(['success' => false]); }
    exit;
}
if ($action === 'api_check') {
    $key = $_GET['key'] ?? $_POST['key'] ?? '';
    $hwid = $_GET['hwid'] ?? $_POST['hwid'] ?? '';
    $keys = load_keys();
    header('Content-Type: application/json');
    if (!isset($keys[$key])) { echo json_encode(['status' => false, 'msg' => 'Invalid key']); exit; }
    $k = $keys[$key];
    if (!empty($k['banned'])) { echo json_encode(['status' => false, 'msg' => 'Banned']); exit; }
    if (strtotime($k['expires_at']) < time()) { echo json_encode(['status' => false, 'msg' => 'Key expired']); exit; }
    if (!empty($k['hwid']) && !empty($hwid) && $k['hwid'] !== $hwid) { echo json_encode(['status' => false, 'msg' => 'HWID mismatch']); exit; }
    if (empty($k['hwid']) && !empty($hwid)) {
        $keys[$key]['hwid'] = $hwid;
        $keys[$key]['activated_at'] = date('Y-m-d H:i:s');
        save_keys($keys);
    }
    echo json_encode([
        'status' => true,
        'type' => $k['type'] ?? 'VIP',
        'features' => $k['features'] ?? [],
        'days' => $k['days'] ?? 30,
        'expires_at' => $k['expires_at']
    ]);
    exit;
}
$keys = load_keys();
$total = count($keys);
$active = 0;
$expired = 0;
$banned = 0;
$now = time();
foreach ($keys as $k) {
    if (!empty($k['banned'])) { $banned++; }
    elseif (strtotime($k['expires_at']) < $now) { $expired++; }
    else { $active++; }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>NTHMOD Admin Panel</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Tahoma,sans-serif}
body{background:linear-gradient(135deg,#0f0c29,#302b63,#24243e);min-height:100vh;color:#fff;padding:20px}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;padding:20px 30px;background:rgba(255,255,255,0.05);backdrop-filter:blur(20px);border-radius:15px;border:1px solid rgba(255,255,255,0.1)}
.header h1{font-size:24px;background:linear-gradient(90deg,#00d4ff,#7b2ff7);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.header .user{display:flex;align-items:center;gap:15px;font-size:14px;color:#b8b8d0}
.btn-logout{background:rgba(255,50,50,0.2);border:1px solid rgba(255,50,50,0.4);color:#ff6b6b;padding:8px 18px;border-radius:8px;text-decoration:none;font-size:13px;transition:0.3s}
.btn-logout:hover{background:rgba(255,50,50,0.3)}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:30px}
.stat{background:rgba(255,255,255,0.05);backdrop-filter:blur(20px);padding:25px;border-radius:15px;border:1px solid rgba(255,255,255,0.1);text-align:center;transition:0.3s}
.stat:hover{transform:translateY(-5px);border-color:rgba(123,47,247,0.5)}
.stat .num{font-size:32px;font-weight:bold;background:linear-gradient(90deg,#00d4ff,#7b2ff7);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.stat .lbl{font-size:13px;color:#b8b8d0;margin-top:8px}
.panel{background:rgba(255,255,255,0.05);backdrop-filter:blur(20px);border-radius:15px;border:1px solid rgba(255,255,255,0.1);padding:30px;margin-bottom:25px}
.panel h2{font-size:18px;margin-bottom:20px;color:#00d4ff;display:flex;align-items:center;gap:10px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:15px}
.field{margin-bottom:15px}
.field label{display:block;margin-bottom:6px;font-size:13px;color:#b8b8d0}
.field input,.field select{width:100%;padding:11px 14px;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;font-size:14px;outline:none;transition:0.3s}
.field input:focus,.field select:focus{border-color:#7b2ff7;box-shadow:0 0 15px rgba(123,47,247,0.3)}
.field select option{background:#1a1a2e}
.btn{padding:12px 24px;background:linear-gradient(90deg,#7b2ff7,#00d4ff);border:none;border-radius:8px;color:#fff;font-size:14px;font-weight:bold;cursor:pointer;transition:0.3s}
.btn:hover{transform:translateY(-2px);box-shadow:0 10px 25px rgba(123,47,247,0.4)}
.features-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin-top:10px}
.feat{display:flex;align-items:center;gap:8px;padding:10px;background:rgba(0,0,0,0.2);border-radius:8px;border:1px solid rgba(255,255,255,0.05);cursor:pointer;transition:0.2s}
.feat:hover{border-color:rgba(123,47,247,0.5);background:rgba(123,47,247,0.1)}
.feat input{width:18px;height:18px;cursor:pointer;accent-color:#7b2ff7}
.feat span{font-size:13px}
table{width:100%;border-collapse:collapse;font-size:13px}
th,td{padding:12px;text-align:right;border-bottom:1px solid rgba(255,255,255,0.05)}
th{background:rgba(0,0,0,0.2);color:#00d4ff;font-weight:600;font-size:12px;text-transform:uppercase}
tr:hover{background:rgba(123,47,247,0.05)}
.badge{padding:4px 10px;border-radius:6px;font-size:11px;font-weight:bold}
.badge.vip{background:rgba(123,47,247,0.3);color:#c39bff}
.badge.free{background:rgba(0,212,255,0.3);color:#7de8ff}
.badge.active{background:rgba(50,255,100,0.2);color:#5fff8f}
.badge.expired{background:rgba(255,150,50,0.2);color:#ffa54f}
.badge.banned{background:rgba(255,50,50,0.2);color:#ff6b6b}
.actions button{padding:6px 10px;margin:2px;border:none;border-radius:6px;cursor:pointer;font-size:11px;transition:0.2s}
.btn-ban{background:rgba(255,50,50,0.3);color:#ff6b6b}
.btn-reset{background:rgba(0,212,255,0.3);color:#7de8ff}
.btn-extend{background:rgba(50,255,100,0.2);color:#5fff8f}
.btn-del{background:rgba(150,0,0,0.3);color:#ff9090}
.actions button:hover{transform:scale(1.05)}
.result{background:rgba(50,255,100,0.1);border:1px solid rgba(50,255,100,0.3);padding:20px;border-radius:10px;margin-top:20px;display:none}
.result h3{color:#5fff8f;margin-bottom:10px;font-size:14px}
.key-list{background:rgba(0,0,0,0.3);padding:15px;border-radius:8px;font-family:monospace;font-size:13px;max-height:300px;overflow-y:auto;direction:ltr;text-align:left}
.key-list div{padding:5px 0;color:#7de8ff;border-bottom:1px solid rgba(255,255,255,0.05)}
.copy-all{margin-top:15px;background:rgba(123,47,247,0.2);color:#c39bff;padding:10px 20px;border:1px solid rgba(123,47,247,0.4);border-radius:8px;cursor:pointer;font-size:13px}
</style>
</head>
<body>
<div class="header">
<h1>⚡ NTHMOD VIP PANEL</h1>
<div class="user">
<span>👤 <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
<a href="?action=logout" class="btn-logout">خروج</a>
</div>
</div>
<div class="stats">
<div class="stat"><div class="num"><?= $total ?></div><div class="lbl">إجمالي المفاتيح</div></div>
<div class="stat"><div class="num" style="background:linear-gradient(90deg,#5fff8f,#00d4ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent"><?= $active ?></div><div class="lbl">نشطة</div></div>
<div class="stat"><div class="num" style="background:linear-gradient(90deg,#ffa54f,#ff6b6b);-webkit-background-clip:text;-webkit-text-fill-color:transparent"><?= $expired ?></div><div class="lbl">منتهية</div></div>
<div class="stat"><div class="num" style="background:linear-gradient(90deg,#ff6b6b,#ff0055);-webkit-background-clip:text;-webkit-text-fill-color:transparent"><?= $banned ?></div><div class="lbl">محظورة</div></div>
</div>
<div class="panel">
<h2>🔑 توليد مفاتيح جديدة</h2>
<form id="genForm">
<div class="grid">
<div class="field"><label>عدد المفاتيح (1-500)</label><input type="number" name="count" value="1" min="1" max="500"></div>
<div class="field"><label>مدة الصلاحية (أيام)</label><input type="number" name="days" value="30" min="1" max="9999"></div>
<div class="field"><label>نوع الحساب</label><select name="type"><option value="VIP">VIP</option><option value="FREE">FREE</option></select></div>
<div class="field"><label>بادئة المفتاح (NTH)</label><input type="text" name="prefix" value="NTH" maxlength="10"></div>
<div class="field"><label>اسم مخصص (اختياري)</label><input type="text" name="custom_name" placeholder="مثال: BURKAN" maxlength="20"></div>
</div>
<div class="field" style="margin-top:15px">
<label>🎯 المميزات المفعّلة في المفتاح</label>
<div class="features-grid">
<label class="feat"><input type="checkbox" name="features[]" value="esp" checked><span>ESP</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="wallhack" checked><span>Wallhack</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="aimbot"><span>Aimbot</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="aimtouch"><span>Aim Touch</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="magic_bullet"><span>Magic Bullet</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="auto_head"><span>Auto Head</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="radar"><span>Radar 360</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="skeleton"><span>Skeleton</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="item_esp"><span>Item ESP</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="vehicle_esp"><span>Vehicle ESP</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="bomb_esp"><span>Bomb ESP</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="aim_warning"><span>Aim Warning</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="ipad_view" checked><span>iPad View</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="unlock_fps"><span>Unlock FPS</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="no_grass"><span>No Grass</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="no_trees"><span>No Trees</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="no_fog"><span>No Fog</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="black_sky"><span>Black Sky</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="mod_skin" checked><span>Mod Skin</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="mod_emote" checked><span>Mod Emote</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="skin_deadbox"><span>Skin Deadbox</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="kill_message"><span>Kill Message</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="kill_counter"><span>Kill Counter</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="fast_car"><span>Fast Car</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="wall_climb"><span>Wall Climb</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="fake_hwid"><span>Fake HWID</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="no_recoil"><span>No Recoil</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="accurate"><span>100% Accuracy</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="weapon_glow"><span>Weapon Glow</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="antenna"><span>Antenna ESP</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="esp_outline"><span>ESP Outline</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="white_body"><span>White Body</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="color_body"><span>Color Body</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="bugman"><span>Bug Man</span></label>
<label class="feat"><input type="checkbox" name="features[]" value="auto_report"><span>Auto Report</span></label>
</div>
</div>
<button type="submit" class="btn" style="margin-top:20px">⚡ توليد المفاتيح</button>
</form>
<div class="result" id="genResult">
<h3>✅ تم التوليد بنجاح</h3>
<div class="key-list" id="keyList"></div>
<button class="copy-all" onclick="copyAll()">📋 نسخ الكل</button>
</div>
</div>
<div class="panel">
<h2>📋 قائمة المفاتيح</h2>
<div style="overflow-x:auto">
<table>
<thead><tr>
<th>المفتاح</th><th>النوع</th><th>المدة</th><th>ينتهي</th><th>HWID</th><th>الحالة</th><th>إجراءات</th>
</tr></thead>
<tbody id="keysTable">
<?php foreach ($keys as $k):
    $isBanned = !empty($k['banned']);
    $isExpired = strtotime($k['expires_at']) < $now;
    $statusClass = $isBanned ? 'banned' : ($isExpired ? 'expired' : 'active');
    $statusText = $isBanned ? 'محظور' : ($isExpired ? 'منتهي' : 'نشط');
    $typeClass = strtolower($k['type'] ?? 'vip');
?>
<tr>
<td style="font-family:monospace;color:#7de8ff"><?= htmlspecialchars($k['key']) ?></td>
<td><span class="badge <?= $typeClass ?>"><?= htmlspecialchars($k['type'] ?? 'VIP') ?></span></td>
<td><?= (int)($k['days'] ?? 30) ?> يوم</td>
<td style="font-size:12px"><?= htmlspecialchars($k['expires_at']) ?></td>
<td style="font-family:monospace;font-size:11px;color:#aaa;max-width:150px;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($k['hwid'] ?? '—') ?></td>
<td><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></td>
<td class="actions">
<button class="btn-ban" onclick="toggleBan('<?= htmlspecialchars($k['key']) ?>')"><?= $isBanned ? 'إلغاء حظر' : 'حظر' ?></button>
<button class="btn-reset" onclick="resetHwid('<?= htmlspecialchars($k['key']) ?>')">تصفير HWID</button>
<button class="btn-extend" onclick="extendKey('<?= htmlspecialchars($k['key']) ?>')">تمديد</button>
<button class="btn-del" onclick="deleteKey('<?= htmlspecialchars($k['key']) ?>')">حذف</button>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<script>
document.getElementById('genForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const res = await fetch('?action=api_generate', { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
        const list = document.getElementById('keyList');
        list.innerHTML = '';
        data.keys.forEach(k => { const d = document.createElement('div'); d.textContent = k; list.appendChild(d); });
        document.getElementById('genResult').style.display = 'block';
        setTimeout(() => location.reload(), 15000);
    }
});
function copyAll() {
    const text = Array.from(document.querySelectorAll('#keyList div')).map(d => d.textContent).join('\n');
    navigator.clipboard.writeText(text).then(() => alert('تم النسخ'));
}
async function toggleBan(key) {
    const fd = new FormData(); fd.append('key', key);
    const res = await fetch('?action=api_ban', { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) location.reload();
}
async function resetHwid(key) {
    if (!confirm('تصفير HWID لهذا المفتاح؟')) return;
    const fd = new FormData(); fd.append('key', key);
    const res = await fetch('?action=api_reset_hwid', { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) location.reload();
}
async function extendKey(key) {
    const days = prompt('كم يوم تريد التمديد؟', '30');
    if (!days) return;
    const fd = new FormData(); fd.append('key', key); fd.append('days', days);
    const res = await fetch('?action=api_extend', { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) location.reload();
}
async function deleteKey(key) {
    if (!confirm('حذف هذا المفتاح نهائياً؟')) return;
    const fd = new FormData(); fd.append('key', key);
    const res = await fetch('?action=api_delete', { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) location.reload();
}
</script>
</body>
</html>
