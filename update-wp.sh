#!/bin/bash


# find all Wordpress installs
WP_SITES=`find /var/www -regex '.*web/wp-config\.php'`

# path to wp-cli
WP=/usr/local/bin/wp

shouldWeUpdate() {
	AUTO_UPDATE=$(echo $1 | tr '[:upper:]' '[:lower:]')

	if [ "$AUTO_UPDATE" = "y" ]; 
	then
		RESULT="y"
		return 0
	fi
	echo -n "Do you want to update $2 for $3: [N/y] "
	read RESULT
}



for i in $WP_SITES
do
	# get the full path to the wp install
	WP_DIR=$(dirname $i)

	# get the owner so we run the upgrade as the correct user
	OWNER=`stat -c %U $WP_DIR`
	echo "Owner $OWNER"
	
	# so we can echo the installation we are upgrading
	SITE_URL=`$WP --path=$WP_DIR option get siteurl`

	# list the plugins and available upgrades
	$WP --path=$WP_DIR plugin list
	
	shouldWeUpdate "$1" 'plugins' "$SITE_URL"

	case $RESULT in
		Y|y)
			echo "Running update all plugins for $SITE_URL"
			sudo -u $OWNER $WP --path=$WP_DIR plugin update --all
			;;
		*)
		echo "Skipping update all for $SITE_URL"
		;;
	esac

	$WP --path=$WP_DIR theme list
	

	shouldWeUpdate "$1" "themes" "$SITE_URL"

	case $RESULT in 
		Y|y)
			echo "Running update of all themes for $SITE_URL"
			sudo -u $OWNER $WP --path=$WP_DIR theme update --all
			;;
		*)
			echo "Skipping update of all themes for $SITE_URL"
			;;
	esac

	$WP --path=$WP_DIR core check-update
	
	shouldWeUpdate "$1" "core"  "$SITE_URL"
	
	case $RESULT in 
		Y|y)
			echo "Running wp core update for $SITE_URL"
			sudo -u $OWNER $WP --path=$WP_DIR core update
			;;
		*)
			echo "Skipping update of core for $SITE_URL"
			;;
	esac
done
