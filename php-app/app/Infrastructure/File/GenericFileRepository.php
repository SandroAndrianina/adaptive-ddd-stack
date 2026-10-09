<?php
namespace App\Infrastructure\File;

/**
 * Mirrors any entity's rows to writable/data/{entity}.json.
 */
class GenericFileRepository
{
    private function path(string $entity): string
    {
        return WRITEPATH . "data/$entity.json";
    }

    /** @param array<int,array<string,mixed>> $rows */
    public function saveAll(string $entity, array $rows): void
    {
        file_put_contents(
            $this->path($entity),
            json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function findAll(string $entity): array
    {
        $p = $this->path($entity);
        if (!is_file($p)) return [];
        return json_decode(file_get_contents($p), true) ?: [];
    }
}