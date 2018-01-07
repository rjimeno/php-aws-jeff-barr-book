#!/usr/bin/env php
<?php

error_reporting(E_ALL);

require 'vendor/autoload.php';
require_once('include/book.inc.php'); // $s3ClientArgs.

use Aws\S3\S3Client;

$bucket = IsSet($_GET['bucket']) ? $_GET['bucket'] : BOOK_BUCKET;
$bucketThumbs = $bucket . THUMB_BUCKET_SUFFIX;

$s3 = new s3Client($s3clientArgs);

try {
    $bucketList = $s3->listBuckets();
} catch (Exception $e) {
    echo "Unable to get list of buckets.\n";
    die($e->getMessage());
}

/* More work is needed in this program to make it display
each bucket (Name?) clearly and cleanly. */
?>
<html>
    <head>
        <title>S3 Buckets</title>
    </head>
    <body>
        <h1>S3 Buckets</h1>
        <ul>
            <?php foreach($bucketList as $bucket): ?>
                <li><?php print_r($bucket) ?></li>
            <?php endforeach ?>
        </ul>
    </body>
</html>



