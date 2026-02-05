<?php
$token = "8584026511:AAEIGd6fWj88sqvKdZ6fjlrmt4xiqTY8sCc";
$configFile = 'admin_config.js';

$update = json_decode(file_get_contents("php://input"), true);
$chat_id = $update['message']['chat']['id'] ?? "";
$message = $update['message']['text'] ?? "";
$caption = strtolower($update['message']['caption'] ?? "");
$photo = $update['message']['photo'] ?? null;

function kirim($ch, $msg) {
    global $token;
    file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=$ch&text=".urlencode($msg)."&parse_mode=Markdown");
}

function updateConfig($key, $val) {
    global $configFile;
    $content = file_get_contents($configFile);
    // Mencari kunci dan mengganti nilainya di dalam admin_config.js
    $res = preg_replace('/"'.$key.'":\s*".*?"/', '"'.$key.'": "'.$val.'"', $content);
    file_put_contents($configFile, $res);
}

// --- 1. LOGIKA TERIMA FOTO (Remote Gambar) ---
if ($photo) {
    $file_id = end($photo)['file_id'];
    $getFile = json_decode(file_get_contents("https://api.telegram.org/bot$token/getFile?file_id=$file_id"), true);
    $file_path = $getFile['result']['file_path'];
    
    $nama_file = "";
    // Menentukan nama file berdasarkan caption yang kamu ketik saat kirim gambar
    if (strpos($caption, "hadiah") !== false) $nama_file = "hadiah_box.png";
    elseif (strpos($caption, "capit") !== false) $nama_file = "claw.png";
    elseif (strpos($caption, "api") !== false) $nama_file = "efek_api.png";
    elseif (strpos($caption, "loading") !== false) $nama_file = "loading.gif";

    if ($nama_file) {
        // Download dan timpa file lama di cPanel
        copy("https://api.telegram.org/file/bot$token/$file_path", $nama_file);
        kirim($chat_id, "✅ *GAMBAR BERHASIL DIUBAH!*\nFile: `$nama_file` sudah aktif di website.");
    } else {
        kirim($chat_id, "❌ *CAPTION SALAH!*\nGunakan caption: `hadiah`, `capit`, `api`, atau `loading`.");
    }
}

// --- 2. LOGIKA TERIMA TEKS (Remote Teks & Kupon) ---
if (strpos($message, "UPDATE:") === 0) {
    // Format: UPDATE:key:value
    $parts = explode(":", $message);
    if (count($parts) >= 3) {
        $key = trim($parts[1]);
        $val = trim($parts[2]);
        updateConfig($key, $val);
        kirim($chat_id, "✅ *TEKS BERHASIL DIUBAH!*\n`$key` sekarang menjadi: *$val*");
    }
}

// --- 3. MENU /SETTINGS ---
if ($message == "/settings" || $message == "/start") {
    $txt = "🕹 *REMOTE CONTROL DASHBOARD*\n\n"
         . "📸 *GANTI GAMBAR:*\n"
         . "Kirim foto langsung ke bot ini dan beri caption:\n"
         . "• `hadiah` - Ganti isi hadiah mesin\n"
         . "• `capit` - Ganti gambar besi capit\n"
         . "• `api` - Ganti gambar halaman depan\n"
         . "• `loading` - Ganti GIF loading\n\n"
         . "✍️ *GANTI TEKS (Remote TV):*\n"
         . "Ketik format: `UPDATE:namaKunci:IsiBaru`\n"
         . "Contoh:\n"
         . "• `UPDATE:judulUtama:WEB GACOR LUAR BIASA`\n"
         . "• `UPDATE:teksTombolMulai:GAS SEKARANG`\n"
         . "• `UPDATE:TIKETGACOR:Saldo 500k` (Tambah Kupon)";
    kirim($chat_id, $txt);
}
?>
