<?php
declare(strict_types=1);

class RateLimiter
{
    private const MAX_FAILURES = 3;
    private const LOCKOUT_SECONDS = 10;
    private const STORAGE_DIR = __DIR__ . '/../temp';

    private static function filePath(string $ip): string
    {
        return self::STORAGE_DIR . '/ratelimit_' . md5($ip) . '.json';
    }

    public static function remainingLockout(string $ip, ?int $now = null): int
    {
        $now ??= time();

        return self::withState($ip, static function (array &$state) use ($now): int {
            $remaining = max(0, (int) ($state['blocked_until'] ?? 0) - $now);
            if ($remaining === 0 && !empty($state['blocked_until'])) {
                $state = ['failures' => 0, 'blocked_until' => 0];
            }

            return $remaining;
        });
    }

    public static function recordFailure(string $ip, ?int $now = null): void
    {
        $now ??= time();

        self::withState($ip, static function (array &$state) use ($now): void {
            if ((int) ($state['blocked_until'] ?? 0) > $now) {
                return;
            }

            $state['failures'] = (int) ($state['failures'] ?? 0) + 1;
            if ($state['failures'] >= self::MAX_FAILURES) {
                $state['blocked_until'] = $now + self::LOCKOUT_SECONDS;
            }
        });
    }

    public static function clear(string $ip): void
    {
        self::withState($ip, static function (array &$state): void {
            $state = ['failures' => 0, 'blocked_until' => 0];
        });
    }

    private static function withState(string $ip, callable $callback): mixed
    {
        if (!is_dir(self::STORAGE_DIR) && !mkdir(self::STORAGE_DIR, 0777, true) && !is_dir(self::STORAGE_DIR)) {
            throw new RuntimeException('Unable to create login rate-limit storage directory.');
        }

        $path = self::filePath($ip);
        $handle = fopen($path, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Unable to open login rate-limit state.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to lock login rate-limit state.');
            }

            rewind($handle);
            $contents = stream_get_contents($handle);
            if ($contents === false) {
                throw new RuntimeException('Unable to read login rate-limit state.');
            }

            $state = ['failures' => 0, 'blocked_until' => 0];
            if ($contents !== '') {
                $decoded = json_decode($contents, true);
                if (is_array($decoded)) {
                    $state = [
                        'failures' => max(0, (int) ($decoded['failures'] ?? 0)),
                        'blocked_until' => max(0, (int) ($decoded['blocked_until'] ?? 0)),
                    ];
                } else {
                    error_log('Resetting legacy or invalid login rate-limit state.');
                }
            }

            $result = $callback($state);
            $encoded = json_encode($state, JSON_THROW_ON_ERROR);
            rewind($handle);
            if (!ftruncate($handle, 0) || fwrite($handle, $encoded) === false || !fflush($handle)) {
                throw new RuntimeException('Unable to persist login rate-limit state.');
            }

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
