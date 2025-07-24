# Wordpress Updater

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
	// /var/www/sitedir/public_html
	// enter array of site dirs to skip
	// to run update on a skipped site use
	// composer wpu -- --only=sitedir
	'SKIP_UPDATE' => ['sitedir']
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

# e.g. if you have multiple sites such as:
# /sites/dir1/web
# /sites/dir2/web
# /sites/dir3/web

# to only do dir2 run
composer wpu -- --only=dir2
```

## sudo Setup
Do not run composer as `root`

`sudo` needs to be setup so the user running the updater can run the update to each Wordpress install as the user that owns each site. 

So if `siteRoot` is `/var/sites`

The `siteRoot` folder might contain the individual site directories:
```
drwxr-xr-x  5 user1    user1    4096 May 21  2021 user1
drwxr-xr-x  5 user2    user2    4096 Apr  9  2021 user2
drwxr-xr-x  5 user3    user3    4096 Aug 12  2020 user3
```

`wpUpdaterUser` is the user that you want to run the `WordpressUpdater::run()` script as

Configure the `wpUpdaterUser` user to sudo to a user who has write access to each Wordpress installation.

So add a sudo config file:

```sh
visudo -f /etc/sudoers.d/wpUpdaterUser
```


```txt
# Content of /etc/sudoers.d/wpUpdaterUser
wpUpdaterUser ALL=(ALL) NOPASSWD: /home/wpUpdaterUser/wordpress-updater/vendor/wp-cli/wp-cli/bin 
```

Example of sudo running as a different user (wpUpdaterUser) to allow update of the wordpress files they own.

```sh
sudo -u user1 /home/wpUpdaterUser/wordpress-updater/vendor/wp-cli/wp-cli/bin/wp \
    --path=/sites/public_html/www/user1/wordpress option get siteurl
```


## Crontab
Once you have successfully configured sudo to allow `wpUpdateUser` to `sudo` as each site user, add a `crontab` to call the updater on a schedule

run at 7:05 AM every day
```sh
# no output
5 7 * * * composer -d /home/wpUpdateUser/wordpress-updater/ wpu > /tmp/update-wp.log 2>&1

# output and send STDERR and STDOUT as email to whoever is in the MAILTO in your crontab
# this is handy as you will see failed updates, or updates that fail due to expired licenses
MAILTO="you@example.com.au"
5 7 * * * composer -d /home/wpUpdateUser/wordpress-updater/ wpu > 2>&1
```

