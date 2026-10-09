<?php
namespace App\Controllers\Api;

use App\Application\ActivityLogService;
use App\Controllers\BaseController;
use App\Infrastructure\File\FileActivityLog;

class Logs extends BaseController
{
    public function create()
    {
        try {
            $payload = $this->request->getJSON(true) ?? [];
            $message = trim((string)($payload['message'] ?? ''));

            if ($message === '') {
                return $this->response->setStatusCode(400)
                    ->setJSON(['error' => 'message is required']);
            }

            (new ActivityLogService(new FileActivityLog()))->write($message);

            return $this->response->setJSON(['status' => 'ok']);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)
                ->setJSON(['error' => $e->getMessage()]);
        }
    }
}