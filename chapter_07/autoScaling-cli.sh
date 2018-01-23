#!/usr/bin/env bash


aws elb create-load-balancer \
  --load-balancer-name LoadBal \
  --listener "LoadBalancerPort=80,InstancePort=80,Protocol=HTTP" \
  --subnets subnet-2577d678 \
  --security-groups sg-afdb9bdb


# The following argument may be missing '--iam-instance-profile CodeDeployDemo-EC2-Instance-Profile \'.
aws autoscaling create-launch-configuration \
  --launch-configuration-name Config \
  --image-id ami-97785bed \
  --key-name EssentialsKeyPair-Virginia \
  --instance-type t2.nano \
  --user-data file://instanceSetup.sh \
  --security-groups sg-afdb9bdb

# The following argument may be missing '  --desired-capacity 1 \'
aws autoscaling create-auto-scaling-group \
  --auto-scaling-group-name AutoScale \
  --launch-configuration-name Config \
  --min-size 1 \
  --max-size 5 \
  --availability-zones us-east-1b \
  --vpc-zone-identifier subnet-2577d678 \
  --load-balancer-name LoadBal

# To be run at the shell prompt (will display ARNs at the core of their output:

aws autoscaling put-scaling-policy \
  --policy-name Trigger1out \
  --auto-scaling-group-name AutoScale \
  --scaling-adjustment 1 \
  --adjustment-type ChangeInCapacity

# arn:aws:autoscaling:us-east-1:abc...

aws autoscaling put-scaling-policy \
  --policy-name Trigger1in \
  --auto-scaling-group-name AutoScale \
  --scaling-adjustment -1 \
  --adjustment-type ChangeInCapacity

# arn:aws:autoscaling:us-east-1:xyz...

The two ARNs above will be used in the commands below:

aws cloudwatch put-metric-alarm \
  --alarm-name AddCapacity \
  --metric-name CPUUtilization \
  --namespace AWS/EC2  \
  --statistic Average \
  --dimensions "Name=AutoScalingGroupName,Value=AutoScale" \
  --unit Percent \
  --period 60 \
  --threshold 0.3 \
  --comparison-operator GreaterThanOrEqualToThreshold  \
  --evaluation-periods 2 \
  --alarm-actions 'arn:aws:autoscaling:us-east-1:abc...'


aws cloudwatch put-metric-alarm \
  --alarm-name SubCapacity \
  --metric-name CPUUtilization \
  --namespace AWS/EC2  \
  --statistic Average \
  --dimensions "Name=AutoScalingGroupName,Value=AutoScale" \
  --unit Percent \
  --period 60 \
  --threshold 0.2 \
  --comparison-operator LessThanOrEqualToThreshold  \
  --evaluation-periods 2 \
  --alarm-actions 'arn:aws:autoscaling:us-east-1:xyz...'

# Note: The thresholds above work for (virutally) load-less instances.
#       You may stick to the values in the book instead (70 and 20 percent, I believe).