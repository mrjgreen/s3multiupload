<?php
declare(strict_types=1);

include 'vendor/autoload.php';

$config = [
    'key' => '',
    'secret' => '',
    'region' => 'eu-west-1',
    'bucket' => '',
];

$s3Client = new Aws\S3\S3Client([
    'credentials' => [
        'key'    => $config['key'],
        'secret' => $config['secret'],
    ],
    'region' => $config['region'],
    'version' => '2006-03-01',
]);

$s3 = new S3MultiUpload\Client($s3Client, new S3MultiUpload\KeyStorage\NativeSession());

$action = $_REQUEST['action']
    ?? $_ENV['action']
    ?? $_SERVER['action']
    ?? null;

switch ($action) {
    case 'sign':
        $chunk = $_REQUEST['chunk']
            ?? $_ENV['chunk']
            ?? $_SERVER['chunk']
            ?? null;
        $size = $_REQUEST['size']
            ?? $_ENV['size']
            ?? $_SERVER['size']
            ?? null;

        if (!isset($chunk) || !isset($size)) {
            exit('action=sign needs chunk and size arguments.' . PHP_EOL);
        }

        $multipart_id = $_REQUEST['uploadId']
            ?? $_ENV['uploadId']
            ?? $_SERVER['uploadId']
            ?? $s3->createMultipart($config['bucket'], uniqid());

        // We need to set the content type as this header is used to form the signed request - we have to make sure that whatever we send with the data matches this
        // This should always be 'application/octet-stream' for chunked uploads with plupload
        echo json_encode($s3->signMultipart($multipart_id, (int)$chunk, [
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => $size,
        ]));
        break;

    case 'complete':
        $multipart_id = $_REQUEST['uploadId'] ?? $_ENV['uploadId'] ?? $_SERVER['uploadId'] ?? null;

        if (!is_string($multipart_id) || $multipart_id === '') {
            exit('action=complete requires the uploadId argument.' . PHP_EOL);
        }

        echo json_encode($s3->completeMultipart($multipart_id));
        break;

    default:
        exit('action must be "sign" or "complete".' . PHP_EOL);
}
