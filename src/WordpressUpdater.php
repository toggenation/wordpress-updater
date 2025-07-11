<?php

namespace Toggenation;

use Composer\Script\Event;
use Exception;

/**
 * 
 * Finds and updates Wordpress plugins, core & themes 
 * using a pattern of /var/www/*\/web/wp-config\.php
 * 
 * ## Examples
 *      ### Run with composer
 *      #### Change the root
 *      $ composer wpu -- --root=/var/sites
 * 
 *      #### Select an individual site
 *      $ composer wpu -- --only=dir1
 * 
 * @package Toggenation
 */
class WordpressUpdater
{
    private string $wp = '';

    private $skipUpdate = [];

    private string $userName = '';

    private string $siteRoot =  '/var/www';

    private string $siteDir  = '';

    private string $dirPattern = '/*/web/wp-config.php';

    private array $configSettings = ['SITE_ROOT', 'DIR_PATTERN', 'SKIP_UPDATE', 'WP_CLI'];

    public function __construct()
    {
        define('ROOT', realpath(__DIR__ . '/../'));

        define('CONFIG', ROOT . '/config');

        define('VENDOR', ROOT . '/vendor');

        $this->setConfig();
       
        $this->debug("WP-cli path: {$this->wp}");
    }

    public function setSiteDir($siteDir)
    {
        $this->siteDir = $siteDir;
    }

    private function setConfig()
    {
        $config = include(CONFIG . '/config.php');

        $this->checkConfig($this->configSettings, $config);

        $this->siteRoot = $config['SITE_ROOT'];

        $this->skipUpdate = $config['SKIP_UPDATE'] ?? [];

	$this->dirPattern = $config['DIR_PATTERN'];

	$this->wp = $config['WP_CLI'];

    }

    private function checkConfig($config)
    {
        foreach ($this->configSettings as $setting) {
            if (!in_array($setting, $config)) {
                throw new \InvalidArgumentException("Missing $setting from config array");
            }
        }
    }

    public function debug($content, $die = false)
    {
        $line = debug_backtrace()[0]['line'];

        echo "Line: {$line} - ";

        echo print_r($content, true) . "\n";

        if ($die) {
            die;
        }
    }

    public function getSiteUrl()
    {
        return  $this->exec(['option', 'get', 'siteurl']);
    }

    private function updatePlugins()
    {
        return $this->exec(['plugin', 'update', '--all']);
    }

    private function updateThemes()
    {
        return $this->exec(['theme', 'update', '--all']);
    }

    private function updateCore()
    {
        return $this->exec(['core', 'update']);
    }

    private function updateLanguageCore()
    {
        return $this->exec(['language', 'core', 'update']);
    }


    /**
     * wp-cli stores downloaded files in ~/.wpi-cli/cache and because each 
     * site might be owned by a different Operating System user the downloads
     * are not reused for multiple sites so clear the above mentioned directory each run
     *
     */
    private function clearCliCache()
    {
        return $this->exec(['cli', 'cache', 'clear']);
    }
    private function flushCache()
    {
        return $this->exec(['cache', 'flush', 'all']);
    }

    private function clearFastestCache()
    {
	    $command = ['fastest-cache', 'clear', 'all'];
	    
	    if(!$this->hasCommand($command)) {
		    return;
	    }

	echo "Clearing fastest-cache\n";

	$this->exec($command);

    }

    private function hasCommand(array $command): bool
    {
		$command = array_merge(
			['cli', 'has-command'],
			$command
		);

	    if($this->exec($command) === 0) {
		    echo join(' ', $command) . ' exists' . PHP_EOL;
		   return true;
	   }
	   
	   return false;	   
	
    }

    /**
     * Builds a command line for wp-cli
     * ## Example
     * sudo -u your_username /path/to/vendor/bin/wp --path=/path/to/wordpress cache flush all
     * 
     * The user running this will need to have sudo access to elevate to each user
     * 
     * Warning be careful how many perms you give permissions to
     * 
     * visudo -f /etc/sudoers.d/your_username
     * 
     * Content of /etc/sudoers.d/your_username
     * 
     * your_username ALL=(ALL) NOPASSWD: /home/your_username/wordpress-updater/vendor/wp-cli/wp-cli/bin
     * 
     * 
     * @param array $command 
     * @return void 
     */
    private function exec(array $command)
    {
        $cmd = join(
            ' ',
            array_merge(
                [
                    'sudo',
                    '-u',
                    $this->userName,
                    $this->wp,
                    "--path={$this->siteDir}",
                ],
                $command
            )
        );

        exec($cmd, $output, $resultCode);

        foreach ($output as $out) {
            echo "$out\n";
	};

	return $resultCode;
    }

    public function setSiteOwnerUserName(): void
    {
        $stats = lstat($this->siteDir);

        $this->userName = posix_getpwuid($stats['uid'])['name'];
    }

    private function filterSkipped(array $filesArray = [], ?string $only = null): array
    {
        # ignores SKIP_UPDATE and runs against whatever is specified in `--only='
        # composer wpu -- --only=dir1

        // Makes /var/web/site1/web/wp-config.php into /var/web/site1/web
        $dirList = array_map('dirname', $filesArray);

        if ($only) {
            $dirList = array_filter($dirList, function ($siteDir) use ($only) {
                $onlyDir = $this->siteRoot . '/' . $only;
                return str_starts_with($siteDir, $onlyDir);
            });

            $dirList = array_values($dirList);

            $this->debug("Directories found: " . join(', ', $dirList));

            return $dirList;
        }

        # when running automated skip any dirs found in SKIP_UPDATE config setting
        $toSkip = array_map(function ($siteSlug) {
            return $this->siteRoot . '/' . $siteSlug;
        }, $this->skipUpdate);

        $filtered = array_filter($dirList, function ($value) use ($toSkip) {
            $match = true;

            foreach ($toSkip as $skipThis) {
                if (str_starts_with($value, $skipThis)) {
                    $match = false;

                    $this->debug("Skipping update for {$skipThis}");
                }
            }

            return $match;
        });

        // re-index
        $filtered = array_values($filtered);

        return $filtered;
    }

    private function getSearchPattern(string $siteRoot): string
    {
        return $siteRoot . $this->dirPattern;
    }

    private function parseArguments($args)
    {
        $options = [];
        $flags = [];
        $arguments = [];

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                // Long option with value
                if (str_contains($arg, '=')) {
                    [$key, $value] = explode('=', substr($arg, 2), 2);
                    $options[$key] = $value;
                } else {
                    $options[substr($arg, 2)] = true;
                }
            } elseif (str_starts_with($arg, '-')) {
                // Short flag(s)
                $flags[] = substr($arg, 1);
            } else {
                // Positional argument
                $arguments[] = $arg;
            }
        }

        $parsedArgs = compact('options', 'arguments', 'flags');

        return $parsedArgs;
    }


    /**
     * return a list of directories under $siteRoot 
     * that contain a wp-config.php
     * @return array
     */
    private function getSites(?string $siteRoot = null, ?array $arguments = null): array
    {
        $only = $this->parseOnly($arguments);

        $searchPattern = $this->getSearchPattern($siteRoot);

        $this->debug("Searching for sites using pattern: `{$searchPattern}`");

        $foundSites = glob($searchPattern);

        $files = $this->filterSkipped($foundSites, $only);

        if (empty($files)) {
            throw new Exception("No valid wordpress installs found in $siteRoot");
        }

        return $files;
    }

    private function findArg($argument, $arguments): ?string
    {
        foreach ($arguments as $arg) {
            if (strpos($arg, "--{$argument}=") !== false) {
                return explode('=', $arg)[1];
            }
        }

        return null;
    }

    public function parseSiteRoot($args)
    {
        $siteRoot = $this->findArg('root', $args);

        if (!empty($siteRoot) && !is_dir($siteRoot)) {
            throw new Exception("$siteRoot is not a valid site root directory");
        }

        $this->siteRoot = $siteRoot ?? $this->siteRoot;

        return $this->siteRoot;
    }

    public function parseOnly($args): ?string
    {
        $only = $this->parseArguments($args)['options']['only'] ?? null;

        if (is_bool($only) || empty($only)) {
            return null;
        }

        $siteRoot = $this->siteRoot;

        if (!empty($only) && !is_dir($siteRoot . '/' . $only)) {
            throw new Exception("'{$only}' is not a valid site directory under {$siteRoot}");
        }

        return $only;
    }

    public static function run(array $arguments)
    {
        $wpu = new WordpressUpdater();

        $siteRoot = $wpu->parseSiteRoot($arguments);

        $wpu->debug('Command line arguments: ' . join(', ', $arguments));

        $siteDirs = $wpu->getSites($siteRoot, $arguments);

        $wpu->debug("Sites to process: " . join(', ', $siteDirs));

        foreach ($siteDirs as $siteDir) {
            $wpu->setSiteDir($siteDir);
            $wpu->setSiteOwnerUserName();
            $wpu->getSiteUrl();
            $wpu->updatePlugins();
            $wpu->updateThemes();
            $wpu->updateCore();
            $wpu->updateLanguageCore();
            $wpu->flushCache();
	    $wpu->clearCliCache();
	    $wpu->clearFastestCache();
        }
    }
}
