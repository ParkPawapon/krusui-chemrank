<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$tests = [
    'RankRegistryTest.php',
    'StudentServiceTest.php',
    'XlsxStudentImportReaderTest.php',
];

$failures = 0;

foreach ($tests as $test) {
    try {
        require __DIR__ . '/' . $test;
        echo "PASS {$test}\n";
    } catch (Throwable $exception) {
        $failures++;
        echo "FAIL {$test}: {$exception->getMessage()}\n";
    }
}

if ($failures > 0) {
    exit(1);
}

function assert_same(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message !== '' ? $message : 'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
