# Wordpress Update Script

Allows you to loop through Worpdress sites on a webserver and select N to skip or y to update Themes, Plugins & Wordpress Core

## Setup

1. Install wp-cli and set path to it in SITES_ROOT in `update-wp.sh`
2. Set the root of your sites in the script


## Usage
To be prompted to do each core, plugin or theme update

```sh
update-wp.sh 
```

To just install everything with no prompting

```sh
update-wp.sh y
```

