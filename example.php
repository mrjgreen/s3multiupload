<?php
include 'vendor/autoload.php';

$config = array(
	'key' => '',
	'secret' => '',
	'region' => 'eu-west-1',
	'bucket' => '',
);


$s3Client = \Aws\Common\Aws::factory(array(
	'key'    => $config['key'],
	'secret' => $config['secret'],
	'region' => $config['region']
))->get('s3');

$s3 = new S3MultiUpload\Client($s3Client, new S3MultiUpload\KeyStorage\NativeSession);

switch ($_REQUEST['action']) {
	
	case 'sign' :
		
		$multipart_id = !empty($_REQUEST['uploadId']) ? 
			
			$_REQUEST['uploadId'] : 

			$s3->createMultipart($config['bucket'], uniqid());
		
		// We need to set the content type as this header is used to form the signed request - we have to make sure that whatever we send with the data matches this
		// This should always be 'application/octet-stream' for chunked uploads with plupload
		echo json_encode($s3->signMultipart($multipart_id, $_REQUEST['chunk'], $_REQUEST['size'], array('Content-Type' => 'application/octet-stream')));
		break;
	
	case 'complete' :
		
		echo json_encode($s3->completeMultipart($_REQUEST['uploadId']));
		break;
		
}