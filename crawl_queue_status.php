#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 01/06/18
 * Time: 12:24 AM
 */

error_reporting(E_ALL);

require 'vendor/autoload.php';
require_once('include/book.inc.php'); // $sqsClientOptions

use Aws\Sqs\SqsClient;

$sqs = new SqsClient($sqsClientOptions);
$queues = array(URL_QUEUE, PARSE_QUEUE, IMAGE_QUEUE, RENDER_QUEUE);
$underlines = '';

foreach ($queues as $queueName) {
    printf("%-12s ", $queueName);
    $underlines .=
        str_repeat('-', strlen($queueName)) .
        str_repeat(' ', 12 - strlen($queueName)) .
        " ";
}
print("\n");
print($underlines . "\n");

foreach ($queues as $queueName) {
    try {
        $res = $sqs->createQueue(['QueueName' => $queueName]);
    } catch (Exception $e) {
        echo "Unable to create queue: ", $e->getMessage(), "\n";
        exit($e->getCode());
    }
    //print("The queue '${queue}' was created successfully.'");
    print_r($res);
    //$res = $sqs->getQueueAttributes(['Queue'])
}
print("\n");

exit(0);
?>