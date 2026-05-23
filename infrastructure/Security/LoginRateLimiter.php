<?php

declare(strict_types=1);

namespace Infrastructure\Security;

final class LoginRateLimiter
{
    public function tooManyAttempts(string $ip, string $username): bool
    {
        $record = $this->read($ip, $username);

        return $record['count'] >= (int) config('security.login_attempts')
            && $record['expires_at'] > time();
    }

    public function hit(string $ip, string $username): void
    {
        $record = $this->read($ip, $username);
        $now = time();

        if ($record['expires_at'] <= $now) {
            $record = ['count' => 0, 'expires_at' => $now + (int) config('security.login_decay_seconds')];
        }

        $record['count']++;
        $record['expires_at'] = $now + (int) config('security.login_decay_seconds');

        $this->write($ip, $username, $record);
    }

    public function clear(string $ip, string $username): void
    {
        $file = $this->file($ip, $username);

        if (is_file($file)) {
            unlink($file);
        }
    }

    /**
     * @return array{count:int,expires_at:int}
     */
    private function read(string $ip, string $username): array
    {
        $file = $this->file($ip, $username);

        if (!is_file($file)) {
            return ['count' => 0, 'expires_at' => 0];
        }

        $data = json_decode((string) file_get_contents($file), true);

        if (!is_array($data)) {
            return ['count' => 0, 'expires_at' => 0];
        }

        return [
            'count' => (int) ($data['count'] ?? 0),
            'expires_at' => (int) ($data['expires_at'] ?? 0),
        ];
    }

    /**
     * @param array{count:int,expires_at:int} $record
     */
    private function write(string $ip, string $username, array $record): void
    {
        $dir = storage_path('rate-limit');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->file($ip, $username), json_encode($record, JSON_THROW_ON_ERROR));
    }

    private function file(string $ip, string $username): string
    {
        return storage_path('rate-limit/' . hash('sha256', $ip . '|' . mb_strtolower($username)) . '.json');
    }
}

