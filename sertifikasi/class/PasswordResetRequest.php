<?php
require_once __DIR__ . '/Database.php';

class PasswordResetRequest extends Database
{
    public function submit(string $email): void
    {
        $this->db->beginTransaction();
        try {
            $userQuery = $this->db->prepare(
                'SELECT id FROM user
                 WHERE email = ? AND masuk_sampah_pada IS NULL
                 FOR UPDATE'
            );
            $userQuery->execute([$email]);
            $userId = $userQuery->fetchColumn();

            if ($userId !== false) {
                $pendingQuery = $this->db->prepare(
                    "SELECT id FROM password_reset_requests
                     WHERE user_id = ? AND status = 'pending'
                     LIMIT 1 FOR UPDATE"
                );
                $pendingQuery->execute([(int) $userId]);

                if ($pendingQuery->fetchColumn() === false) {
                    $insert = $this->db->prepare(
                        "INSERT INTO password_reset_requests (user_id, status)
                         VALUES (?, 'pending')"
                    );
                    $insert->execute([(int) $userId]);
                }
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}
