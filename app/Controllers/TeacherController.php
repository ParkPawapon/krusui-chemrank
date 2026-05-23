<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\CsrfTokenManager;
use App\Services\StudentExcelImportService;
use App\Services\StudentService;
use App\Support\Flash;
use App\Support\Request;
use App\Support\Response;
use App\Support\View;
use Domain\Repositories\AcademicYearRepository;
use Domain\Repositories\StudentRepository;
use Domain\Rank\RankRegistry;

final class TeacherController
{
    private const INITIAL_STUDENT_PASSWORD = '12345678';

    public function __construct(
        private readonly AuthService $auth,
        private readonly StudentRepository $students,
        private readonly StudentService $studentService,
        private readonly StudentExcelImportService $studentImportService,
        private readonly AcademicYearRepository $academicYears,
        private readonly RankRegistry $ranks,
        private readonly CsrfTokenManager $csrf,
    ) {
    }

    public function dashboard(Request $request): string
    {
        $className = trim((string) $request->query('class', ''));
        $search = trim((string) $request->query('q', ''));
        $activeAcademicYear = $this->academicYears->activeOrCreateDefault();

        return View::render('teacher/dashboard', [
            'title' => 'Teacher Dashboard',
            'description' => 'แดชบอร์ดครูสำหรับเพิ่มนักเรียน ปรับหยดสาร ดู Rank และตรวจภาพรวมของห้องเรียน',
            'robots' => 'noindex,nofollow',
            'canonicalPath' => '/teacher',
            'students' => $this->students->all($className !== '' ? $className : null, $search !== '' ? $search : null),
            'classNames' => $this->students->classNames(),
            'academicYears' => $this->academicYears->all(),
            'activeAcademicYear' => $activeAcademicYear,
            'selectedClass' => $className,
            'search' => $search,
            'ranks' => $this->ranks,
            'csrf' => $this->csrf,
        ]);
    }

    public function createStudent(Request $request): never
    {
        $user = $this->auth->currentUser();

        try {
            $this->studentService->createStudent(
                (string) $request->post('full_name', ''),
                (string) $request->post('student_number', ''),
                (string) $request->post('class_level', ''),
                (string) $request->post('room', ''),
                (int) $request->post('academic_year_id', 0),
                self::INITIAL_STUDENT_PASSWORD,
                (int) ($user?->id ?? 0)
            );
            Flash::put('success', 'เพิ่มนักเรียนเรียบร้อย');
        } catch (\InvalidArgumentException $exception) {
            Flash::put('error', $exception->getMessage());
        } catch (\Throwable) {
            Flash::put('error', 'ไม่สามารถเพิ่มนักเรียนได้ กรุณาลองใหม่');
        }

        Response::redirect('/teacher');
    }

    public function importStudents(Request $request): never
    {
        $user = $this->auth->currentUser();

        try {
            $result = $this->studentImportService->import(
                $request->file('student_file'),
                (int) $request->post('academic_year_id', 0),
                self::INITIAL_STUDENT_PASSWORD,
                (int) ($user?->id ?? 0)
            );

            if ($result->createdRows > 0) {
                Flash::put('success', 'นำเข้านักเรียนสำเร็จ ' . $result->createdRows . ' คน');
            }

            if ($result->failedRows > 0) {
                $message = 'มีรายการที่นำเข้าไม่ได้ ' . $result->failedRows . ' แถว';
                $details = implode(' | ', $result->errors);
                Flash::put('error', trim($message . ($details !== '' ? ': ' . $details : '') . ($result->hasMoreErrors ? ' | ยังมีข้อผิดพลาดเพิ่มเติม' : '')));
            }
        } catch (\InvalidArgumentException $exception) {
            Flash::put('error', $exception->getMessage());
        } catch (\Throwable) {
            Flash::put('error', 'ไม่สามารถนำเข้ารายชื่อนักเรียนได้ กรุณาลองใหม่');
        }

        Response::redirect('/teacher#teacher-import');
    }

    public function adjustDrops(Request $request): never
    {
        $user = $this->auth->currentUser();

        try {
            $result = $this->studentService->adjustDrops(
                (int) $request->post('student_id', 0),
                (int) ($user?->id ?? 0),
                (int) $request->post('amount', 0),
                (string) $request->post('type', 'add'),
                (string) $request->post('reason', '')
            );

            if ($request->expectsJson()) {
                Response::json([
                    'ok' => true,
                    'message' => (string) $request->post('type', 'add') === 'add' ? 'เพิ่มหยดสารเรียบร้อย' : 'ปรับคะแนนเรียบร้อย',
                    'rankChanged' => $result->rankChanged(),
                    'direction' => $result->direction(),
                    'student' => [
                        'id' => $result->student->id,
                        'drops' => $result->student->drops,
                    ],
                    'rank' => [
                        'level' => $result->newRank->level,
                        'thaiName' => $result->newRank->thaiName,
                        'englishSlug' => $result->newRank->englishSlug,
                        'assetPath' => $result->newRank->assetPath,
                        'themeColor' => $result->newRank->themeColor,
                    ],
                    'progress' => [
                        'percentToNext' => $result->progress->percentToNext,
                        'message' => $result->progress->message,
                        'dropsNeededForNext' => $result->progress->dropsNeededForNext,
                    ],
                ]);
            }

            Flash::put('success', $result->rankChanged() ? 'Rank เปลี่ยนเรียบร้อย' : 'ปรับหยดสารเรียบร้อย');
        } catch (\InvalidArgumentException $exception) {
            if ($request->expectsJson()) {
                Response::json(['ok' => false, 'message' => $exception->getMessage()], 422);
            }

            Flash::put('error', $exception->getMessage());
        } catch (\Throwable) {
            if ($request->expectsJson()) {
                Response::json(['ok' => false, 'message' => 'ไม่สามารถปรับหยดสารได้'], 500);
            }

            Flash::put('error', 'ไม่สามารถปรับหยดสารได้ กรุณาลองใหม่');
        }

        Response::redirect('/teacher');
    }

    public function deleteStudent(Request $request): never
    {
        $user = $this->auth->currentUser();

        try {
            $this->studentService->deleteStudent((int) $request->post('student_id', 0), (int) ($user?->id ?? 0));
            Flash::put('success', 'ลบนักเรียนเรียบร้อย');
        } catch (\Throwable) {
            Flash::put('error', 'ไม่สามารถลบนักเรียนได้');
        }

        Response::redirect('/teacher');
    }
}
