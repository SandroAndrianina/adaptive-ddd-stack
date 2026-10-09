<?php
namespace App\Infrastructure\Config;

/**
 * Reads /etc/adaptive/entities.json (mounted read-only).
 */
class EntityConfigReader
{
    private const PATH = '/etc/adaptive/entities.json';

    public function all(): array
    {
        $raw = @file_get_contents(self::PATH);
        if ($raw === false) throw new \RuntimeException('entities.json not mounted');
        $json = json_decode($raw, true);
        return $json['entities'] ?? [];
    }

    /** @return array<string,mixed> */
    public function get(string $name): array
    {
        $all = $this->all();
        if (!isset($all[$name])) {
            throw new \RuntimeException("Unknown entity: $name");
        }
        return $all[$name];
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->all());
    }
}