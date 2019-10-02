<?php

namespace S3MultiUpload\KeyStorage;

class NativeSession implements KeyStorageInterface
{
    public function __construct()
    {
        $this->sessionStatus() or session_start();
    }

    public function sessionStatus()
    {
        if (function_exists('session_status')) {
            return PHP_SESSION_ACTIVE === session_status();
        }

        return '' != session_id();
    }

    public function put($multipart_id, $data)
    {
        $_SESSION[$multipart_id] = $data;
    }

    public function delete($multipart_id)
    {
        if (isset($_SESSION[$multipart_id])) {
            unset($_SESSION[$multipart_id]);
        }
    }

    public function get($multipart_id)
    {
        return isset($_SESSION[$multipart_id]) ? $_SESSION[$multipart_id] : false;
    }
}
