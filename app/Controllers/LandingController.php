<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Request;
use App\Support\View;
use Domain\Rank\RankRegistry;

final class LandingController
{
    public function __construct(private readonly RankRegistry $ranks)
    {
    }

    public function __invoke(Request $request): string
    {
        return View::render('landing', [
            'title' => 'เก็บหยดสาร',
            'seoTitle' => 'เก็บหยดสาร เห็นความก้าวหน้าชัดขึ้น | Chem Rank',
            'description' => 'ระบบสะสมหยดสารสำหรับห้องเรียนเคมี นักเรียนเห็น Rank และเป้าหมายถัดไป ส่วนครูปรับหยดสารและดูภาพรวมของห้องได้รวดเร็ว',
            'canonicalPath' => '/',
            'ranks' => $this->ranks->all(),
        ]);
    }
}
