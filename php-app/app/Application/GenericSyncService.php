<?php
namespace App\Application;

use App\Infrastructure\File\GenericFileRepository;
use App\Infrastructure\Http\GenericJavaClient;

/**
 * Flow A, generic: fetch entity list from Java, mirror to file.
 */
class GenericSyncService
{
    public function __construct(
        private GenericJavaClient     $java,
        private GenericFileRepository $files
    ) {}

    /** @return array<int,array<string,mixed>> */
    public function sync(string $entity): array
    {
        $rows = $this->java->list($entity);
        $this->files->saveAll($entity, $rows);
        return $rows;
    }
}