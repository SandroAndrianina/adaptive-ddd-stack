<?php
namespace App\Infrastructure\Http;

use Config\Services;

/**
 * Path-agnostic HTTP client to the Java service.
 * One method per HTTP verb; entity name is just a path segment.
 */
class GenericJavaClient
{
    private string $base;

    public function __construct()
    {
        $this->base = rtrim(env('JAVA_BASE_URL', 'http://java-service:8080'), '/');
    }

    /** @return array<int,array<string,mixed>> */
    public function list(string $entity): array
    {
        return $this->request('get', "/api/$entity");
    }

    /** @return array<string,mixed>|null */
    public function get(string $entity, $id): ?array
    {
        return $this->request('get', "/api/$entity/$id");
    }

    /** @return array<string,mixed> */
    public function create(string $entity, array $payload): array
    {
        return $this->request('post', "/api/$entity", $payload);
    }

    /** @return array<string,mixed> */
    public function update(string $entity, $id, array $payload): array
    {
        return $this->request('put', "/api/$entity/$id", $payload);
    }

    /** @return array<string,mixed> */
    public function delete(string $entity, $id): array
    {
        return $this->request('delete', "/api/$entity/$id");
    }

    private function request(string $verb, string $path, ?array $body = null): array
    {
        $verb    = strtoupper($verb);            // GET, POST, PUT, DELETE
        $client  = Services::curlrequest();
        $options = ['timeout' => 5, 'http_errors' => false];

        if ($body !== null) $options['json'] = $body;

        $res  = $client->request($verb, $this->base . $path, $options);
        $json = json_decode((string) $res->getBody(), true);

        if ($res->getStatusCode() >= 400) {
            $msg = is_array($json) && isset($json['error']) ? $json['error'] : 'HTTP ' . $res->getStatusCode();
            throw new \RuntimeException("Java $verb $path -> {$res->getStatusCode()}: $msg");
        }
        return $json ?? [];
    }
}