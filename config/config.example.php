<?php

# copy to config.php and edit to match your site root
# For example if your layout is as follows then SITE_ROOT and DIR_PATTERN are as below
#
# /var/sites/www.example.com/public_html/wp-config.php
# /var/sites/www.example2.com/public_html/wp-config.php

return [
	'SITE_ROOT' => '/var/sites',
	'SKIP_UPDATE' => ['www.example2.com'],
	'WP_CLI' => '/path/to/wpi-cli/wp'
];
