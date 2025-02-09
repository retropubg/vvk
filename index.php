<?php

# Include configuration, database, and utility functions
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

# Get and decode update from Telegram
$update = json_decode(file_get_contents('php://input'), true);

# [ TELEGRAM API VARIABLES ]
$chat_id = $update['message']['chat']['id'] ?? null;
$message_id = $update['message']['message_id'] ?? null;
$text = $update['message']['text'] ?? null;
$userId = $update['message']['from']['id'] ?? null;
$name = $update['message']['from']['first_name'] ?? 'Unknown';
$username = $update['message']['from']['username'] ?? 'NoUsername';

# Check and add the user to the database if not already added
checkAndAddUser($pdo, $userId, $name, $username);

# Downgrade user if their premium status has expired
downgradeUserIfExpired($pdo, $userId);

# Automatically include and execute all command files
foreach (glob(__DIR__ . "/commands/*.php") as $commandFile) {
    require_once $commandFile;
}

?>
