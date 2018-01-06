#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/6/18
 * Time: 12:50 AM
 */

error_reporting(E_ALL);

require 'vendor/autoload.php';
require_once('include/book.inc.php'); // $sqsClientOptions

use Aws\Sqs\SqsClient;

if (count($argv) < 2) {
    exit("Usage: " . $argv[0] . " URL...\n");
}

$sqs = new SqsClient($sqsClientOptions);
$queueURL = findQueueUrl($sqs, URL_QUEUE);

for ($i = 1; $i < $argc; $i++) {
    $histItem = array('Posted by ' . $argv[0] . ' at ' . date('c'));
    $message = json_encode(array(
        'Action'  => 'FetchPage',
        'Origin'  => $argv[0],
        'Data'    => $argv[$i],
        'History' => $histItem
    ));
    try {
        $res = $sqs->sendMessage([
            'MessageBody' => $message,
            'QueueUrl' => $queueURL,
        ]);
    } catch (Exception $e) {
        echo "Unable to send '${message}' to queue '${queueURL}': ", $e->getMessage(), "\n";
        exit($e->getCode());
    }
    print("Posted '${message}' to QueueURL '$queueURL'.\n'");
}

?>