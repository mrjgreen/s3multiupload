<?php
declare(strict_types=1);

namespace S3MultiUpload\KeyStorage;

interface KeyStorageInterface
{
    public function put(string $multipart_id, array $data): void;

    public function delete(string $multipart_id): void;

    public function get(string $multipart_id): ?array;
}
