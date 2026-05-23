<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\CsrfTokenManager;
use App\Support\Flash;
use App\Support\Request;
use App\Support\Response;
use App\Support\View;
use Domain\Rank\RankRegistry;
use Domain\ValueObjects\Role;

final class AuthController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly CsrfTokenManager $csrf,
        private readonly RankRegistry $ranks,
    ) {
    }

    public function form(Request $request): string
    {
        $role = (string) $request->query('role', Role::STUDENT);
        $role = Role::isValid($role) ? $role : Role::STUDENT;
        $allRanks = $this->ranks->all();

        return View::render('auth/login', [
            'title' => $role === Role::TEACHER ? 'ครูเข้าสู่ระบบ' : 'นักเรียนเข้าสู่ระบบ',
            'description' => $role === Role::TEACHER
                ? 'เข้าสู่ระบบสำหรับครูเพื่อจัดการหยดสารและดูภาพรวมนักเรียนใน Chem Rank'
                : 'เข้าสู่ระบบสำหรับนักเรียนเพื่อดูหยดสาร Rank ปัจจุบัน และเป้าหมายถัดไปใน Chem Rank',
            'robots' => 'noindex,follow',
            'canonicalPath' => '/login',
            'role' => $role,
            'csrf' => $this->csrf,
            'loginRank' => $role === Role::TEACHER
                ? $allRanks[count($allRanks) - 1]
                : $allRanks[0],
        ]);
    }

    public function login(Request $request): never
    {
        $role = (string) $request->post('role', Role::STUDENT);
        $role = Role::isValid($role) ? $role : Role::STUDENT;
        $loginIdentifier = (string) $request->post('login_identifier', $request->post('username', ''));
        $user = $this->auth->attempt(
            $loginIdentifier,
            (string) $request->post('password', ''),
            $request->ip(),
            $role
        );

        if (!$user) {
            Flash::put('error', $role === Role::STUDENT ? 'เลขประจำตัวนักเรียนหรือรหัสผ่านไม่ถูกต้อง' : 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
            Response::redirect('/login?role=' . $role);
        }

        Response::redirect($user->role === Role::TEACHER ? '/teacher' : '/student');
    }

    public function logout(Request $request): never
    {
        $this->auth->logout();
        Response::redirect('/');
    }
}
