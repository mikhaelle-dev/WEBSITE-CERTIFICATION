<?php
require_once __DIR__ . '/Database.php';

class Auth extends Database
{
    public function getActiveAdminEmail(): ?string
    {
        $stmt = $this->db->query(
            "SELECT email
             FROM user
             WHERE role = 'admin' AND status = 1 AND masuk_sampah_pada IS NULL
             ORDER BY id
             LIMIT 1"
        );
        $email = $stmt->fetchColumn();

        return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    public function hasAdminAccount(): bool
    {
        return (bool) $this->db->query("SELECT id FROM user WHERE role = 'admin' LIMIT 1")->fetchColumn();
    }

    public function isActiveAccount(int $userId): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM user WHERE id = ? AND status = 1');
        $stmt->execute([$userId]);
        return (bool) $stmt->fetch();
    }
    public function isRestrictedAccount(int $userId): bool
    {
        $accountAccess = $this->getAccountAccess($userId);
        return $accountAccess === null
            || (int) $accountAccess['status'] !== 1
            || $accountAccess['masuk_sampah_pada'] !== null;
    }

    public function getAccountAccess(int $userId): ?array
    {
        $sql = sprintf(
            'SELECT id, status, masuk_sampah_pada,
                    CASE
                        WHEN masuk_sampah_pada IS NULL THEN NULL
                        ELSE GREATEST(0, TIMESTAMPDIFF(
                            SECOND,
                            CURRENT_TIMESTAMP,
                            DATE_ADD(masuk_sampah_pada, INTERVAL %d MINUTE)
                        ))
                    END AS waktu_sampah_tersisa
             FROM user
             WHERE id = ?',
            self::ACCOUNT_TRASH_RETENTION_MINUTES
        );
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $access = $stmt->fetch();
        if (!$access) {
            return null;
        }

        $access['status'] = (int) $access['status'];
        $access['waktu_sampah_tersisa'] = $access['waktu_sampah_tersisa'] === null
            ? null
            : (int) $access['waktu_sampah_tersisa'];

        return $access;
    }

    /**
     * @return array ['ok' => bool, 'user' => array|null, 'error' => string]
     */
    public function login(string $email, string $password): array
    {
        $sql = sprintf(
            'SELECT u.id, u.email, u.nama, u.password, u.role, u.status, u.masuk_sampah_pada,
                    GREATEST(0, TIMESTAMPDIFF(
                        SECOND,
                        CURRENT_TIMESTAMP,
                        DATE_ADD(u.masuk_sampah_pada, INTERVAL %d MINUTE)
                    )) AS waktu_sampah_tersisa,
                    p.foto_profil
             FROM user u
             LEFT JOIN profil_user p ON p.user_id = u.id
             WHERE u.email = ?',
            self::ACCOUNT_TRASH_RETENTION_MINUTES
        );
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Pesan sengaja sama untuk "email tidak ada" dan "password salah"
        if (!$user || !password_verify($password, $user['password'])) {
            return ['ok' => false, 'user' => null, 'error' => 'Email atau password salah.'];
        }
        $restricted = $user['masuk_sampah_pada'] !== null || (int) $user['status'] !== 1;
        unset($user['password']);
        return ['ok' => true, 'user' => $user, 'restricted' => $restricted, 'error' => ''];
    }

    public function createInitialAdmin(string $nama, string $email, string $password): void
    {
        $database = $this->db->query('SELECT DATABASE()')->fetchColumn();
        if (!is_string($database) || $database === '') {
            throw new RuntimeException('Unable to identify the active database for initial admin setup.');
        }

        $lockName = 'sertifikasi_setup_' . substr(hash('sha256', $database), 0, 40);
        $lock = $this->db->prepare('SELECT GET_LOCK(?, 5)');
        $lock->execute([$lockName]);
        if ((int) $lock->fetchColumn() !== 1) {
            throw new RuntimeException('Unable to acquire the initial admin setup lock.');
        }

        try {
            $adminExists = $this->db->query("SELECT id FROM user WHERE role = 'admin' LIMIT 1")->fetchColumn();
            if ($adminExists !== false) {
                throw new DomainException('Setup admin sudah selesai. Silakan login.');
            }

            $insert = $this->db->prepare(
                "INSERT INTO user (email, nama, password, role) VALUES (?, ?, ?, 'admin')"
            );
            $insert->execute([$email, $nama, password_hash($password, PASSWORD_DEFAULT)]);
        } finally {
            $release = $this->db->prepare('SELECT RELEASE_LOCK(?)');
            $release->execute([$lockName]);
        }
    }

    /**
     * Register publik SELALU membuat akun siswa.
     * Akun instruktur/admin dibuat oleh admin, bukan lewat form ini.
     */
    public function register(string $nama, string $email, string $password): array
    {
        $cek = $this->db->prepare('SELECT id FROM user WHERE email = ?');
        $cek->execute([$email]);
        if ($cek->fetch()) {
            return ['ok' => false, 'error' => 'Email sudah terdaftar.'];
        }

        $ins = $this->db->prepare("INSERT INTO user (email, nama, password, role) VALUES (?, ?, ?, 'siswa')");
        $ins->execute([$email, $nama, password_hash($password, PASSWORD_DEFAULT)]);
        return ['ok' => true, 'error' => ''];
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT password FROM user WHERE id = ? AND status = 1 FOR UPDATE');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                $this->db->rollBack();
                return false;
            }

            $update = $this->db->prepare('UPDATE user SET password = ? WHERE id = ?');
            $update->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function getProfile(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.nama, u.email,
                    COALESCE(p.bahasa, 'Indonesia') AS bahasa,
                    COALESCE(p.negara, 'Indonesia') AS negara,
                    COALESCE(p.provinsi, '') AS provinsi,
                    COALESCE(p.tujuan_karier, '') AS tujuan_karier,
                    p.foto_profil
             FROM user u
             LEFT JOIN profil_user p ON p.user_id = u.id
             WHERE u.id = ?"
        );
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();

        return $profile ?: null;
    }

    public function updateProfile(int $userId, string $nama, array $profile): bool
    {
        $this->db->beginTransaction();

        try {
            $cek = $this->db->prepare('SELECT id FROM user WHERE id = ? FOR UPDATE');
            $cek->execute([$userId]);
            if (!$cek->fetch()) {
                $this->db->rollBack();
                return false;
            }

            $updateUser = $this->db->prepare('UPDATE user SET nama = ? WHERE id = ?');
            $updateUser->execute([$nama, $userId]);

            $updateProfile = $this->db->prepare(
                'INSERT INTO profil_user (user_id, bahasa, negara, provinsi, tujuan_karier, foto_profil)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    bahasa = VALUES(bahasa),
                    negara = VALUES(negara),
                    provinsi = VALUES(provinsi),
                    tujuan_karier = VALUES(tujuan_karier),
                    foto_profil = COALESCE(VALUES(foto_profil), foto_profil)'
            );
            $updateProfile->execute([
                $userId,
                $profile['bahasa'],
                $profile['negara'],
                $profile['provinsi'],
                $profile['tujuan_karier'],
                $profile['foto_profil'] ?? null,
            ]);
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
