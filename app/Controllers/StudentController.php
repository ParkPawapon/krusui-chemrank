<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Support\Response;
use App\Support\View;
use Domain\Repositories\StudentRepository;
use Domain\Rank\RankRegistry;

final class StudentController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly StudentRepository $students,
        private readonly RankRegistry $ranks,
    ) {
    }

    public function show(): string
    {
        $user = $this->auth->currentUser();
        $student = $user ? $this->students->findByUserId($user->id) : null;

        if (!$student) {
            Response::abort(404, 'ไม่พบข้อมูลนักเรียนของบัญชีนี้');
        }

        return View::render('student/show', [
            'title' => 'ห้องทดลองของฉัน',
            'description' => 'ห้องทดลองส่วนตัวสำหรับดูหยดสาร Rank ปัจจุบัน และเป้าหมายถัดไป',
            'robots' => 'noindex,nofollow',
            'canonicalPath' => '/student',
            'student' => $student,
            'rank' => $this->ranks->forDrops($student->drops),
            'progress' => $this->ranks->progress($student->drops),
            'ranks' => $this->ranks->all(),
        ]);
    }
}
