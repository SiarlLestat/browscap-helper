<?php
/**
 * This file is part of the browscap-helper package.
 *
 * Copyright (c) 2015-2017, Thomas Mueller <mimmi20@live.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);
namespace BrowscapHelper;

use BrowserDetector\Detector;
use Monolog\ErrorHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Noodlehaus\Config;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Application;

/**
 * Class BrowscapHelper
 *
 * @category   Browscap Helper
 *
 * @author     Thomas Mueller <mimmi20@live.de>
 */
class BrowscapHelper extends Application
{
    /**
     * @var string
     */
    public const DEFAULT_RESOURCES_FOLDER = '../sources';

    public function __construct()
    {
        parent::__construct('Browscap Helper Project', 'dev-master');

        $sourcesDirectory = realpath(__DIR__ . '/../sources/');
        $targetDirectory  = realpath(__DIR__ . '/../results/');

        $formatter = new LineFormatter(null, null, true, true);
        $stream    = new StreamHandler('log/error.log', Logger::INFO);
        $stream->setFormatter($formatter);

        $logger = new Logger('browser-detector-helper');
        $logger->pushHandler($stream);

        ErrorHandler::register($logger);

        $cache    = new FilesystemAdapter('', 0, __DIR__ . '/../cache/');
        $detector = new Detector($cache, $logger);

        $config = new Config(['data/configs/config.json']);

        $commands = [
            new Command\ConvertLogsCommand($logger, $sourcesDirectory, $targetDirectory),
            new Command\CopyTestsCommand($logger, $cache),
            new Command\CreateTestsCommand($logger, $cache, $detector, $sourcesDirectory),
            new Command\RewriteTestsCommand($logger, $cache, $detector),
            new Command\CompareCommand($logger, $cache, $config),
            new Command\ParseCommand($logger, $cache, $config),
        ];

        foreach ($commands as $command) {
            $this->add($command);
        }
    }
}
