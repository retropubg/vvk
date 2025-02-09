<?php

// Database Connection
try {
    $pdo = new PDO('sqlite:bot.db'); // Database file name: bot.db
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create `users` Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY,          -- Telegram user ID
            name TEXT,                       -- User's full name
            username TEXT,                   -- Telegram username
            type TEXT DEFAULT 'free',        -- User type: free, premium, owner
            credits INTEGER DEFAULT 0,       -- Available credits
            expiry DATE                      -- Expiry date for premium users
        )
    ");

    // Create `redeem_codes` Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS redeem_codes (
            code TEXT PRIMARY KEY,           -- Unique redeem code
            credits INTEGER DEFAULT 0,       -- Credits the code adds
            expiry DATE                      -- Expiry the code sets for the user
        )
    ");

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

?>
