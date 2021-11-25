# Wordpress Update Script

Allows you to loop through Worpdress sites on a webserver and select N to skip or y to update Themes, Plugins & Wordpress Core

## Setup

1. Install wp-cli and set path to it in this script
2. Set the root of your sites in the script


To be prompted to do each core, plugin or theme update

```
update-wp.sh 
```

To just install everything with no prompting

```
update-wp.sh y
```

