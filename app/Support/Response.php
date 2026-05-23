<?php

declare(strict_types=1);

namespace App\Support;

final class Response
{
    public static function redirect(string $path): never
    {
        header('Location: ' . $path, true, 302);
        exit;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function abort(int $status = 404, string $message = 'ไม่พบหน้าที่ต้องการ'): never
    {
        http_response_code($status);
        echo \App\Support\View::render('errors/generic', [
            'title' => $status === 403 ? 'ไม่มีสิทธิ์เข้าถึง' : 'เกิดข้อผิดพลาด',
            'message' => $message,
            'robots' => 'noindex,nofollow',
        ]);
        exit;
    }
}
