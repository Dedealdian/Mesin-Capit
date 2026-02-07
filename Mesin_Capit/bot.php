<?php
/**
 * BOT MESIN CAPIT - FINAL VERSION 100%
 * Anti-Warning, Anti-Error, Auto-Sync
 */

$token    = "8584026511:AAEIGd6fWj88sqvKdZ6fjlrmt4xiqTY8sCc";
$admin_id = "8298238837"; 

if (isset($update_data)) {
    $update = $update_data;
} else {
    $input  = file_get_contents("php://input");
    $update = json_decode($input, true);
}

if (!$update) exit;

$message    = $update['message']['text'] ?? "";
$chat_id    = $update['message']['chat']['id'] ?? "";
$from_id    = $update['message']['from']['id'] ?? "";
$photo      = $update['message']['photo'] ?? null;
$document   = $update['message']['document'] ?? null;
$callback   = $update['callback_query'] ?? null;

if (!function_exists('sendMessage')) {
    function sendMessage($id, $text, $menu = null) {
        global $token;
        $data = ['chat_id' => $id, 'text' => $text, 'parse_mode' => 'Markdown'];
        if ($menu) $data['reply_markup'] = json_encode($menu);
        
        $ch = curl_init("https://api.telegram.org/bot$token/sendMessage");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
    }
}

// 1. Tombol Callback
if ($callback) {
    if ($callback['from']['id'] != $admin_id) exit;
    $cb_data = $callback['data'];
    file_put_contents("status_$admin_id.txt", $cb_data);
    $label = str_replace('set_', '', $cb_data);
    sendMessage($admin_id, "📸 *Mode Ganti $label Aktif!*\nKirim Foto/GIF sekarang.");
    exit;
}

// 2. Terima File
if (($photo || $document) && $from_id == $admin_id) {
    $status = @file_get_contents("status_$admin_id.txt");
    if ($status) {
        $file_id = $photo ? end($photo)['file_id'] : $document['file_id'];
        $getFile = json_decode(file_get_contents("https://api.telegram.org/bot$token/getFile?file_id=$file_id"), true);
        $path = $getFile['result']['file_path'];
        $target = ($status == "set_loading") ? "loading.gif" : (($status == "set_capit") ? "claw.png" : "efek_api.png");

        copy("https://api.telegram.org/file/bot$token/$path", $target);
        sendMessage($chat_id, "⏳ Menyinkronkan asset ke GitHub...");
        shell_exec("sh up.sh");
        unlink("status_$admin_id.txt");
        sendMessage($chat_id, "✅ Asset `$target` Berhasil Diperbarui!");
    }
}

// 3. Sudo Generate (30 Kupon)
if ($message == "/sudogenerate" && $from_id == $admin_id) {
    $db = json_decode(file_get_contents("kupon.json"), true) ?? [];
    $pool = ["1000", "2000", "5000", "10000", "Bunga", "Zonk"];
    $list = "🎫 *30 KUPON BARU*\n━━━━━━━━━━━━━━━\n";

    for ($i = 1; $i <= 30; $i++) {
        $kode = "REGE-" . strtoupper(substr(md5(microtime().$i), 0, 6));
        $hadiah = $pool[array_rand($pool)];
        $db[] = ["kode" => $kode, "hadiah" => $hadiah, "status" => "aktif"];
        $list .= "{$i}. `{$kode}` ({$hadiah})\n";
    }

    file_put_contents("kupon.json", json_encode($db, JSON_PRETTY_PRINT));
    shell_exec("sh up.sh");
    sendMessage($chat_id, $list . "\n✅ *Sudah Sinkron ke Website!*");
}

// 4. Start Menu
if ($message == "/start") {
    $menu = ['inline_keyboard' => [
        [['text' => '🔄 Ganti Loading', 'callback_data' => 'set_loading']],
        [['text' => '🏗️ Ganti Capit', 'callback_data' => 'set_capit']],
        [['text' => '🔥 Ganti Api', 'callback_data' => 'set_api']]
    ]];
    sendMessage($chat_id, "🎮 *Admin Control Panel*\n\nID: `$from_id`\nGunkan `/sudogenerate` untuk 30 kupon.", $menu);
}
