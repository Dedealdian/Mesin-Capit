<?php
// --- KONFIGURASI ---
$token = "8584026511:AAEIGd6fWj88sqvKdZ6fjlrmt4xiqTY8sCc";
$configFile = 'admin_config.js';

// Ambil input dari Telegram
$update = json_decode(file_get_contents("php://input"), true);
$chat_id = $update['message']['chat']['id'] ?? "";
$message = $update['message']['text'] ?? "";
$caption = strtolower($update['message']['caption'] ?? "");
$photo = $update['message']['photo'] ?? null;

// --- FUNGSI KIRIM PESAN ---
function kirim($ch, $msg) {
    global $token;
    $url = "https://api.telegram.org/bot$token/sendMessage?chat_id=$ch&text=".urlencode($msg)."&parse_mode=Markdown";
    file_get_contents($url);
}

// --- FUNGSI UPDATE CONFIG ---
function updateConfig($key, $val) {
    global $configFile;
    $content = file_get_contents($configFile);
    // Mencari kunci dan mengganti nilainya di dalam admin_config.js
    $res = preg_replace('/"'.$key.'":\s*".*?"/', '"'.$key.'": "'.$val.'"', $content);
    file_put_contents($configFile, $res);
}

// --- FUNGSI OTOMATIS PUSH KE GITHUB ---
function jalankanPush() {
    // Menjalankan script up.sh dan menangkap outputnya
    $output = shell_exec("sh up.sh 2>&1");
    return $output;
}

// --- 1. LOGIKA TERIMA FOTO ---
if ($photo) {
    $file_id = end($photo)['file_id'];
    $getFile = json_decode(file_get_contents("https://api.telegram.org/bot$token/getFile?file_id=$file_id"), true);
    $file_path = $getFile['result']['file_path'];

    $nama_file = "";
    if (strpos($caption, "hadiah") !== false) $nama_file = "hadiah_box.png";
    elseif (strpos($caption, "capit") !== false) $nama_file = "claw.png";
    elseif (strpos($caption, "api") !== false) $nama_file = "efek_api.png";
    elseif (strpos($caption, "loading") !== false) $nama_file = "loading.gif";

    if ($nama_file) {
        copy("https://api.telegram.org/file/bot$token/$file_path", $nama_file);
        $statusGit = jalankanPush();
        kirim($chat_id, "✅ *GAMBAR BERHASIL DIUBAH!*\nFile: `$nama_file` sudah aktif.\n\n🚀 *STATUS GITHUB:*\n`$statusGit`");
    } else {
        kirim($chat_id, "❌ *CAPTION SALAH!*\nGunakan: `hadiah`, `capit`, `api`, atau `loading`.");
    }
}

// --- 2. LOGIKA TERIMA TEKS (UPDATE:key:value) ---
if (strpos($message, "UPDATE:") === 0) {
    $parts = explode(":", $message);
    if (count($parts) >= 3) {
        $key = trim($parts[1]);
        $val = trim($parts[2]);
        updateConfig($key, $val);
        $statusGit = jalankanPush();
        kirim($chat_id, "✅ *TEKS BERHASIL DIUBAH!*\n`$key` -> *$val*\n\n🚀 *STATUS GITHUB:*\n`$statusGit`");
    }
}

// --- 3. MENU SETTINGS ---
if ($message == "/settings" || $message == "/start") {
    $txt = "🕹 *REMOTE CONTROL DASHBOARD*\n\n"
         . "📸 *GANTI GAMBAR:*\n"
         . "Kirim foto + caption: `hadiah`, `capit`, `api`, atau `loading`\n\n"
         . "✍️ *GANTI TEKS:*\n"
         . "Format: `UPDATE:namaKunci:IsiBaru`\n"
         . "Contoh: `UPDATE:judulUtama:WARKOP GACOR` ";
    kirim($chat_id, $txt);
}
?>
