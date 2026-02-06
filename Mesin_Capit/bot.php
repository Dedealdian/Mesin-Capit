<?php
// --- KONFIGURASI ---
$token = "8584026511:AAEIGd6fWj88sqvKdZ6fjlrmt4xiqTY8sCc";
$configFile = 'admin_config.js';

// --- LOGIKA PENERIMA DATA ---
// Mendukung input dari Webhook (file_get_contents) maupun Polling (variable $update_data)
$input = file_get_contents("php://input");
if (!$input && isset($update_data)) { 
    $update = $update_data; 
} else {
    $update = json_decode($input, true);
}

if (!$update) exit; 

$message = $update['message']['text'] ?? "";
$chat_id = $update['message']['chat']['id'] ?? "";
$caption = strtolower($update['message']['caption'] ?? "");
$photo = $update['message']['photo'] ?? null;

// --- FUNGSI-FUNGSI UTAMA (DIBUNGKUS AGAR TIDAK ERROR DI TERMUX) ---

if (!function_exists('kirim')) {
    function kirim($ch, $msg) {
        global $token;
        $url = "https://api.telegram.org/bot$token/sendMessage";
        $post_fields = [
            'chat_id' => $ch,
            'text' => $msg,
            'parse_mode' => 'Markdown'
        ];

        $ch_curl = curl_init();
        curl_setopt($ch_curl, CURLOPT_URL, $url);
        curl_setopt($ch_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_curl, CURLOPT_POSTFIELDS, $post_fields);
        curl_exec($ch_curl);
        // curl_close() dihapus karena tidak diperlukan lagi di PHP 8.0+
    }
}

if (!function_exists('updateConfig')) {
    function updateConfig($key, $val) {
        global $configFile;
        if (!file_exists($configFile)) return;
        $content = file_get_contents($configFile);
        // Mencari kunci dan mengganti nilainya di dalam admin_config.js
        $res = preg_replace('/"' . $key . '":\s*".*?"/', '"' . $key . '": "' . $val . '"', $content);
        file_put_contents($configFile, $res);
    }
}

if (!function_exists('jalankanPush')) {
    function jalankanPush() {
        // Memberikan izin eksekusi ke script shell
        shell_exec("chmod +x up.sh");
        // Menjalankan push dan menangkap output error jika ada
        $output = shell_exec("sh up.sh 2>&1");
        return $output;
    }
}

// --- 1. LOGIKA TERIMA FOTO (GANTI GAMBAR) ---
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
        $url_file = "https://api.telegram.org/file/bot$token/$file_path";
        if (copy($url_file, $nama_file)) {
            $statusGit = jalankanPush();
            kirim($chat_id, "✅ *GAMBAR BERHASIL DIUBAH!*\nFile: `$nama_file` sudah aktif di server.\n\n🚀 *STATUS GITHUB:*\n`$statusGit`");
        } else {
            kirim($chat_id, "❌ *GAGAL MENYIMPAN GAMBAR!*");
        }
    } else {
        kirim($chat_id, "❌ *CAPTION SALAH!*\nGunakan: `hadiah`, `capit`, `api`, atau `loading`.");
    }
}

// --- 2. LOGIKA UPDATE TEKS (UPDATE:key:value) ---
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

// --- 3. MENU UTAMA ---
if ($message == "/start" || $message == "/settings") {
    $txt = "🕹 *REMOTE CONTROL DASHBOARD*\n"
         . "━━━━━━━━━━━━━━━━━━━━\n"
         . "📸 *GANTI GAMBAR:*\n"
         . "Kirim foto + caption: `hadiah`, `capit`, `api`, atau `loading`\n\n"
         . "✍️ *GANTI TEKS:*\n"
         . "Format: `UPDATE:namaKunci:IsiBaru`\n"
         . "Contoh: `UPDATE:judulUtama:CAPIT WARKOP69` ";
    kirim($chat_id, $txt);
}
?>
