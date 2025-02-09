<?php

if (preg_match('/^\/info$/i', $text)) {
    handleInfo($pdo, $chat_id, $userId, $message_id);
}

function handleInfo($pdo, $chat_id, $userId, $message_id) {
    $response = getUserInfo($pdo, $userId);
    bot('sendMessage', ['chat_id' => $chat_id, 'text' => $response, 'reply_to_message_id' => $message_id, 'parse_mode' => 'html']);
}
