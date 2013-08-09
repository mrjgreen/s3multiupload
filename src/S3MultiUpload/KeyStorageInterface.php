<?php namespace S3MultiUpload;

class S3KeyStorageKeyNotFoundException extends Exception{}

interface KeyStorageInterface {
	
	public function put($multipart_id, $data);
	
	public function delete($multipart_id);
	
	public function get($multipart_id);
	
}
