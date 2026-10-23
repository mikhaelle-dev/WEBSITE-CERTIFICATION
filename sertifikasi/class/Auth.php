<?php
require_once __DIR__ . '/Database.php';

class Auth extends Database
{
    /**
     * @return array ['ok' => bool, 'user' => array|null, 'error' => string]
     */
    public function login(string $email, string $password): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.email, u.nama, u.password, u.role, u.status, p.foto_profil
             FROM user u
             LEFT JOIN profil_user p ON p.user_id = u.id
             WHERE u.email = ?'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Pesan sengaja sama untuk "email tidak ada" dan "password salah"
        if (!$user || !password_verify($password, $user['password'])) {
            return ['ok' => false, 'user' => null, 'error' => 'Email atau password salah.'];
        }
        if ((int) $user['status'] !== 1) {
            return ['ok' => false, 'user' => null, 'error' => 'Akun kamu dinonaktifkan. Hubungi admin.'];
        }

        unset($user['password']);
        return ['ok' => true, 'user' => $user, 'error' => ''];
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
