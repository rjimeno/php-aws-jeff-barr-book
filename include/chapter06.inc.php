<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/5/18
 * Time: 11:47 PM
 */

define('URL_QUEUE', 'c_url');
define('PARSE_QUEUE', 'c_parse');
define('IMAGE_QUEUE', 'c_image');
define('RENDER_QUEUE', 'c_render');

$sqsClientOptions = [
    'region' => 'us-east-1',
    'version' => '2012-11-05',
    'profile' => 'default'
];

function findQueueUrl($sqs, $queueName) {
    try {
        $res = $sqs->createQueue(['QueueName' => $queueName]);
    } catch (Exception $e) {
        echo "Unable to create queue: ", $e->getMessage(), "\n";
        exit($e->getCode());
    }
    return $res['QueueUrl'];
}

function pullMessage($sqs, $queueURL) {
    while(true) {
        try {
            $res = $sqs->receiveMessage(['QueueUrl' => $argv[1]]);
        } catch (Exception $e) {
            echo "Unable to receive message from queue '${argv[1]}': ", $e->getMessage(), "\n";
            exit($e->getCode()); // return null; instead?
        }
        if(isset($res['Messages'])) {
            $message = $res['Messages'][0];
            $messageBody = $message['Body'];
            $messageDetail = json_decode($messageBody, true);
            $receiptHandle = $message['ReceiptHandle'];

            return array(
                'QueueURL' => $queueURL,
                'Timestamp' => date('c'),
                'Message' => $message,
                'MessageDetail' => $messageDetail,
                'ReceiptHandle' => $receiptHandle
            );
        } else {
            sleep(1);
        }
    }
}

?>