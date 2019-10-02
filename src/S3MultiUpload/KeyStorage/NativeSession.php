<?php
declare(strict_types=1);

namespace S3MultiUpload\KeyStorage;

class NativeSession implements KeyStorageInterface
{
    public function __construct()
    {
        $this->sessionStatus() or session_start();
    }

    private function sessionStatus(): bool
    {
        if (function_exists('session_status')) {
            return PHP_SESSION_ACTIVE === session_status();
        }

        return '' !== session_id();
    }

    public function put(string $multipart_id, array $data): void
    {
        $_SESSION[$multipart_id] = $data;
    }

    public function delete(string $multipart_id): void
    {
        if (isset($_SESSION[$multipart_id])) {
            unset($_SESSION[$multipart_id]);
        }
    }

    public function get(string $multipart_id): ?array
    {
        return $_SESSION[$multipart_id] ?? null;
    }
}
