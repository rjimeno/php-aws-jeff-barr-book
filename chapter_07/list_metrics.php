#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/12/18
 * Time: 4:23 PM
 */

error_reporting(E_ALL);

require '../vendor/autoload.php';
require_once('../include/book.inc.php'); // $cWclientArguments

use Aws\CloudWatch\CloudWatchClient;

$cW = new CloudWatchClient($cWclientArguments);

try {
    /* $res = $cW->listMetrics([
        'Namespace' => 'AWS/EC2',
        'Dimensions' => [
            ['Name' => 'InstanceId', 'Value' => 'i-0e4827c4d02891661']
        ]
    ]); */
    $res = $cW->listMetrics(); // Simplest possible or above.
} catch (Exception $e) {
    print("Unable to list metrics: " . $e->getMessage() . "\n");
    exit($e->getCode());
}

if($res) {
    print_r($res);
}
