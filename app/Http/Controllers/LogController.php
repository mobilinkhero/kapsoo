<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LogController extends Controller
{
    protected string $logFile;

    public function __construct()
    {
        $this->logFile = storage_path('logs/laravel.log');
    }

    public function index()
    {
        return view('logs.index');
    }

    public function data(Request $request)
    {
        $lines = $this->readLines((int) $request->get('lines', 300));

        return response()->json(['lines' => $lines, 'size' => $this->fileSize()]);
    }

    public function clear()
    {
        if (file_exists($this->logFile)) {
            file_put_contents($this->logFile, '');
        }

        return response()->json(['ok' => true]);
    }

    protected function readLines(int $limit): array
    {
        if (! file_exists($this->logFile)) {
            return [];
        }
        $content = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($content === false) {
            return [];
        }

        return array_slice($content, -$limit);
    }

    protected function fileSize(): int
    {
        return file_exists($this->logFile) ? filesize($this->logFile) : 0;
    }
}
