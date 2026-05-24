<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\CsrfTokenManager;
use App\Services\UserPasswordService;
use App\Support\Flash;
use App\Support\Request;
use App\Support\Response;
use App\Support\View;
use InvalidArgumentException;

final class ProfileController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly UserPasswordService $passwords,
        private readonly CsrfTokenManager $csrf,
        private readonly string $profilePath = '/student/profile',
        private readonly string $profileLabel = 'โปรไฟล์นักเรียน',
        private readonly string $profileDescription = 'เปลี่ยนรหัสผ่านของบัญชีให้ปลอดภัยขึ้น โดยข้อมูลหยดสารและ Rank จะไม่เปลี่ยนแปลง',
        private readonly string $buttonClass = 'btn-primary',
    ) {
    }

    public function show(): string
    {
        $user = $this->auth->currentUser();

        if (!$user) {
            Response::redirect('/login');
        }

        return View::render('profile/show', [
            'title' => $this->profileLabel,
            'description' => $this->profileDescription,
            'robots' => 'noindex,nofollow',
            'canonicalPath' => $this->profilePath,
            'user' => $user,
            'csrf' => $this->csrf,
            'profileLabel' => $this->profileLabel,
            'profileDescription' => $this->profileDescription,
            'profileVariant' => str_contains($this->profilePath, '/teacher') ? 'teacher' : 'student',
            'formAction' => $this->profilePath . '/password',
            'buttonClass' => $this->buttonClass,
        ]);
    }

    public function updatePassword(Request $request): never
    {
        $user = $this->auth->currentUser();

        if (!$user) {
            Response::redirect('/login');
        }

        try {
            $this->passwords->changePassword(
                $user,
                (string) $request->post('current_password', ''),
                (string) $request->post('new_password', ''),
                (string) $request->post('new_password_confirmation', ''),
            );

            Flash::put('success', 'เปลี่ยนรหัสผ่านเรียบร้อย');
        } catch (InvalidArgumentException $exception) {
            Flash::put('error', $exception->getMessage());
        } catch (\Throwable) {
            Flash::put('error', 'ไม่สามารถเปลี่ยนรหัสผ่านได้ กรุณาลองใหม่');
        }

        Response::redirect($this->profilePath);
    }
}
