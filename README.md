# Wordpress Update Scripts

A Shell script and PHP script to run Theme, Core and Plugin updates from `crontab`

## Shell Script Setup

1. Install wp-cli and set path to it in SITES_ROOT in `update-wp.sh`
2. Set the root of your sites in the script


## Shell Script Usage
To be prompted to do each core, plugin or theme update

```sh
update-wp.sh 
```

To just install everything with no prompting

```sh
update-wp.sh y
```

## Shell Script Crontab

run at 7:05 AM every day
```sh
5 7 * * * /home/rupert/wordpress-updater/update-wp.sh y > /tmp/update-wp.log 2>&1
```

## PHP Script Setup
After clone this repo run `composer install` to pull in the dependencies

This script has no switch to run individually or not

## PHP Script Usage

Run composer which will call the `Toggenation\WordpressUpdater::run` method as follows

```sh
composer wpu
```

## PHP Script Crontab

run at 7:05 AM every day
```sh
5 7 * * * composer -d /home/rupert/wordpress-updater/ wpu > /tmp/update-wp.log 2>&1
```



