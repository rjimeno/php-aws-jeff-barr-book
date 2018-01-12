#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/12/18
 * Time: 1:22 PM
 */

error_reporting(E_ALL);

require '../vendor/autoload.php';
require_once('../include/book.inc.php'); // $ec2clientArguments

use Aws\Ec2\Ec2Client;

if (count($argv) < 2) {
    exit("Usage: " . $argv[0] . " INSTANCE ID...\n");
}

$nextId = 1;
$instanceIDs = [];
$opt = array();
for ($i = 1; $i < $argc; $i++) {
    $opt["InstanceId.${nextId}"] = $argv[$i];
    array_push($instanceIDs, $argv[$i]);
    $nextId++;
}
$opt['InstanceIds']=$instanceIDs;

$ec2 = new Ec2Client($ec2clientArguments);

try {
    $result = $ec2->monitorInstances($opt);
} catch (Exception $e) {
    print("Unable to monitor ec2 instance(s) [" . join(", ", $instanceIDs) . "]: " . $e->getMessage() . "\n");
    exit($e->getCode());
}

if($result['InstanceMonitorings']) {
    $instanceMonitoring = $result['InstanceMonitorings'];
    for ($i = 0; $i < count($instanceMonitoring); $i++) {
        $instanceId = (string) $instanceMonitoring[$i]['InstanceId'];
        $monitoringState = (string) $instanceMonitoring[$i]['Monitoring']['State'];

        print("InstanceId ${instanceId}: ${monitoringState}.\n");
    }
} else {
    print("Could not monitor instance(s):\n");
    print_r($result);
    die("The structure above does not seem to have information about instance monitoring.");
}

?>