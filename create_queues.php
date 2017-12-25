#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 12/24/17
 * Time: 6:04 PM
 */

error_reporting(E_ALL);

require 'vendor/autoload.php';
require_once('include/book.inc.php');

use Aws\Sqs\SqsClient;

if (count($argv) < 2) {
    exit("Usage: " . $argv[0] . " QUEUE...\n");
}

$sqsClientOptions = [
    'region' => 'us-east-1',
    'version' => '2012-11-05',
    'profile' => 'default'
];

$sqs = new SqsClient($sqsClientOptions);

for ($i = 1; $i < count($argv); $i++) {
    $queue = $argv[$i];

    try {
        $res = $sqs->createQueue(['QueueName' => $queue]);
    } catch (Exception $e) {
        echo "Unable to create queue: ", $e->getMessage(), "\n";
        exit($e->getCode());
    }
    print("The queue '${queue}' was created successfully.'");
    // print_r($res);
}

