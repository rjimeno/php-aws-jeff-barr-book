#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/9/18
 * Time: 11:13 PM
 */

error_reporting(E_ALL);

require 'vendor/autoload.php';
require_once('include/book.inc.php'); // $s3clientArgs & $sqsClientOptions.
require_once('include/advanced_html_dom.php');

use Aws\Sqs\SqsClient;
use Aws\S3\S3Client;

$sqs = new SqsClient($sqsClientOptions);
$s3 = new s3Client($s3clientArgs);

$queueFetch = findQueueURL($sqs, IMAGE_QUEUE);
$queueRender = findQueueURL($sqs, RENDER_QUEUE);


while (true) {
    $message = pullMessage($sqs, $queueFetch);

    if ($message != null) {
        $messageDetail = $message['MessageDetail'];
        $receiptHandle = $message['ReceiptHandle'];
        $imageURLs = $messageDetail['Data'];

        $s3ImageKeys = array();
        foreach ($imageURLs as $imageURL) {
            print(" Fetch image '${imageURL}'.\n");
            $image = file_get_contents($imageURL);
            print(" Retrieved " . strlen($image) . " byte image.\n");

            $imageThumb = thumbnailImage($image, 'image/png');

            $key = 'image_' . md5($imageURL) . '.png';

            if (uploadObject($s3, BOOK_BUCKET, $key, $imageThumb)) {
                print(" Stored image in S3 using key '${key}'.\n");
                $s3ImageKeys[] = $key;
            }
        }

        if (count($imageURLs) == count($s3ImageKeys)) {
            $origin = $messageDetail['Origin'];
            $history = $messageDetail['History'];
            $pageTitle = $messageDetail['PageTitle'];

            $history[] = 'Processed by ' . $argv[0] . ' at ' .  date('c');

            $message = json_encode(array(
                'Action' => 'RenderImages',
                'Data' => $s3ImageKeys,
                'History' => $history,
                'PageTitle' => $pageTitle
            ));

            try {
                $res = $sqs->sendMessage(['QueueUrl' => $queueRender, 'MessageBody' => $message]);
            } catch (Exception $e) {
                echo "Unable to send '${message}' to queue '${queueRender}': ", $e->getMessage(), "\n";
                exit($e->getCode());
            }
            print(" Sent page to image renderer.\n");

            try {
                $res = $sqs->deleteMessage([
                    'QueueUrl' => $queueFetch,
                    'ReceiptHandle' => $receiptHandle
                ]);
            } catch (Exception $e) {
                echo "Unable to delete message with handle '${receiptHandle}' from queue '${queueURL}': ", $e->getMessage(), "\n";
                exit($e->getCode());
            }
            print(" Deleted message from fetch queue.\n");
            print("\n");
        }
    }
}

?>