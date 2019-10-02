<?php

declare(strict_types=1);

namespace S3MultiUpload;

use Aws\S3\S3Client;
use Guzzle\Service\Resource\Model;
use S3MultiUpload\KeyStorage\KeyStorageInterface;
use S3MultiUpload\KeyStorage\Exception\KeyNotFoundException;

class Client
{
    /** @var S3Client */
    public $s3;

    /** @var KeyStorageInterface */
    public $key_storage;

    public function __construct(S3Client $s3, KeyStorageInterface $key_storage)
    {
        $this->s3 = $s3;
        $this->key_storage = $key_storage;
    }

    /**
     * Send a request to Amazon S3 to initiate a multipart upload.
     *
     * @param string $bucket
     * @param string $key
     * @param array  $options
     *
     * @return string
     */
    public function createMultipart(string $bucket, string $key, array $options = []): string
    {
        $response = $this->s3->createMultipartUpload($options + [
            'Bucket' => $bucket,
            'Key' => $key,
        ]);

        $multipart_id = $response->getPath('UploadId');

        $this->key_storage->put($multipart_id, [$bucket, $key]);

        return $multipart_id;
    }

    /**
     * @param string $multipart_id
     * @param int    $chunk        The current chunk number
     * @param array  $headers
     *
     * @return array An array containing the presigned url to "PUT" the data to, the uploadId
     *
     * @throws KeyNotFoundException
     */
    public function signMultipart(string $multipart_id, int $chunk, array $headers = []): array
    {
        if (!list($bucket, $key) = $this->key_storage->get($multipart_id)) {
            throw new KeyNotFoundException('There is no upload in progress for key "'.$multipart_id.'"');
        }

        $command = $this->s3->getCommand('UploadPart', [
            'Bucket' => $bucket,
            'Key' => $key,
            'UploadId' => $multipart_id,
            'Body' => '',
            'PartNumber' => $chunk + 1,
            'command.headers' => $headers,
        ]);

        return [
            'url' => $command->createPresignedUrl('+10 minutes'),
            'uploadId' => $multipart_id,
        ];
    }

    /**
     * Calling `$this->s3->listParts($fileoptions);` is okay in general unless there are more than 1000 parts - amazon will only send back the manifest for 1000 parts at a time
     * With a chuink size of 5mb the file only needs to be >5GB for this to happen.
     * We call the function recursively to fetch the next set of parts until we have them all.
     *
     * @param array $fileoptions
     *
     * @return array
     */
    private function listParts(array $fileoptions): ?array
    {
        $parts = $this->s3->listParts($fileoptions);

        $fileoptions['PartNumberMarker'] = $parts['NextPartNumberMarker'];

        return $parts['IsTruncated'] ? array_merge($parts['Parts'], $this->listParts($fileoptions)) : $parts['Parts'];
    }

    /**
     * @param string $multipart_id
     *
     * @return mixed The Amazon S3 response
     *
     * @throws KeyNotFoundException
     * @throws \S3MultiUpload\Exception
     */
    public function completeMultipart(string $multipart_id): Model
    {
        if (!list($bucket, $key) = $this->key_storage->get($multipart_id)) {
            throw new KeyNotFoundException('There is no upload in progress for key "'.$multipart_id.'"');
        }

        $parts = $this->listParts([
            'Bucket' => $bucket,
            'Key' => $key,
            'UploadId' => $multipart_id,
        ]);

        try {
            $response = $this->s3->completeMultipartUpload([
                'Bucket' => $bucket,
                'Key' => $key,
                'UploadId' => $multipart_id,
                'Parts' => $parts,
            ]);
        } catch (\Exception $e) {
            $this->abortMultipartUpload($multipart_id);

            throw $e;
        }

        return $response;
    }

    /**
     * Abort the multipart upload with amazon and remove the key from storage.
     *
     * @param string $multipart_id
     *
     * @throws KeyNotFoundException
     */
    public function abortMultipartUpload(string $multipart_id): void
    {
        if (!list($bucket, $key) = $this->key_storage->get($multipart_id)) {
            throw new KeyNotFoundException('There is no upload in progress for key "'.$multipart_id.'"');
        }

        $this->s3->abortMultipartUpload([
            'Bucket' => $bucket,
            'Key' => $key,
            'UploadId' => $multipart_id,
        ]);

        $this->key_storage->delete($multipart_id);
    }
}
