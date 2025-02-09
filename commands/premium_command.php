<?php
// Premium Command
function premiumCommand($pdo, $user_id) {
    try {
        // Check if the user is a premium user
        $stmt = $pdo->prepare("SELECT type FROM users WHERE id = :user_id");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['type'] === 'premium') {
            return "✅ Welcome, Premium User! You have access to this command.";
        } else {
            return "❌ This command is only available to premium users. Please upgrade your account to access this feature.";
        }
    } catch (PDOException $e) {
        return "❌ An error occurred: " . $e->getMessage();
    }
}
?>
