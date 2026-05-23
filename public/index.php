<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\LandingController;
use App\Controllers\LeaderboardController;
use App\Controllers\RankGuideController;
use App\Controllers\StudentController;
use App\Controllers\TeacherController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\CsrfTokenManager;
use App\Services\LeaderboardService;
use App\Services\StudentExcelImportService;
use App\Services\StudentService;
use App\Services\XlsxStudentImportReader;
use App\Support\Request;
use App\Support\Response;
use App\Support\Router;
use App\Support\View;
use Domain\Rank\RankRegistry;
use Domain\ValueObjects\Role;
use Infrastructure\Database\DatabaseConnection;
use Infrastructure\Persistence\PdoAcademicYearRepository;
use Infrastructure\Persistence\PdoActivityLogRepository;
use Infrastructure\Persistence\PdoDropTransactionRepository;
use Infrastructure\Persistence\PdoStudentRepository;
use Infrastructure\Persistence\PdoUserRepository;
use Infrastructure\Security\LoginRateLimiter;
use Infrastructure\Security\PasswordHasher;
use Infrastructure\Security\SecurityHeaders;
use Infrastructure\Security\SessionManager;

require dirname(__DIR__) . '/bootstrap/app.php';

date_default_timezone_set((string) config('app.timezone', 'Asia/Bangkok'));
ini_set('display_errors', config('app.debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', storage_path('logs/php-error.log'));

(new SecurityHeaders())->send();

$request = new Request();

$robotsHandler = static function (): string {
    header('Content-Type: text/plain; charset=UTF-8');

    return implode("\n", [
        'User-agent: *',
        'Allow: /',
        'Disallow: /login',
        'Disallow: /student',
        'Disallow: /teacher',
        'Sitemap: ' . absolute_url('/sitemap.xml'),
        '',
    ]);
};

$sitemapHandler = static function (): string {
    header('Content-Type: application/xml; charset=UTF-8');

    $updatedAt = date('Y-m-d');
    $urls = [
        ['loc' => absolute_url('/'), 'priority' => '1.0'],
        ['loc' => absolute_url('/ranks'), 'priority' => '0.8'],
        ['loc' => absolute_url('/leaderboard'), 'priority' => '0.7'],
    ];

    $sitemap = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $sitemap .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

    foreach ($urls as $url) {
        $sitemap .= "  <url>\n";
        $sitemap .= '    <loc>' . e($url['loc']) . "</loc>\n";
        $sitemap .= '    <lastmod>' . e($updatedAt) . "</lastmod>\n";
        $sitemap .= "    <changefreq>weekly</changefreq>\n";
        $sitemap .= '    <priority>' . e($url['priority']) . "</priority>\n";
        $sitemap .= "  </url>\n";
    }

    return $sitemap . "</urlset>\n";
};

if (in_array($request->method(), ['GET', 'HEAD'], true)) {
    $crawlerResponse = match ($request->path()) {
        '/robots.txt' => $robotsHandler(),
        '/sitemap.xml' => $sitemapHandler(),
        default => null,
    };

    if ($crawlerResponse !== null) {
        if ($request->method() !== 'HEAD') {
            echo $crawlerResponse;
        }

        exit;
    }
}

$sessionManager = new SessionManager();
$sessionManager->start();

$database = new DatabaseConnection();
$pdo = $database->pdo();

$users = new PdoUserRepository($pdo);
$students = new PdoStudentRepository($pdo);
$transactions = new PdoDropTransactionRepository($pdo);
$academicYears = new PdoAcademicYearRepository($pdo);
$activityLogs = new PdoActivityLogRepository($pdo);
$passwords = new PasswordHasher();
$rateLimiter = new LoginRateLimiter();
$csrf = new CsrfTokenManager();
$ranks = new RankRegistry();
$auth = new AuthService($users, $passwords, $rateLimiter, $sessionManager);
$academicYears->activeOrCreateDefault();
$studentService = new StudentService($pdo, $students, $users, $transactions, $academicYears, $activityLogs, $passwords, $ranks);
$studentImportService = new StudentExcelImportService($studentService, new XlsxStudentImportReader());
$leaderboard = new LeaderboardService($students, $ranks);
$authMiddleware = new AuthMiddleware($auth);

$router = new Router();

$router->get('/robots.txt', $robotsHandler);
$router->get('/sitemap.xml', $sitemapHandler);

$router->get('/', new LandingController($ranks));
$router->get('/login', [new AuthController($auth, $csrf, $ranks), 'form']);
$router->post('/login', [new AuthController($auth, $csrf, $ranks), 'login']);
$router->post('/logout', [new AuthController($auth, $csrf, $ranks), 'logout']);

$router->get('/student', function () use ($authMiddleware, $auth, $students, $ranks): string {
    $authMiddleware->requireRole(Role::STUDENT);
    return (new StudentController($auth, $students, $ranks))->show();
});

$router->get('/teacher', function (Request $request) use ($authMiddleware, $auth, $students, $studentService, $studentImportService, $academicYears, $ranks, $csrf): string {
    $authMiddleware->requireRole(Role::TEACHER);
    return (new TeacherController($auth, $students, $studentService, $studentImportService, $academicYears, $ranks, $csrf))->dashboard($request);
});

$router->post('/teacher/students', function (Request $request) use ($authMiddleware, $auth, $students, $studentService, $studentImportService, $academicYears, $ranks, $csrf): never {
    $authMiddleware->requireRole(Role::TEACHER);
    (new TeacherController($auth, $students, $studentService, $studentImportService, $academicYears, $ranks, $csrf))->createStudent($request);
});

$router->post('/teacher/students/import', function (Request $request) use ($authMiddleware, $auth, $students, $studentService, $studentImportService, $academicYears, $ranks, $csrf): never {
    $authMiddleware->requireRole(Role::TEACHER);
    (new TeacherController($auth, $students, $studentService, $studentImportService, $academicYears, $ranks, $csrf))->importStudents($request);
});

$router->post('/teacher/students/import-preview', function (Request $request) use ($authMiddleware, $auth, $students, $studentService, $studentImportService, $academicYears, $ranks, $csrf): never {
    $authMiddleware->requireRole(Role::TEACHER);
    (new TeacherController($auth, $students, $studentService, $studentImportService, $academicYears, $ranks, $csrf))->previewImport($request);
});

$router->post('/teacher/drops', function (Request $request) use ($authMiddleware, $auth, $students, $studentService, $studentImportService, $academicYears, $ranks, $csrf): never {
    $authMiddleware->requireRole(Role::TEACHER);
    (new TeacherController($auth, $students, $studentService, $studentImportService, $academicYears, $ranks, $csrf))->adjustDrops($request);
});

$router->post('/teacher/students/delete', function (Request $request) use ($authMiddleware, $auth, $students, $studentService, $studentImportService, $academicYears, $ranks, $csrf): never {
    $authMiddleware->requireRole(Role::TEACHER);
    (new TeacherController($auth, $students, $studentService, $studentImportService, $academicYears, $ranks, $csrf))->deleteStudent($request);
});

$router->get('/leaderboard', function (Request $request) use ($leaderboard, $students): string {
    return (new LeaderboardController($leaderboard, $students))->index($request);
});

$router->get('/ranks', [new RankGuideController($ranks), 'index']);

try {
    (new CsrfMiddleware($csrf))->handle($request);
    $response = $router->dispatch($request);

    if ($request->method() !== 'HEAD') {
        echo $response;
    }
} catch (Throwable $exception) {
    error_log(sprintf(
        "[%s] %s in %s:%d\n%s\n",
        date(DATE_ATOM),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    ));

    if (config('app.debug')) {
        throw $exception;
    }

    http_response_code(500);
    echo View::render('errors/generic', [
        'title' => 'เกิดข้อผิดพลาด',
        'message' => 'ระบบไม่สามารถทำงานตามคำขอได้ในขณะนี้',
        'robots' => 'noindex,nofollow',
    ]);
}
