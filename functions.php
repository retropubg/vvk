<?php

require_once 'db.php';

function startsWithAnyCommand($text, $commands) {
    $prefixes = [
        "/",
        ".",
        "!",
        "~",
        ":",
        "@",
        "$",
        "<",
        "-",
        "+",
        "?",
        "¿",
        ")",
        "(",
        "#",
        "%",
        "&",
        "*",
        '"',
        "'",
        ";",
        ",",
        "_",
        ">",
        "`",
        "•",
        "|",
        "√",
        "π",
        "×",
        "¶",
        "∆",
        "£",
        "¢",
        "€",
        "¥",
        "^",
        "°",
        "=",
        "{",
        "}",
        "©",
        "®",
        "™",
        "[",
        "]",
    ];
    foreach ($prefixes as $prefix) {
        foreach ($commands as $command) {
            if (strpos($text, $prefix . $command) === 0) {
                return true;
            }
        }
    }
    return false;
}

function checkAndAddUser($pdo, $userId, $name, $username) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        $stmt = $pdo->prepare("
            INSERT INTO users (id, name, username, type)
            VALUES (:id, :name, :username, 'free')
        ");
        $stmt->execute([
            ':id' => $userId,
            ':name' => $name,
            ':username' => $username,
        ]);
    }
}
function isPremium($user_id) {
    try {
        $pdo = new PDO('sqlite:bot.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT type FROM users WHERE id = :user_id");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user && $user['type'] === 'premium';
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}
function getUserInfo($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if ($user) {
        $type = ucfirst($user['type']);
        $credits = $user['credits'];
        $expiry = $user['expiry'] ?? 'N/A';

        return "<b>👤 User Info</b>\n\n"
            . "ID: <code>{$userId}</code>\n"
            . "Name: <code>{$user['name']}</code>\n"
            . "Username: <code>@{$user['username']}</code>\n"
            . "Type: <code>{$type}</code>\n"
            . "Credits: <code>{$credits}</code>\n"
            . "Expiry: <code>{$expiry}</code>";
    } else {
        return "❌ User not found in the database.";
    }
}

function generateKeyByVersion($pdo, $version) {
    include 'config.php';
    global $userId;

    if ($userId != $owner_id) {
        return "❌ Only the owner can generate keys.";
    }

    $expiryDays = 1;
    $credits = 0;

    if ($version == 'v1') {
        $expiryDays = 7;
    } elseif ($version == 'v2') {
        $expiryDays = 7;
        $credits = 100;
    }

    $code = strtoupper(bin2hex(random_bytes(4)));
    $expiryDate = date('Y-m-d', strtotime("+$expiryDays days"));

    $stmt = $pdo->prepare("
        INSERT INTO redeem_codes (code, credits, expiry)
        VALUES (:code, :credits, :expiry)
    ");
    $stmt->execute([
        ':code' => $code,
        ':credits' => $credits,
        ':expiry' => $expiryDate,
    ]);

    return "✅ Key Generated!\n\n"
        . "Code: <code>{$code}</code>\n"
        . "Credits: <code>{$credits}</code>\n"
        . "Expiry: <code>{$expiryDate}</code>";
}

function redeemKey($pdo, $userId, $code) {
    $stmt = $pdo->prepare("SELECT * FROM redeem_codes WHERE code = :code");
    $stmt->execute([':code' => $code]);
    $key = $stmt->fetch();

    if (!$key) {
        return "❌ Invalid redeem code.";
    }

    $stmt = $pdo->prepare("
        UPDATE users
        SET credits = credits + :credits,
            type = 'premium',
            expiry = :expiry
        WHERE id = :id
    ");
    $stmt->execute([
        ':credits' => $key['credits'],
        ':expiry' => $key['expiry'],
        ':id' => $userId,
    ]);

    $stmt = $pdo->prepare("DELETE FROM redeem_codes WHERE code = :code");
    $stmt->execute([':code' => $code]);

    return "✅ Key Redeemed Successfully!\n\n"
        . "Credits Added: <code>{$key['credits']}</code>\n"
        . "Premium Expiry: <code>{$key['expiry']}</code>";
}

function downgradeExpiredUsers($pdo) {
    $stmt = $pdo->prepare("
        UPDATE users
        SET type = 'free', credits = 0, expiry = NULL
        WHERE type = 'premium' AND expiry <= :today
    ");
    $stmt->execute([':today' => date('Y-m-d')]);
}
function bot($method, $data = []) {
    global $botToken;
    $url = "https://api.telegram.org/bot" . $botToken . "/" . $method;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return "Curl error: $error";
    }

    return json_decode($response, true);
}
function downgradeUserIfExpired($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND type = 'premium'");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    if ($user && $user['expiry'] <= date('Y-m-d')) {
        // Downgrade to free
        $stmt = $pdo->prepare("
            UPDATE users
            SET type = 'free', credits = 0, expiry = NULL
            WHERE id = :id
        ");
        $stmt->execute([':id' => $userId]);

        bot('sendMessage', [
            'chat_id' => $userId,
            'text' => "⏳ Your premium membership has expired. You are now a free user.",
            'parse_mode' => 'html'
        ]);
    }
}
?>
