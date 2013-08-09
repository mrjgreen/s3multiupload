<?php namespace S3MultiUpload\KeyStorage;

interface KeyStorageInterface {
	
	public function put($multipart_id, $data);
	
	public function delete($multipart_id);
	
	public function get($multipart_id);
	
}
