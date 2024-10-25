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

$options = getopt('vh', ['vv::', 'recipe::', 'help']);

define('VERBOSE', array_key_exists('v', $options));
define('DEBUG', array_key_exists('vv', $options));

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
$artifactStore = 'file:///artifacts/';
$artifactPattern = 'todo js and css';
$projects = [
    'g7system' => [
        'components' => ['g7system', 'commons'],
        'includes' => ['3rdParty/_3rdPartyResources.php', 'commons/g7-bootstrap.php'],
        'web-artifacts' => ['3rdParty'],
    ],

];

//TODO announce steps and log timing
$sourceArtifactMaps = buildArtifacts($sourceDir, $projects, $artifactPattern, $artifactStore);
prepareCode($appDir, $projects, $sourceArtifactMaps);
publish($publish, $projects);
// End main execution before defining functions
exit();

function printHelp(array $argv): never {
    echo "Usage: $argv[0] [--help|-h] [--verbose|-v] [--vv[=level]] [--recipe=<name>]\n";
    echo "\n";
    echo "Options:\n";
    echo "  -h, --help          Show this help message and exit\n";
    echo "  -v, --verbose       Enable verbose output\n";
    echo "  --vv[=level]        Enable debug mode with an optional level (default: 1)\n";
    echo "  --recipe=<name>     Specify a recipe\n";
    exit();
}

function buildArtifacts($sourceDir, $projects, $artifactPattern, $artifactStore): array {
    //TODO grab all artifacts
}

/** @throws Exception */
function storeArtifact(string $artifactPath, string $artifactStore): void {

    $hash = hash_file('sha256', $artifactPath);
    $extension = pathinfo($artifactPath, PATHINFO_EXTENSION);
    $parsedArtifactStore = parse_url($artifactStore);
    //TODO validate
    ['scheme' => $scheme, 'path' => $destPath, 'query' => $destQuery,] = $parsedArtifactStore;
    $storeFunction = match ($parsedArtifactStore['scheme']) {
        'file' => fileArtifactStore(...),
        default => throw new Exception("Protocol '{$parsedArtifactStore['scheme']}' is not supported for storing artifacts")
    };
    $storeFunction($artifactPath, $hash, $extension, $destPath, $destQuery);
}

function fileArtifactStore ($artifactPath, $hash, $extension, $path) {
    //TODO store as path/XX/XXXXX.extension
}
