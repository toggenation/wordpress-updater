


WP_SITES=`find /var/www -regex '.*web/wp-config\.php'`

WP=/usr/local/bin/wp


for i in $WP_SITES
do
	WP_DIR=$(dirname $i)
	SITE_URL=`$WP --path=$WP_DIR option get siteurl --format=json`
	PLUGIN_LIST=`$WP --path=$WP_DIR plugin list --format=json`
done
