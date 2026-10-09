<?php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Infrastructure\Config\EntityConfigReader;

class Schema extends BaseController
{
    /** GET /api/_entities */
    public function entities()
    {
        try {
            return $this->response->setJSON((new EntityConfigReader())->names());
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['error' => $e->getMessage()]);
        }
    }

    /** GET /api/_schema/{entity} */
    public function show(string $entity)
    {
        try {
            return $this->response->setJSON((new EntityConfigReader())->get($entity));
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(404)->setJSON(['error' => $e->getMessage()]);
        }
    }
}