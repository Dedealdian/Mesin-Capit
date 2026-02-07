<?php
/**
 * BOT MESIN CAPIT - FIREBASE REALTIME VERSION
 * 100% Fix & Clean
 */

// --- CONFIGURATION ---
$token        = "8584026511:AAEIGd6fWj88sqvKdZ6fjlrmt4xiqTY8sCc";
$admin_id     = "8298238837"; 
$firebase_url = "https://mesin-capit-bd535-default-rtdb.firebaseio.com";

// Menangkap Update
if (isset($update_data)) {
    $update = $update_data;
} else {
    $input  = file_get_contents("php://input");
    $update = json_decode($input, true);
}

if (!$update) exit;

$message  = $update['message']['text'] ?? "";
$chat_id  = $update['message']['chat']['id'] ?? "";
$from_id  = $update['message']['from']['id'] ?? "";

// --- FUNGSI KIRIM PESAN ---
function sendMessage($id, $text, $menu = null) {
    global $token;
    $data = ['chat_id' => $id, 'text' => $text, 'parse_mode' => 'Markdown'];
    if ($menu) $data['reply_markup'] = json_encode($menu);
    
    $ch = curl_init("https://api.telegram.org/bot$token/sendMessage");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

// --- FUNGSI KIRIM KE FIREBASE ---
function sendToFirebase($path, $data) {
    global $firebase_url;
    $url = "{$firebase_url}/{$path}.json";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// --- LOGIKA /START ---
if ($message == "/start") {
    $text = "🕹️ *Mesin Capit Admin Pro*\n\n"
          . "Status: `Firebase Online` ✅\n"
          . "ID Anda: `$from_id`\n\n"
          . "Commands:\n"
          . "• `/generate [hadiah]` - Buat 1 kupon VIP\n"
          . "• `/sudogenerate` - Buat 30 kupon massal";
    sendMessage($chat_id, $text);
}

// --- LOGIKA /SUDOGENERATE (30 KUPON) ---
if ($message == "/sudogenerate") {
    if ($from_id != $admin_id) {
        sendMessage($chat_id, "❌ Akses ditolak.");
        exit;
    }

    sendMessage($chat_id, "⏳ Memproses 30 kupon ke Firebase Cloud...");
    
    $pool = ["1000", "2000", "5000", "10000", "Jackpot", "Zonk"];
    $report = "🎫 *30 KUPON REALTIME BERHASIL*\n━━━━━━━━━━━━━━━\n";

    for ($i = 1; $i <= 30; $i++) {
        $kode = "REGE-" . strtoupper(substr(md5(microtime() . $i), 0, 6));
        $hadiah = $pool[array_rand($pool)];
        
        $data = [
            "hadiah" => $hadiah,
            "status" => "aktif",
            "dibuat" => date("Y-m-d H:i:s")
        ];

        // Kirim ke Firebase path: kupon/KODE
        sendToFirebase("kupon/$kode", $data);
        $report .= "{$i}. `{$kode}` ({$hadiah})\n";
    }

    sendMessage($chat_id, $report . "\n✅ *Kupon sudah aktif di website!*");
}

// --- LOGIKA /GENERATE (SATUAN) ---
if (preg_match('/^\/generate\s+(.+)/', $message, $matches)) {
    if ($from_id != $admin_id) exit;
    
    $hadiah = $matches[1];
    $kode = "VIPS-" . strtoupper(substr(md5(time()), 0, 6));
    
    $data = [
        "hadiah" => $hadiah,
        "status" => "aktif",
        "dibuat" => date("Y-m-d H:i:s")
    ];

    sendToFirebase("kupon/$kode", $data);
    
    sendMessage($chat_id, "🎫 *Kupon VIP Berhasil!*\n\nKode: `{$kode}`\nHadiah: *{$hadiah}*\n\n_Cek website sekarang!_");
}
