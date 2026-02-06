<?php
// polling.php
echo "🚀 Bot Mesin Capit berjalan di Termux...\n";
$token = "8584026511:AAEIGd6fWj88sqvKdZ6fjlrmt4xiqTY8sCc";
$offset = 0;

while (true) {
    $url = "https://api.telegram.org/bot$token/getUpdates?offset=$offset&timeout=30";
    $resp = @file_get_contents($url);
    $data = json_decode($resp, true);

    if (isset($data['result'])) {
        foreach ($data['result'] as $result) {
            $offset = $result['update_id'] + 1;
            // Kirim data ke bot.php
            $update_data = $result;
            include 'bot.php';
        }
    }
    sleep(1);
}
