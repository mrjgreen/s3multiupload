<?php namespace S3MultiUpload;


class S3KeyStorage implements KeyStorageInterface {
	
	public function put($multipart_id, $data){
		$_SESSION[$multipart_id] = $data;
	}
	
	public function delete($multipart_id){
		if(isset($_SESSION[$multipart_id])) unset($_SESSION[$multipart_id]);
	}
	
	public function get($multipart_id){
		return isset($_SESSION[$multipart_id]) ? $_SESSION[$multipart_id] : false;
	}
	
}
