<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/6/18
 * Time: 3:34 PM
 */

define('S3_ACL_PRIVATE', 'private');


$s3clientArgs = [
    'region'    => 'us-east-1',
    'version'   => '2006-03-01',
];


function uploadObject(
    $s3,
    $bucket,
    $key,
    $data,
    $acl = S3_ACL_PRIVATE,
    $contentType = "text/plain")
{

    try {
        $res = $s3->putObject(array(
            'Bucket' => $bucket,
            'Key' => $key,
            'Body' => $data,
            'ContentType' => $contentType,
            'ACL' => $acl
        ));
    } catch (Exception $e) {
        print($e->getMessage());
        exit($e->getCode());
    }

    try {
        $s3->waitUntil('ObjectExists', array(
            'Bucket' => $bucket,
            'Key' => $key
        ));
    } catch (Exception $e) {
        print($e->getMessage());
        exit($e->getCode());
    }

    return true; // The operation has finished successfully.
}

?>