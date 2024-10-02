<?php

namespace YourVendor\FixTranslators;

require 'vendor/autoload.php';

use YourVendor\FixTranslators\FixTranslators;

class FixTranslatorsCommand
{
    public static function run($apiKey, $directory)
    {
        $fixTranslators = new FixTranslators($apiKey);
        $fixTranslators->processDirectory($directory);
        echo "Translators' comments added successfully to $directory.\n";
    }

    public static function handleArguments()
    {
        $options = getopt("", ["openApiKey:", "directory:"]);

        if (!isset($options['openApiKey']) || !isset($options['directory'])) {
            echo "Usage: composer run-script fix-translators-comments --openApiKey=<YOUR_API_KEY> --directory=<DIRECTORY_PATH>\n";
            exit(1);
        }

        self::run($options['openApiKey'], $options['directory']);
    }
}

// Call the handleArguments method when this file is executed directly
if (PHP_SAPI === 'cli') {
    FixTranslatorsCommand::handleArguments();
}
