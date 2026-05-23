<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Request;
use App\Support\View;
use Domain\Rank\RankRegistry;

final class RankGuideController
{
    public function __construct(private readonly RankRegistry $ranks)
    {
    }

    public function index(Request $request): string
    {
        return View::render('ranks/guide', [
            'title' => 'แผนที่ Rank',
            'seoTitle' => 'แผนที่ Rank หยดสาร | Chem Rank',
            'description' => 'สำรวจระดับ Rank ทั้ง 7 ขั้นของ Chem Rank พร้อมช่วงหยดสาร ไอคอน และคำอธิบายสั้น ๆ สำหรับนักเรียนและครู',
            'canonicalPath' => '/ranks',
            'ranks' => $this->ranks->all(),
        ]);
    }
}
