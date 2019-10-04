<?php
declare(strict_types=1);

namespace S3MultiUpload\KeyStorage;

class Callback implements KeyStorageInterface
{
    public $put;

    public $get;

    public $delete;

    public function __construct(callable $put, callable $get, callable $delete)
    {
        $this->get = $get;
        $this->put = $put;
        $this->delete = $delete;
    }

    public function put(string $multipart_id, array $data): void
    {
        call_user_func($this->put, $multipart_id, $data);
    }

    public function delete(string $multipart_id): void
    {
        call_user_func($this->delete, $multipart_id);
    }

    public function get(string $multipart_id): ?array
    {
        call_user_func($this->get, $multipart_id);
    }
}
