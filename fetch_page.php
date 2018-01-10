#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/6/18
 * Time: 12:06 PM
 */

error_reporting(E_ALL);
define('DEBUG', true); // Set to true to obtain debugging messages.

require 'vendor/autoload.php';
require_once('include/book.inc.php');  // $s3ClientArgs & $sqsClientOptions.

use Aws\Sqs\SqsClient;
use Aws\S3\S3Client;

$sqs = new SqsClient($sqsClientOptions);
$s3 = new s3Client($s3clientArgs);

$queueURL = findQueueURL($sqs, URL_QUEUE);
$queueParse = findQueueURL($sqs, PARSE_QUEUE);

while(true) {
    $message = pullMessage($sqs, $queueURL);

    if ($message != null) {
        DEBUG && print_r($message);
        $messageDetail = $message['MessageDetail'];
        $receiptHandle = $message['ReceiptHandle'];
        $pageURL = $messageDetail['Data'];

        print("Processing URL '${pageURL}':\n");
        try {
            $html = file_get_contents($pageURL);
            if ($html === false) {
                die("Unable to get the content of '${pageURL}'.\n");
            }
        } catch (Exception $e) {
            echo "Unable to get the content of '${pageURL}': " . $e->getMessage() . "\n";
        }
        print("  Retrieved " . strlen($html) . " bytes of HTML.\n");

        $key = 'page_' . md5($pageURL) . '.html';
        if (uploadObject($s3, BOOK_BUCKET, $key, $html)) {
            $s3URL = $s3->getObjectUrl(BOOK_BUCKET, $key);
            print(" Uploaded page to S3 as '${key}'.\n");

            $origin = $messageDetail['Origin'];
            $history = $messageDetail['History'];
            $history[] = 'Fetched by ' . $argv[0] . ' at ' . date('c');

            $message = json_encode(array(
                'Action'  => 'ParsePage',
                'Origin'  => $origin,
                'Data'    => $s3URL,
                'PageURL' => $pageURL,
                'History' => $history
            ));

            try {
                $res = $sqs->sendMessage(['QueueUrl' => $queueParse, 'MessageBody' => $message]);
            } catch (Exception $e) {
                echo "Unable to send '${message}' to queue '${queueParse}': ", $e->getMessage(), "\n";
                exit($e->getCode());
            }
            print(" Sent page to parser.\n");

            try {
                $res = $sqs->deleteMessage([
                    'QueueUrl' => $queueURL,
                    'ReceiptHandle' => $receiptHandle
                ]);
            } catch (Exception $e) {
                echo "Unable to delete message with handle '${receiptHandle}' from queue '${queueURL}': ", $e->getMessage(), "\n";
                exit($e->getCode());
            }
        } else {
            print("Error uploading HTML to S3.\n");
        }
    }
}

?>