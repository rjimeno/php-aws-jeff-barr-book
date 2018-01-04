#!/usr/bin/env php
<?php

error_reporting(E_ALL);

require 'vendor/autoload.php';
use Aws\S3\S3Client;

$s3Client = new s3Client([
    'region' => 'us-east-1',
    'version' => '2006-03-01'
]);

$buckets = $s3Client->listBuckets();
foreach ($buckets['Buckets'] as $bucket) {
        echo $bucket['Name'] . "\n";
}
