<?php
$token = getenv('TELEGRAM_BOT_TOKEN'); // Obtener token de variables de entorno

$website = "https://api.telegram.org/bot".$token;
$upda = json_decode(file_get_contents('php://input'), true);
$data = file_get_contents("php://input");
$json = json_decode($data, true);
$update = $json["message"];
//---------PERSONAL---------//
$id = $update["from"]["id"];
$Name = $update["from"]["first_name"];
$last = $update["from"]["last_name"];
$message_id = $update["message_id"];
$message = $update["text"];
//----------GRUPOS----------//
$chat_id = $update["chat"]["id"];
$id_new = $update["new_chat_member"]["id"];
$grupo = $update["chat"]["title"];
$nuevo = $update["new_chat_member"]["first_name"]. ' '.$update["new_chat_member"]["last>
//----------------------END VARIABLES----------------------//

//$user = $update["from"]["username"];
//------------seguridad-------------//
// ID de tu usuario (para permitir mensajes personales solo para ti)
$myid = "1292171163"; // Reemplaza con tu ID de usuario


// Configurar webhook automáticamente al visitar /?setup
/*
if(isset($_GET['setup'])) {
    $webhook_url = getenv('RAILWAY_STATIC_URL') . '/?port=' . getenv('PORT');
    file_get_contents("https://api.telegram.org/bot$bot_token/setWebhook?url=$webhook_url");
    die("✅ Webhook configurado!");
}
*/

    // Único comando: /start
    if($text === "/start") {
        $respuesta = "👋 ¡Hola! Soy un bot simple.\n\n"
            . "Mi único propósito es saludarte cuando escribes /start 😊";
        sendMessage($chat_id, $respuesta, $message_id);

    }


//-------FUNCION DE ENVIAR---------//
function sendMessage($chatID, $respuesta, $message_id) {
$url = $GLOBALS["website"]."/sendMessage?disable_web_page_preview=true&chat_id=".$chatID."&reply_to_message_id=".$message_id."&parse_mode=HTML&text=".urlencode($respuesta);
//$url = $GLOBALS["website"]."/sendMessage?disable_web_page_preview=true&chat_id=".$chatID."&parse_mode=HTML&text=".urlencode($respuesta);
$cap_message_id = file_get_contents($url);
//------------EXTRAE EL ID DEL MENSAGE----------//
$id_cap = capture($cap_message_id, '"message_id":', ',');
file_put_contents("ID", $id_cap);
}
