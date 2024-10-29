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

$publish = "/public/";
$appDir = '/virtualweb/';
$projects = [
    'g7system' => [
        'components' => ['g7system', 'commons'],
        'includes' => ['3rdParty/_3rdPartyResources.php', 'commons/g7-bootstrap.php',],
        'web-artifacts' => ['3rdParty',],
        'storeURL' => 'file:///artifacts/',
        'artifactPattern' => ['*.js', '*.css'],
        'publish' => $publish,
        'appDir' => $appDir,
    ],
];

//TODO announce steps and log timing
array_map(prepareProject(...), $projects);
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

/** @throws Exception
 * Copies sources and artifacts to their destination and generates the artifact index
 */
function prepareProject(array $projectConfig): void {
    $artifactMap = [];
    ['artifactPattern' => $artifactPattern,'storeURL' => $storeURL,
        'components' => $components, 'web-artifacts' => $webArtifacts,
        'appDir' => $appDir, 'sourceDir' => $sourceDir] = $projectConfig;
    foreach (array_merge($components, $webArtifacts) as $source) {
        // Prepend SOURCE_DIR if the source does not start with '/'
        $fullPath = $source[0] === '/' ? $source : rtrim($sourceDir, '/') . "/$source";
        if (!is_dir($fullPath)) {
            //TODO warn
            continue;
        }
        //TODO enumerate all files
        foreach ($files as $file) {
            $filePurpose = determineFilePurpose($file, $artifactPattern);
            $fileData = match ($filePurpose) {
                'artifact' => storeArtifact($file, $storeURL),
                default => storeSource($file, $appDir),
            };
            $fileData['role'] = $filePurpose;
            recordFilePath($artifactMap, $sourceDir, $file, $fileData);
        }
    }
    // TODO save $artifactMap; via export and copy it into the build
}

function determineFilePurpose(string $file, array $artifactPattern):string {
    //TODO match on patterns
}

function storeSource(string $file, string $appDir):array {
    //TODO copy file and get relevant metadata
}

function recordFilePath(array &$artifactMap, string $sourceDir, string $file, array $hash): void {
    $relativePath = str_replace($sourceDir . '/', '', $file);
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    $nestedPath = explode('/', $relativePath);
    $current = &$artifactMap[$extension];
    foreach ($nestedPath as $pathPart) {
        $current =& $current[$pathPart];
    }
    $current = $hash;
}

/** @throws Exception */
function storeArtifact(string $artifactPath, string $artifactStore): array {
    $hash = hash_file('sha256', $artifactPath);
    $extension = pathinfo($artifactPath, PATHINFO_EXTENSION);
    $parsedArtifactStore = parse_url($artifactStore);
    //TODO validate URL
    ['scheme' => $scheme, 'path' => $destPath, 'query' => $destQuery,] = $parsedArtifactStore;
    $storeFunction = match ($scheme) {
        'file' => fileArtifactStore(...),
        default => throw new Exception("Protocol '$scheme' is not supported for storing artifacts")
    };
    return $storeFunction($artifactPath, $hash, $extension, $destPath, $destQuery);
}

function fileArtifactStore($artifactPath, $hash, $extension, $path): array {
    $dir = sprintf('%s/%s/%s/', $path, substr($hash, 0, 2), substr($hash, 2));
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $hashedFileName = "$hash.$extension";
    $location = "$dir$hashedFileName";
    copyFile($artifactPath, $location);
    return compact(['hash', 'location']);
}

function copyFile($filePath, string $destination): bool {
    //TODO if present use SOURCE_DATE_EPOCH
    return copy($filePath, $destination);
}
