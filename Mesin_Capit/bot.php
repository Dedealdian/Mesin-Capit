<?php
// --- CONFIGURATION ---
$token        = "8584026511:AAEIGd6fWj88sqvKdZ6fjlrmt4xiqTY8sCc";
$admin_id     = "8298238837"; 
$firebase_url = "https://mesin-capit-bd535-default-rtdb.asia-southeast1.firebasedatabase.app";

$input = file_get_contents("php://input");
$update = json_decode($input, true);
if (!$update) exit;

$message    = $update['message']['text'] ?? "";
$photo      = $update['message']['photo'] ?? null;
$chat_id    = $update['message']['chat']['id'] ?? "";
$from_id    = $update['message']['from']['id'] ?? "";
$callback   = $update['callback_query'] ?? null;

function apiRequest($method, $data) {
    global $token;
    $url = "https://api.telegram.org/bot$token/$method";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    return json_decode(curl_exec($ch), true);
}

function updateFirebase($path, $data) {
    global $firebase_url;
    $url = "{$firebase_url}/{$path}.json";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
}

// --- LOGIKA START ---
if ($message == "/start" && $from_id == $admin_id) {
    $txt = "Klik /settings IBAB🐽 •|• Mau Setting kan lu?\n\nKalo mau Buat cupon klik `/generate <hadiah>` atau klik /sudogenerate buat masal.";
    apiRequest("sendMessage", ['chat_id' => $chat_id, 'text' => $txt, 'parse_mode' => 'Markdown']);
}

// --- MENU SETTINGS MULTI-LENGKAP ---
$menu = ['inline_keyboard' => [
    [['text' => '📝 Judul Web', 'callback_data' => 'edit_title'], ['text' => '📜 Sub-Judul', 'callback_data' => 'edit_sub']],
    [['text' => '🖼️ Background (Kirim Foto)', 'callback_data' => 'edit_bg']],
    [['text' => '🧸 Gambar Boneka (Kirim Foto)', 'callback_data' => 'edit_doll']],
    [['text' => '🏗️ Gambar Capit (Kirim Foto)', 'callback_data' => 'edit_claw']],
    [['text' => '🎫 Buat 10 Kupon Masal', 'callback_data' => 'gen_masal']],
    [['text' => '❌ Tutup', 'callback_data' => 'close']]
]];

if ($message == "/settings" && $from_id == $admin_id) {
    updateFirebase("admin_state", ["action" => "idle"]);
    apiRequest("sendMessage", ['chat_id' => $chat_id, 'text' => "🛠 **MASTER SETTINGS V2**\nSemua yang lu ubah di sini bakal langsung gantiin yang lama!", 'reply_markup' => json_encode($menu)]);
}

// --- CALLBACK HANDLING ---
if ($callback) {
    $data = $callback['data'];
    $cb_chat_id = $callback['message']['chat']['id'];
    $msg_id = $callback['message']['message_id'];

    if ($data == "close") { apiRequest("deleteMessage", ['chat_id'=>$cb_chat_id, 'message_id'=>$msg_id]); exit; }
    if ($data == "gen_masal") {
        $pool = ["10K", "50K", "Jackpot", "Zonk"];
        for($i=1;$i<=10;$i++){ $k="REGE-".strtoupper(substr(md5(microtime().$i),0,6)); updateFirebase("kupon/$k", ["hadiah"=>$pool[array_rand($pool)],"status"=>"aktif"]); }
        apiRequest("answerCallbackQuery", ['callback_query_id'=>$callback['id'], 'text'=>"✅ 10 Kupon Berhasil Dibuat!"]);
        exit;
    }

    $labels = ['title'=>'Ketik Judul Baru', 'sub'=>'Ketik Sub-Judul Baru', 'bg'=>'Kirim FOTONYA langsung (Background)', 'doll'=>'Kirim FOTONYA langsung (Boneka)', 'claw'=>'Kirim FOTONYA langsung (Capit)'];
    updateFirebase("admin_state", ["action" => $data]);
    apiRequest("editMessageText", ['chat_id'=>$cb_chat_id, 'message_id'=>$msg_id, 'text'=>"👉 " . $labels[str_replace('edit_','',$data)], 'reply_markup'=>json_encode(['inline_keyboard'=>[[['text'=>'⬅️ Batal','callback_data'=>'back']]]])]);
}

if ($callback && $callback['data'] == "back") {
    apiRequest("editMessageText", ['chat_id'=>$callback['message']['chat']['id'], 'message_id'=>$callback['message']['message_id'], 'text'=>"🛠 **MASTER SETTINGS V2**", 'reply_markup'=>json_encode($menu)]);
}

// --- HANDLING INPUT (FOTO & TEKS) ---
if ($from_id == $admin_id) {
    $state_raw = file_get_contents("{$firebase_url}/admin_state.json");
    $state = json_decode($state_raw, true)['action'] ?? "idle";

    if ($state != "idle") {
        $val = "";
        // Jika input berupa foto
        if ($photo) {
            $file_id = end($photo)['file_id'];
            $file = apiRequest("getFile", ['file_id' => $file_id]);
            $val = "https://api.telegram.org/file/bot$token/" . $file['result']['file_path'];
        } else {
            $val = $message;
        }

        if ($val && strpos($message, '/') !== 0) {
            $field = str_replace('edit_', '', $state);
            updateFirebase("config", [$field => $val]);
            updateFirebase("admin_state", ["action" => "idle"]);
            apiRequest("sendMessage", ['chat_id'=>$chat_id, 'text'=>"✅ SUCCESS! Settingan `$field` lama udah dihapus & diganti yang baru.", 'reply_markup'=>json_encode(['inline_keyboard'=>[[['text'=>'🔙 Balik Menu','callback_data'=>'back']]]])]);
        }
    }
}
?>
