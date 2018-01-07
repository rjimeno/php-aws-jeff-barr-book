#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/3/18
 * Time: 8:57 PM
 */

error_reporting(E_ALL);

require 'vendor/autoload.php';
require_once('include/book.inc.php'); // $sqsClientOptions

use Aws\Sqs\SqsClient;

if (count($argv) != 2) {
    exit("Usage: " . $argv[0] . " QUEUE_URL\n");
}

$sqs = new SqsClient($sqsClientOptions);

while(true) {
    try {
        $res = $sqs->receiveMessage(['QueueUrl' => $argv[1]]);
    } catch (Exception $e) {
        echo "Unable to receive message from queue '${argv[1]}': ", $e->getMessage(), "\n";
        exit($e->getCode());
    }
    if(isset($res['Messages'])) {
        $message = $res['Messages'][0];
        $messageBody = $message['Body'];
        $receiptHandle = $message['ReceiptHandle'];

        print("Message: ${messageBody}.\n");

        try {
            $res = $sqs->deleteMessage([
                'QueueUrl' => $argv[1],
                'ReceiptHandle' => $receiptHandle
            ]);
        } catch (Exception $e) {
            echo "Unable to delete message with handle '${receiptHandle}' from queue '${argv[1]}': ", $e->getMessage(), "\n";
            exit($e->getCode());
        }
        sleep(1);
    } else {
        exit(0); // No more messages in the queue!
    }
}

?>
