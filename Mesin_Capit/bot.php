<?php
/**
 * BOT MESIN CAPIT - FINAL VERSION PRO
 * Fitur: Admin Only, Auto-Push GitHub, Anti-Error
 */

// --- KONFIGURASI ---
$token    = "8584026511:AAEIGd6fWj88sqvKdZ6fjlrmt4xiqTY8sCc";
$admin_id = "8298238837"; // ID Telegram kamu agar orang lain tidak bisa generate kupon

// Menangkap data update
if (isset($update_data)) {
    $update = $update_data;
} else {
    $input  = file_get_contents("php://input");
    $update = json_decode($input, true);
}

if (!$update) exit;

// Variabel Pesan
$message    = $update['message']['text'] ?? "";
$chat_id    = $update['message']['chat']['id'] ?? "";
$from_id    = $update['message']['from']['id'] ?? "";
$photo      = $update['message']['photo'] ?? null;
$document   = $update['message']['document'] ?? null;
$callback   = $update['callback_query'] ?? null;

// --- FUNGSI KIRIM PESAN ---
if (!function_exists('sendMessage')) {
    function sendMessage($id, $text, $menu = null) {
        global $token;
        $data = [
            'chat_id' => $id,
            'text' => $text,
            'parse_mode' => 'Markdown'
        ];
        if ($menu) $data['reply_markup'] = json_encode($menu);
        
        $url = "https://api.telegram.org/bot$token/sendMessage";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
    }
}

// --- 1. LOGIKA CALLBACK (GANTI ASSET) ---
if ($callback) {
    $cb_data = $callback['data'];
    $cb_chat_id = $callback['message']['chat']['id'];
    $cb_from_id = $callback['from']['id'];

    if ($cb_from_id != $admin_id) {
        sendMessage($cb_chat_id, "❌ Anda bukan Admin!");
        exit;
    }

    file_put_contents("status_$cb_chat_id.txt", $cb_data);
    $label = str_replace('set_', '', $cb_data);
    sendMessage($cb_chat_id, "📸 *Mode Ganti $label Aktif!*\nKirim Foto/GIF sekarang.");
    exit;
}

// --- 2. LOGIKA TERIMA FILE ---
if ($photo || $document) {
    $status = @file_get_contents("status_$chat_id.txt");
    if ($status && $from_id == $admin_id) {
        $file_id = $photo ? end($photo)['file_id'] : $document['file_id'];
        $getFile = json_decode(file_get_contents("https://api.telegram.org/bot$token/getFile?file_id=$file_id"), true);
        $path = $getFile['result']['file_path'];
        
        $target = ($status == "set_loading") ? "loading.gif" : (($status == "set_capit") ? "claw.png" : "efek_api.png");

        copy("https://api.telegram.org/file/bot$token/$path", $target);
        sendMessage($chat_id, "⏳ Mengunggah `$target` ke GitHub...");
        shell_exec("sh up.sh"); 
        unlink("status_$chat_id.txt");
        sendMessage($chat_id, "✅ Asset berhasil diperbarui!");
    }
}

// --- 3. LOGIKA SUDOGENERATE (30 KUPON) ---
if ($message == "/sudogenerate") {
    if ($from_id != $admin_id) {
        sendMessage($chat_id, "❌ Perintah ini hanya untuk Admin.");
        exit;
    }

    $db = json_decode(file_get_contents("kupon.json"), true) ?? [];
    $pool = ["1000", "2000", "5000", "10000", "Zonk", "Jackpot"];
    $list_text = "🎫 *30 KUPON BARU GENERATED*\n━━━━━━━━━━━━━━━\n";

    for ($i = 1; $i <= 30; $i++) {
        $kode = "REGE-" . strtoupper(substr(md5(microtime().$i), 0, 6));
        $hadiah = $pool[array_rand($pool)];
        $db[] = ["kode" => $kode, "hadiah" => $hadiah, "status" => "aktif"];
        $list_text .= "{$i}. `{$kode}` ({$hadiah})\n";
    }

    file_put_contents("kupon.json", json_encode($db, JSON_PRETTY_PRINT));
    
    sendMessage($chat_id, "⏳ Menghubungkan ke server GitHub...");
    shell_exec("sh up.sh");
    
    sendMessage($chat_id, $list_text . "\n✅ *Kupon sudah aktif di website!*");
}

// --- 4. LOGIKA /GENERATE (SATUAN) ---
if (preg_match('/^\/generate\s+(.+)/', $message, $matches)) {
    if ($from_id != $admin_id) exit;
    
    $hadiah = $matches[1];
    $db = json_decode(file_get_contents("kupon.json"), true) ?? [];
    $kode = "VIPS-" . strtoupper(substr(md5(time()), 0, 6));
    
    $db[] = ["kode" => $kode, "hadiah" => $hadiah, "status" => "aktif"];
    file_put_contents("kupon.json", json_encode($db, JSON_PRETTY_PRINT));
    shell_exec("sh up.sh");
    
    sendMessage($chat_id, "🎫 *Kupon VIP Berhasil Dibuat!*\n\nKode: `{$kode}`\nHadiah: *{$hadiah}*");
}

// --- 5. MENU UTAMA ---
if ($message == "/start") {
    $menu = ['inline_keyboard' => [
        [['text' => '🔄 Ganti Loading', 'callback_data' => 'set_loading']],
        [['text' => '🏗️ Ganti Capit', 'callback_data' => 'set_capit']],
        [['text' => '🔥 Ganti Api', 'callback_data' => 'set_api']]
    ]];
    sendMessage($chat_id, "🎮 *Admin Control Panel*\n\nID Anda: `$from_id`\n\nPerintah:\n• `/sudogenerate` - Buat 30 Kupon\n• `/generate [hadiah]` - Buat 1 Kupon", $menu);
}
