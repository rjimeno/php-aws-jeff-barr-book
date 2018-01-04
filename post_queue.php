#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/1/18
 * Time: 7:02 PM
 */

error_reporting(E_ALL);

require 'vendor/autoload.php';
require_once('include/book.inc.php');

use Aws\Sqs\SqsClient;

if (count($argv) < 3) {
    exit("Usage: " . $argv[0] . " QUEUE_URL ITEM1 ...\n");
}

$sqsClientOptions = [
    'region' => 'us-east-1',
    'version' => '2012-11-05',
    'profile' => 'default'
];

$sqs = new SqsClient($sqsClientOptions);

$queueUrl=$argv[1];

for ($i = 2; $i < count($argv); $i++) {
    $messageBody = $argv[$i];

    try {
        $res = $sqs->sendMessage([
            'MessageBody' => $messageBody,
            'QueueUrl' => $queueUrl,
        ]);
    } catch (Exception $e) {
        echo "Unable to send '${messagBody}' to queue '${queueUrl}': ", $e->getMessage(), "\n";
        exit($e->getCode());
    }
    // print_r($res);
}
print('Message(s) sent successfully.');

