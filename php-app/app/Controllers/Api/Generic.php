<?php
namespace App\Controllers\Api;

use App\Application\GenericSyncService;
use App\Controllers\BaseController;
use App\Infrastructure\File\GenericFileRepository;
use App\Infrastructure\Http\GenericJavaClient;

class Generic extends BaseController
{
    private GenericJavaClient     $java;
    private GenericFileRepository $files;

    public function __construct()
    {
        $this->java  = new GenericJavaClient();
        $this->files = new GenericFileRepository();
    }

    /** GET /api/{entity} */
    public function list(string $entity)
    {
        try {
            $svc  = new GenericSyncService($this->java, $this->files);
            $rows = $svc->sync($entity);
            return $this->response->setJSON($rows);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => $e->getMessage()]);
        }
    }

    /** GET /api/{entity}/{id} */
    public function show(string $entity, string $id)
    {
        try {
            return $this->response->setJSON($this->java->get($entity, $id) ?? []);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => $e->getMessage()]);
        }
    }

    /** POST /api/{entity} */
    public function create(string $entity)
    {
        try {
            $body   = $this->request->getJSON(true) ?? [];
            $saved  = $this->java->create($entity, $body);
            // refresh mirror
            (new GenericSyncService($this->java, $this->files))->sync($entity);
            return $this->response->setStatusCode(201)->setJSON($saved);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => $e->getMessage()]);
        }
    }

    /** PUT /api/{entity}/{id} */
    public function update(string $entity, string $id)
    {
        try {
            $body    = $this->request->getJSON(true) ?? [];
            $updated = $this->java->update($entity, $id, $body);
            (new GenericSyncService($this->java, $this->files))->sync($entity);
            return $this->response->setJSON($updated);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => $e->getMessage()]);
        }
    }

    /** DELETE /api/{entity}/{id} */
    public function delete(string $entity, string $id)
    {
        try {
            $res = $this->java->delete($entity, $id);
            (new GenericSyncService($this->java, $this->files))->sync($entity);
            return $this->response->setJSON($res);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => $e->getMessage()]);
        }
    }
}