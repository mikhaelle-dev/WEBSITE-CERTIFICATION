<?php
require_once __DIR__ . '/Database.php';

class AdminUser extends Database
{
    public function isActiveAdmin(int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM user WHERE id = ? AND role = 'admin' AND status = 1");
        $stmt->execute([$userId]);
        return (bool) $stmt->fetch();
    }

    public function getStats(): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total,
                    SUM(role = 'siswa') AS siswa,
                    SUM(role = 'instruktur') AS instruktur,
                    SUM(role = 'admin') AS admin,
                    SUM(status = 1) AS aktif,
                    SUM(status = 0) AS nonaktif
             FROM user
             WHERE masuk_sampah_pada IS NULL"
        );
        $stmt->execute();
        $stats = $stmt->fetch();

        return array_map('intval', $stats);
    }

    public function getUsers(string $search, string $role, string $status): array
    {
        $sql = 'SELECT user.id, user.nama, user.email, user.role, user.status, user.dibuat_pada,
                       profil_user.foto_profil
                FROM user
                LEFT JOIN profil_user ON profil_user.user_id = user.id
                WHERE user.masuk_sampah_pada IS NULL';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (user.nama LIKE ? OR user.email LIKE ?)';
            $term = '%' . $search . '%';
            $params[] = $term;
            $params[] = $term;
        }
        if (in_array($role, ['admin', 'instruktur', 'siswa'], true)) {
            $sql .= ' AND user.role = ?';
            $params[] = $role;
        }
        if ($status === 'aktif' || $status === 'nonaktif') {
            $sql .= ' AND user.status = ?';
            $params[] = $status === 'aktif' ? 1 : 0;
        }

        $sql .= ' ORDER BY user.dibuat_pada DESC, user.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getTrashedUsers(): array
    {
        $sql = sprintf(
            'SELECT user.id, user.nama, user.email, user.role, user.dibuat_pada,
                    user.masuk_sampah_pada,
                    UNIX_TIMESTAMP(DATE_ADD(
                        user.masuk_sampah_pada,
                        INTERVAL %d MINUTE
                    )) AS hapus_pada_epoch,
                    profil_user.foto_profil
             FROM user
             LEFT JOIN profil_user ON profil_user.user_id = user.id
             WHERE user.masuk_sampah_pada IS NOT NULL
             ORDER BY user.masuk_sampah_pada ASC, user.id ASC',
            self::ACCOUNT_TRASH_RETENTION_MINUTES
        );
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function changeRole(int $actorId, int $userId, string $role): string
    {
        if (!in_array($role, ['admin', 'instruktur', 'siswa'], true)) {
            throw new DomainException('Role akun tidak valid.');
        }
        if ($actorId === $userId) {
            throw new DomainException('Kamu tidak dapat mengubah role akun sendiri.');
        }

        return $this->withLockedUsers($actorId, function () use ($userId, $role): string {
            $target = $this->getLockedUser($userId);
            if ($target === null) {
                throw new DomainException('Akun tidak ditemukan.');
            }
            if ($target['masuk_sampah_pada'] !== null) {
                throw new DomainException('Pulihkan akun dari sampah sebelum mengubah role.');
            }
            if ($target['role'] === 'admin' && $target['status'] === 1 && $role !== 'admin'
                && $this->activeAdminCount() <= 1) {
                throw new DomainException('Admin aktif terakhir tidak dapat diturunkan role-nya.');
            }

            $stmt = $this->db->prepare('UPDATE user SET role = ? WHERE id = ?');
            $stmt->execute([$role, $userId]);
            return 'Role akun berhasil diperbarui.';
        });
    }

    public function setStatus(int $actorId, int $userId, int $status): string
    {
        if (!in_array($status, [0, 1], true)) {
            throw new DomainException('Status akun tidak valid.');
        }
        if ($actorId === $userId && $status === 0) {
            throw new DomainException('Kamu tidak dapat menonaktifkan akun sendiri.');
        }

        return $this->withLockedUsers($actorId, function () use ($userId, $status): string {
            $target = $this->getLockedUser($userId);
            if ($target === null) {
                throw new DomainException('Akun tidak ditemukan.');
            }
            if ($target['masuk_sampah_pada'] !== null) {
                throw new DomainException('Pulihkan akun dari sampah sebelum mengubah status.');
            }
            if ($target['role'] === 'admin' && $target['status'] === 1 && $status === 0
                && $this->activeAdminCount() <= 1) {
                throw new DomainException('Admin aktif terakhir tidak dapat dinonaktifkan.');
            }

            $stmt = $this->db->prepare('UPDATE user SET status = ? WHERE id = ?');
            $stmt->execute([$status, $userId]);
            return $status === 1 ? 'Akun berhasil diaktifkan.' : 'Akun berhasil dinonaktifkan.';
        });
    }

    public function resetPassword(int $actorId, int $userId, string $password): void
    {
        if ($actorId === $userId) {
            throw new DomainException('Gunakan halaman profil untuk mengganti password sendiri.');
        }

        $this->withLockedUsers($actorId, function () use ($userId, $password): void {
            $target = $this->getLockedUser($userId);
            if ($target === null) {
                throw new DomainException('Akun tidak ditemukan.');
            }
            if ($target['masuk_sampah_pada'] !== null) {
                throw new DomainException('Pulihkan akun dari sampah sebelum mereset kata sandi.');
            }
            $stmt = $this->db->prepare('UPDATE user SET password = ? WHERE id = ?');
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
        });
    }

    public function moveUsersToTrash(int $actorId, array $userIds): string
    {
        if (!$userIds || count($userIds) > 5) {
            throw new DomainException('Pilih minimal satu dan maksimal lima akun.');
        }
        foreach ($userIds as $userId) {
            if (!is_int($userId) || $userId < 1) {
                throw new DomainException('Pilihan akun tidak valid.');
            }
        }
        if (count($userIds) !== count(array_unique($userIds))) {
            throw new DomainException('Pilihan akun duplikat.');
        }
        $userIds = array_values($userIds);
        if (in_array($actorId, $userIds, true)) {
            throw new DomainException('Kamu tidak dapat memindahkan akun sendiri ke sampah.');
        }

        return $this->withLockedUsers($actorId, function () use ($userIds): string {
            $targets = [];
            $activeAdminsToTrash = 0;
            foreach ($userIds as $userId) {
                $target = $this->getLockedUser($userId);
                if ($target === null) {
                    throw new DomainException('Salah satu akun yang dipilih tidak ditemukan.');
                }
                if ($target['masuk_sampah_pada'] !== null) {
                    throw new DomainException('Salah satu akun yang dipilih sudah berada di sampah.');
                }
                if ($target['role'] === 'admin' && $target['status'] === 1) {
                    $activeAdminsToTrash++;
                }
                $targets[] = $userId;
            }

            if ($this->activeAdminCount() - $activeAdminsToTrash < 1) {
                throw new DomainException('Admin aktif terakhir tidak dapat dipindahkan ke sampah.');
            }

            $stmt = $this->db->prepare(
                'UPDATE user
                 SET masuk_sampah_pada = CURRENT_TIMESTAMP,
                     status_sebelum_sampah = status,
                     status = 0
                 WHERE id = ? AND masuk_sampah_pada IS NULL'
            );
            foreach ($targets as $userId) {
                $stmt->execute([$userId]);
            }
            return count($targets) . ' akun dinonaktifkan dan dipindahkan ke sampah. Akun akan dihapus permanen setelah '
                . self::ACCOUNT_TRASH_RETENTION_MINUTES . ' menit jika tidak dipulihkan.';
        });
    }

    public function restoreUser(int $actorId, int $userId): string
    {
        if ($actorId === $userId) {
            throw new DomainException('Kamu tidak dapat memulihkan akun sendiri dari sampah.');
        }

        return $this->withLockedUsers($actorId, function () use ($userId): string {
            $target = $this->getLockedUser($userId);
            if ($target === null || $target['masuk_sampah_pada'] === null) {
                throw new DomainException('Akun tidak ditemukan di sampah.');
            }
            if ((int) $target['sampah_kedaluwarsa'] === 1) {
                $this->deleteTrashedUser($userId);
                return 'Waktu pemulihan akun sudah habis; akun dihapus permanen.';
            }

            $stmt = $this->db->prepare(
                'UPDATE user
                 SET status = COALESCE(status_sebelum_sampah, 0),
                     status_sebelum_sampah = NULL,
                     masuk_sampah_pada = NULL
                 WHERE id = ? AND masuk_sampah_pada IS NOT NULL'
            );
            $stmt->execute([$userId]);
            return 'Akun berhasil dipulihkan.';
        });
    }

    public function permanentlyDeleteTrashedUser(int $actorId, int $userId): string
    {
        if ($actorId === $userId) {
            throw new DomainException('Kamu tidak dapat menghapus akun sendiri.');
        }

        return $this->withLockedUsers($actorId, function () use ($userId): string {
            $target = $this->getLockedUser($userId);
            if ($target === null || $target['masuk_sampah_pada'] === null) {
                throw new DomainException('Akun tidak ditemukan di sampah.');
            }

            $this->deleteTrashedUser($userId);
            return 'Akun dan seluruh data terkait berhasil dihapus permanen.';
        });
    }

    public function purgeExpiredTrash(int $actorId): int
    {
        $expiredSql = sprintf(
            'SELECT 1
             FROM user
             WHERE masuk_sampah_pada IS NOT NULL
               AND masuk_sampah_pada <= CURRENT_TIMESTAMP - INTERVAL %d MINUTE
             LIMIT 1',
            self::ACCOUNT_TRASH_RETENTION_MINUTES
        );
        $expired = $this->db->prepare($expiredSql);
        $expired->execute();
        if (!$expired->fetchColumn()) {
            return 0;
        }

        $this->db->beginTransaction();
        try {
            $admin = $this->db->prepare(
                "SELECT id FROM user WHERE id = ? AND role = 'admin' AND status = 1 FOR UPDATE"
            );
            $admin->execute([$actorId]);
            if (!$admin->fetch()) {
                throw new DomainException('Sesi admin tidak lagi aktif. Silakan login kembali.');
            }

            $deleteSql = sprintf(
                'DELETE FROM user
                 WHERE masuk_sampah_pada IS NOT NULL
                   AND masuk_sampah_pada <= CURRENT_TIMESTAMP - INTERVAL %d MINUTE',
                self::ACCOUNT_TRASH_RETENTION_MINUTES
            );
            $stmt = $this->db->prepare($deleteSql);
            $stmt->execute();
            $purgedCount = $stmt->rowCount();
            $this->db->commit();
            return $purgedCount;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function deleteTrashedUser(int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM user WHERE id = ? AND masuk_sampah_pada IS NOT NULL');
        $stmt->execute([$userId]);
    }

    private function withLockedUsers(int $actorId, callable $callback)
    {
        $this->db->beginTransaction();
        try {
            $lock = $this->db->prepare('SELECT id FROM user ORDER BY id FOR UPDATE');
            $lock->execute();
            $lock->fetchAll();
            if (!$this->isActiveAdmin($actorId)) {
                throw new DomainException('Sesi admin tidak lagi aktif. Silakan login kembali.');
            }

            $result = $callback();
            $this->db->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function getLockedUser(int $userId): ?array
    {
        $sql = sprintf(
            'SELECT id, role, status, masuk_sampah_pada,
                    (masuk_sampah_pada <= CURRENT_TIMESTAMP - INTERVAL %d MINUTE) AS sampah_kedaluwarsa
             FROM user
             WHERE id = ?
             FOR UPDATE',
            self::ACCOUNT_TRASH_RETENTION_MINUTES
        );
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }

        $user['status'] = (int) $user['status'];
        return $user;
    }

    private function activeAdminCount(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM user WHERE role = 'admin' AND status = 1");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

}
