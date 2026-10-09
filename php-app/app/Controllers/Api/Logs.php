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
            $p       = $this->request->getJSON(true) ?? [];
            $entity  = (string)($p['entity']  ?? '');
            $action  = (string)($p['action']  ?? '');
            $id      = (string)($p['id']      ?? '');

            if ($entity === '' || $action === '') {
                return $this->response->setStatusCode(400)
                    ->setJSON(['error' => 'entity and action are required']);
            }

            $line = trim("$entity $action #$id");
            (new ActivityLogService(new FileActivityLog()))->write($line);

            return $this->response->setJSON(['status' => 'ok']);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)
                ->setJSON(['error' => $e->getMessage()]);
        }
    }
}