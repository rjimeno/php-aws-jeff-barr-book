#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/7/18
 * Time: 4:54 PM
 */

error_reporting(E_ALL);
ini_set('memory_limit', '4096M');
define('DEBUG', false); // Set to true to display debugging messages.
define('FBF', 16); // A four-by-four matrix has 16 squares.

require 'vendor/autoload.php';
require_once('include/book.inc.php'); // $s3clientArgs & $sqsClientOptions.
//require_once('include/simple_html_dom.php');
require_once('include/advanced_html_dom.php');

use Aws\Sqs\SqsClient;
use Aws\S3\S3Client;

$sqs = new SqsClient($sqsClientOptions);
$s3 = new s3Client($s3clientArgs);

$queueParse = findQueueURL($sqs, PARSE_QUEUE);
$queueFetch = findQueueURL($sqs, IMAGE_QUEUE);

while(true) {
    $message = pullMessage($sqs, $queueParse);

    if ($message != null) {
        DEBUG && print("MESSAGE from queue seem to be well-formed.\n");
        $messageDetail = $message['MessageDetail'];
        $receiptHandle = $message['ReceiptHandle'];
        $pageURL = $messageDetail['Data'];

        print("Processing URL '${pageURL}':\n");
        $dom = new AdvancedHtmlDom();
        // $dom->clear();
        // unset($dom);
        try {
            $dom->load_file($pageURL);
            // $dom = file_get_html($pageURL);
            // $data = file_get_contents($pageURL);
            /* $data = '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
'; */
            // $dom = str_get_html($data);
        } catch (Exception $e) {
            echo "Unable to load '${pageURL}': " . $e->getMessage() . "\n";
            exit($e->getCode());
        }
        if (!$dom) {
            die("We couldn't properly load the DOM as it is 'false'.");
        }
        if (!is_object($dom)) {
            die("We couldn't properly load the DOM as it is not an object.");
        }
        DEBUG && print("NODES: " . "\n");
        DEBUG && print_r($dom);
        $pageTitle = $dom->find('title', 0)->innertext;
        print(" Retreived page '${pageTitle}'.\n");


        $imageURLs = array();
        DEBUG && print("WILL work with:\n");
        DEBUG && print_r($dom->find('img'));
        DEBUG && print("\n");
        foreach ($dom->find('img') as $image) {
            $imageURL = $image->src;
            DEBUG && print("ABOUT to process ${imageURL}.\n");
            if (substr($imageURL, 0, 4) == 'http') {
                DEBUG && print("DID    match: ${imageURL}.\n");
                print(" Found absolute URL '${imageURL}'.\n");
                $imageURLs[] = $imageURL;
                DEBUG && print("PRE-PROCESSING image number " . count($imageURLs));
                if (count($imageURLs) == FBF) {
                    break;
                }
            } else {
                DEBUG && print("DIDN'T match: ${imageURL}.\n");
            }
        }

        if (count($imageURLs) > 0) {
            $origin = $messageDetail['Origin'];
            $history = $messageDetail['History'];
            $history[] = 'Processed by ' . $argv[0] . ' at ' . date('c');

            $message = json_encode(array(
                'Action'  => 'FetchImages',
                'Origin'  => $origin,
                'Data'    => $imageURLs,
                'History' => $history,
                'PageTitle' => $pageTitle,
            ));

            try {
                $res = $sqs->sendMessage(['QueueUrl' => $queueFetch, 'MessageBody' => $message]);
            } catch (Exception $e) {
                echo "Unable to send '${message}' to queue '${queueFetch}': ", $e->getMessage(), "\n";
                exit($e->getCode());
            }
            print(" Sent page to image fetcher.\n");

            try {
                $res = $sqs->deleteMessage([
                    'QueueUrl' => $queueParse,
                    'ReceiptHandle' => $receiptHandle
                ]);
            } catch (Exception $e) {
                echo "Unable to delete message with handle '${receiptHandle}' from queue '${queueParse}': ", $e->getMessage(), "\n";
                exit($e->getCode());
            }
            print("\n");
        }
    }
}
?>