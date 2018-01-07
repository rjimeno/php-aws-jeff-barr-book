#!/usr/bin/env php
<?php

error_reporting(E_ALL);

require 'vendor/autoload.php';
use Aws\S3\S3Client;

$s3Client = new s3Client([
    'region' => 'us-east-1',
    'version' => '2006-03-01'
]);

// The following line should be inside a try-catch instead.
$buckets = $s3Client->listBuckets(); // Will fail if DNS can't resolve.
foreach ($buckets['Buckets'] as $bucket) {
        echo $bucket['Name'] . "\n";
}
