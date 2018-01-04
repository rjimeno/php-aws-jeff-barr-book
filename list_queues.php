#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/1/18
 * Time: 6:03 PM
 */

error_reporting(E_ALL);

require 'vendor/autoload.php';
require_once('include/book.inc.php');

use Aws\Sqs\SqsClient;

$sqsClientOptions = [
    'region' => 'us-east-1',
    'version' => '2012-11-05',
    'profile' => 'default'
];

$sqs = new SqsClient($sqsClientOptions);
try {
    $res = $sqs->listQueues([]);
} catch (Exception $e) {
    echo "Unable to get list of queues with prefix '${prefix}': ", $e->getMessage(), "\n";
    exit($e->getCode());
}

foreach ($res['QueueUrls'] as $URL) {
    print $URL;
}


