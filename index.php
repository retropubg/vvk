<?php
// Obtener el token del bot de Telegram desde las variables de entorno
$token = getenv('TELEGRAM_BOT_TOKEN');

// Obtener las credenciales de la base de datos desde las variables de entorno
$host = getenv('MYSQLHOST');
$user = getenv('MYSQLUSER');
$password = getenv('MYSQLPASSWORD');
$database = getenv('MYSQLDATABASE');
$port = getenv('MYSQLPORT');

// Crear la conexión a la base de datos
$conn = new mysqli($host, $user, $password, $database, $port);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error al conectar a la base de datos: " . $conn->connect_error);
}
echo "Conectado a la base de datos MySQL correctamente.";

// Crear una tabla de usuarios si no existe
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL UNIQUE,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    die("Error al crear la tabla: " . $conn->error);
}

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
        // Guardar al usuario en la base de datos
        $sql = "INSERT INTO users (user_id, first_name, last_name) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $id, $Name, $last);
        $stmt->execute();
        $stmt->close();

        // Respuesta al usuario
        $respuesta = "👋 ¡Hola, $Name! Soy un bot simple rj.\n\n"
            . "Mi único propósito es saludarte cuando escribes /start 😊";
        sendMessage($chat_id, $respuesta, $message_id);
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

// Cerrar la conexión a la base de datos
$conn->close();
?>
