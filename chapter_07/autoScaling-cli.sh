
# To be run at the shell prompt (will display ARNs at the core of their output:

aws autoscaling put-scaling-policy --policy-name Trigger1out --auto-scaling-group-name AutoScale --scaling-adjustment 1 --adjustment-type ChangeInCapacity

# arn:aws:autoscaling:us-east-1:abc...

aws autoscaling put-scaling-policy --policy-name Trigger1in --auto-scaling-group-name AutoScale --scaling-adjustment -1 --adjustment-type ChangeInCapacity

# arn:aws:autoscaling:us-east-1:xyz...

The two ARNs above will be used in the commands below:

aws cloudwatch put-metric-alarm --alarm-name AddCapacity --metric-name CPUUtilization --namespace AWS/EC2  --statistic Average --dimensions "Name=AutoScalingGroupName,Value=AutoScale" --unit Percent --period 60 --threshold 70 --comparison-operator GreaterThanOrEqualToThreshold  --evaluation-periods 2 --alarm-actions 'arn:aws:autoscaling:us-east-1:abc...'


aws cloudwatch put-metric-alarm --alarm-name SubCapacity --metric-name CPUUtilization --namespace AWS/EC2  --statistic Average --dimensions "Name=AutoScalingGroupName,Value=AutoScale" --unit Percent --period 60 --threshold 30 --comparison-operator LessThanOrEqualToThreshold  --evaluation-periods 2 --alarm-actions 'arn:aws:autoscaling:us-east-1:xyz...'