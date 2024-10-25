#!/usr/bin/env php
<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For a list of contributors, please see the CONTRIBUTORS.md file
 * @see https://github.com/manhart/pool/blob/master/CONTRIBUTORS.md
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, or visit the following link:
 * @see https://github.com/manhart/pool/blob/master/LICENSE
 *
 * For more information about this project:
 * @see https://github.com/manhart/pool
 */

//disable timeout
set_time_limit(0);
//the implicit flush is turned on, so output is immediately displayed
ob_implicit_flush(1);

$options = getopt('vh', ['vv::', 'recipe::', 'help', 'insecure-transports']);

define('VERBOSE', array_key_exists('v', $options));
define('DEBUG', array_key_exists('vv', $options));
define('INSECURE_TRANSPORTS', array_key_exists('insecure-transports', $options));

if (array_key_exists('h', $options) || array_key_exists('help', $options)) {
    printHelp($argv);
}

// Check if SOURCE_DIR environment variable is set
$sourceDir = getenv('SOURCE_DIR');
if ($sourceDir === false) {
    fwrite(STDERR, "Error: SOURCE_DIR environment variable is not set.\n");
    exit(1);
}

// Check if SOURCE_DATE_EPOCH environment variable is set
$sourceDateEpoch = getenv('SOURCE_DATE_EPOCH');
if ($sourceDateEpoch === false) {
    fwrite(STDERR, "Error: SOURCE_DATE_EPOCH environment variable is not set.\n");
    exit(1);
}
$publish = "/public/";
$appDir = '/virtualweb/';
$projects = [
    'g7system' => [
        'components' => ['g7system', 'commons'],
        'includes' => ['3rdParty/_3rdPartyResources.php', 'commons/g7-bootstrap.php',],
        'web-artifacts' => ['3rdParty',],
        'storeURL' => 'file:///artifacts/',
        'artifactPattern' => ['*.js', '*.css'],
    ],
];

//TODO announce steps and log timing
$sourceArtifactMaps = buildArtifacts($sourceDir, $projects);
prepareCode($appDir, $projects, $sourceArtifactMaps);
publish($publish, $projects);
// End main execution before defining functions
exit();

function printHelp(array $argv): never {
    echo "Usage: $argv[0] [--help|-h] [--verbose|-v] [--vv[=level]] [--recipe=<name>] [--insecure-transports]\n";
    echo "\n";
    echo "Options:\n";
    echo "  -h, --help              Show this help message and exit\n";
    echo "  -v, --verbose           Enable verbose output\n";
    echo "  --vv[=level]            Enable debug mode with an optional level (default: 1)\n";
    echo "  --recipe=<name>         Specify a recipe\n";
    echo "  --insecure-transports   Allow fetching sources over insecure transports\n";
    exit();
}

function buildArtifacts($sourceDir, $projects): array {
    $artifactMaps = [];

    foreach ($projects as $projectName => $projectConfig) {
        $artifactPattern = $projectConfig['artifactPattern'];
        $storeURL = $projectConfig['storeURL'];
        $artifactMap = [
            'stylesheet' => [],
            'javaScript' => [],
            'image' => [],
        ];

        $callback = function ($artifactPath) use ($storeURL, &$artifactMap, $sourceDir) {
            $hash = hash_file('sha256', $artifactPath);
            storeArtifact($artifactPath, $storeURL);
            recordArtifactPath($artifactMap, $sourceDir, $artifactPath, $hash);
        };
        foreach (array_merge($projectConfig['components'], $projectConfig['web-artifacts']) as $source) {
            // Prepend SOURCE_DIR if the source does not start with '/'
            $fullPath = $source[0] === '/' ? $source : rtrim($sourceDir, '/') . '/' . ltrim($source, '/');
            if (is_dir($fullPath)) {
                foreach ($artifactPattern as $pattern) {
                    $files = glob("$fullPath/$pattern");
                    foreach ($files as $file) {
                        $callback($file);
                    }
                }
            }
        }

        $artifactMaps[$projectName] = $artifactMap;
    }
    return $artifactMaps;
}

function recordArtifactPath(array &$artifactMap, string $sourceDir, string $file, string $hash): void {
    $relativePath = str_replace($sourceDir . '/', '', $file);
    $type = match (pathinfo($file, PATHINFO_EXTENSION)) {
        'js' => 'javaScript',
        'css' => 'stylesheet',
        'png', 'jpg', 'jpeg', 'gif' => 'image',
        default => null
    };
    if ($type) {
        $nestedPath = explode('/', $relativePath);
        $current = &$artifactMap[$type];
        foreach ($nestedPath as $pathPart) {
            if (!isset($current[$pathPart])) {
                $current[$pathPart] = [];
            }
            $current = &$current[$pathPart];
        }
        $current['hash'] = $hash;
    }
}

/** @throws Exception */
function storeArtifact(string $artifactPath, string $artifactStore): void {
    $hash = hash_file('sha256', $artifactPath);
    $extension = pathinfo($artifactPath, PATHINFO_EXTENSION);
    $parsedArtifactStore = parse_url($artifactStore);
    //TODO validate
    ['scheme' => $scheme, 'path' => $destPath, 'query' => $destQuery,] = $parsedArtifactStore;
    $storeFunction = match ($scheme) {
        'file' => fileArtifactStore(...),
        default => throw new Exception("Protocol '$scheme' is not supported for storing artifacts")
    };
    $storeFunction($artifactPath, $hash, $extension, $destPath, $destQuery);
}

function fileArtifactStore($artifactPath, $hash, $extension, $path) {
    $dir = sprintf('%s/%s/%s/', $path, substr($hash, 0, 2), substr($hash, 2));
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $hashedFileName = "$hash.$extension";
    copy($artifactPath, "$dir$hashedFileName");
}
