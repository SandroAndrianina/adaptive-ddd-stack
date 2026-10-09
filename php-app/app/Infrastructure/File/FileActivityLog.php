<?php
namespace App\Infrastructure\File;

/**
 * Appends one line per event to writable/data/activity.log.
 */
class FileActivityLog
{
    private string $path;

    public function __construct()
    {
        $this->path = WRITEPATH . 'data/activity.log';
    }

    public function append(string $message): void
    {
        $line = date('Y-m-d H:i:s') . ' | ' . $message . PHP_EOL;
        file_put_contents($this->path, $line, FILE_APPEND);
    }
}