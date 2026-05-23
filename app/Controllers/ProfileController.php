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
    ) {
    }

    public function show(): string
    {
        $user = $this->auth->currentUser();

        if (!$user) {
            Response::redirect('/login');
        }

        return View::render('profile/show', [
            'title' => 'โปรไฟล์',
            'description' => 'เปลี่ยนรหัสผ่านสำหรับบัญชีนักเรียน Chem Rank',
            'robots' => 'noindex,nofollow',
            'canonicalPath' => '/student/profile',
            'user' => $user,
            'csrf' => $this->csrf,
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

        Response::redirect('/student/profile');
    }
}
