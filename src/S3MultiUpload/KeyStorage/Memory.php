<?php
declare(strict_types=1);

namespace S3MultiUpload\KeyStorage;

class Memory implements KeyStorageInterface
{
    public $storage = [];

    public function put(string $multipart_id, array $data): void
    {
        $this->storage[$multipart_id] = $data;
    }

    public function delete(string $multipart_id): void
    {
        if (isset($this->storage[$multipart_id])) {
            unset($this->storage[$multipart_id]);
        }
    }

    public function get(string $multipart_id): ?array
    {
        return $this->storage[$multipart_id] ?? null;
    }
}
