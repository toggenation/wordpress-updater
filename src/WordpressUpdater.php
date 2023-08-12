<?php

namespace Toggenation;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use Exception;

class WordpressUpdater
{
    private string $wp = '';

    private string $userName = '';

    private string $siteRoot =  '/var/www';

    private string $siteDir  = '';

    private function __construct()
    {
        define('VENDOR', __DIR__ . '/../vendor/');

        $this->wp = VENDOR . 'bin/wp';
    }

    private function getSiteUrl()
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

    private function flushCache()
    {
        return $this->exec(['cache', 'flush', 'all']);
    }

    /**
     * Builds a command line for wp-cli
     * e.g.
     * sudo -u username /path/to/vendor/bin/wp --path=/path/to/wordpress cache flush all
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

        $exec = exec($cmd, $output);

        foreach ($output as $out) {
            echo "$out\n";
        };
    }

    private function getOwnerName()
    {
        $stats = lstat($this->siteDir);

        $this->userName = posix_getpwuid($stats['uid'])['name'];

        return $this->userName;
    }

    private function getSites(?string $siteRoot = null)
    {
        $glob = glob($siteRoot . '/*/web/wp-config\.php');

        $files = array_map('dirname', $glob);

        if (empty($files)) {
            throw new Exception("No valid wordpress installs found in $siteRoot");
        }

        return $files;
    }

    private function parseSiteRoot($args)
    {
        foreach ($args as $arg) {
            if (strpos($arg, '--root=') !== false) {
                $siteRoot =  explode('=', $arg)[1];

                if (!is_dir($siteRoot)) {
                    throw new Exception("$siteRoot is not a valid directory");
                }

                return $siteRoot;
            }
        }

        return null;
    }

    public static function run(Event $event)
    {
        $wpu = new WordpressUpdater();

        $siteRoot = $wpu->parseSiteRoot($event->getArguments());

        $siteDirs = $wpu->getSites($siteRoot);

        foreach ($siteDirs as $siteDir) {
            $wpu->siteDir = $siteDir;
            $wpu->getOwnerName();
            $wpu->getSiteUrl();
            $wpu->updatePlugins();
            $wpu->updateThemes();
            $wpu->updateCore();
            $wpu->flushCache();
        }
    }
}