#!/bin/bash

# This script is run as last stage of instance provisioning.

# The following was obtained from: https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/install-LAMP.html

sudo yum update -y
sudo yum install -y httpd24 php70 # mysql56-server php70-mysqlnd
sudo service httpd start
sudo chkconfig httpd on
sudo usermod -a -G apache ec2-user
sudo chown -R ec2-user:apache /var/www
sudo chmod 2775 /var/www
find /var/www -type d -exec sudo chmod 2775 {} \;
find /var/www -type f -exec sudo chmod 0664 {} \;
echo '<?php phpinfo(); ?>' > phpinfo.php
sudo mv phpinfo.php /var/www/html/
echo '<?php print("PHP Information for instance $_SERVER[\'SERVER_NAME\'] ($_SERVER[\'SERVER_ADDR\'])!"); ?>' > index.php
sudo mv index.php /var/www/html/
