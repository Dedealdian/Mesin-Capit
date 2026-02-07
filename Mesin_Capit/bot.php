<?php
// KONFIGURASI
$token = "8584026511:AAEIGd6fWj88sqvKdZ6fjlrmt4xiqTY8sCc";

// Menerima input dari Telegram
$input = file_get_contents("php://input");
$update = json_decode($input, true);

// Jika dijalankan via polling.php, data dikirim lewat variabel $update_data
if (isset($update_data)) $update = $update_data;

$message = $update['message']['text'] ?? "";
$chat_id = $update['message']['chat']['id'] ?? "";
$photo = $update['message']['photo'] ?? null;
$document = $update['message']['document'] ?? null;
$callback_query = $update['callback_query'] ?? null;

// --- FUNGSI KIRIM PESAN ---
function kirimPesan($id, $msg, $keyboard = null) {
    global $token;
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $post_fields = [
        'chat_id' => $id,
        'text' => $msg,
        'parse_mode' => 'Markdown'
    ];
    if ($keyboard) $post_fields['reply_markup'] = json_encode($keyboard);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_exec($ch);
    curl_close($ch);
}

// --- LOGIKA TOMBOL (GANTI GAMBAR) ---
if ($callback_query) {
    $data = $callback_query['data'];
    $cb_id = $callback_query['message']['chat']['id'];
    file_put_contents("status_$cb_id.txt", $data); // Simpan status
    
    $label = str_replace('set_', '', $data);
    kirimPesan($cb_id, "📸 Siap! Kirimkan **Foto/GIF** untuk mengganti: *$label*");
    exit;
}

// --- LOGIKA TERIMA FILE (FOTO/GIF) ---
if ($photo || $document) {
    $status = @file_get_contents("status_$chat_id.txt");
    if ($status) {
        $file_id = $photo ? end($photo)['file_id'] : $document['file_id'];
        $getFile = json_decode(file_get_contents("https://api.telegram.org/bot$token/getFile?file_id=$file_id"), true);
        $file_path = $getFile['result']['file_path'];
        
        $nama_file = "";
        if ($status == "set_loading") $nama_file = "loading.gif";
        if ($status == "set_capit") $nama_file = "claw.png";
        if ($status == "set_api") $nama_file = "efek_api.png";

        if ($nama_file) {
            $url_download = "https://api.telegram.org/file/bot$token/$file_path";
            copy($url_download, $nama_file);
            shell_exec("sh up.sh"); // Push ke GitHub
            unlink("status_$chat_id.txt");
            kirimPesan($chat_id, "✅ Berhasil! File `$nama_file` telah diperbarui di GitHub.");
        }
    }
}

// --- LOGIKA GENERATE KUPON TUNGGAL (/generate) ---
if (strpos($message, '/generate') === 0 && strpos($message, '/sudogenerate') === false) {
    $parts = explode(' ', $message);
    $nominal = $parts[1] ?? "0";
    
    $db = json_decode(file_get_contents("kupon.json"), true) ?? [];
    $kode = "VIPS-" . strtoupper(substr(md5(rand()), 0, 6));
    
    $db[] = ["kode" => $kode, "hadiah" => $nominal, "status" => "aktif"];
    file_put_contents("kupon.json", json_encode($db, JSON_PRETTY_PRINT));
    shell_exec("sh up.sh");

    kirimPesan($chat_id, "✅ **Kupon Tunggal Dibuat!**\n🎫 Kode: `$kode`\n🎁 Hadiah: `$nominal` (1x pakai)");
}

// --- LOGIKA SUDO GENERATE (/sudogenerate) ---
if ($message == "/sudogenerate") {
    $db = json_decode(file_get_contents("kupon.json"), true) ?? [];
    $hadiahAcak = ["1000", "5000", "Zonk", "Jackpot", "10000", "500"];
    
    $text = "🎫 **Coupon Ready**\n━━━━━━━━━━━━━━━\n";
    for ($i = 1; $i <= 30; $i++) {
        $kode = "REGE-" . strtoupper(substr(md5(rand().$i), 0, 6));
        $hadiah = $hadiahAcak[array_rand($hadiahAcak)];
        $db[] = ["kode" => $kode, "hadiah" => $hadiah, "status" => "aktif"];
        $text .= "{$i}. `{$kode}` ({$hadiah})\n";
    }
    
    file_put_contents("kupon.json", json_encode($db, JSON_PRETTY_PRINT));
    shell_exec("sh up.sh");
    kirimPesan($chat_id, $text);
}

// --- MENU UTAMA (/start) ---
if ($message == "/start") {
    $keyboard = ['inline_keyboard' => [
        [['text' => '🔄 Ganti Loading', 'callback_data' => 'set_loading']],
        [['text' => '🏗️ Ganti Capit', 'callback_data' => 'set_capit']],
        [['text' => '🔥 Ganti Api', 'callback_data' => 'set_api']]
    ]];
    kirimPesan($chat_id, "🎮 **Admin Mesin Capit**\nSilakan pilih menu atau gunakan perintah generate.", $keyboard);
}
