<?php
/*******************************************************************************
 * INIT
 ******************************************************************************/
ini_set('memory_limit', '-1');
ini_set('max_execution_time', '-1');
ini_set('max_input_time', '-1');
ini_set('display_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Europe/Berlin');

chdir(dirname(__DIR__));

require 'vendor/autoload.php';

$logger = new \Monolog\Logger('browser-detector-tests');
$logger->pushHandler(new \Monolog\Handler\NullHandler());

$cache    = new \WurflCache\Adapter\NullStorage();
$detector = new \BrowserDetector\BrowserDetector($cache, $logger);

$checks          = [];
$data            = [];
$sourceDirectory = 'vendor/mimmi20/browser-detector/tests/issues/';

$files = scandir($sourceDirectory, SCANDIR_SORT_ASCENDING);

foreach ($files as $filename) {
    $file = new \SplFileInfo($sourceDirectory . DIRECTORY_SEPARATOR . $filename);

    echo 'checking file ', $file->getBasename(), ' ...', PHP_EOL;

    /** @var $file \SplFileInfo */
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    echo 'reading file ', $file->getBasename(), ' ...', PHP_EOL;

    $tests  = require_once $file->getPathname();
    $chunks = array_chunk($tests, 100, true);

    foreach ($chunks as $chunkId => $chunk) {
        if (!count($chunk)) {
            continue;
        }

        $outputDetector  = "<?php\n\nreturn [\n";

        foreach ($chunk as $key => $test) {
            if (isset($data[$key])) {
                // Test data is duplicated for key
                echo 'Test data is duplicated for key "' . $key . '"', PHP_EOL;
                unset($tests[$key]);
                continue;
            }

            if (isset($checks[$test['ua']])) {
                // UA was added more than once
                echo 'UA "' . $test['ua'] . '" added more than once, now for key "' . $key . '", before for key "'
                    . $checks[$test['ua']] . '"', PHP_EOL;
                unset($tests[$key]);
                continue;
            }

            $data[$key]          = $test;
            $checks[$test['ua']] = $key;

            $result = $detector->getBrowser($test['ua']);

            if (isset($test['properties']['Platform_Name'])) {
                $platformName = $test['properties']['Platform_Name'];
            } else {
                $platformName = 'unknown';
            }

            if (isset($test['properties']['Platform_Version'])) {
                $platformVersion = $test['properties']['Platform_Version'];
            } else {
                $platformVersion = 'unknown';
            }

            if (isset($test['properties']['Platform_Bits'])) {
                $platformBits = $test['properties']['Platform_Bits'];
            } else {
                $platformBits = 'unknown';
            }

            if (isset($test['properties']['Platform_Maker'])) {
                $platformMaker = $test['properties']['Platform_Maker'];
            } else {
                $platformMaker = 'unknown';
            }

            // rewrite undetected platform properties
            if ('unknown' === $platformName) {
                echo '["' . $key . '"] platform name for UA "' . $test['ua'] . '" is unknown yet, rewriting', PHP_EOL;

                $platformName    = $result->getOs()->getName();
            }

            $detectVersion = $result->getOs()->getVersion();

            if ('unknown' === $platformVersion) {
                echo '["' . $key . '"] platform version for UA "' . $test['ua'] . '" is unknown yet, rewriting', PHP_EOL;

                $platformVersion = $detectVersion;
            } elseif (strlen($detectVersion) > strlen($platformVersion)
                && substr($detectVersion, 0, strlen($platformVersion)) === $platformVersion
            ) {
                echo '["' . $key . '"] platform version for UA "' . $test['ua'] . '" is incomplete, rewriting', PHP_EOL;

                $platformVersion = $detectVersion;
            }

            if ('unknown' === $platformBits) {
                echo '["' . $key . '"] platform bits for UA "' . $test['ua'] . '" is unknown yet, rewriting', PHP_EOL;

                $platformBits = $result->getOs()->getBits();
            }

            if ('unknown' === $platformMaker) {
                echo '["' . $key . '"] platform maker for UA "' . $test['ua'] . '" is unknown yet, rewriting', PHP_EOL;

                $platformMaker = $result->getOs()->getManufacturer();
            }

            $outputDetector .= "    '$key' => [
            'ua'         => '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $test['ua']) . "',
            'properties' => [
                'Browser_Name'            => '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $test['properties']['Browser_Name']) . "',
                'Browser_Type'            => '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $test['properties']['Browser_Type']) . "',
                'Browser_Bits'            => " . str_replace(['\\', "'"], ['\\\\', "\\'"], $test['properties']['Browser_Bits']) . ",
                'Browser_Maker'           => '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $test['properties']['Browser_Maker']) . "',
                'Browser_Modus'           => '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $test['properties']['Browser_Modus']) . "',
                'Browser_Version'         => '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $test['properties']['Browser_Version']) . "',
                'Platform_Name'           => '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $platformName) . "',
                'Platform_Version'        => '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $platformVersion) . "',
                'Platform_Bits'           => " . str_replace(['\\', "'"], ['\\\\', "\\'"], $platformBits) . ",
                'Platform_Maker'          => '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $platformMaker) . "',
                'Device_Name'             => '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $test['properties']['Device_Name']) . "',
                'Device_Maker'            => '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $test['properties']['Device_Maker']) . "',
                'Device_Type'             => '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $test['properties']['Device_Type']) . "',
                'Device_Pointing_Method'  => '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $test['properties']['Device_Pointing_Method']) . "',
                'Device_Code_Name'        => '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $test['properties']['Device_Code_Name']) . "',
                'Device_Brand_Name'       => '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $test['properties']['Device_Brand_Name']) . "',
                'RenderingEngine_Name'    => '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $test['properties']['RenderingEngine_Name']) . "',
                'RenderingEngine_Version' => '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $test['properties']['RenderingEngine_Version']) . "',
                'RenderingEngine_Maker'   => '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $test['properties']['RenderingEngine_Maker']) . "',
            ],
        ],\n";
        }

        $outputDetector .= "];\n";

        $basename = $file->getBasename('php');

        if (preg_match('/test\-(\d+)/', $basename, $matches)) {
            $basename = sprintf('test-%1$05d', $matches[1]);
        } elseif (preg_match('/browscap\-issue\-(\d+)/', $basename, $matches)) {
            $basename = sprintf('browscap-issue-%1$05d', $matches[1]);
        }

        if (count($chunks) > 1) {
            echo 'writing file ', $basename, '-', $chunkId, '.php', ' ...', PHP_EOL;

            file_put_contents(
                $file->getPath() . '/' . $basename . '-' . $chunkId . '.php',
                $outputDetector
            );
        } else {
            echo 'writing file ', $basename, '.php', ' ...', PHP_EOL;

            file_put_contents($file->getPath() . '/' . $basename . '.php', $outputDetector);
        }
    }
}
