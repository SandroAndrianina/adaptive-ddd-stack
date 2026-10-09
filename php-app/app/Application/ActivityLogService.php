<?php
namespace App\Application;

use App\Infrastructure\File\FileActivityLog;

class ActivityLogService
{
    public function __construct(private FileActivityLog $log) {}

    public function write(string $message): void
    {
        $this->log->append($message);
    }
}