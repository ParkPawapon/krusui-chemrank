<?php

declare(strict_types=1);

namespace App\Services;

use Domain\Entities\User;
use Domain\Repositories\ActivityLogRepository;
use Domain\Repositories\UserRepository;
use Infrastructure\Security\PasswordHasher;
use InvalidArgumentException;

final class UserPasswordService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $passwords,
        private readonly ActivityLogRepository $activityLogs,
    ) {
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword, string $confirmation): void
    {
        $currentPassword = trim($currentPassword);
        $newPassword = trim($newPassword);
        $confirmation = trim($confirmation);

        if ($currentPassword === '' || $newPassword === '' || $confirmation === '') {
            throw new InvalidArgumentException('กรุณากรอกรหัสผ่านให้ครบถ้วน');
        }

        if (!$this->passwords->verify($currentPassword, $user->passwordHash)) {
            throw new InvalidArgumentException('รหัสผ่านเดิมไม่ถูกต้อง');
        }

        if (mb_strlen($newPassword) < 8) {
            throw new InvalidArgumentException('รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร');
        }

        if ($newPassword !== $confirmation) {
            throw new InvalidArgumentException('ยืนยันรหัสผ่านใหม่ไม่ตรงกัน');
        }

        if ($this->passwords->verify($newPassword, $user->passwordHash)) {
            throw new InvalidArgumentException('รหัสผ่านใหม่ต้องไม่ซ้ำกับรหัสผ่านเดิม');
        }

        $this->users->updatePasswordHash($user->id, $this->passwords->hash($newPassword));
        $this->activityLogs->record($user->id, 'password.changed', 'user', $user->id, [
            'role' => $user->role,
        ]);
    }
}
