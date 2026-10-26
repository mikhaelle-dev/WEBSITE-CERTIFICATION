<?php
require_once __DIR__ . '/Database.php';

class Feedback extends Database
{
    public function create(int $userId, int $rating, string $type, string $message): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO user_feedback (user_id, rating, category, message)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $rating, $type, $message]);
    }
}
