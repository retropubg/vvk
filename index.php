<?php
// Obtener el token del bot de Telegram desde las variables de entorno
$token = getenv('TELEGRAM_BOT_TOKEN');

// URL base de la API de Telegram
$website = "https://api.telegram.org/bot".$token;

// Obtener los datos enviados por Telegram en formato JSON
$data = file_get_contents("php://input");
$json = json_decode($data, true);

// Verificar si el JSON contiene un mensaje
if (isset($json["message"])) {
    $update = $json["message"];

    //---------DATOS PERSONALES---------//
    $id = $update["from"]["id"]; // ID del usuario que envió el mensaje
    $Name = $update["from"]["first_name"]; // Nombre del usuario
    $last = $update["from"]["last_name"]; // Apellido del usuario
    $message_id = $update["message_id"]; // ID del mensaje
    $message = $update["text"]; // Texto del mensaje

    //----------DATOS DE GRUPOS----------//
    $chat_id = $update["chat"]["id"]; // ID del chat (puede ser un grupo o un chat privado)
    $id_new = $update["new_chat_member"]["id"] ?? null; // ID del nuevo miembro (si es un grupo)
    $grupo = $update["chat"]["title"] ?? null; // Nombre del grupo (si es un grupo)

    //------------SEGURIDAD-------------//
    // ID de tu usuario (para permitir mensajes personales solo para ti)
    $myid = "1292171163"; // Reemplaza con tu ID de usuario

    // Único comando: /start
    if ($message === "/start") {
        $respuesta = "👋 ¡Hola! Soy un bot simple.\n\n"
            . "Mi único propósito es saludarte cuando escribes /start 😊";
        sendMessage($chat_id, $respuesta, $message_id);
    }




  // Comando para generar una clave con fecha de expiración
    if (strpos($message, "/generate") === 0) {
        // Extraer la fecha de expiración del mensaje (formato: /generate YYYY-MM-DD)
        $parts = explode(" ", $message);
        if (count($parts) == 2 && preg_match("/^\d{4}-\d{2}-\d{2}$/", $parts[1])) {
            $expiration_date = $parts[1];
            $key = generateKey($expiration_date); // Generar la clave
            sendMessage($chat_id, "🔑 Clave generada: $key\n📅 Fecha de expiración: $expiration_date");
        } else {
            sendMessage($chat_id, "❌ Formato incorrecto. Usa: /generate YYYY-MM-DD");
        }
    }

    // Comando para reclamar una clave
    if ($message === "/claim") {
        $key = isset($_GET['key']) ? $_GET['key'] : null;
        if ($key && claimKey($key)) {
            sendMessage($chat_id, "🎉 ¡Clave reclamada con éxito!");
        } else {
            sendMessage($chat_id, "❌ Clave inválida o expirada.");
        }
    }


// Función para generar una clave con fecha de expiración
function generateKey($expiration_date) {
    $key = bin2hex(random_bytes(8)); // Generar una clave aleatoria
    $data = [
        'key' => $key,
        'expiration_date' => $expiration_date,
        'claimed' => false
    ];
    file_put_contents("keys/$key.json", json_encode($data)); // Guardar la clave en un archivo
    return $key;

}    
// Función para reclamar una clave
function claimKey($key) {
    $file = "keys/$key.json";
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (!$data['claimed'] && strtotime($data['expiration_date']) >= time()) {
            $data['claimed'] = true;
            file_put_contents($file, json_encode($data)); // Marcar la clave como reclamada
            return true;
        }
    }
    return false;
}

    
}

//-------FUNCIÓN PARA ENVIAR MENSAJES---------//
function sendMessage($chatID, $respuesta, $message_id) {
    // Construir la URL para enviar el mensaje
    $url = $GLOBALS["website"]."/sendMessage?disable_web_page_preview=true&chat_id=".$chatID."&reply_to_message_id=".$message_id."&parse_mode=HTML&text=".urlencode($respuesta);
    
    // Enviar el mensaje y capturar la respuesta
    $cap_message_id = file_get_contents($url);

    // Extraer el ID del mensaje enviado (opcional)
    $id_cap = capture($cap_message_id, '"message_id":', ',');
    file_put_contents("ID", $id_cap); // Guardar el ID en un archivo (opcional)
}

// Función para extraer un valor de una cadena JSON (opcional)
function capture($string, $start, $end) {
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}
