<?php
$bot_token = getenv('TELEGRAM_BOT_TOKEN'); // Obtener token de variables de entorno

// Configurar webhook automáticamente al visitar /?setup
/*
if(isset($_GET['setup'])) {
    $webhook_url = getenv('RAILWAY_STATIC_URL') . '/?port=' . getenv('PORT');
    file_get_contents("https://api.telegram.org/bot$bot_token/setWebhook?url=$webhook_url");
    die("✅ Webhook configurado!");
}
*/
// Solo procesar comandos si es una solicitud POST
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update = json_decode(file_get_contents('php://input'), true);
    $message = $update['message'];
    $text = $message['text'];
    $chat_id = $message['chat']['id'];

    // Único comando: /start
    if($text === "/start") {
        $respuesta = "👋 ¡Hola! Soy un bot simple.\n\n"
            . "Mi único propósito es saludarte cuando escribes /start 😊";
        
        // Enviar respuesta
        file_get_contents("https://api.telegram.org/bot$bot_token/sendMessage?" . http_build_query([
            'chat_id' => $chat_id,
            'text' => $respuesta
        ]));
    }
}
