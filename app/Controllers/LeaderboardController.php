<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\LeaderboardService;
use App\Support\Request;
use App\Support\View;
use Domain\Repositories\StudentRepository;

final class LeaderboardController
{
    public function __construct(
        private readonly LeaderboardService $leaderboard,
        private readonly StudentRepository $students,
    ) {
    }

    public function index(Request $request): string
    {
        $className = trim((string) $request->query('class', ''));

        return View::render('leaderboard/index', [
            'title' => 'อันดับหยดสาร',
            'seoTitle' => 'อันดับหยดสารของห้องเรียน | Chem Rank',
            'description' => 'ดูอันดับหยดสารของนักเรียนแบบอ่านง่าย รองรับคะแนนเท่ากัน และช่วยฉลองความก้าวหน้าของแต่ละคนอย่างอ่อนโยน',
            'canonicalPath' => '/leaderboard',
            'rankedStudents' => $this->leaderboard->ranked($className !== '' ? $className : null),
            'classNames' => $this->students->classNames(),
            'selectedClass' => $className,
        ]);
    }
}
