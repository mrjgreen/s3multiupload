<?php namespace S3MultiUpload\KeyStorage;


class Memory implements KeyStorageInterface {
	
	public $storage = array();
	
	public function put($multipart_id, $data){
		$this->storage[$multipart_id] = $data;
	}
	
	public function delete($multipart_id){
		if(isset($this->storage[$multipart_id])) unset($this->storage[$multipart_id]);
	}
	
	public function get($multipart_id){
		return isset($this->storage[$multipart_id]) ? $this->storage[$multipart_id] : false;
	}
	
}
