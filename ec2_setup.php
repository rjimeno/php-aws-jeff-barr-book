#!/usr/bin/env php
<?php

error_reporting(E_ALL);

require 'vendor/autoload.php';
require_once('include/book.inc.php'); // $s3ClientArgs & $bucketArgs.

use Aws\Ec2\Ec2Client;

$ec2clientOptions = [
    'region' => 'us-east-1',
    'version' => '2016-11-15',
    'profile' => 'default'
];

$ec2 = new EC2Client($ec2clientOptions);

$options = [
    'ImageId' => 'ami-55ef662f', // Obs.: The root device name is /dev/xvda
    'MaxCount' => 1,
    'MinCount' => 1,
    'KeyName' => 'EssentialsKeyPair-Virginia',
    'InstanceType' => 't2.nano',
    'SubnetId' => 'subnet-fef59db5',
];

try {
    $res = $ec2->runInstances($options);
} catch (Exception $e) {
    echo "Unable to run instance: ", $e->getMessage(), "\n";
    exit($e->getCode());
}

$instances = $res['Instances'];
$instanceId = $instances[0]['InstanceId'];
$availabilityZone = $instances[0]['Placement']['AvailabilityZone'];

print("Launched instance ${instanceId} in availability zone ${availabilityZone}.\n");

// Warning: The next block re-uses variables $options, $res and $instances.
$options = array('InstanceIds' => [$instanceId]);
do {
    $res = $ec2->DescribeInstances($options);
    $instances = $res['Reservations'][0]['Instances'];
    $stateArray = $instances[0]['State'];
    $state = $stateArray['Name'];
    $running = ('running' == $state);

    if (!$running) {
        print("Instance is currently in state '${state}''; waiting 10 seconds.\n");
        sleep(10);
    }
} while(!$running);

$publicIP = $res['Reservations'][0]['Instances'][0]['PublicIpAddress'];
print("Assigned IP address is ${publicIP}.\n");

$res = createVolume($ec2, $availabilityZone, 1);
$volumeId1 = $res['VolumeId'];
$res = createVolume($ec2, $availabilityZone, 1);
$volumeId2 = $res['VolumeId'];

print("Created EBS volumes ${volumeId1} and ${volumeId2}.\n");

$options = array('VolumeIds' => [$volumeId1, $volumeId2]);
do {
    $res = $ec2->DescribeVolumes($options);
    $stateV1 = $res['Volumes'][0]['State'];
    $stateV2 = $res['Volumes'][1]['State'];
    $available = ('available' == $stateV1 && $stateV1 == $stateV2);

    if (!$available) {
        print("Volumes are not yet available; waiting 10 seconds before re-trying.\n");
        sleep(10);
    }
} while(!$available);

// Note that the (somewhat) arbitrary substring '/dev/xvd' is dependant on $options['ImageId'] (ami-55ef662f).
// Hint: aws ec2 describe-images --region us-east-1 --image-ids ami-55ef662f --query Images[].RootDeviceName
try {
    $options = ['InstanceId' => $instanceId, 'VolumeId' => $volumeId1, 'Device' => '/dev/xvdb'];
    $ec2->attachVolume($options);
    $options['VolumeId'] = $volumeId2;
    $options['Device'] = '/dev/xvdc';
    $ec2->attachVolume($options);
} catch(Exception $e) {
    echo "Unable to attach volume(s): ", $e->getMessage(), "\n";
    exit($e->getCode());
}

print("Volumes attached successfully.\n");
?>
