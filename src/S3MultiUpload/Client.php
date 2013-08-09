<?php namespace S3MultiUpload;

class Client {

	public $s3;
	
	public $key_storage;
	
	public function __construct(Aws\S3\S3Client $s3, KeyStorageInterface $key_storage){
		$this->s3 = $s3;
		$this->key_storage = $key_storage;
	}
	
	public function createMultipart($bucket, $key, $acl = \Aws\S3\Enum\CannedAcl::AUTHENTICATED_READ) {
		
		$response = $this->s3->createMultipartUpload(array(
			'Bucket' => $bucket,
			'Key' => $key,
			'ACL' => $acl,
		));


		$multipart_id = $response->getPath('UploadId');

		$this->key_storage->put($multipart_id,array($bucket,$key));

		return $multipart_id;
	}

	public function signMultipart($multipart_id, $chunk, $chunksize, $headers = array()) {

		if (!list($bucket,$key) = $this->key_storage->get($multipart_id)) {
			throw new S3KeyStorageKeyNotFoundException('There is no upload in progress for key "' . $multipart_id . '"');
		}

		$command = $this->s3->getCommand('UploadPart', array(
			'Bucket' => $bucket,
			'Key' => $key,
			'UploadId' => $multipart_id,
			'Body' => '',
			'PartNumber' => (string) $chunk + 1,
			'ContentLength' => $chunksize,
			'command.headers' => $headers
		));

		return array(
			'url' => $command->createPresignedUrl('+10 minutes'),
			'uploadId' => $multipart_id,
			'key' => $key,
			'bucket' => $bucket
		);
	}

	private function listParts($fileoptions) {

		$parts = $this->s3->listParts($fileoptions);

		$fileoptions['PartNumberMarker'] = $parts['NextPartNumberMarker'];

		return $parts['IsTruncated'] ? array_merge($parts['Parts'], $this->listParts($fileoptions)) : $parts['Parts'];
	}

	public function completeMultipart($multipart_id) {

		if(!list($bucket,$key) = $this->key_storage->get($multipart_id)){
			throw new S3KeyStorageKeyNotFoundException('There is no upload in progress for key "' . $multipart_id . '"');
		}
		
		$parts = $this->listParts(array(
			'Bucket' => $bucket,
			'Key' => $key,
			'UploadId' => $multipart_id,
		));

		try {
			
			$response = $this->s3->completeMultipartUpload(array(
				'Bucket' => $bucket,
				'Key' => $key,
				'UploadId' => $multipart_id,
				'Parts' => $parts
			));
			
		} catch (\Exception $e) {

			$this->abortMultipartUpload($multipart_id);

			$this->key_storage->forget($multipart_id);
			
			throw $e;
		}

		return $response;
	}

	public function abortMultipartUpload($multipart_id) {
		
		if(!list($bucket,$key) = $this->key_storage->get($multipart_id)){
			throw new S3KeyStorageKeyNotFoundException('There is no upload in progress for key "' . $multipart_id . '"');
		}
		
		$this->s3->abortMultipartUpload(array(
			'Bucket' => $bucket,
			'Key' => $key,
			'UploadId' => $multipart_id
		));
	}

}