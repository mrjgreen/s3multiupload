<?php

namespace S3MultiUpload\KeyStorage;

class Callback implements KeyStorageInterface
{
    public $put;

    public $get;

    public $delete;

    public function __construct(\Closure $put, \Closure $get, \Closure $delete)
    {
        $this->get = $get;

        $this->put = $put;

        $this->delete = $delete;
    }

    public function put($multipart_id, $data)
    {
        call_user_func($this->put, $multipart_id, $data);
    }

    public function delete($multipart_id)
    {
        call_user_func($this->delete, $multipart_id);
    }

    public function get($multipart_id)
    {
        call_user_func($this->get, $multipart_id);
    }
}
