<?php
$COMMANDS = ["start", "menu", "help"];
if (startsWithAnyCommand($text, $COMMANDS)) {
    handleStart($chat_id, $message_id);
}

function handleStart($chat_id, $message_id) {
    global $bot_user;

    $msg = "<b>🚀 Bot Status: Operational 🟢</b>\n\n📣 Stay tuned for news and upgrades!";
    $keyboard = [
        'inline_keyboard' => [
            [['text' => "🔍 Menu", 'callback_data' => "menu"]],
            [['text' => "🤖 Add To Group", 'url' => "https://t.me/$bot_user?startgroup"]],
        ],
    ];

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $msg,
        'parse_mode' => 'html',
        'reply_to_message_id' => $message_id,
        'reply_markup' => json_encode($keyboard),
    ]);
}

# Handle the callback data
switch ($data) {
    case 'menu':
        sendMenu($callback_chat_id, $callback_message_id);
        break;

    case 'auth':
        sendAuth($callback_chat_id, $callback_message_id);
        break;

    case 'charge':
        sendCharge($callback_chat_id, $callback_message_id);
        break;

    case 'premium':
        sendPremium($callback_chat_id, $callback_message_id);
        break;

    case 'end':
        bot('deleteMessage', [
            'chat_id' => $callback_chat_id,
            'message_id' => $callback_message_id,
        ]);
        break;

    default:
        sendComingSoon($callback_chat_id, $callback_message_id);
        break;
}

function sendMenu($chat_id, $message_id) {
    $msg = "💡 What can I help you with today?\n\n🌟 Explore the newest features and enhancements!";
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '🔒 Auth', 'callback_data' => "auth"], ['text' => "⚡ Charge", 'callback_data' => "charge"]],
            [['text' => '🛒 Buy Premium', 'callback_data' => "premium"]],
            [['text' => '❌ Exit', 'callback_data' => "end"]],
        ],
    ];

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $msg,
        'parse_mode' => 'html',
        'reply_markup' => json_encode($keyboard),
    ]);
}

function sendAuth($chat_id, $message_id) {
    $msg = "<b>🔒 Auth Gateway</b>\n\nVerify card validity with advanced tools.";
    $keyboard = [
        'inline_keyboard' => [
            [['text' => 'Braintree', 'callback_data' => "braintree_auth"], ['text' => "Stripe", 'callback_data' => "stripe_auth"]],
            [['text' => '↩️ Back', 'callback_data' => "menu"]],
        ],
    ];

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $msg,
        'parse_mode' => 'html',
        'reply_markup' => json_encode($keyboard),
    ]);
}

function sendCharge($chat_id, $message_id) {
    $msg = "<b>⚡ Charge Gateway</b>\n\nPerform charges with secure gates.";
    $keyboard = [
        'inline_keyboard' => [
            [['text' => 'Braintree', 'callback_data' => "braintree_charge"], ['text' => "Stripe", 'callback_data' => "stripe_charge"]],
            [['text' => '↩️ Back', 'callback_data' => "menu"]],
        ],
    ];

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $msg,
        'parse_mode' => 'html',
        'reply_markup' => json_encode($keyboard),
    ]);
}

function sendPremium($chat_id, $message_id) {
    global $owner_user;

    $msg = "<b>🌟 Premium Membership</b>\n\nWhy upgrade?\n\n• Unlimited Features\n• Priority Support\n\nTap below to buy Premium!";
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '💵 Price', 'callback_data' => "price"]],
            [['text' => '👤 Buy Now', 'url' => "t.me/$owner_user"]],
            [['text' => '↩️ Back', 'callback_data' => "menu"]],
        ],
    ];

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $msg,
        'parse_mode' => 'html',
        'reply_markup' => json_encode($keyboard),
    ]);
}

function sendComingSoon($chat_id, $message_id) {
    $msg = "🔧 The feature is under development. Stay tuned! 🚀";
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '↩️ Back to Menu', 'callback_data' => "menu"]],
        ],
    ];

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $msg,
        'parse_mode' => 'html',
        'reply_markup' => json_encode($keyboard),
    ]);
}
