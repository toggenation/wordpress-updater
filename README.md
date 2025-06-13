# Wordpress Update Scripts




## PHP Script Setup
Clone this repo the run `composer install` to pull in the dependencies

## Edit config.php

```sh
cp config/config.example.php config/config.php
```

```php
# config/config.php edit to taste
# see notes in config/config.example.php
return [
	'SITE_ROOT' => '/var/sites',
	'DIR_PATTERN' => '/*/public_html/wp-config.php',
	'SKIP_UPDATE' => ['www.example2.com']
];
```

## PHP Script Usage
See below for `sudo` setup
Run composer which will call the `Toggenation\WordpressUpdater::run` method as follows

```sh
composer wpu
```

To only run update on one site

```sh
composer wpu -- --only=sitedir

# e.g. if you have multiple sites

/sites/dir1/web
/sites/dir2/web
/sites/dir3/web

# to only do dir2 run
composer wpu -- --only=dir2
```

## sudo Setup
To successfully update each Wordpress site and NOT run `WordpressUpdater::run()` as `root` requires the user you want to run the script as to be able `sudo` as the owner of the wordpress directory

So if `siteRoot` is `/sites/public_html/www`

Contents of the `siteRoot` folder might be:

```
drwxr-xr-x  5 user1    user1    4096 May 21  2021 user1
drwxr-xr-x  5 user2    user2    4096 Apr  9  2021 user2
drwxr-xr-x  5 user3    user3    4096 Aug 12  2020 user3
```

`your_username` is the user that you want to run the `WordpressUpdater::run()` script with 

The `your_username` user needs to be able to sudo the wp-cli as the user who owns Wordpress directory and files:

So add a sudo config file:

```sh
visudo -f /etc/sudoers.d/your_username
```


```
# Content of /etc/sudoers.d/your_username
your_username ALL=(ALL) NOPASSWD: /home/your_username/wordpress-updater/vendor/wp-cli/wp-cli/bin 
```

Example of sudo running as a different user (user1) to allow update of the wordpress files they own.

```
sudo -u user1 /home/your_username/wordpress-updater/vendor/wp-cli/wp-cli/bin/wp \
    --path=/sites/public_html/www/user1/wordpress option get siteurl
```




## Shell Script Crontab

Once you have successfully configured the script running user for `sudo` you can add a crontab

run at 7:05 AM every day
```sh
5 7 * * * /home/rupert/wordpress-updater/update-wp.sh y > /tmp/update-wp.log 2>&1
```

## PHP Script Crontab

run at 7:05 AM every day
```sh
5 7 * * * composer -d /home/rupert/wordpress-updater/ wpu > /tmp/update-wp.log 2>&1
```

