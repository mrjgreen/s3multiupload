s3multiupload
=============


Full example for uploading directly to https://github.com/joegreen0991/chunkedPluploadToS3Example

    // We need to give our class an instance of the S3Client
    $s3Client = \Aws\Common\Aws::factory(array(
      'key'    => S3_KEY,
    	'secret' => S3_SECRET,
    	'region' => S3_REGION
    ))->get('s3');
    
    // We also need to pass in a storage handler, so we can remember the multipart_id between requests - use native sessions, or role your own
    $keyStorage = new S3MultiUpload\KeyStorage\NativeSession;
    
    // Create our object to manage the signing server side - we generate the url and all the params, but hand it back to the client to send the actual data
    $s3 = new S3MultiUpload\Client($s3Client, $keyStorage);
    
    
    switch ($_REQUEST['action']) {
    
    	case 'sign' :
    
    		if(empty($_REQUEST['uploadId'])){
    			// This is a new upload
    
    			$filename = $_REQUEST['name']; // Using original file name, but you could use randomly generated names etc...
    
    			$multipart_id = $s3->createMultipart(S3_BUCKET, $filename);
    
    		}else{
    
    			$multipart_id = $_REQUEST['uploadId'];
    		}
    
    		die(json_encode($s3->signMultipart($multipart_id, $_REQUEST['chunk'], array('Content-Type' => 'application/octet-stream'))));
    
    	case 'complete' :
    
    		die(json_encode($s3->completeMultipart($_REQUEST['uploadId'])));
    
    }
