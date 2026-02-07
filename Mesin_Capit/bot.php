<?php
/**
 * BOT MESIN CAPIT - FINAL CLEAN VERSION
 */

$token = "8584026511:AAEIGd6fWj88sqvKdZ6fjlrmt4xiqTY8sCc";

if (isset($update_data)) {
    $update = $update_data;
} else {
    $input = file_get_contents("php://input");
    $update = json_decode($input, true);
}

if (!$update) exit;

$message    = $update['message']['text'] ?? "";
$chat_id    = $update['message']['chat']['id'] ?? "";
$photo      = $update['message']['photo'] ?? null;
$document   = $update['message']['document'] ?? null;
$callback   = $update['callback_query'] ?? null;

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
        // curl_close() dihapus untuk menghindari pesan deprecated di PHP 8.x
    }
}

// --- 1. LOGIKA CALLBACK TOMBOL ---
if ($callback) {
    $cb_data = $callback['data'];
    $cb_chat_id = $callback['message']['chat']['id'];
    file_put_contents("status_$cb_chat_id.txt", $cb_data);
    
    $label = str_replace('set_', '', $cb_data);
    sendMessage($cb_chat_id, "📸 *Mode Ganti $label Aktif!*\n\nKirim Foto/GIF sekarang untuk memperbarui GitHub.");
    exit;
}

// --- 2. LOGIKA TERIMA FOTO / GIF ---
if ($photo || $document) {
    $status = @file_get_contents("status_$chat_id.txt");
    if ($status) {
        $file_id = $photo ? end($photo)['file_id'] : $document['file_id'];
        $getFile = json_decode(file_get_contents("https://api.telegram.org/bot$token/getFile?file_id=$file_id"), true);
        $path = $getFile['result']['file_path'];
        
        $target = "";
        if ($status == "set_loading") $target = "loading.gif";
        if ($status == "set_capit")   $target = "claw.png";
        if ($status == "set_api")     $target = "efek_api.png";

        if ($target) {
            copy("https://api.telegram.org/file/bot$token/$path", $target);
            sendMessage($chat_id, "⏳ Memproses upload `$target`...");
            shell_exec("sh up.sh"); 
            unlink("status_$chat_id.txt");
            sendMessage($chat_id, "✅ *Berhasil Diperbarui!*");
        }
    }
}

// --- 3. LOGIKA /GENERATE ---
if (preg_match('/^\/generate\s+(.+)/', $message, $matches)) {
    $hadiah = $matches[1];
    $db = json_decode(file_get_contents("kupon.json"), true) ?? [];
    $kode = "VIPS-" . strtoupper(substr(md5(time()), 0, 6));
    $db[] = ["kode" => $kode, "hadiah" => $hadiah, "status" => "aktif"];
    file_put_contents("kupon.json", json_encode($db, JSON_PRETTY_PRINT));
    shell_exec("sh up.sh");
    sendMessage($chat_id, "🎫 *Kupon VIP Ready!*\n`$kode` ($hadiah)");
}

// --- 4. LOGIKA /SUDOGENERATE ---
if ($message == "/sudogenerate") {
    $db = json_decode(file_get_contents("kupon.json"), true) ?? [];
    $pool = ["1000", "5000", "Zonk", "Jackpot"];
    $report = "🎫 *Coupon Ready (30 Items)*\n━━━━━━━━━━━━━━━\n";
    for ($i = 1; $i <= 30; $i++) {
        $kode = "REGE-" . strtoupper(substr(md5(microtime().$i), 0, 6));
        $hadiah = $pool[array_rand($pool)];
        $db[] = ["kode" => $kode, "hadiah" => $hadiah, "status" => "aktif"];
        $report .= "{$i}. `$kode` ($hadiah)\n";
    }
    file_put_contents("kupon.json", json_encode($db, JSON_PRETTY_PRINT));
    shell_exec("sh up.sh");
    sendMessage($chat_id, $report);
}

// --- 5. MENU UTAMA ---
if ($message == "/start") {
    $menu = ['inline_keyboard' => [
        [['text' => '🔄 Ganti Loading', 'callback_data' => 'set_loading']],
        [['text' => '🏗️ Ganti Capit', 'callback_data' => 'set_capit']],
        [['text' => '🔥 Ganti Efek Api', 'callback_data' => 'set_api']]
    ]];
    sendMessage($chat_id, "🎮 *Admin Mesin Capit Control*", $menu);
}
