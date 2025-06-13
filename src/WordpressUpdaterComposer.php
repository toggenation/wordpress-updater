<?php

namespace Toggenation;

use Toggenation\WordpressUpdater;
use Composer\Script\Event;
use Exception;

class WordpressUpdaterComposer
{
    public static function run(Event $event)
    {
        $arguments = $event->getArguments();

        WordpressUpdater::run($arguments);
    }
}
