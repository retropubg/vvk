<?php
// Credits-based Command
function creditsCommand($pdo, $user_id) {
    $credits_required = 1; // Credits required per use

    try {
        // Check the user's current credits
        $stmt = $pdo->prepare("SELECT credits FROM users WHERE id = :user_id");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return "❌ User not found in the database.";
        }

        $current_credits = (int)$user['credits'];

        if ($current_credits >= $credits_required) {
            // Deduct credits and execute the command
            $new_credits = $current_credits - $credits_required;
            $update_stmt = $pdo->prepare("UPDATE users SET credits = :new_credits WHERE id = :user_id");
            $update_stmt->bindValue(':new_credits', $new_credits, PDO::PARAM_INT);
            $update_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $update_stmt->execute();

            return "✅ Command executed successfully. Remaining credits: {$new_credits}.";
        } else {
            // Insufficient credits
            $needed_credits = $credits_required - $current_credits;
            return "❌ You need {$needed_credits} more credits to use this command.";
        }
    } catch (PDOException $e) {
        return "❌ An error occurred: " . $e->getMessage();
    }
}
?>