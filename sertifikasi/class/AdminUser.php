<?php
require_once __DIR__ . '/Database.php';

class AdminUser extends Database
{
    private ?array $userReferences = null;

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
             FROM user"
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
                WHERE 1 = 1';
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
        $users = $stmt->fetchAll();

        foreach ($users as &$user) {
            $user['status'] = (int) $user['status'];
            $user['related_tables'] = $this->getRelatedDataTables((int) $user['id']);
        }
        unset($user);

        return $users;
    }

    public function createUser(int $actorId, string $nama, string $email, string $password, string $role): void
    {
        if (!in_array($role, ['admin', 'instruktur', 'siswa'], true)) {
            throw new DomainException('Role akun tidak valid.');
        }

        $this->withLockedUsers($actorId, function () use ($nama, $email, $password, $role): void {
            $stmt = $this->db->prepare('INSERT INTO user (email, nama, password, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$email, $nama, password_hash($password, PASSWORD_DEFAULT), $role]);
        });
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
            if ($this->getLockedUser($userId) === null) {
                throw new DomainException('Akun tidak ditemukan.');
            }
            $stmt = $this->db->prepare('UPDATE user SET password = ? WHERE id = ?');
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
        });
    }

    public function deleteUser(int $actorId, int $userId): string
    {
        if ($actorId === $userId) {
            throw new DomainException('Kamu tidak dapat menghapus akun sendiri.');
        }

        return $this->withLockedUsers($actorId, function () use ($userId): string {
            $target = $this->getLockedUser($userId);
            if ($target === null) {
                throw new DomainException('Akun tidak ditemukan.');
            }
            if ($target['role'] === 'admin' && $target['status'] === 1 && $this->activeAdminCount() <= 1) {
                throw new DomainException('Admin aktif terakhir tidak dapat dihapus.');
            }
            if ($this->getRelatedDataTables($userId)) {
                throw new DomainException('Akun memiliki data terkait sehingga tidak dapat dihapus permanen. Nonaktifkan akun saja.');
            }

            $stmt = $this->db->prepare('DELETE FROM user WHERE id = ?');
            $stmt->execute([$userId]);
            return 'Akun berhasil dihapus permanen.';
        });
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
        $stmt = $this->db->prepare('SELECT id, role, status FROM user WHERE id = ? FOR UPDATE');
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

    private function getRelatedDataTables(int $userId): array
    {
        $tables = [];
        foreach ($this->getUserReferences() as $reference) {
            $table = $this->quoteIdentifier($reference['table']);
            $column = $this->quoteIdentifier($reference['column']);
            $stmt = $this->db->prepare("SELECT 1 FROM {$table} WHERE {$column} = ? LIMIT 1");
            $stmt->execute([$userId]);
            if ($stmt->fetchColumn()) {
                $tables[] = $reference['table'];
            }
        }

        return array_values(array_unique($tables));
    }

    private function getUserReferences(): array
    {
        if ($this->userReferences !== null) {
            return $this->userReferences;
        }

        $stmt = $this->db->prepare(
            'SELECT TABLE_NAME AS `table`, COLUMN_NAME AS `column`
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND REFERENCED_TABLE_NAME = ?
               AND REFERENCED_COLUMN_NAME = ?'
        );
        $stmt->execute(['user', 'id']);
        $this->userReferences = $stmt->fetchAll();
        return $this->userReferences;
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new RuntimeException('Nama tabel database tidak valid.');
        }
        return '`' . $identifier . '`';
    }
}
