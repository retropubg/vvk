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

// Crear tablas si no existen
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL UNIQUE,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === FALSE) {
    die("Error al crear la tabla users: " . $conn->error);
}

$sql = "CREATE TABLE IF NOT EXISTS keys_table (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_value VARCHAR(255) NOT NULL UNIQUE,
    duration INT NOT NULL,
    duration_type ENUM('d', 'h', 'm') NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    used_by BIGINT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === FALSE) {
    die("Error al crear la tabla keys_table: " . $conn->error);
}

$sql = "CREATE TABLE IF NOT EXISTS premiums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL UNIQUE,
    first_name VARCHAR(255) NOT NULL,
    username VARCHAR(255),
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === FALSE) {
    die("Error al crear la tabla premiums: " . $conn->error);
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
    $username = $update["from"]["username"] ?? null; // Username del usuario
    $message_id = $update["message_id"]; // ID del mensaje
    $message = $update["text"]; // Texto del mensaje

    //----------DATOS DE GRUPOS----------//
    $chat_id = $update["chat"]["id"]; // ID del chat (puede ser un grupo o un chat privado)

    // Comando /start
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
            . "Mis comandos disponibles son:\n"
            . "/genkey [d|h|m][número] - Generar una key de premium.\n"
            . "/claim [key] - Canjear una key de premium.\n"
            . "/listpremiums - Ver la lista de usuarios premium.";
        sendMessage($chat_id, $respuesta, $message_id);
    }

    // Comando /genkey
    if (strpos($message, "/genkey") === 0) {
        $parts = explode(" ", $message);
        if (count($parts) === 2 && preg_match("/^[dhm]\d+$/", $parts[1])) {
            $duration_type = substr($parts[1], 0, 1); // d, h, o m
            $duration = intval(substr($parts[1], 1)); // Número de días, horas o minutos
            $key_value = generateKey(); // Generar una key única

            // Insertar la key en la base de datos
            $sql = "INSERT INTO keys_table (key_value, duration, duration_type) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sis", $key_value, $duration, $duration_type);
            $stmt->execute();
            $stmt->close();

            // Respuesta al usuario
            $respuesta = "🔑 Key generada:\n\n"
                . "Key: <code>$key_value</code>\n"
                . "Duración: $duration $duration_type\n\n"
                . "⚠️ Esta key solo puede ser usada una vez.";
            sendMessage($chat_id, $respuesta, $message_id);
        } else {
            sendMessage($chat_id, "❌ Formato incorrecto. Usa /genkey [d|h|m][número].", $message_id);
        }
    }

    // Comando /claim
    if (strpos($message, "/claim") === 0) {
        $parts = explode(" ", $message);
        if (count($parts) === 2) {
            $key_value = $parts[1]; // Key proporcionada por el usuario

            // Verificar si la key existe y no ha sido usada
            $sql = "SELECT * FROM keys_table WHERE key_value = ? AND used = FALSE";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $key_value);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $key_data = $result->fetch_assoc();
                $duration = $key_data["duration"];
                $duration_type = $key_data["duration_type"];

                // Calcular la fecha de expiración
                $expires_at = date("Y-m-d H:i:s", strtotime("+$duration $duration_type"));

                // Marcar la key como usada
                $sql = "UPDATE keys_table SET used = TRUE, used_by = ? WHERE key_value = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("is", $id, $key_value);
                $stmt->execute();

                // Guardar al usuario como premium
                $sql = "INSERT INTO premiums (user_id, first_name, username, expires_at) VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE expires_at = VALUES(expires_at)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isss", $id, $Name, $username, $expires_at);
                $stmt->execute();

                // Respuesta al usuario
                $respuesta = "🎉 ¡Felicidades, $Name! Ahora eres premium hasta el $expires_at.";
                sendMessage($chat_id, $respuesta, $message_id);
            } else {
                sendMessage($chat_id, "❌ Key inválida o ya ha sido usada.", $message_id);
            }
        } else {
            sendMessage($chat_id, "❌ Formato incorrecto. Usa /claim [key].", $message_id);
        }
    }

    // Comando /listpremiums
    if ($message === "/listpremiums") {
        // Obtener la lista de usuarios premium
        $sql = "SELECT * FROM premiums";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $respuesta = "👑 Lista de usuarios premium:\n\n";
            while ($row = $result->fetch_assoc()) {
                $respuesta .= "👤 Nombre: " . $row["first_name"] . "\n"
                    . "🆔 ID: " . $row["user_id"] . "\n"
                    . "📅 Expira: " . $row["expires_at"] . "\n\n";
            }
        } else {
            $respuesta = "ℹ️ No hay usuarios premium en este momento.";
        }
        sendMessage($chat_id, $respuesta, $message_id);
    }
}

//-------FUNCIÓN PARA ENVIAR MENSAJES---------//
function sendMessage($chatID, $respuesta, $message_id) {
    // Construir la URL para enviar el mensaje
    $url = $GLOBALS["website"]."/sendMessage?disable_web_page_preview=true&chat_id=".$chatID."&reply_to_message_id=".$message_id."&parse_mode=HTML&text=".urlencode($respuesta);
    
    // Enviar el mensaje y capturar la respuesta
    file_get_contents($url);
}

// Función para generar una key única
function generateKey() {
    return substr(md5(uniqid(rand(), true)), 0, 10); // Key de 10 caracteres
}

// Cerrar la conexión a la base de datos
$conn->close();
?>
