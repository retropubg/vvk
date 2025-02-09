<?php
// Configuración esencial
$bot_token = getenv('TELEGRAM_BOT_TOKEN');
$admin_id = getenv('OWNER_TELEGRAM_ID');
$webhook_url = getenv('RAILWAY_STATIC_URL') ?: ('https://' . getenv('RAILWAY_SERVICE_NAME') . '.up.railway.app');

// Auto-webhook setup
if(isset($_GET['setup'])) {
    $url = "$webhook_url/?port=" . getenv('PORT');
    $response = file_get_contents("https://api.telegram.org/bot$bot_token/setWebhook?url=$url");
    die("<pre>Webhook configurado: " . json_encode(json_decode($response), JSON_PRETTY_PRINT) . "</pre>");
}

// Inicialización de DB
if(!file_exists('premium.db')) {
    file_put_contents('premium.db', '');
    chmod('premium.db', 0666);
}
$db = new SQLite3('premium.db');

// Tablas si no existen
$db->exec("CREATE TABLE IF NOT EXISTS tokens(
    token TEXT PRIMARY KEY,
    created_at INTEGER,
    claimed_by INTEGER DEFAULT 0
)");

$db->exec("CREATE TABLE IF NOT EXISTS users(
    user_id INTEGER PRIMARY KEY,
    premium_expiry INTEGER DEFAULT 0
)");

// Función de respuesta
function sendMessage($chat_id, $text, $markdown = true) {
    global $bot_token;
    $params = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => $markdown ? 'Markdown' : null
    ];
    file_get_contents("https://api.telegram.org/bot$bot_token/sendMessage?" . http_build_query($params));
}

// Procesar actualización
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update = json_decode(file_get_contents('php://input'), true);
    $message = $update['message'];
    $text = $message['text'];
    $user_id = $message['from']['id'];
    
    // Comando: /generatekey (Admin)
    if(strpos($text, "/generatekey") === 0 && $user_id == $admin_id) {
        $token = bin2hex(random_bytes(16)) . time();
        $stmt = $db->prepare("INSERT INTO tokens(token, created_at) VALUES(?, ?)");
        $stmt->bindValue(1, $token);
        $stmt->bindValue(2, time());
        $stmt->execute();
        
        sendMessage($user_id, "🔑 *Nuevo Token Premium*\nValido por 2 horas\n\n`$token`");
    }
    
    // Comando: /claim [token]
    if(strpos($text, "/claim") === 0) {
        $token = explode(' ', $text)[1] ?? null;
        
        if(!$token) {
            sendMessage($user_id, "⚠️ Uso correcto: `/claim <token>`", false);
            exit;
        }
        
        // Verificar token
        $stmt = $db->prepare("SELECT created_at FROM tokens WHERE token = ? AND claimed_by = 0");
        $stmt->bindValue(1, $token);
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        
        if(!$result || (time() - $result['created_at']) > 7200) {
            sendMessage($user_id, "❌ Token inválido o expirado");
            exit;
        }
        
        // Activar premium
        $expiry = time() + 7200; // 2 horas
        $db->exec("UPDATE tokens SET claimed_by = $user_id WHERE token = '$token'");
        $db->exec("INSERT OR REPLACE INTO users VALUES($user_id, $expiry)");
        
        sendMessage($user_id, "🎉 ¡Premium activado!\nVálido hasta: " . date('d/m H:i', $expiry));
    }
}
