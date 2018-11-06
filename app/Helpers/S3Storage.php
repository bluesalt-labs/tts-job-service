<?php

namespace App\Helpers;

use Aws\S3\S3Client;

class S3Storage
{
    const S3_VERSION = 'latest';

    protected $s3;
    protected $bucket;

    /**
     * S3Storage constructor.
     */
    public function __construct() {
        $this->bucket = env('AWS_BUCKET');
        $this->s3 = static::getS3Client();
    }

    /**
     * @return S3Client
     */
    private static function getS3Client() {
        $credentials = [
            'version'   => static::S3_VERSION,
            'region'    => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'credentials' => [
                'key'         => env('AWS_ACCESS_KEY_ID'),
                'secret'      => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ];

        return new S3Client($credentials);
    }

    public function registerStreamWrapper() {
        return $this->s3->registerStreamWrapper();
    }

    public function exists($filepath) {
        return $this->s3->doesObjectExist( env('AWS_BUCKET'), $filepath);
    }

    public function getUrl($filepath) {
        return $this->s3->getObjectUrl($this->bucket, $filepath);
    }


    public function get($filepath) {
        $object = $this->s3->getObject([
            'Bucket'    => $this->bucket,
            'Key'       => $filepath,
        ]);

        // todo: see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#getobject
        return $object;
    }


    public function getBody($filepath) {
        $object = $this->get($filepath);

        if($object) {
            return $object['Body']->getContents();
        }

        return null;
    }

    /**
     *
     *
     * @param string $localPath
     * @param string $remotePath
     * @return \Aws\Result
     */
    public function putFile($localPath, $remotePath) {
        $object = $this->s3->putObject([
            'Bucket'        => $this->bucket,
            'SourceFile'    => $localPath,
            'Key'           => $remotePath,
        ]);

        return $object; // todo
    }

    /**
     *
     *
     * @param $remotePath
     * @param string | resource | \Psr\Http\Message\StreamInterface $fileData
     * @return \Aws\Result
     */
    public function put($remotePath, $fileData) {
        // todo
        /*
        $localImage = '/Users/jim/Photos/summer-vacation/DP00342654.jpg';
        $s3->putObject(array(
        'Bucket'     => 'my-uniquely-named-bucket',
        'SourceFile' => $localImage,
        'Key'        => 'photos/summer/' . basename($localImage)
         */

        $object = $this->s3->putObject([
            'Bucket'    => $this->bucket,
            'Body'      => $fileData,
            'Key'       => $remotePath,
        ]);

        return $object;
    }

    public function delete($remotePath) {
        $object = $this->s3->deleteObject([
            'Bucket'    => $this->bucket,
            'Key'       => $remotePath,
        ]);

        return !!$object['DeleteMarker'];
    }

}
