<?php

if (preg_match('/^\/key(?: (v\d+))?$/i', $text, $matches)) {
    $version = $matches[1] ?? 'v1';
    handleKey($pdo, $chat_id, $userId, $owner_id, $version);
}

function handleKey($pdo, $chat_id, $userId, $ownerId, $version) {
    $response = generateKeyByVersion($pdo, $version);
    bot('sendMessage', ['chat_id' => $chat_id, 'text' => $response, 'parse_mode' => 'html']);
}
