<?php

// List of valid prefixes
$PREFIXES = [
    "/", ".", "!", "~", ":", "@", "$", "<", "-", "+", "?", "¿", ")", "(", "#", "%", "&", "*", '"', "'", ";", ",", "_", ">", "`", "•", "|", "√", "π", "×", "¶", "∆", "£", "¢", "€", "¥", "^", "°", "=", "{", "}", "©", "®", "™", "[", "]",
];

function normalizeCommand($text, $prefixes) {
    foreach ($prefixes as $prefix) {
        if (strpos($text, $prefix) === 0) {
            return substr($text, strlen($prefix)); // Remove the prefix
        }
    }
    return $text;
}

$normalizedText = normalizeCommand($text, $PREFIXES);

if (strpos($normalizedText, "redeem") === 0) {
    $code = trim(substr($normalizedText, strlen("redeem")));

    if (empty($code)) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "You need to provide a redeem key. Example: /redeem YOUR_KEY",
            'parse_mode' => 'html',
        ]);
    } else {
        handleRedeem($pdo, $chat_id, $userId, $code);
    }
}

function handleRedeem($pdo, $chat_id, $userId, $code) {
    $response = redeemKey($pdo, $userId, $code);
    bot('sendMessage', ['chat_id' => $chat_id, 'text' => $response, 'parse_mode' => 'html']);
}
